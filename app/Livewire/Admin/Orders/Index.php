<?php

namespace App\Livewire\Admin\Orders;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\SendsCustomEmail;
use App\Concerns\WithSearch;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Services\OrderEmailService;
use App\Services\OrderPlacement;
use App\Support\AdminActivity;
use App\Support\PaymentMethods;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasBulkSelection, HasPerPage, SendsCustomEmail, WithPagination, WithSearch;

    public ?int $viewingId = null;

    /** The order_cancellation_cutoff_status Setting, edited from the Cancellation Rule modal. */
    #[Validate('required|in:pending,processing,shipped,delivered')]
    public string $cancellationCutoffStatus = 'shipped';

    public function viewDetails(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeDetails(): void
    {
        $this->viewingId = null;
    }

    /**
     * Manual fallback for the automatic send on order creation (see
     * Order::booted()) — in case that send failed (SMTP hiccup, etc.) and
     * needs to be retried, or the admin just wants to re-notify someone.
     */
    public function resendEmail(int $orderId, string $audience): void
    {
        $order = Order::findOrFail($orderId);
        $service = app(OrderEmailService::class);

        $sent = match ($audience) {
            'customer' => $service->sendCustomerConfirmation($order),
            'admin' => $service->sendAdminNotification($order),
            default => false,
        };

        if (! $sent) {
            $this->dispatch('notify', message: "Could not send the {$audience} email — check the email template and recipient address.");

            return;
        }

        AdminActivity::log('updated', "Resent {$audience} email for Order: {$order->order_number}");
        $this->dispatch('notify', message: ucfirst($audience).' email sent.');
    }

    /**
     * Opens the SendsCustomEmail modal pre-filled with this order's customer
     * email — the "Send Email" option in the per-row resend-email dropdown.
     */
    public function openCustomEmailFor(int $orderId): void
    {
        $this->customEmailTo = Order::findOrFail($orderId)->customer_email;
        $this->dispatch('open-modal', name: 'send-custom-email');
    }

    public string $statusFilter = '';

    public string $paymentStatusFilter = '';

    public string $paymentMethodFilter = '';

    public string $fromDate = '';

    public string $toDate = '';

    public string $productFilter = '';

    public string $productSearch = '';

    public string $priceMin = '';

    public string $priceMax = '';

    public function mount(): void
    {
        $this->fromDate = $this->toDate = CarbonImmutable::now(display_timezone())->toDateString();
        $this->cancellationCutoffStatus = Setting::orderCancellationCutoffStatus();
    }

    /**
     * Persists the fulfillment status past which an order can no longer be
     * cancelled (see Order::canBeCancelled()) — straight from this page's own
     * Settings modal, same pattern as Products/Pages/Posts' Frontend URL one.
     */
    public function saveCancellationRule(): void
    {
        Gate::authorize('access-admin-system');

        $this->validate();

        Setting::set('order_cancellation_cutoff_status', $this->cancellationCutoffStatus);

        AdminActivity::log('updated', 'Order cancellation rule updated');

        $this->dispatch('close-modal', name: 'cancellation-rule-settings');
        $this->dispatch('notify', message: 'Cancellation rule saved.');
    }

    // ── Create Order (an admin keys in an order on a customer's behalf) ──

    public string $newName = '';

    public string $newEmail = '';

    public string $newPhone = '';

    public string $newAddress = '';

    public string $newPaymentMethod = '';

    public string $newPaymentStatus = 'pending';

    public string $newStatus = 'pending';

    public string $newShippingMethodId = '';

    public string $newCoupon = '';

    public string $newNotes = '';

    /** @var array<int, array{product_id: string, quantity: int|string}> */
    public array $newItems = [['product_id' => '', 'quantity' => 1]];

    public function openCreateOrder(): void
    {
        $this->resetCreateOrder();
        $this->dispatch('open-modal', name: 'create-order');
    }

    public function addNewItem(): void
    {
        $this->newItems[] = ['product_id' => '', 'quantity' => 1];
    }

    public function removeNewItem(int $index): void
    {
        unset($this->newItems[$index]);
        $this->newItems = array_values($this->newItems) ?: [['product_id' => '', 'quantity' => 1]];
    }

    private function resetCreateOrder(): void
    {
        $this->reset([
            'newName', 'newEmail', 'newPhone', 'newAddress', 'newPaymentMethod',
            'newPaymentStatus', 'newStatus', 'newShippingMethodId', 'newCoupon', 'newNotes', 'newItems',
        ]);
        $this->resetValidation();
    }

    public function createOrder(OrderPlacement $placement): void
    {
        $this->validate([
            'newName' => 'required|string|max:255',
            'newEmail' => 'required|email|max:255',
            'newPhone' => 'required|string|max:30',
            'newAddress' => 'required|string|max:2000',
            'newPaymentMethod' => ['required', Rule::in(array_keys(PaymentMethods::available()))],
            'newPaymentStatus' => ['required', Rule::in(Order::PAYMENT_STATUSES)],
            'newStatus' => ['required', Rule::in(Order::STATUSES)],
            'newShippingMethodId' => ['nullable', 'integer', Rule::exists('shipping_methods', 'id')->where('status', 'active')],
            'newCoupon' => 'nullable|string|max:50',
            'newNotes' => 'nullable|string|max:1000',
            'newItems' => 'required|array|min:1',
            'newItems.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('status', 'active')->where('is_upcoming', false)],
            'newItems.*.quantity' => 'required|integer|min:1|max:1000',
        ], [
            'newItems.*.product_id.required' => 'Pick a product for every row.',
        ]);

        try {
            $order = $placement->placeProducts(
                array_map(fn (array $i) => ['product_id' => (int) $i['product_id'], 'quantity' => (int) $i['quantity']], $this->newItems),
                [
                    'customer_name' => $this->newName,
                    'customer_email' => $this->newEmail,
                    'customer_phone' => $this->newPhone,
                    'shipping_address' => $this->newAddress,
                    'payment_method' => $this->newPaymentMethod,
                    'notes' => $this->newNotes ?: null,
                ],
                $this->newCoupon ?: null,
                $this->newShippingMethodId !== '' ? (int) $this->newShippingMethodId : null,
                onBehalfOfCustomer: true,
            );
        } catch (ValidationException $e) {
            // Service errors use API field names; surface them on this form's fields.
            throw ValidationException::withMessages(collect($e->errors())->mapWithKeys(fn ($m, $k) => [match ($k) {
                'coupon_code' => 'newCoupon',
                'shipping_method_id' => 'newShippingMethodId',
                default => 'newItems',
            } => $m])->all());
        }

        $order->update(['status' => $this->newStatus, 'payment_status' => $this->newPaymentStatus]);

        if ($this->newPaymentStatus === 'paid') {
            $order->transactions()->latest()->first()?->update(['status' => 'success', 'paid_at' => now()]);
        }

        AdminActivity::log('created', "Created Order #{$order->order_number} on behalf of {$order->customer_name}");

        $this->resetCreateOrder();
        $this->dispatch('close-modal', name: 'create-order');
        $this->dispatch('notify', message: "Order {$order->order_number} created.");
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentMethodFilter(): void
    {
        $this->resetPage();
    }

    public function updatedFromDate(): void
    {
        $this->resetPage();
    }

    public function updatedToDate(): void
    {
        $this->resetPage();
    }

    public function updatedProductFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPriceMin(): void
    {
        $this->resetPage();
    }

    public function updatedPriceMax(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'paymentStatusFilter', 'paymentMethodFilter', 'productFilter', 'productSearch', 'priceMin', 'priceMax']);
        $this->fromDate = $this->toDate = CarbonImmutable::now(display_timezone())->toDateString();
        $this->resetPage();
    }

    /**
     * @return Collection<int, object{id: int, label: string}>
     */
    public function productOptions(): Collection
    {
        return Product::query()
            ->when($this->productSearch, fn ($q) => $q
                ->where('name->en', 'like', "%{$this->productSearch}%")
                ->orWhere('name->bn', 'like', "%{$this->productSearch}%"))
            ->orderBy('name->en')
            ->limit(30)
            ->get(['id', 'name'])
            ->map(fn (Product $product) => (object) [
                'id' => $product->id,
                'label' => $product->getTranslation('name', 'en', false),
            ]);
    }

    public function selectedProductLabel(): ?string
    {
        if (! $this->productFilter) {
            return null;
        }

        return Product::query()->find($this->productFilter)?->getTranslation('name', 'en', false);
    }

    /**
     * @return array<string, string>
     */
    public function filters(): array
    {
        return array_filter([
            'search' => $this->search,
            'status' => $this->statusFilter,
            'payment_status' => $this->paymentStatusFilter,
            'payment_method' => $this->paymentMethodFilter,
            'from' => $this->fromDate,
            'to' => $this->toDate,
            'product' => $this->productFilter,
            'price_min' => $this->priceMin,
            'price_max' => $this->priceMax,
        ]);
    }

    private function filteredQuery()
    {
        return Order::query()
            ->when($this->search, fn ($q) => $q
                ->where('order_number', 'like', "%{$this->search}%")
                ->orWhere('customer_name', 'like', "%{$this->search}%")
                ->orWhere('customer_email', 'like', "%{$this->search}%"))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->paymentStatusFilter, fn ($q) => $q->where('payment_status', $this->paymentStatusFilter))
            ->when($this->paymentMethodFilter, fn ($q) => $q->where('payment_method', $this->paymentMethodFilter))
            ->when($this->fromDate, fn ($q) => $q->where('created_at', '>=', CarbonImmutable::parse($this->fromDate, display_timezone())->startOfDay()->utc()))
            ->when($this->toDate, fn ($q) => $q->where('created_at', '<=', CarbonImmutable::parse($this->toDate, display_timezone())->endOfDay()->utc()))
            ->when($this->productFilter, fn ($q) => $q->whereHas('items', fn ($q2) => $q2->where('product_id', $this->productFilter)))
            ->when($this->priceMin !== '', fn ($q) => $q->where('total', '>=', $this->priceMin))
            ->when($this->priceMax !== '', fn ($q) => $q->where('total', '<=', $this->priceMax));
    }

    public function render()
    {
        return view('livewire.admin.orders.index', [
            'orders' => $this->filteredQuery()
                ->withCount('items')
                // One extra query for the whole page beats a lazy load per row —
                // and the referrer is the only reason this table needs the join.
                ->with('referrer:id,name,code')
                ->latest()
                ->paginate($this->perPage),
            'createProducts' => Product::where('status', 'active')->where('is_upcoming', false)
                ->orderBy('name->en')->limit(500)->get(['id', 'name'])
                ->map(fn (Product $p) => (object) ['id' => $p->id, 'label' => $p->getTranslation('name', 'en', false)]),
            'shippingMethods' => ShippingMethod::active()->get(['id', 'name', 'cost']),
        ])->layout('layouts.admin', ['title' => 'Orders', 'hidePageHeading' => true]);
    }
}
