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
    <div class="pointer-events-none absolute -right-16 -top-20 h-64 w-64 rounded-full bg-[var(--me-accent)]/20" aria-hidden="true"></div>
    <div class="relative mx-auto max-w-[1400px] px-4 py-12 sm:px-8 sm:py-16">
        <a href="{{ route('careers') }}" class="inline-flex items-center gap-1.5 text-[0.7rem] font-bold uppercase tracking-widest text-white/60 transition hover:text-white">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            {{ __('All openings') }}
        </a>
        @if ($job->department)
            <p class="mt-6 text-[0.65rem] font-bold uppercase tracking-[0.25em] text-[var(--me-accent)]">{{ $job->department }}</p>
        @endif
        <h1 class="mt-2 text-3xl font-black uppercase tracking-tight sm:text-4xl">{{ $job->title }}</h1>
        <div class="mt-4 flex flex-wrap gap-2 text-xs font-bold uppercase tracking-widest">
            @if ($job->location)
                <span class="rounded-full bg-white/10 px-3 py-1.5 ring-1 ring-white/10">{{ $job->location }}</span>
            @endif
            <span class="rounded-full bg-white/10 px-3 py-1.5 ring-1 ring-white/10">{{ $job->employmentTypeLabel() }}</span>
            <span class="rounded-full bg-white/10 px-3 py-1.5 ring-1 ring-white/10">{{ $job->vacancy }} {{ $job->vacancy === 1 ? __('vacancy') : __('vacancies') }}</span>
        </div>
    </div>
</section>

<main class="mx-auto grid max-w-[1400px] gap-8 px-4 py-12 sm:px-8 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        @if ($job->description)
            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-lg font-black uppercase tracking-wide text-[#1a1a1a]">{{ __('About the role') }}</h2>
                <div class="mt-4 whitespace-pre-line text-[15px] leading-7 text-gray-600">{{ $job->description }}</div>
            </section>
        @endif
        @if ($job->requirements)
            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-lg font-black uppercase tracking-wide text-[#1a1a1a]">{{ __('Requirements') }}</h2>
                <div class="mt-4 whitespace-pre-line text-[15px] leading-7 text-gray-600">{{ $job->requirements }}</div>
            </section>
        @endif
        @unless ($job->description || $job->requirements)
            <section class="rounded-2xl border border-gray-100 bg-white p-6 text-sm text-gray-500 shadow-sm">
                {{ __('More details about this role will be shared during the hiring process.') }}
            </section>
        @endunless
    </div>

    <aside class="space-y-6 lg:col-span-1">
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h2 class="text-[0.7rem] font-bold uppercase tracking-widest text-gray-400">{{ __('Job overview') }}</h2>
            <dl class="mt-4 divide-y divide-gray-100 text-sm">
                <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">{{ __('Employment') }}</dt><dd class="font-bold text-[#1a1a1a]">{{ $job->employmentTypeLabel() }}</dd></div>
                @if ($job->location)
                    <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">{{ __('Location') }}</dt><dd class="text-right font-bold text-[#1a1a1a]">{{ $job->location }}</dd></div>
                @endif
                <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">{{ __('Vacancies') }}</dt><dd class="font-bold text-[#1a1a1a]">{{ $job->vacancy }}</dd></div>
                @if ($job->deadline)
                    <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">{{ __('Apply by') }}</dt><dd class="font-bold text-[#1a1a1a]">{{ $job->deadline->toFormattedDateString() }}</dd></div>
                @endif
                <div class="flex justify-between gap-4 py-3"><dt class="text-gray-500">{{ __('Posted') }}</dt><dd class="font-bold text-[#1a1a1a]">{{ $job->created_at->toFormattedDateString() }}</dd></div>
            </dl>
        </div>

        <div id="apply" class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm lg:sticky lg:top-24">
            <h2 class="text-[0.7rem] font-bold uppercase tracking-widest text-gray-400">{{ __('Apply for this position') }}</h2>
            <p class="mb-5 mt-1 text-sm text-gray-500">{{ __('Fill in your details and attach your résumé.') }}</p>
            @if ($job->isAcceptingApplications())
                <livewire:frontend.job-application-form :job-post-id="$job->id" :key="'job-apply-'.$job->id" />
            @else
                <div class="rounded-lg bg-gray-100 px-4 py-3 text-sm text-gray-600">{{ __('This position is no longer accepting applications.') }}</div>
            @endif
        </div>
    </aside>
</main>

@include('theme-modern_ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
