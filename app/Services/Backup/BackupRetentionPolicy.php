<?php

namespace App\Services\Backup;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final class BackupRetentionPolicy
{
    public function __construct(
        private readonly int $dailyKeepDays,
        private readonly int $monthlyKeepMonths,
    ) {}

    /**
     * @param  list<string>  $remotePaths
     * @return list<string> paths safe to delete
     */
    public function remotePathsToDelete(array $remotePaths, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();

        $parsed = [];
        foreach ($remotePaths as $path) {
            $stamp = $this->parseBackupTimestamp($path);
            if ($stamp === null) {
                continue;
            }
            $parsed[] = ['path' => $path, 'at' => $stamp];
        }

        if ($parsed === []) {
            return [];
        }

        $keep = [];

        $dailyCutoff = $now->subDays($this->dailyKeepDays - 1)->startOfDay();
        $byDay = [];
        foreach ($parsed as $row) {
            $day = $row['at']->format('Y-m-d');
            $byDay[$day][] = $row;
        }

        foreach ($byDay as $day => $rows) {
            $dayDate = CarbonImmutable::parse($day)->startOfDay();
            if ($dayDate->greaterThanOrEqualTo($dailyCutoff)) {
                usort($rows, fn ($a, $b) => $a['at'] <=> $b['at']);
                $keep[$rows[array_key_last($rows)]['path']] = true;
            }
        }

        $monthFloor = $now->startOfMonth()->subMonths($this->monthlyKeepMonths - 1);
        $byMonth = [];
        foreach ($parsed as $row) {
            $monthKey = $row['at']->format('Y-m');
            $byMonth[$monthKey][] = $row;
        }

        foreach ($byMonth as $monthKey => $rows) {
            $monthStart = CarbonImmutable::createFromFormat('Y-m', $monthKey)?->startOfMonth();
            if ($monthStart === null || $monthStart->lessThan($monthFloor)) {
                continue;
            }

            usort($rows, fn ($a, $b) => $a['at'] <=> $b['at']);
            $keep[$rows[0]['path']] = true;
        }

        $delete = [];
        foreach ($parsed as $row) {
            if (! isset($keep[$row['path']])) {
                $delete[] = $row['path'];
            }
        }

        return $delete;
    }

    /**
     * @param  list<string>  $localEncPaths
     * @return list<string> paths to delete
     */
    public function localPathsToDelete(array $localEncPaths, int $keepCount): array
    {
        if (count($localEncPaths) <= $keepCount) {
            return [];
        }

        usort($localEncPaths, function (string $a, string $b) {
            $ma = @filemtime($a) ?: 0;
            $mb = @filemtime($b) ?: 0;

            return $mb <=> $ma;
        });

        return array_slice($localEncPaths, $keepCount);
    }

    private function parseBackupTimestamp(string $path): ?CarbonImmutable
    {
        if (! preg_match('#/zatca/(\d{4})/(\d{2})/zatca-(\d{8})-(\d{6})\.sql\.gz\.enc$#', $path, $m)) {
            return null;
        }

        $stamp = $m[3].$m[4];
        $parsed = CarbonImmutable::createFromFormat('YmdHis', $stamp);

        return $parsed === false ? null : $parsed;
    }

    public function buildRemotePath(CarbonImmutable $at): string
    {
        $yyyy = $at->format('Y');
        $mm = $at->format('m');
        $basename = 'zatca-'.$at->format('Ymd-His').'.sql.gz.enc';

        return '/zatca/'.$yyyy.'/'.$mm.'/'.$basename;
    }

    public static function filenameMatches(string $basename): bool
    {
        return Str::is('zatca-*-*-*.sql.gz.enc', $basename)
            && (bool) preg_match('#^zatca-\d{8}-\d{6}\.sql\.gz\.enc$#', $basename);
    }
}
