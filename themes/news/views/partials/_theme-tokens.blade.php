@php
    $accent = \App\Support\ThemeSettings::text('news', 'theme_news_accent_color');
    $accent = preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $accent) ? $accent : '#b91c1c';
@endphp
<style>
    :root {
        --nw-accent: {{ $accent }};
        --color-brand: {{ $accent }};
    }
</style>
