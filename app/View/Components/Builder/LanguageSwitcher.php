<?php

namespace App\View\Components\Builder;

use App\Support\Locale;

/**
 * A top bar with the visitor's language switch (and, optionally, an email and phone number).
 * Two languages show one button that flips to the other; more show a dropdown.
 */
class LanguageSwitcher extends Block
{
    public static function key(): string
    {
        return 'language-switcher';
    }

    public static function name(): string
    {
        return 'Language switcher';
    }

    public static function hint(): string
    {
        return 'Top bar with email, phone and language button';
    }

    public static function icon(): string
    {
        return 'list';
    }

    public static function preview(): array
    {
        return ['sample' => 'EN / বাংলা', 'shape' => 'logo', 'columns' => false, 'count' => 1];
    }

    public static function fields(): array
    {
        return [
            ['name' => 'email', 'type' => 'text', 'label' => 'Email (optional)', 'default' => ''],
            ['name' => 'phone', 'type' => 'text', 'label' => 'Phone (optional)', 'default' => ''],
            ['name' => 'background', 'type' => 'text', 'label' => 'Background colour', 'default' => '#043c26'],
            ['name' => 'color', 'type' => 'text', 'label' => 'Text colour', 'default' => '#ffffff'],
        ];
    }

    protected function viewData(): array
    {
        $current = Locale::current();
        $languages = Locale::active();

        return [
            'languages' => $languages,
            'current' => $current,
            'other' => $languages->where('code', '!=', $current)->values(),
            'email' => $this->text('email'),
            'phone' => $this->text('phone'),
            'background' => $this->colour('background', '#043c26'),
            'color' => $this->colour('color', '#ffffff'),
        ];
    }

    /** A CSS colour from the placeholder, or the default if it holds anything that isn't one. */
    private function colour(string $name, string $default): string
    {
        $value = $this->text($name);

        return preg_match('/^(#[0-9a-f]{3,8}|[a-z]+|(rgb|hsl)a?\([0-9 ,.%\/]+\))$/i', $value) ? $value : $default;
    }
}
