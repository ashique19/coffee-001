<?php

namespace App\Services\Backup;

use RuntimeException;

/**
 * Hybrid backup encryption (server holds public key only).
 *
 * File format (ZATCABAK1), big-endian lengths:
 *
 * | Offset | Field |
 * |--------|-------|
 * | 0      | Magic "ZATCABAK1" (9 bytes) |
 * | 9      | Version uint8 (currently 1) |
 * | 10     | sealed_key_len uint32 |
 * | 14     | sealed_key (RSA-OAEP, PKCS#1 v2.1) wrapping 32-byte AES key |
 * | 14+N   | iv (12 bytes, AES-GCM) |
 * | 26+N   | tag (16 bytes, AES-GCM auth tag) |
 * | 42+N   | ciphertext (AES-256-GCM of gzip payload) |
 */
final class HybridBackupCipher
{
    public const MAGIC = 'ZATCABAK1';

    public const VERSION = 1;

    public const AES_KEY_BYTES = 32;

    public const IV_BYTES = 12;

    public const TAG_BYTES = 16;

    /**
     * @return array{sha256: string, bytes: int}
     */
    public function encryptFile(string $gzipPlainPath, string $outputEncPath, string $publicKeyPem): array
    {
        $plain = file_get_contents($gzipPlainPath);
        if ($plain === false) {
            throw new RuntimeException('Unable to read gzip payload for encryption.');
        }

        $key = random_bytes(self::AES_KEY_BYTES);
        $iv = random_bytes(self::IV_BYTES);
        $tag = '';

        $ciphertext = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', self::TAG_BYTES);
        if ($ciphertext === false || strlen($tag) !== self::TAG_BYTES) {
            throw new RuntimeException('AES-256-GCM encryption failed.');
        }

        $sealedKey = '';
        $publicKey = openssl_pkey_get_public($publicKeyPem);
        if ($publicKey === false) {
            throw new RuntimeException('Invalid backup public key.');
        }

        $sealed = openssl_public_encrypt($key, $sealedKey, $publicKey, OPENSSL_PKCS1_OAEP_PADDING);
        if (! $sealed || $sealedKey === '') {
            throw new RuntimeException('RSA-OAEP key sealing failed.');
        }

        $payload = self::MAGIC
            .chr(self::VERSION)
            .pack('N', strlen($sealedKey))
            .$sealedKey
            .$iv
            .$tag
            .$ciphertext;

        if (file_put_contents($outputEncPath, $payload) === false) {
            throw new RuntimeException('Unable to write encrypted backup file.');
        }

        return [
            'sha256' => hash('sha256', $payload),
            'bytes' => strlen($payload),
        ];
    }

    public function decryptFile(string $encPath, string $outputGzipPath, string $privateKeyPem): void
    {
        $blob = file_get_contents($encPath);
        if ($blob === false) {
            throw new RuntimeException('Unable to read encrypted backup file.');
        }

        $parsed = $this->parsePayload($blob);

        $privateKey = openssl_pkey_get_private($privateKeyPem);
        if ($privateKey === false) {
            throw new RuntimeException('Invalid backup private key.');
        }

        $aesKey = '';
        $unsealed = openssl_private_decrypt($parsed['sealed'], $aesKey, $privateKey, OPENSSL_PKCS1_OAEP_PADDING);
        if (! $unsealed || strlen($aesKey) !== self::AES_KEY_BYTES) {
            throw new RuntimeException('RSA-OAEP key unsealing failed.');
        }

        $plain = openssl_decrypt($parsed['ciphertext'], 'aes-256-gcm', $aesKey, OPENSSL_RAW_DATA, $parsed['iv'], $parsed['tag']);
        if ($plain === false) {
            throw new RuntimeException('AES-256-GCM decryption failed.');
        }

        if (file_put_contents($outputGzipPath, $plain) === false) {
            throw new RuntimeException('Unable to write decrypted gzip payload.');
        }
    }

    /**
     * @return array{
     *     sealed: string,
     *     iv: string,
     *     tag: string,
     *     ciphertext: string
     * }
     */
    private function parsePayload(string $blob): array
    {
        if (! str_starts_with($blob, self::MAGIC)) {
            throw new RuntimeException('Unrecognized backup file (bad magic).');
        }

        $version = ord($blob[9] ?? "\0");
        if ($version !== self::VERSION) {
            throw new RuntimeException("Unsupported backup file version: {$version}.");
        }

        $sealedLen = unpack('N', substr($blob, 10, 4))[1] ?? 0;
        $offset = 14;
        $sealed = substr($blob, $offset, $sealedLen);
        $offset += $sealedLen;

        if (strlen($sealed) !== $sealedLen) {
            throw new RuntimeException('Truncated sealed key block.');
        }

        $iv = substr($blob, $offset, self::IV_BYTES);
        $offset += self::IV_BYTES;
        $tag = substr($blob, $offset, self::TAG_BYTES);
        $offset += self::TAG_BYTES;
        $ciphertext = substr($blob, $offset);

        if (strlen($iv) !== self::IV_BYTES || strlen($tag) !== self::TAG_BYTES || $ciphertext === '') {
            throw new RuntimeException('Truncated ciphertext block.');
        }

        return [
            'sealed' => $sealed,
            'iv' => $iv,
            'tag' => $tag,
            'ciphertext' => $ciphertext,
        ];
    }
}
