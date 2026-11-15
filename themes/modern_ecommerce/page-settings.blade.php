{{--
    Modern Ecommerce — the home page's banners, shown under Page Settings → Home.

    Included by the Theme Settings screen's Page Settings card when a theme ships a
    page-settings.blade.php at its root. Both lists bind to the screen's $repeaters
    bag and are stored in this theme's theme.json; views/index.blade.php reads them
    with theme_rows().

      hero_slides   the sliding hero (image, badge, headline, subtitle, button, link)
      promos        the promo tiles under it (name, link, image)

    Until a list has a row the home page shows its built-in sample content, so a
    fresh install is never an empty page.
--}}
<div wire:key="modern-home-banners" class="space-y-3">
    <x-admin-collapse-section icon="photo" title="Hero slider" description="The large sliding banner at the top of the home page. Until you add a slide the theme's sample slides are shown.">
        <x-admin-repeatable-fields
            :repeaters="$repeaters"
            setting-key="theme_modern_ecommerce_hero_slides"
            empty-title="No slides yet"
            empty-hint="The sample slides stay on the home page until you add your own."
            add-label="Add slide"
            :max="6"
            :columns="1"
            :fields="[
                ['name' => 'title', 'label' => 'Headline', 'placeholder' => 'LEATHER BACKPACK'],
                ['name' => 'subtitle', 'label' => 'Second line (accent colour)', 'placeholder' => 'LEATHER BAG'],
                ['name' => 'badge', 'label' => 'Badge', 'placeholder' => 'NEW ARRIVALS'],
                ['name' => 'button', 'label' => 'Button label', 'placeholder' => 'SHOP NOW'],
                ['name' => 'link', 'label' => 'Button link', 'placeholder' => '/shop or https://…'],
                ['name' => 'image', 'label' => 'Background image', 'type' => 'media'],
            ]" />
    </x-admin-collapse-section>

    <x-admin-collapse-section icon="squares-2x2" title="Promo tiles" description="Up to three banner tiles under the hero, each linking where you choose. Leave empty to hide the row.">
        <x-admin-repeatable-fields
            :repeaters="$repeaters"
            setting-key="theme_modern_ecommerce_promos"
            empty-title="No promo tiles yet"
            empty-hint="The row is hidden until you add one."
            add-label="Add tile"
            :max="3"
            :columns="1"
            :fields="[
                ['name' => 'alt', 'label' => 'Name', 'placeholder' => 'Summer sale'],
                ['name' => 'link', 'label' => 'Link', 'placeholder' => '/shop or https://…'],
                ['name' => 'image', 'label' => 'Image', 'type' => 'media'],
            ]" />
    </x-admin-collapse-section>
</div>
