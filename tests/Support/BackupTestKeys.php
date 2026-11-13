<?php

namespace Tests\Support;

final class BackupTestKeys
{
    /**
     * @return array{public: string, private: string, public_path: string, private_path: string}
     */
    public static function createRsa4096Pair(): array
    {
        $config = [
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 4096,
        ];

        $resource = openssl_pkey_new($config);
        if ($resource === false) {
            throw new \RuntimeException('Unable to generate RSA test keypair.');
        }

        openssl_pkey_export($resource, $privatePem);
        $details = openssl_pkey_get_details($resource);
        $publicPem = $details['key'] ?? '';

        $dir = sys_get_temp_dir().'/zatca-backup-test-'.bin2hex(random_bytes(4));
        mkdir($dir, 0700, true);
        $publicPath = $dir.'/public.pem';
        $privatePath = $dir.'/private.pem';
        file_put_contents($publicPath, $publicPem);
        file_put_contents($privatePath, $privatePem);
        chmod($publicPath, 0600);
        chmod($privatePath, 0600);

        return [
            'public' => $publicPem,
            'private' => $privatePem,
            'public_path' => $publicPath,
            'private_path' => $privatePath,
        ];
    }
}
