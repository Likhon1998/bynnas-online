<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CmsBlog;
use App\Models\CmsPage;
use App\Services\WebsiteService;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class SeoController extends Controller
{
    public function __construct(protected WebsiteService $website) {}

    public function robots(): Response
    {
        $lines = ['User-agent: *'];

        if (app()->environment('production')) {
            foreach (['/dashboard', '/admin', '/account', '/checkout', '/cart/', '/search/', '/refresh-session', '/csrf-token', '/go/'] as $path) {
                $lines[] = 'Disallow: '.$path;
            }
            $lines[] = '';
            $lines[] = 'Sitemap: '.route('sitemap');
        } else {
            // Keep staging / local copies out of search results.
            $lines[] = 'Disallow: /';
        }

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        $shopId = $this->website->shopId();
        $urls = [
            ['loc' => route('home'), 'lastmod' => null],
            ['loc' => route('website.shop'), 'lastmod' => null],
            ['loc' => route('website.blogs'), 'lastmod' => null],
            ['loc' => route('website.faqs'), 'lastmod' => null],
            ['loc' => route('website.contact'), 'lastmod' => null],
        ];

        if ($shopId) {
            $this->website->catalogQuery($shopId)
                ->whereNotNull('slug')
                ->select(['id', 'slug', 'updated_at'])
                ->orderBy('id')
                ->each(function ($product) use (&$urls) {
                    $urls[] = ['loc' => route('website.product', $product), 'lastmod' => $product->updated_at];
                });

            Category::where('shop_id', $shopId)->whereNotNull('slug')->get(['id', 'slug', 'updated_at'])
                ->each(function ($category) use (&$urls) {
                    $urls[] = ['loc' => route('website.category', $category->slug), 'lastmod' => $category->updated_at];
                });

            Brand::where('shop_id', $shopId)
                ->where(fn ($q) => $q->where('is_active', true)->orWhereNull('is_active'))
                ->get(['id', 'name', 'updated_at'])
                ->unique(fn ($brand) => Str::slug($brand->name))
                ->each(function ($brand) use (&$urls) {
                    $urls[] = ['loc' => route('website.brand', Str::slug($brand->name)), 'lastmod' => $brand->updated_at];
                });

            CmsPage::where('shop_id', $shopId)->where('is_published', true)->get(['slug', 'updated_at'])
                ->each(function ($page) use (&$urls) {
                    $urls[] = ['loc' => route('website.page', $page->slug), 'lastmod' => $page->updated_at];
                });

            CmsBlog::where('shop_id', $shopId)->published()->get(['slug', 'updated_at'])
                ->each(function ($blog) use (&$urls) {
                    $urls[] = ['loc' => route('website.blog', $blog->slug), 'lastmod' => $blog->updated_at];
                });
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.e($url['loc']).'</loc>'
                .($url['lastmod'] ? '<lastmod>'.$url['lastmod']->toAtomString().'</lastmod>' : '')
                ."</url>\n";
        }
        $xml .= '</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
