<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CmsBlog;
use App\Models\CmsPage;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $user = Auth::user();
        $shopId = $user->shop_id;

        if (mb_strlen($q) < 1) {
            return response()->json(['groups' => []]);
        }

        $like = '%'.mb_strtolower(str_replace(['%', '_'], ['\\%', '\\_'], $q)).'%';
        $groups = [];
        $canInventory = Gate::allows('manage inventory');
        $retail = retail_enabled();
        $canPos = $retail && Gate::allows('process pos sales');
        $canSales = Gate::allows('view sales ledger');
        $canWebsite = Gate::allows('manage website');
        $canStaff = Gate::allows('manage staff');
        $canOrders = Gate::allows('manage orders');
        $canCustomers = Gate::allows('manage customers');
        $isAdmin = $user->isAdminUser();

        if ($canInventory || $canPos) {
            $this->searchProducts($groups, $shopId, $like, $canInventory, $canPos);
        }

        if ($canInventory) {
            $this->searchCategories($groups, $shopId, $like);
            $this->searchBrands($groups, $shopId, $like);
            $this->searchSuppliers($groups, $shopId, $like);
            $this->searchPurchaseOrders($groups, $shopId, $like, $q);
        }

        if ($retail && ($canSales || $canPos || $isAdmin)) {
            $this->searchOrders($groups, $shopId, $like, $q, $isAdmin, $canSales);
        }

        if ($canOrders) {
            $this->searchOnlineOrders($groups, $shopId, $like, $q);
        }

        if ($canCustomers) {
            $this->searchCustomers($groups, $shopId, $like);
        }

        if ($canWebsite) {
            $this->searchCmsPages($groups, $shopId, $like);
            $this->searchCmsBlogs($groups, $shopId, $like);
        }

        if ($canStaff) {
            $this->searchStaff($groups, $shopId, $like);
        }

        return response()->json(['groups' => $groups]);
    }

    protected function searchProducts(array &$groups, int $shopId, string $like, bool $canInventory, bool $canPos): void
    {
        $products = Product::where('shop_id', $shopId)
            ->where(function ($query) use ($like) {
                $this->looseMatch($query, 'name', $like);
                $query->orWhere(function ($inner) use ($like) {
                    $this->looseMatch($inner, 'sku', $like);
                });
                $query->orWhere(function ($inner) use ($like) {
                    $this->looseMatch($inner, 'barcode', $like);
                });
            })
            ->orderByDesc('is_new_arrival')
            ->orderBy('name')
            ->limit(6)
            ->get(['id', 'name', 'sku', 'barcode', 'selling_price', 'stock_quantity', 'image', 'is_new_arrival', 'is_featured']);

        if ($products->isEmpty()) {
            return;
        }

        $groups[] = [
            'key' => 'products',
            'label' => 'Products',
            'items' => $products->map(function (Product $p) use ($canInventory, $canPos) {
                $flags = collect([
                    $p->is_new_arrival ? 'New' : null,
                    $p->is_featured ? 'Featured' : null,
                ])->filter()->implode(' · ');

                if ($canInventory) {
                    $url = route('products.edit', $p);
                } elseif ($canPos) {
                    $url = route('pos.index');
                } else {
                    $url = route('dashboard');
                }

                return [
                    'id' => 'product-'.$p->id,
                    'title' => $p->name,
                    'subtitle' => trim(collect([
                        $p->sku ? 'SKU '.$p->sku : null,
                        $p->barcode ? 'Barcode '.$p->barcode : null,
                        'Stock '.(int) $p->stock_quantity,
                        $flags ?: null,
                    ])->filter()->implode(' · ')),
                    'meta' => format_taka((float) $p->selling_price),
                    'url' => $url,
                    'icon' => 'product',
                    'image' => public_storage_url($p->image),
                    'target' => (! $canInventory && $canPos) ? 'pos' : null,
                ];
            })->values(),
        ];
    }

    protected function searchCategories(array &$groups, int $shopId, string $like): void
    {
        $items = Category::where('shop_id', $shopId)
            ->where(function ($query) use ($like) {
                $this->looseMatch($query, 'name', $like);
                $query->orWhere(function ($inner) use ($like) {
                    $this->looseMatch($inner, 'slug', $like);
                });
            })
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'slug', 'icon']);

        if ($items->isEmpty()) {
            return;
        }

        $groups[] = [
            'key' => 'categories',
            'label' => 'Categories',
            'items' => $items->map(fn (Category $c) => [
                'id' => 'category-'.$c->id,
                'title' => $c->name,
                'subtitle' => trim(collect([
                    $c->slug ? '/'.$c->slug : null,
                    $c->icon ? 'Icon: '.$c->icon : null,
                ])->filter()->implode(' · ')),
                'meta' => null,
                'url' => route('categories.edit', $c),
                'icon' => 'category',
                'image' => null,
            ])->values(),
        ];
    }

    protected function searchBrands(array &$groups, int $shopId, string $like): void
    {
        $items = Brand::where('shop_id', $shopId)
            ->where(function ($query) use ($like) {
                $this->looseMatch($query, 'name', $like);
            })
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'is_active']);

        if ($items->isEmpty()) {
            return;
        }

        $groups[] = [
            'key' => 'brands',
            'label' => 'Brands',
            'items' => $items->map(fn (Brand $b) => [
                'id' => 'brand-'.$b->id,
                'title' => $b->name,
                'subtitle' => $b->is_active ? 'Active brand' : 'Inactive brand',
                'meta' => null,
                'url' => route('brands.edit', $b),
                'icon' => 'brand',
                'image' => null,
            ])->values(),
        ];
    }

    protected function searchSuppliers(array &$groups, int $shopId, string $like): void
    {
        $items = Supplier::where('shop_id', $shopId)
            ->where(function ($query) use ($like) {
                $this->looseMatch($query, 'name', $like);
                $query->orWhere(function ($inner) use ($like) {
                    $this->looseMatch($inner, 'phone', $like);
                });
                $query->orWhere(function ($inner) use ($like) {
                    $this->looseMatch($inner, 'email', $like);
                });
            })
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'phone', 'email']);

        if ($items->isEmpty()) {
            return;
        }

        $groups[] = [
            'key' => 'suppliers',
            'label' => 'Suppliers',
            'items' => $items->map(fn (Supplier $s) => [
                'id' => 'supplier-'.$s->id,
                'title' => $s->name,
                'subtitle' => trim(collect([$s->phone, $s->email])->filter()->implode(' · ')),
                'meta' => null,
                'url' => route('supply.suppliers.edit', $s),
                'icon' => 'supplier',
                'image' => null,
            ])->values(),
        ];
    }

    protected function searchPurchaseOrders(array &$groups, int $shopId, string $like, string $q): void
    {
        if (! Route::has('supply.purchase-orders.show')) {
            return;
        }

        $items = PurchaseOrder::where('shop_id', $shopId)
            ->where(function ($query) use ($like, $q) {
                $this->looseMatch($query, 'po_number', $like);
                if (ctype_digit($q)) {
                    $query->orWhere('id', (int) $q);
                }
            })
            ->latest('id')
            ->limit(5)
            ->get(['id', 'po_number', 'status', 'created_at', 'total_amount']);

        if ($items->isEmpty()) {
            return;
        }

        $groups[] = [
            'key' => 'purchase_orders',
            'label' => 'Purchase Orders',
            'items' => $items->map(function (PurchaseOrder $po) {
                $title = $po->po_number ?: ('PO #'.$po->id);

                return [
                    'id' => 'po-'.$po->id,
                    'title' => $title,
                    'subtitle' => trim(collect([
                        ucfirst((string) ($po->status ?? 'draft')),
                        optional($po->created_at)->format('M j, Y'),
                    ])->filter()->implode(' · ')),
                    'meta' => $po->total_amount !== null
                        ? format_taka((float) $po->total_amount)
                        : null,
                    'url' => route('supply.purchase-orders.show', $po),
                    'icon' => 'order',
                    'image' => null,
                ];
            })->values(),
        ];
    }

    protected function searchOrders(array &$groups, int $shopId, string $like, string $q, bool $isAdmin, bool $canSales): void
    {
        $ordersQuery = Order::where('shop_id', $shopId)
            ->with('customer:id,name,phone')
            ->where(function ($query) use ($like, $q) {
                $this->looseMatch($query, 'invoice_no', $like);
                if (ctype_digit($q)) {
                    $query->orWhere('id', (int) $q);
                }
                $query->orWhereHas('customer', function ($c) use ($like) {
                    $this->looseMatch($c, 'name', $like);
                    $c->orWhere(function ($inner) use ($like) {
                        $this->looseMatch($inner, 'phone', $like);
                    });
                });
            });

        // Counter staff: POS sales only (online orders have their own admin group).
        if (! $isAdmin) {
            $ordersQuery->whereNotNull('counter_id');
        } else {
            // Prefer physical sales here; online shown separately for admins.
            $ordersQuery->whereNotNull('counter_id');
        }

        $orders = $ordersQuery
            ->latest()
            ->limit(6)
            ->get(['id', 'invoice_no', 'total_amount', 'status', 'counter_id', 'customer_id', 'created_at']);

        if ($orders->isEmpty()) {
            return;
        }

        $groups[] = [
            'key' => 'orders',
            'label' => 'POS Orders',
            'items' => $orders->map(function (Order $order) use ($canSales) {
                $url = route('orders.invoice', $order);

                return [
                    'id' => 'order-'.$order->id,
                    'title' => $order->invoice_no,
                    'subtitle' => trim(collect([
                        $order->customer?->name ?? 'Walk-in',
                        ucfirst($order->status),
                        'POS',
                        optional($order->created_at)->format('M j, Y'),
                    ])->filter()->implode(' · ')),
                    'meta' => format_taka((float) $order->total_amount),
                    'url' => $url,
                    'icon' => 'order',
                    'image' => null,
                ];
            })->values(),
        ];
    }

    protected function searchOnlineOrders(array &$groups, int $shopId, string $like, string $q): void
    {
        $orders = Order::where('shop_id', $shopId)
            ->onlineOrders()
            ->with('customer:id,name,phone')
            ->where(function ($query) use ($like, $q) {
                $this->looseMatch($query, 'invoice_no', $like);
                if (ctype_digit($q)) {
                    $query->orWhere('id', (int) $q);
                }
                $query->orWhereHas('customer', function ($c) use ($like) {
                    $this->looseMatch($c, 'name', $like);
                    $c->orWhere(function ($inner) use ($like) {
                        $this->looseMatch($inner, 'phone', $like);
                    });
                });
            })
            ->latest()
            ->limit(6)
            ->get(['id', 'invoice_no', 'total_amount', 'status', 'customer_id', 'created_at']);

        if ($orders->isEmpty()) {
            return;
        }

        $groups[] = [
            'key' => 'online_orders',
            'label' => 'Online Orders',
            'items' => $orders->map(fn (Order $order) => [
                'id' => 'online-'.$order->id,
                'title' => $order->invoice_no,
                'subtitle' => trim(collect([
                    $order->customer?->name ?? 'Guest',
                    ucfirst($order->status),
                    'Online',
                    optional($order->created_at)->format('M j, Y'),
                ])->filter()->implode(' · ')),
                'meta' => format_taka((float) $order->total_amount),
                'url' => route('online-orders.show', $order),
                'icon' => 'globe',
                'image' => null,
            ])->values(),
        ];
    }

    protected function searchCustomers(array &$groups, int $shopId, string $like): void
    {
        $customers = Customer::where('shop_id', $shopId)
            ->where(function ($query) use ($like) {
                $this->looseMatch($query, 'name', $like);
                $query->orWhere(function ($inner) use ($like) {
                    $this->looseMatch($inner, 'phone', $like);
                });
                $query->orWhere(function ($inner) use ($like) {
                    $this->looseMatch($inner, 'email', $like);
                });
            })
            ->orderBy('name')
            ->limit(6)
            ->get(['id', 'name', 'phone', 'email']);

        if ($customers->isEmpty()) {
            return;
        }

        $groups[] = [
            'key' => 'customers',
            'label' => 'Customers',
            'items' => $customers->map(fn (Customer $c) => [
                'id' => 'customer-'.$c->id,
                'title' => $c->name ?: 'Customer',
                'subtitle' => trim(collect([$c->phone, $c->email])->filter()->implode(' · ')),
                'meta' => null,
                'url' => route('customers.show', $c),
                'icon' => 'customer',
                'image' => null,
            ])->values(),
        ];
    }

    protected function searchCmsPages(array &$groups, int $shopId, string $like): void
    {
        $items = CmsPage::where('shop_id', $shopId)
            ->where(function ($query) use ($like) {
                $this->looseMatch($query, 'title', $like);
                $query->orWhere(function ($inner) use ($like) {
                    $this->looseMatch($inner, 'slug', $like);
                });
            })
            ->orderBy('title')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'is_published']);

        if ($items->isEmpty()) {
            return;
        }

        $groups[] = [
            'key' => 'cms_pages',
            'label' => 'CMS Pages',
            'items' => $items->map(fn (CmsPage $p) => [
                'id' => 'page-'.$p->id,
                'title' => $p->title,
                'subtitle' => trim(collect([
                    $p->slug ? '/page/'.$p->slug : null,
                    $p->is_published ? 'Published' : 'Draft',
                ])->filter()->implode(' · ')),
                'meta' => null,
                'url' => route('cms.pages.edit', $p),
                'icon' => 'page',
                'image' => null,
            ])->values(),
        ];
    }

    protected function searchCmsBlogs(array &$groups, int $shopId, string $like): void
    {
        $items = CmsBlog::where('shop_id', $shopId)
            ->where(function ($query) use ($like) {
                $this->looseMatch($query, 'title', $like);
                $query->orWhere(function ($inner) use ($like) {
                    $this->looseMatch($inner, 'slug', $like);
                });
            })
            ->latest('id')
            ->limit(5)
            ->get(['id', 'title', 'slug']);

        if ($items->isEmpty()) {
            return;
        }

        $groups[] = [
            'key' => 'cms_blogs',
            'label' => 'Blogs',
            'items' => $items->map(fn (CmsBlog $b) => [
                'id' => 'blog-'.$b->id,
                'title' => $b->title,
                'subtitle' => $b->slug ? '/blog/'.$b->slug : 'Blog post',
                'meta' => null,
                'url' => route('cms.blogs.edit', $b),
                'icon' => 'blog',
                'image' => null,
            ])->values(),
        ];
    }

    protected function searchStaff(array &$groups, int $shopId, string $like): void
    {
        $items = User::staffMembers()
            ->where('shop_id', $shopId)
            ->where(function ($query) use ($like) {
                $this->looseMatch($query, 'name', $like);
                $query->orWhere(function ($inner) use ($like) {
                    $this->looseMatch($inner, 'email', $like);
                });
            })
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'email', 'role']);

        if ($items->isEmpty()) {
            return;
        }

        $groups[] = [
            'key' => 'staff',
            'label' => 'Staff',
            'items' => $items->map(fn (User $u) => [
                'id' => 'staff-'.$u->id,
                'title' => $u->name,
                'subtitle' => trim(collect([$u->email, $u->role])->filter()->implode(' · ')),
                'meta' => null,
                'url' => route('staff.edit', $u),
                'icon' => 'staff',
                'image' => null,
            ])->values(),
        ];
    }

    protected function looseMatch($query, string $column, string $like): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            $query->where($column, 'ilike', $like);

            return;
        }

        $query->whereRaw("LOWER({$column}) LIKE ?", [$like]);
    }
}
