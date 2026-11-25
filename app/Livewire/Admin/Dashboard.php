<?php

namespace App\Livewire\Admin;

use App\Models\Contact;
use App\Models\MediaLibrary;
use App\Models\Order;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Support\Features;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Dashboard extends Component
{
    public int $totalProducts = 0;

    public int $totalCategories = 0;

    public int $totalPosts = 0;

    public int $publishedPosts = 0;

    public int $draftPosts = 0;

    public int $totalPages = 0;

    public int $totalMedia = 0;

    public string $totalMediaSize = '0 B';

    public int $productsThisMonth = 0;

    public int $postsThisMonth = 0;

    public int $pagesThisMonth = 0;

    public int $mediaThisMonth = 0;

    public int $totalServices = 0;

    public int $servicesThisMonth = 0;

    // Admin-only cards (Users, Orders, Contacts): filled in mount() only for an
    // account that passes access-admin-system, so staff never receive the numbers.
    public int $totalUsers = 0;

    public int $usersThisMonth = 0;

    public int $totalOrders = 0;

    public int $ordersThisMonth = 0;

    public int $totalContacts = 0;

    public int $contactsThisMonth = 0;

    /** Statistics only an Admin may see. */
    private const ADMIN_ONLY = [
        'totalUsers', 'usersThisMonth', 'totalOrders', 'ordersThisMonth', 'totalContacts', 'contactsThisMonth',
    ];

    public function mount(): void
    {
        $isAdmin = Gate::allows('access-admin-system');

        foreach ($this->statistics() as $property => $value) {
            if (! $isAdmin && in_array($property, self::ADMIN_ONLY, true)) {
                continue;
            }

            $this->{$property} = $value;
        }
    }

    /**
     * The stat cards' numbers. These are full-table COUNT/SUM queries that cost
     * real time once a table grows past a few thousand rows, and they rarely
     * change more than once a minute — so they're memoized briefly instead of
     * re-run on every mount/Livewire request.
     *
     * @return array<string, int|string>
     */
    private function statistics(): array
    {
        return Cache::remember('admin:dashboard:statistics', 60, function () {
            $monthStart = now()->startOfMonth();

            return [
                'totalProducts' => Product::count(),
                'totalCategories' => ProductCategory::count(),
                'totalPosts' => Post::count(),
                'publishedPosts' => Post::published()->count(),
                'draftPosts' => Post::draft()->count(),
                'totalPages' => Page::count(),
                'totalMedia' => MediaLibrary::count(),
                'totalMediaSize' => $this->formatBytes(MediaLibrary::sum('file_size')),
                'productsThisMonth' => Product::where('created_at', '>=', $monthStart)->count(),
                'postsThisMonth' => Post::where('created_at', '>=', $monthStart)->count(),
                'pagesThisMonth' => Page::where('created_at', '>=', $monthStart)->count(),
                'mediaThisMonth' => MediaLibrary::where('created_at', '>=', $monthStart)->count(),
                'totalServices' => Service::count(),
                'servicesThisMonth' => Service::where('created_at', '>=', $monthStart)->count(),
                'totalUsers' => User::count(),
                'usersThisMonth' => User::where('created_at', '>=', $monthStart)->count(),
                'totalOrders' => Order::count(),
                'ordersThisMonth' => Order::where('created_at', '>=', $monthStart)->count(),
                'totalContacts' => Contact::count(),
                'contactsThisMonth' => Contact::where('created_at', '>=', $monthStart)->count(),
            ];
        });
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }

    public function render()
    {
        return view('livewire.admin.dashboard', [
            'lowStockProducts' => Product::with('categories')
                ->where(function ($query) {
                    $query->whereNull('quantity')
                        ->orWhere('quantity', '<=', Setting::productMinStockQuantity());
                })
                ->orderByRaw('COALESCE(quantity, 0)')
                ->orderBy('id')
                ->take(10)
                ->get(),
            'productsEnabled' => Features::enabled('products'),
            'servicesEnabled' => Features::enabled('services'),
            'usersEnabled' => Features::enabled('access-control') && Gate::allows('access-admin-system'),
            'ordersEnabled' => Features::enabled('orders') && Gate::allows('access-admin-system'),
            'contactsEnabled' => Features::enabled('contacts') && Gate::allows('access-admin-system'),
        ])->layout('layouts.admin', ['title' => 'Dashboard', 'hidePageHeading' => true]);
    }
}
