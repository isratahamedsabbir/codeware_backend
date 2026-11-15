{{-- Settings → Custom Code → Head Code: pasted verbatim before </head> on
     every public theme page (never the admin/auth screens), followed by the
     snippet picked in the page's SEO section. --}}

{{-- Google Tag Manager — loaded on every public theme page, ahead of the
     admin-managed custom code so the container is the first tag on the page.
     The container ID comes from GOOGLE_TAG_MANAGER_ID (.env) via
     config/services.php; a blank ID means no container at all. Pages push
     page-specific events with @push('datalayer'), ahead of gtm.js. --}}
@if (filled($gtmId = config('services.google_tag_manager.id')))
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
</script>
@stack('datalayer')
<script>
  // Standard GTM bootstrap: without this event the container's "All Pages"
  // (gtm.js) trigger never fires.
  window.dataLayer.push({'gtm.start': new Date().getTime(), event: 'gtm.js'});
</script>
<script async src="https://www.googletagmanager.com/gtm.js?id={{ $gtmId }}"></script>
<script>
  document.addEventListener('livewire:init', () => {
    Livewire.on('add-to-cart', ({ item }) => {
      window.dataLayer = window.dataLayer || [];
      dataLayer.push({ ecommerce: null });
      dataLayer.push({
        event: 'add_to_cart',
        ecommerce: {
          currency: @json(\App\Models\Setting::get('currency_code', 'BDT')),
          value: Number(item.price) * Number(item.quantity),
          items: [{
            item_id: String(item.item_id),
            item_name: item.item_name,
            price: Number(item.price),
            quantity: Number(item.quantity),
          }],
        },
      });
    });
  });
</script>
@endif

@if (filled($customHeadCode = \App\Support\CustomCode::render('head', $page ?? null)))
{!! $customHeadCode !!}
@endif
