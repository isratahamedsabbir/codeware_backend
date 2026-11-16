{{-- The admin loader GIF over the whole screen while the given Livewire action
     of the surrounding component is in flight (e.g. loading a row into the form
     beside its table). Lives inside the component, so wire:target is scoped to
     it — the layout-level overlay (partials/_admin-loader-overlay) sits outside
     any component and doesn't see these requests. Usage:
     @include('partials._admin-loader-target', ['target' => 'edit']) --}}
<div wire:loading.flex wire:target="{{ $target }}"
    class="fixed inset-0 z-[9999] hidden items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-zinc-900/70">
    <img src="{{ \App\Models\Setting::get('loader') ?: asset('default/loader.gif') }}" alt="Loading" class="max-w-[150px] object-contain">
</div>
