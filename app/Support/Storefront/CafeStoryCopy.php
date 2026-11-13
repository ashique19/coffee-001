<?php

namespace App\Support\Storefront;

final class CafeStoryCopy
{
    /**
     * @param  array<string, mixed>  $content  Café content slice (content.cafe)
     * @return array{
     *     mode: 'custom'|'default',
     *     body?: string,
     *     pull_quote?: string,
     *     paragraphs?: list<string>,
     * }
     */
    public static function resolve(array $content, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $stored = self::storedText($content, $locale);

        if ($stored !== '') {
            return [
                'mode' => 'custom',
                'body' => $stored,
            ];
        }

        $pullQuote = (string) __('cafe.default_story.pull_quote', [], $locale);
        $paragraphs = __('cafe.default_story.paragraphs', [], $locale);
        if (! is_array($paragraphs)) {
            $paragraphs = [];
        }

        return [
            'mode' => 'default',
            'pull_quote' => $pullQuote,
            'paragraphs' => array_values(array_map('strval', $paragraphs)),
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     */
    public static function storedText(array $content, string $locale): string
    {
        $text = match ($locale) {
            'ar' => (string) ($content['about_ar'] ?? ''),
            'bn' => (string) ($content['about_bn'] ?? $content['about_en'] ?? ''),
            'ur' => (string) ($content['about_ur'] ?? $content['about_ar'] ?? ''),
            'hi' => (string) ($content['about_hi'] ?? $content['about_en'] ?? ''),
            default => (string) ($content['about_en'] ?? ''),
        };

        return trim($text);
    }
}
