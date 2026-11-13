<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\Http;
use RuntimeException;

final class DropboxBackupClient
{
    public function __construct(
        private readonly string $appKey,
        private readonly string $appSecret,
        private readonly string $refreshToken,
        private readonly int $simpleUploadMaxBytes,
        private readonly int $uploadChunkBytes,
    ) {}

    public function upload(string $localPath, string $remotePath): void
    {
        $token = $this->accessToken();
        $size = filesize($localPath);
        if ($size === false) {
            throw new RuntimeException('Unable to read backup file size.');
        }

        if ($size <= $this->simpleUploadMaxBytes) {
            $this->simpleUpload($token, $localPath, $remotePath, $size);

            return;
        }

        $this->sessionUpload($token, $localPath, $remotePath, $size);
    }

    /**
     * @return list<array{path: string, server_modified: string}>
     */
    public function listBackups(string $rootPath): array
    {
        $token = $this->accessToken();
        $entries = [];
        $cursor = null;
        $hasMore = true;

        while ($hasMore) {
            if ($cursor === null) {
                $response = Http::withToken($token)
                    ->acceptJson()
                    ->post('https://api.dropboxapi.com/2/files/list_folder', [
                        'path' => $rootPath,
                        'recursive' => true,
                    ]);
            } else {
                $response = Http::withToken($token)
                    ->acceptJson()
                    ->post('https://api.dropboxapi.com/2/files/list_folder/continue', [
                        'cursor' => $cursor,
                    ]);
            }

            if (! $response->successful()) {
                throw new RuntimeException('Dropbox list_folder failed.');
            }

            foreach ($response->json('entries', []) as $entry) {
                if (($entry['.tag'] ?? '') === 'file') {
                    $entries[] = [
                        'path' => (string) ($entry['path_display'] ?? $entry['path_lower'] ?? ''),
                        'server_modified' => (string) ($entry['server_modified'] ?? ''),
                    ];
                }
            }

            $hasMore = (bool) $response->json('has_more', false);
            $cursor = $response->json('cursor');
        }

        return $entries;
    }

    public function delete(string $remotePath): void
    {
        $token = $this->accessToken();

        $response = Http::withToken($token)
            ->acceptJson()
            ->post('https://api.dropboxapi.com/2/files/delete_v2', [
                'path' => $remotePath,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Dropbox delete failed.');
        }
    }

    private function accessToken(): string
    {
        $response = Http::asForm()->post('https://api.dropboxapi.com/oauth2/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refreshToken,
            'client_id' => $this->appKey,
            'client_secret' => $this->appSecret,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Dropbox token refresh failed.');
        }

        $token = $response->json('access_token');
        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Dropbox token refresh returned no access token.');
        }

        return $token;
    }

    private function simpleUpload(string $token, string $localPath, string $remotePath, int $size): void
    {
        $handle = fopen($localPath, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to open backup for upload.');
        }

        $body = stream_get_contents($handle);
        fclose($handle);

        $response = Http::withToken($token)
            ->withHeaders([
                'Dropbox-API-Arg' => json_encode([
                    'path' => $remotePath,
                    'mode' => 'add',
                    'autorename' => false,
                    'mute' => true,
                ], JSON_THROW_ON_ERROR),
                'Content-Type' => 'application/octet-stream',
            ])
            ->withBody($body === false ? '' : $body, 'application/octet-stream')
            ->post('https://content.dropboxapi.com/2/files/upload');

        if (! $response->successful()) {
            throw new RuntimeException('Dropbox upload failed.');
        }
    }

    private function sessionUpload(string $token, string $localPath, string $remotePath, int $size): void
    {
        $handle = fopen($localPath, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to open backup for chunked upload.');
        }

        $firstChunk = fread($handle, $this->uploadChunkBytes);
        if ($firstChunk === false) {
            fclose($handle);
            throw new RuntimeException('Unable to read backup chunk.');
        }

        $startResponse = Http::withToken($token)
            ->withHeaders([
                'Dropbox-API-Arg' => json_encode(['close' => false], JSON_THROW_ON_ERROR),
                'Content-Type' => 'application/octet-stream',
            ])
            ->withBody($firstChunk, 'application/octet-stream')
            ->post('https://content.dropboxapi.com/2/files/upload_session/start');

        if (! $startResponse->successful()) {
            fclose($handle);
            throw new RuntimeException('Dropbox upload_session/start failed.');
        }

        $sessionId = $startResponse->json('session_id');
        if (! is_string($sessionId) || $sessionId === '') {
            fclose($handle);
            throw new RuntimeException('Dropbox upload session missing session_id.');
        }

        $offset = strlen($firstChunk);

        while (! feof($handle)) {
            $chunk = fread($handle, $this->uploadChunkBytes);
            if ($chunk === false) {
                fclose($handle);
                throw new RuntimeException('Unable to read backup chunk.');
            }

            if ($chunk === '') {
                break;
            }

            $appendResponse = Http::withToken($token)
                ->withHeaders([
                    'Dropbox-API-Arg' => json_encode([
                        'cursor' => [
                            'session_id' => $sessionId,
                            'offset' => $offset,
                        ],
                        'close' => false,
                    ], JSON_THROW_ON_ERROR),
                    'Content-Type' => 'application/octet-stream',
                ])
                ->withBody($chunk, 'application/octet-stream')
                ->post('https://content.dropboxapi.com/2/files/upload_session/append_v2');

            if (! $appendResponse->successful()) {
                fclose($handle);
                throw new RuntimeException('Dropbox upload_session/append_v2 failed.');
            }

            $offset += strlen($chunk);
        }

        fclose($handle);

        $finishResponse = Http::withToken($token)
            ->withHeaders([
                'Dropbox-API-Arg' => json_encode([
                    'cursor' => [
                        'session_id' => $sessionId,
                        'offset' => $size,
                    ],
                    'commit' => [
                        'path' => $remotePath,
                        'mode' => 'add',
                        'autorename' => false,
                        'mute' => true,
                    ],
                ], JSON_THROW_ON_ERROR),
                'Content-Type' => 'application/octet-stream',
            ])
            ->post('https://content.dropboxapi.com/2/files/upload_session/finish');

        if (! $finishResponse->successful()) {
            throw new RuntimeException('Dropbox upload_session/finish failed.');
        }
    }
}
