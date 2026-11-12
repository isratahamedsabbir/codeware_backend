<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials._head')
    @include('partials._seo-meta')
    @include('partials._custom-code-head')
    @include('theme-modern_ecommerce::partials._theme-tokens')
</head>
<body class="bg-white text-gray-900 antialiased">

@include('theme-modern_ecommerce::partials._header')

<section class="relative overflow-hidden bg-[#1a1a1a] text-white">
    <div class="pointer-events-none absolute -right-20 -top-24 h-80 w-80 rounded-full bg-[var(--me-accent)]/20" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -bottom-28 left-1/3 h-72 w-72 rounded-full bg-white/5" aria-hidden="true"></div>
    <div class="relative mx-auto max-w-[1400px] px-4 py-14 text-center sm:px-8 lg:py-20">
        <span class="text-[0.65rem] font-bold uppercase tracking-[0.25em] text-[var(--me-accent)]">{{ __('Careers') }}</span>
        <h1 class="mt-3 text-3xl font-black uppercase tracking-tight sm:text-4xl lg:text-5xl">{{ __('Build your career with us') }}</h1>
        <p class="mx-auto mt-4 max-w-2xl text-[15px] leading-7 text-white/60">{{ __('Join a team that is making everyday shopping simpler for customers across Bangladesh. Explore our open roles and find the one that fits you.') }}</p>
        <span class="mt-7 inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-xs font-bold uppercase tracking-widest ring-1 ring-white/15">
            <span class="h-2 w-2 rounded-full bg-emerald-400" aria-hidden="true"></span>
            {{ trans_choice('{0} No open positions|{1} 1 open position|[2,*] :count open positions', $jobs->count(), ['count' => $jobs->count()]) }}
        </span>
    </div>
</section>

<main class="mx-auto max-w-5xl px-4 py-12 sm:px-8">
    @if ($departments->count() > 1)
        <nav aria-label="{{ __('Departments') }}" class="mb-8 flex flex-wrap items-center gap-2">
            <a href="{{ route('careers') }}"
                class="rounded-full px-5 py-2 text-[0.7rem] font-bold uppercase tracking-widest transition {{ $department === '' ? 'bg-[var(--me-accent)] text-white shadow-sm' : 'bg-white text-gray-500 ring-1 ring-gray-200 hover:text-[var(--me-accent)] hover:ring-[var(--me-accent)]/40' }}">{{ __('All') }}</a>
            @foreach ($departments as $name)
                <a href="{{ route('careers', ['department' => $name]) }}"
                    class="rounded-full px-5 py-2 text-[0.7rem] font-bold uppercase tracking-widest transition {{ $department === $name ? 'bg-[var(--me-accent)] text-white shadow-sm' : 'bg-white text-gray-500 ring-1 ring-gray-200 hover:text-[var(--me-accent)] hover:ring-[var(--me-accent)]/40' }}">{{ $name }}</a>
            @endforeach
        </nav>
    @endif

    <div class="space-y-4">
        @forelse ($jobs as $job)
            <a href="{{ route('careers.show', $job->slug) }}"
                class="group flex flex-col gap-4 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-xl sm:flex-row sm:items-center sm:justify-between">
                <span class="hidden h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[var(--me-accent)]/10 text-[var(--me-accent)] transition group-hover:bg-[var(--me-accent)] group-hover:text-white sm:flex" aria-hidden="true">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                </span>
                <div class="min-w-0 flex-1">
                    @if ($job->department)
                        <span class="text-[0.65rem] font-bold uppercase tracking-widest text-[var(--me-accent)]">{{ $job->department }}</span>
                    @endif
                    <h2 class="mt-1 text-xl font-extrabold text-[#1a1a1a] transition group-hover:text-[var(--me-accent)]">{{ $job->title }}</h2>
                    <ul class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm text-gray-500">
                        @if ($job->location)
                            <li class="inline-flex items-center gap-1.5">
                                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                                {{ $job->location }}
                            </li>
                        @endif
                        <li class="inline-flex items-center gap-1.5">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                            {{ $job->employmentTypeLabel() }}
                        </li>
                        <li class="inline-flex items-center gap-1.5">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                            {{ __('Apply by') }} {{ $job->deadline?->toFormattedDateString() ?: __('Open until filled') }}
                        </li>
                    </ul>
                </div>
                <span class="inline-flex shrink-0 items-center justify-center gap-2 bg-[#1a1a1a] px-6 py-3 text-[0.65rem] font-bold uppercase tracking-widest text-white transition group-hover:bg-[var(--me-accent)]">
                    {{ __('View & apply') }}
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </span>
            </a>
        @empty
            <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-gray-200 bg-white py-20 text-center">
                <p class="font-extrabold uppercase tracking-wide text-[#1a1a1a]">{{ __('No open positions right now') }}</p>
                <p class="mt-1 text-sm text-gray-500">{{ __('Please check back soon — new roles are posted regularly.') }}</p>
            </div>
        @endforelse
    </div>
</main>

@include('theme-modern_ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
