<div class="bg-white rounded-[5px] shadow-sm overflow-hidden">

    <div class="flex items-center gap-3 p-4 flex-wrap">
        <select wire:model.live="file"
            class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 bg-white min-w-[200px]">
            @forelse ($files as $f)
                <option value="{{ $f['name'] }}">{{ $f['name'] }} ({{ number_format($f['size'] / 1024, 1) }} KB)</option>
            @empty
                <option value="">No log files</option>
            @endforelse
        </select>

        <select wire:model.live="level"
            class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 bg-white min-w-[130px]">
            <option value="">All levels</option>
            @foreach ($levels as $l)
                <option value="{{ $l }}">{{ ucfirst($l) }}</option>
            @endforeach
        </select>

        <select wire:model.live="perPage"
            class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 bg-white">
            @foreach ([10, 25, 50, 100] as $n)
                <option value="{{ $n }}">{{ $n }} / page</option>
            @endforeach
        </select>

        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search message or stack trace…"
            class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 w-full max-w-xs" />

        @if ($file)
            <div class="ml-auto flex items-center gap-2">
                <button wire:click="$refresh" type="button"
                    class="px-3 h-8 text-sm font-medium rounded-lg text-zinc-600 hover:bg-zinc-100 cursor-pointer">Refresh</button>
                <button wire:click="download" type="button"
                    class="px-3 h-8 text-sm font-medium rounded-lg text-zinc-600 hover:bg-zinc-100 cursor-pointer">Download</button>
                <button wire:click="clear" wire:confirm="Empty {{ $file }}? This cannot be undone." type="button"
                    class="px-3 h-8 text-sm font-medium rounded-lg text-amber-700 hover:bg-amber-50 cursor-pointer">Clear</button>
                <button wire:click="delete" wire:confirm="Delete {{ $file }}? This cannot be undone." type="button"
                    class="px-3 h-8 text-sm font-medium rounded-lg text-rose-600 hover:bg-rose-50 cursor-pointer">Delete</button>
            </div>
        @endif
    </div>

    @if ($truncated)
        <p class="mx-4 mb-3 text-xs text-amber-700 bg-amber-50 rounded-lg px-3 py-2">
            This file is large — showing only the newest 5 MB. Download it to read everything.
        </p>
    @endif

    <div class="overflow-x-auto">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full table-fixed divide-y divide-gray-200">
                <colgroup>
                    <col class="w-40">
                    <col class="w-28">
                    <col>
                </colgroup>
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider whitespace-nowrap">Time</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Level</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Message</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($entries as $i => $e)
                        @php $idx = $offset + $i; @endphp
                        <tr wire:key="log-{{ $idx }}"
                            class="hover:bg-indigo-50/30 transition-colors {{ $e['detail'] !== '' ? 'cursor-pointer' : '' }}"
                            @if ($e['detail'] !== '') wire:click="toggle({{ $idx }})" @endif>
                            <td class="px-4 py-2 text-sm text-zinc-500 whitespace-nowrap align-top">{{ $e['time'] }}</td>
                            <td class="px-4 py-2 align-top">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium
                                    {{ match (true) {
                                        in_array($e['level'], ['emergency', 'alert', 'critical', 'error']) => 'bg-rose-50 text-rose-700',
                                        $e['level'] === 'warning' => 'bg-amber-50 text-amber-700',
                                        in_array($e['level'], ['notice', 'info']) => 'bg-blue-50 text-blue-700',
                                        default => 'bg-zinc-100 text-zinc-600',
                                    } }}">{{ ucfirst($e['level']) }}</span>
                            </td>
                            <td class="px-4 py-2 text-sm text-zinc-800 [overflow-wrap:anywhere]">
                                {{ \Illuminate\Support\Str::limit($e['message'], 300) }}
                                @if ($e['detail'] !== '' && $open === $idx)
                                    <pre class="mt-2 p-3 bg-zinc-900 text-zinc-100 text-xs rounded-lg overflow-x-auto max-h-96 whitespace-pre-wrap [overflow-wrap:anywhere]">{{ $e['detail'] }}</pre>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-16 text-center text-sm text-zinc-600">No log entries found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="p-4">{{ $entries->links() }}</div>
</div>
