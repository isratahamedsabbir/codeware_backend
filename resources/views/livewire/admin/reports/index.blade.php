<div class="space-y-5">

    {{-- Summary cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-[5px] border border-zinc-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-1">Total Orders</p>
            <p class="text-2xl font-bold text-zinc-900">{{ number_format($totalOrders) }}</p>
        </div>
        <div class="bg-white rounded-[5px] border border-zinc-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-1">Revenue (Paid)</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format((float) $totalRevenue, 2) }}</p>
        </div>
        <div class="bg-white rounded-[5px] border border-zinc-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-1">Pending Payments</p>
            <p class="text-2xl font-bold text-amber-600">{{ number_format((float) $pendingAmount, 2) }}</p>
            <p class="text-xs text-zinc-400 mt-0.5">{{ $pendingCount }} order(s)</p>
        </div>
        <div class="bg-white rounded-[5px] border border-zinc-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Revenue by Method</p>
            <div class="space-y-1">
                @forelse ($revenueByMethod as $method => $amount)
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-zinc-600">{{ \App\Support\PaymentMethods::label($method) }}</span>
                        <span class="font-medium text-zinc-800">{{ number_format((float) $amount, 2) }}</span>
                    </div>
                @empty
                    <p class="text-xs text-zinc-400">No paid orders yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Orders by status --}}
    <div class="bg-white rounded-[5px] border border-zinc-100 shadow-sm p-5">
        <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-3">Orders by Status</p>
        <div class="flex gap-3 flex-wrap">
            @forelse (\App\Models\Order::STATUSES as $status)
                <div class="px-4 py-2 rounded-lg bg-zinc-50 border border-zinc-100">
                    <span class="text-xs text-zinc-500">{{ ucfirst($status) }}</span>
                    <span class="ml-2 text-sm font-semibold text-zinc-800">{{ $ordersByStatus[$status] ?? 0 }}</span>
                </div>
            @empty
            @endforelse
        </div>
    </div>

    {{-- Filters + table --}}
    <div class="bg-white rounded-[5px] shadow-sm overflow-hidden">
        <div class="flex items-center gap-3 p-4 flex-wrap">
            <x-per-page-select :options="$this->perPageOptions()" />

            <select wire:model.live="statusFilter"
                class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[140px] transition-all"
                style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
                <option value="">All statuses</option>
                @foreach (\App\Models\Order::STATUSES as $status)
                    <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                @endforeach
            </select>

            <select wire:model.live="paymentStatusFilter"
                class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[160px] transition-all"
                style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
                <option value="">All payment statuses</option>
                @foreach (\App\Models\Order::PAYMENT_STATUSES as $status)
                    <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                @endforeach
            </select>

            <select wire:model.live="paymentMethodFilter"
                class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[160px] transition-all"
                style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
                <option value="">All payment methods</option>
                @foreach (\App\Support\PaymentMethods::available() as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>

            <div class="ml-auto flex items-center gap-2">
                <flux:button size="sm" variant="ghost" icon="arrow-down-tray"
                    href="{{ route('admin.reports.export', $this->filters()) }}">
                    Export CSV
                </flux:button>
            </div>
        </div>

        <div class="flex items-center gap-3 px-4 pb-4 flex-wrap">
            <x-date-range-picker :from="$fromDate" :to="$toDate" />
            <flux:button size="sm" variant="ghost" wire:click="resetFilters" class="ml-auto">Reset</flux:button>
        </div>

        <div class="overflow-x-auto">
            <div class="border border-zinc-100 rounded-lg">
                <table class="w-full divide-y divide-gray-200">
                    <colgroup>
                        <col style="width:5%">
                        <col style="width:15%">
                        <col style="width:18%">
                        <col class="hidden lg:table-column" style="width:12%">
                        <col class="hidden lg:table-column" style="width:12%">
                        <col style="width:11%">
                        <col style="width:13%">
                        <col class="hidden lg:table-column" style="width:14%">
                    </colgroup>
                    <thead>
                        <tr class="bg-zinc-50">
                            <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8"></th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Order #</th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Customer</th>
                            <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Method</th>
                            <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Payment</th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-2.5 text-right text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Total</th>
                            <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Placed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($orders as $order)
                            <tr class="hover:bg-indigo-50/30 transition-colors">
                                <td class="px-2 py-2 text-center">
                                    <x-admin-row-expand-toggle class="lg:hidden" :expanded="$viewingId === $order->id"
                                        wire:click="{{ $viewingId === $order->id ? 'closeDetails' : 'viewDetails('.$order->id.')' }}" />
                                </td>
                                <td class="px-4 py-2 font-mono text-xs text-zinc-700">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" wire:navigate class="hover:underline">
                                        <x-truncate :text="$order->order_number" />
                                    </a>
                                </td>
                                <td class="px-4 py-2 text-sm text-zinc-800"><x-truncate :text="$order->customer_name" /></td>
                                <td class="hidden lg:table-cell px-4 py-2 text-sm text-zinc-600">{{ \App\Support\PaymentMethods::label($order->payment_method) }}</td>
                                <td class="hidden lg:table-cell px-4 py-2 text-sm text-zinc-600">{{ ucfirst($order->payment_status) }}</td>
                                <td class="px-4 py-2 text-sm text-zinc-600">{{ ucfirst($order->status) }}</td>
                                <td class="px-4 py-2 text-sm font-medium text-zinc-900 text-right">{{ number_format((float) $order->total, 2) }}</td>
                                <td class="hidden lg:table-cell px-4 py-2 text-xs text-zinc-500">{{ $order->created_at->toDisplay() }}</td>
                            </tr>
                            @if ($viewingId === $order->id)
                                <x-admin-row-details colspan="8">
                                    <x-admin-row-details.item label="Method">{{ \App\Support\PaymentMethods::label($order->payment_method) }}</x-admin-row-details.item>
                                    <x-admin-row-details.item label="Payment">{{ ucfirst($order->payment_status) }}</x-admin-row-details.item>
                                    <x-admin-row-details.item label="Placed">{{ $order->created_at->toDisplay() }}</x-admin-row-details.item>
                                </x-admin-row-details>
                            @endif
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-16 text-center text-sm text-zinc-500">No orders match these filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="px-6 py-3">
            {{ $orders->links() }}
        </div>
    </div>

</div>
