<?php

use App\Models\BuilderDesign;
use App\Support\Builder\Translations;
use App\Support\Locale;

it('swaps an element for its translation and strips the attributes', function () {
    $html = '<section><h1 data-t-bn="আমাদের সম্পর্কে" id="a">About us</h1><p data-t-bn="একটি <b>বাক্য</b>">One <i>line</i></p><div data-t-bn="x"><div>in</div>out</div></section>';

    $bn = Translations::apply($html, 'bn');
    expect($bn)->toContain('<h1 id="a">আমাদের সম্পর্কে</h1>')
        ->toContain('<p>একটি <b>বাক্য</b></p>')
        ->toContain('<div>x</div>')
        ->not->toContain('data-t-');

    // Primary language (and any language with no text) keeps the original text.
    $en = Translations::apply($html, Locale::primary());
    expect($en)->toContain('About us')->toContain('One <i>line</i>')->not->toContain('data-t-');
    expect(Translations::apply('<h1 data-t-bn="">T</h1>', 'bn'))->toBe('<h1>T</h1>');
});

it('serves one design in either language from the same builder', function () {
    $design = BuilderDesign::create(['name' => 'Landing']);
    $design->saveLocale(Locale::primary(), ['pages' => []], '<h1 data-t-bn="শিরোনাম">Title</h1>', '');

    expect($design->renderedHtml('bn'))->toContain('শিরোনাম')->not->toContain('>Title<')
        ->and($design->renderedHtml(Locale::primary()))->toContain('>Title<')
        ->and($design->locales())->toContain('bn');
});
