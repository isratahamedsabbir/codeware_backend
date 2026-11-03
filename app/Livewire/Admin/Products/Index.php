<?php

namespace App\Livewire\Admin\Products;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\ImportsCsv;
use App\Concerns\WithSearch;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Import\CsvImporter;
use App\Services\Import\ProductCsvImporter;
use App\Support\AdminActivity;
use App\Support\EnvFile;
use App\Support\LandingTriggers;
use App\Support\PageCascade;
use App\Support\PuckEditor;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class Index extends Component
{
    use HasBulkSelection, HasPerPage, ImportsCsv, WithPagination, WithSearch;

    public string $statusFilter = '';

    public string $bulkLabel = '';

    public string $bulkProductSearch = '';

    /** @var array<int, int|string> */
    public array $bulkProductIds = [];

    public ?int $deletingId = null;

    public ?int $viewingId = null;

    /** The product whose per-variant stock the "variants" stock modal shows. */
    public ?int $stockProductId = null;

    /** The FRONTEND_URL .env value, edited from the Settings modal (see saveFrontendUrl()). */
    public string $frontendUrl = '';

    /** The FRONTEND_PRODUCT_PATH .env value — see saveFrontendUrl(). */
    public string $productPreviewPath = '';

    /** Stock level at or below which a product row shows the low-stock warning — see saveStockSettings(). */
    public int $minStockQuantity = 10;

    /** Rules for when a product link opens its landing page, one per line — see LandingTriggers. */
    public string $landingTriggers = '';

    public function mount(): void
    {
        $this->landingTriggers = LandingTriggers::raw();
        $this->frontendUrl = EnvFile::get('FRONTEND_URL', '') ?? '';
        $this->productPreviewPath = EnvFile::get('FRONTEND_PRODUCT_PATH', '') ?? '';
        $this->minStockQuantity = Setting::productMinStockQuantity();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    protected function csvImporter(): CsvImporter
    {
        return new ProductCsvImporter;
    }

    protected function importModalName(): string
    {
        return 'product-import';
    }

    protected function importEntityLabel(): string
    {
        return 'product';
    }

    /**
     * Persists the public site's base URL (used to build product links in
     * emails/sitemaps — see Product::product_url) and the optional path
     * segment for this page's own Preview links, straight from this page's
     * Settings modal, rather than sending the admin off to the full
     * Settings → Env tab for a couple of fields.
     */
    public function saveFrontendUrl(): void
    {
        // The trigger button/modal are hidden from staff in the Blade view (this
        // page's route only requires access-admin, not access-admin-system), but
        // a Livewire component's public methods are still directly callable —
        // this is the actual enforcement, not the hidden UI.
        Gate::authorize('access-admin-system');

        $this->validate([
            'frontendUrl' => 'nullable|url',
            'productPreviewPath' => 'nullable|string|max:255|regex:/^[a-z0-9\-\/]*$/i',
        ], [], ['frontendUrl' => 'frontend URL', 'productPreviewPath' => 'product path']);

        $this->productPreviewPath = trim($this->productPreviewPath, '/');

        try {
            EnvFile::set([
                'FRONTEND_URL' => $this->frontendUrl,
                'FRONTEND_PRODUCT_PATH' => $this->productPreviewPath,
            ]);
        } catch (RuntimeException $e) {
            $this->dispatch('notify', message: 'Could not save the frontend URL: '.$e->getMessage());

            return;
        }

        Artisan::call('config:clear');

        AdminActivity::log('updated', 'Frontend URL updated');

        $this->dispatch('close-modal', name: 'frontend-url-settings');
        $this->dispatch('notify', message: 'Frontend URL saved.');
    }

    /**
     * Persists the stock threshold at which products count as "low stock" on
     * this screen (rows at or below it turn red with a warning) — straight
     * from this page's Stock Settings modal.
     */
    public function saveStockSettings(): void
    {
        Gate::authorize('access-admin-system');

        $this->validate([
            'minStockQuantity' => 'required|integer|min:0|max:1000000',
        ], [], ['minStockQuantity' => 'minimum stock quantity']);

        Setting::set('product_min_stock_quantity', $this->minStockQuantity);

        AdminActivity::log('updated', 'Product minimum stock quantity set to '.$this->minStockQuantity);

        $this->dispatch('close-modal', name: 'product-stock-settings');
        $this->dispatch('notify', message: 'Stock settings saved.');
    }

    /**
     * Persists which visits get a product's landing page (query-string rules,
     * see LandingTriggers) from this page's Landing Page modal. Blank turns the
     * landing pages off.
     */
    public function saveLandingTriggers(): void
    {
        Gate::authorize('access-admin-system');

        $rules = LandingTriggers::rules($this->landingTriggers);

        $this->validate([
            'landingTriggers' => ['nullable', 'string', 'max:2000', function (string $attribute, mixed $value, \Closure $fail) use ($rules) {
                if (count($rules) > LandingTriggers::MAX_RULES) {
                    $fail('Use at most '.LandingTriggers::MAX_RULES.' rules.');
                }

                foreach ($rules as $rule) {
                    if (! preg_match(LandingTriggers::RULE_PATTERN, $rule)) {
                        $fail("\"{$rule}\" is not a valid rule. Use a parameter name (fbclid) or name=value (utm_source=facebook).");

                        return;
                    }
                }
            }],
        ], [], ['landingTriggers' => 'landing page rules']);

        $this->landingTriggers = implode("\n", $rules);

        Setting::set(LandingTriggers::SETTING, $this->landingTriggers);

        AdminActivity::log('updated', 'Landing page rules updated');

        $this->dispatch('close-modal', name: 'landing-page-settings');
        $this->dispatch('notify', message: 'Landing page rules saved.');
    }

    public function reorder(array $order): void
    {
        foreach ($order as $sortOrder => $productId) {
            Product::where('id', $productId)->update(['sort_order' => $sortOrder]);
        }
    }

    public function openPuckEditor(int $productId): void
    {
        $product = Product::with('page')->findOrFail($productId);

        $page = $product->page ?? Page::create([
            'user_id' => auth()->id(),
            'product_id' => $product->id,
            'type' => 'product',
            'title' => $product->name,
            'status' => $product->status,
        ]);

        $token = PuckEditor::token(auth()->user(), "puck-builder-{$page->id}");

        $url = config('cms.editor_base_url')."/puck/edit/product/{$page->id}#token={$token}";
        $this->js('window.open('.json_encode($url).', \'_blank\')');
    }

    public function toggleStatus(int $id): void
    {
        $product = Product::with('page')->findOrFail($id);
        $newStatus = $product->status === 'active' ? 'inactive' : 'active';

        $product->update(['status' => $newStatus]);
        $product->page?->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Product #{$product->id}: {$product->name} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Product status updated');
    }

    public function toggleFeatured(int $id): void
    {
        $product = Product::findOrFail($id);
        $product->update(['is_featured' => ! $product->is_featured]);

        AdminActivity::log('updated', "Product #{$product->id}: {$product->name} ".($product->is_featured ? 'marked featured' : 'unmarked featured'));
        $this->dispatch('notify', message: 'Product featured status updated');
    }

    public function toggleUpcoming(int $id): void
    {
        $product = Product::findOrFail($id);
        $product->update(['is_upcoming' => ! $product->is_upcoming]);

        AdminActivity::log('updated', "Product #{$product->id}: {$product->name} ".($product->is_upcoming ? 'marked upcoming' : 'unmarked upcoming'));
        $this->dispatch('notify', message: 'Product upcoming status updated');
    }

    public function toggleChargeShipping(int $id): void
    {
        $product = Product::findOrFail($id);
        $product->update(['charge_shipping' => ! $product->charge_shipping]);

        AdminActivity::log('updated', "Product #{$product->id}: {$product->name} ".($product->charge_shipping ? 'shipping charge enabled' : 'shipping charge disabled'));
        $this->dispatch('notify', message: 'Product shipping charge updated');
    }

    public function viewDetails(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeDetails(): void
    {
        $this->viewingId = null;
    }

    public function viewStock(int $id): void
    {
        $this->stockProductId = $id;
        $this->dispatch('open-modal', name: 'product-stock');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'product-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $product = Product::with('page')->findOrFail($this->deletingId);
            PageCascade::deletePageFor($product);
            AdminActivity::log('deleted', "Product #{$product->id}: {$product->name}");
            $product->delete();
            $this->dispatch('notify', message: 'Product deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'product-delete');
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->dispatch('open-modal', name: 'product-bulk-delete');
    }

    public function openBulkLabel(): void
    {
        // Keep whatever was ticked in this modal the last time it was open and
        // fold in any rows selected in the table since then. Clearing the
        // selection every time made the admin lose track of the products they
        // were labelling.
        $this->bulkProductIds = $this->normaliseIds([
            ...$this->bulkProductIds,
            ...$this->selectedIds,
        ]);
        $this->bulkProductSearch = '';
        $this->resetValidation('bulkLabel');
        $this->resetValidation('bulkProductIds');
        $this->dispatch('open-modal', name: 'product-bulk-label');
    }

    public function updatedBulkProductIds(): void
    {
        $this->bulkProductIds = $this->normaliseIds($this->bulkProductIds);
    }

    public function removeProductLabel(int $productId): void
    {
        $product = Product::findOrFail($productId);
        $product->update(['label' => null]);

        AdminActivity::log('updated', "Product image label removed from Product #{$product->id}");
        $this->dispatch('notify', message: 'Product image label removed.');
    }

    public function bulkUpdateLabel(): void
    {
        if ($this->bulkProductIds === []) {
            return;
        }

        $this->validate([
            'bulkLabel' => ['required', 'string', 'max:40'],
            'bulkProductIds' => ['required', 'array', 'min:1'],
            'bulkProductIds.*' => ['integer', 'exists:products,id'],
        ], [], ['bulkLabel' => 'product label']);

        $ids = $this->normaliseIds($this->bulkProductIds);
        $products = Product::whereIn('id', $ids)->get();
        $label = trim($this->bulkLabel);

        foreach ($products as $product) {
            $product->update(['label' => $label]);
        }

        $count = $products->count();

        // Keep the modal selection so the admin can reuse the same batch, but
        // do not change the table's independent row selection.
        $this->bulkProductIds = $ids;

        AdminActivity::log('updated', "Product image label applied to {$count} products");
        $this->dispatch('close-modal', name: 'product-bulk-label');
        $this->dispatch('notify', message: "Label applied to {$count} ".Str::plural('product', $count));
    }

    /**
     * Checkbox values arrive as strings; the rest of the component compares
     * ids strictly, so normalise everything to unique integers.
     *
     * @param  array<int, int|string>  $ids
     * @return array<int, int>
     */
    private function normaliseIds(array $ids): array
    {
        return collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function bulkDelete(): void
    {
        $products = Product::with('page')->whereIn('id', $this->selectedIds)->get();

        foreach ($products as $product) {
            PageCascade::deletePageFor($product);
            AdminActivity::log('deleted', "Product #{$product->id}: {$product->name}");
            $product->delete();
        }

        $count = $products->count();
        $this->selectedIds = [];
        $this->bulkProductIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('product', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'product-bulk-delete');
    }

    public function render()
    {
        $labelSearch = trim($this->bulkProductSearch);
        $selectedLabelIds = $this->bulkProductIds;
        $defaultLabelProductIds = Product::query()
            ->orderBy('id')
            ->limit(100)
            ->pluck('id')
            ->all();

        $labelProductOptions = Product::query()
            ->select(['id', 'name', 'label'])
            ->where(function ($query) use ($labelSearch, $selectedLabelIds, $defaultLabelProductIds) {
                if ($labelSearch !== '') {
                    if ($selectedLabelIds !== []) {
                        $query->whereIn('id', $selectedLabelIds)->orWhere(function ($query) use ($labelSearch) {
                            $term = '%'.$labelSearch.'%';
                            $query->where('name->en', 'like', $term)
                                ->orWhere('name->bn', 'like', $term);
                        });
                    } else {
                        $term = '%'.$labelSearch.'%';
                        $query->where('name->en', 'like', $term)
                            ->orWhere('name->bn', 'like', $term);
                    }
                } else {
                    $query->whereIn('id', $defaultLabelProductIds);

                    if ($selectedLabelIds !== []) {
                        $query->orWhereIn('id', $selectedLabelIds);
                    }
                }
            })
            ->orderBy('id')
            ->when($labelSearch !== '', fn ($query) => $query->limit(100))
            ->get();

        return view('livewire.admin.products.index', [
            'labelProductOptions' => $labelProductOptions,
            'products' => Product::query()
                ->with(['categories', 'page', 'creator'])
                ->when($this->search, fn ($q) => $q
                    ->where('name->en', 'like', "%{$this->search}%")
                    ->orWhere('name->bn', 'like', "%{$this->search}%")
                    ->orWhereHas('page', fn ($p) => $p->where('slug', 'like', "%{$this->search}%")))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Products', 'hidePageHeading' => true]);
    }
}
