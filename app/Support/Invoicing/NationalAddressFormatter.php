<?php

namespace App\Support\Invoicing;

/**
 * Formats Saudi national-address snapshots for 80mm invoice/receipt print.
 */
final class NationalAddressFormatter
{
    public static function formatNationalAddressArPdfLineHtml(array $na): array
    {
        $building = trim(self::scalarString($na['building'] ?? ''));
        $street = trim(self::scalarString($na['street_ar'] ?? $na['street'] ?? ''));

        $lines = [];

        $line1Text = match (true) {
            $building !== '' && $street !== '' => $building.' '.$street,
            $building !== '' => $building,
            $street !== '' => $street,
            default => null,
        };
        if ($line1Text !== null) {
            $lines[] = self::pdfAddressDigitRunsHtml($line1Text);
        }

        $district = trim(self::scalarString($na['district_ar'] ?? $na['district'] ?? ''));
        $city = trim(self::scalarString($na['city_ar'] ?? $na['city'] ?? ''));
        $postal = trim(self::scalarString($na['postal_code'] ?? ''));

        $cityPostal = trim($city.($postal !== '' ? ' '.$postal : ''));
        $line2Text = match (true) {
            $district !== '' && $cityPostal !== '' => $district.'، '.$cityPostal,
            $district !== '' => $district,
            $cityPostal !== '' => $cityPostal,
            default => null,
        };
        if ($line2Text !== null) {
            $additional = trim(self::scalarString($na['additional'] ?? ''));
            if ($additional !== '') {
                $line2Text .= '، '.$additional;
            }
            // Keep district/city/postal in one RTL run so PDF engines do not pull LTR digit spans to the line start.
            $lines[] = e($line2Text);
        }

        return $lines;
    }

    private static function pdfAddressDigitRunsHtml(string $text): string
    {
        $escaped = e($text);

        return preg_replace_callback(
            '/(\d+)/u',
            static fn (array $m): string => '<span dir="ltr" class="invoice-pdf-ltr-digits">'.$m[1].'</span>',
            $escaped,
        ) ?? $escaped;
    }

    public static function formatNationalAddressEn(array $na): ?string
    {
        $lines = self::formatNationalAddressEnPrintLines($na);
        if ($lines === []) {
            return null;
        }

        return implode(', ', $lines);
    }

    /**
     * Explicit EN lines for invoice print/PDF (building + street, district/city/postal, additional).
     * Omits country when SA; never duplicates district when equal to city.
     * Strips trailing separators and peels district/city off a street or free-text
     * line when those places already appear on a later line.
     *
     * @param  array<string, mixed>  $na
     * @return list<string>
     */
    public static function formatNationalAddressEnPrintLines(array $na): array
    {
        $building = self::trimAddressSeparators(self::scalarString($na['building'] ?? ''));
        $street = self::trimAddressSeparators(self::scalarString($na['street'] ?? ''));
        $district = self::trimAddressSeparators(self::scalarString($na['district'] ?? ''));
        $city = self::trimAddressSeparators(self::scalarString($na['city'] ?? ''));
        $postal = self::trimAddressSeparators(self::scalarString($na['postal_code'] ?? $na['postal'] ?? ''));
        $additional = self::trimAddressSeparators(self::scalarString($na['additional'] ?? ''));

        $street = self::stripTrailingCoveredPlaceTokens($street, [], [$district, $city]);

        $lines = [];

        $line1 = match (true) {
            $building !== '' && $street !== '' => $building.', '.$street,
            $building !== '' => $building,
            $street !== '' => $street,
            default => null,
        };
        if ($line1 !== null) {
            $lines[] = $line1;
        } else {
            $freeText = trim(self::scalarString($na['address_line'] ?? $na['address'] ?? $na['line'] ?? ''));
            if ($freeText !== '') {
                foreach (preg_split('/\r\n|\n|\r/', $freeText) ?: [] as $ftLine) {
                    $ftLine = self::trimAddressSeparators($ftLine);
                    if ($ftLine !== '') {
                        $lines[] = $ftLine;
                    }
                }
            }
        }

        $cityPostal = trim($city.($postal !== '' ? ' '.$postal : ''));
        $districtDistinct = $district !== ''
            && ($city === '' || strcasecmp($district, $city) !== 0);

        $line2 = match (true) {
            $districtDistinct && $cityPostal !== '' => $district.', '.$cityPostal,
            $districtDistinct => $district,
            $cityPostal !== '' => $cityPostal,
            default => null,
        };
        if ($line2 !== null) {
            if ($additional !== '') {
                $line2 .= ', '.$additional;
            }
            $lines[] = $line2;
        } elseif ($additional !== '') {
            $lines[] = $additional;
        }

        $country = strtoupper(self::scalarString($na['country'] ?? 'SA'));
        if ($country !== '' && $country !== 'SA') {
            $lines[] = $country;
        }

        return self::normalizePrintAddressLines($lines, [$district, $city]);
    }

    public static function normalizePrintAddressLines(array $lines, array $places = []): array
    {
        $places = array_values(array_filter(array_map(
            static fn (mixed $v): string => self::trimAddressSeparators(self::scalarString($v)),
            $places,
        ), static fn (string $v): bool => $v !== ''));

        $normalized = [];
        foreach ($lines as $line) {
            $line = self::trimAddressSeparators(self::scalarString($line));
            if ($line !== '') {
                $normalized[] = $line;
            }
        }

        $count = count($normalized);
        for ($i = 0; $i < $count; $i++) {
            $later = array_slice($normalized, $i + 1);
            $normalized[$i] = self::stripTrailingCoveredPlaceTokens($normalized[$i], $later, $places);
        }

        $kept = [];
        foreach ($normalized as $i => $line) {
            if ($line === '' || self::isRedundantAddressLine($line, $normalized, $i)) {
                continue;
            }
            $kept[] = $line;
        }

        return array_values($kept);
    }

    private static function trimAddressSeparators(string $value): string
    {
        return trim($value, " \t\n\r\0\x0B,،");
    }

    /**
     * @param  list<string>  $laterLines
     * @param  list<string>  $places
     */
    private static function stripTrailingCoveredPlaceTokens(string $line, array $laterLines, array $places): string
    {
        $parts = preg_split('/\s*,\s*/u', $line) ?: [];
        $parts = array_values(array_filter(
            array_map(static fn (string $part): string => trim($part), $parts),
            static fn (string $part): bool => $part !== '',
        ));

        while (count($parts) > 1) {
            $last = $parts[count($parts) - 1];
            if (! self::tokenCoveredByLaterLineOrPlace($last, $laterLines, $places)) {
                break;
            }
            array_pop($parts);
        }

        return implode(', ', $parts);
    }

    /**
     * @param  list<string>  $laterLines
     * @param  list<string>  $places
     */
    private static function tokenCoveredByLaterLineOrPlace(string $token, array $laterLines, array $places): bool
    {
        foreach ($places as $place) {
            if (strcasecmp($token, $place) === 0) {
                return true;
            }
        }

        foreach ($laterLines as $later) {
            if (preg_match('/(?<![\p{L}\p{N}])'.preg_quote($token, '/').'(?=$|,|\s)/iu', (string) $later) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $lines
     */
    private static function isRedundantAddressLine(string $line, array $lines, int $index): bool
    {
        foreach ($lines as $j => $other) {
            if ($j === $index || $other === '') {
                continue;
            }
            if (strcasecmp($line, $other) === 0) {
                return $index > $j;
            }
            if (self::addressLineIsCoveredBy($line, $other)) {
                return true;
            }
        }

        return false;
    }

    private static function addressLineIsCoveredBy(string $shorter, string $longer): bool
    {
        if (mb_strlen($shorter) >= mb_strlen($longer)) {
            return false;
        }

        return preg_match('/^'.preg_quote($shorter, '/').'(?=$|,|\s)/iu', $longer) === 1;
    }

    public static function scalarString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '';
        }
        if (is_numeric($value)) {
            return (string) $value;
        }
        if (is_string($value)) {
            return $value;
        }

        return '';
    }
}
