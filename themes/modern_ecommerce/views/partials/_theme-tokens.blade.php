@php
    $accent = \App\Support\ThemeSettings::text('modern_ecommerce', 'theme_modern_ecommerce_accent_color');
    $accent = preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $accent) ? $accent : '#e63946';
@endphp
<style>
    :root {
        --me-accent: {{ $accent }};
        --color-brand: {{ $accent }};
        --color-sf-price: {{ $accent }};
        --color-sf-button: {{ $accent }};
        --color-sf-button-text: #ffffff;
        --color-sf-heading: #1a1a1a;
        --color-sf-text: #374151;
    }
</style>
