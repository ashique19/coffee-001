<?php

namespace App\Services\Themes;

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\File;
use ZipArchive;

class ThemeImportService
{
    public function importFromZip(string $zipPath, ?string $targetSlug = null): Tenant
    {
        $work = storage_path('app/theme-imports/'.uniqid('bundle-', true));
        File::ensureDirectoryExists($work);

        try {
            $zip = new ZipArchive;
            if ($zip->open($zipPath) !== true) {
                throw new \InvalidArgumentException('Invalid theme bundle zip.');
            }
            $zip->extractTo($work);
            $zip->close();

            $tenantFile = $work.'/tenant.json';
            if (! is_file($tenantFile)) {
                throw new \InvalidArgumentException('Bundle missing tenant.json');
            }

            /** @var array{slug: string, name: string, settings: array<string, mixed>} $payload */
            $payload = json_decode(file_get_contents($tenantFile), true, flags: JSON_THROW_ON_ERROR);

            $slug = $targetSlug ?? $payload['slug'];

            $assetsDir = $work.'/assets';
            if (is_dir($assetsDir)) {
                $this->copyAssetsIntoPublic($assetsDir, public_path());
            }

            return Tenant::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $payload['name'],
                    'settings' => $payload['settings'],
                ]
            );
        } finally {
            File::deleteDirectory($work);
        }
    }

    private function copyAssetsIntoPublic(string $assetsRoot, string $publicRoot): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($assetsRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isDir()) {
                continue;
            }
            $relative = substr($file->getPathname(), strlen($assetsRoot) + 1);
            $target = $publicRoot.'/'.$relative;
            File::ensureDirectoryExists(dirname($target));
            File::copy($file->getPathname(), $target);
        }
    }
}
