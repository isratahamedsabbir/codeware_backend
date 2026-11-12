{{-- Breadcrumb-only trail for pages without a title banner: same look as every other page (the shared page banner). --}}
@php
    $crumbs = $crumbs ?? [['label' => __('Home'), 'url' => url('/')]];
@endphp

<div class="mx-auto max-w-7xl px-4 sm:px-6">
    @include('theme-classic_ecommerce::partials._page-banner', [
        'title' => collect($crumbs)->last()['label'] ?? '',
        'tag' => 'p',
        'hideTitle' => true,
        'crumbs' => $crumbs,
        'icon' => 'M2.25 12 11.2 3.05a1.125 1.125 0 0 1 1.6 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
    ])
</div>
