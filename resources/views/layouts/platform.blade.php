<x-platform-layout :title="$title ?? __('platform.export_heading')" :preview-url="$previewUrl ?? null">
    {{ $slot }}
</x-platform-layout>
