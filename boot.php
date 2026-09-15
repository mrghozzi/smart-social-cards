<?php

declare(strict_types=1);

use App\Helpers\Hooks;
use App\Support\SeoPayload;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use MyAds\Plugins\SmartSocialCards\Controllers\AdminSocialCardsController;
use MyAds\Plugins\SmartSocialCards\Controllers\OgImageController;
use MyAds\Plugins\SmartSocialCards\Services\SocialCardGeneratorService;

// ─── 1. Autoloader ─────────────────────────────────────────────────────────────
$socialCardsBasePath = __DIR__;
$socialCardsNamespace = 'MyAds\\Plugins\\SmartSocialCards\\';

if (!function_exists('myads_smart_social_cards_autoload_registered')) {
    function myads_smart_social_cards_autoload_registered(string $namespace, string $basePath): bool
    {
        static $registered = false;
        if ($registered) {
            return true;
        }

        spl_autoload_register(static function (string $class) use (&$registered, $namespace, $basePath): void {
            if (!str_starts_with($class, $namespace)) {
                return;
            }

            $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($namespace)));
            $filePath = $basePath . '/src/' . $relativePath . '.php';

            if (is_file($filePath)) {
                require_once $filePath;
            }
        });

        $registered = true;
        return true;
    }
}

myads_smart_social_cards_autoload_registered($socialCardsNamespace, $socialCardsBasePath);

// ─── 2. Service Singleton Helper ───────────────────────────────────────────────
if (!function_exists('smart_social_cards_service')) {
    function smart_social_cards_service(): SocialCardGeneratorService
    {
        static $instance = null;
        if (!$instance instanceof SocialCardGeneratorService) {
            $instance = new SocialCardGeneratorService();
        }
        return $instance;
    }
}

// ─── 3. Register Views & Translations ──────────────────────────────────────────
View::addNamespace('smart-social-cards', __DIR__ . '/views');
app('translator')->addNamespace('smart_social_cards', __DIR__ . '/lang');

// ─── 4. Admin Sidebar Hook ─────────────────────────────────────────────────────
Hooks::add_action('admin_sidebar_menu', function (): void {
    $url = route('admin.smart_social_cards.index');
    $isActive = request()->routeIs('admin.smart_social_cards.*');
    $linkClass = $isActive ? 'nxl-link active' : 'nxl-link';

    echo '<li class="nxl-item">'
        . '<a href="' . e($url) . '" class="' . e($linkClass) . '">'
        . '<span class="nxl-micon"><i class="feather-image"></i></span>'
        . '<span class="nxl-mtext">' . __('smart_social_cards::messages.menu_title') . '</span>'
        . '</a>'
        . '</li>';
});

// ─── 5. Frontend View Composer: Inject Dynamic OG & Twitter Image ──────────────
View::composer('theme::layouts.master', function ($view): void {
    try {
        $seo = $view->getData()['seo'] ?? null;
        if (!$seo instanceof SeoPayload) {
            return;
        }

        $ogUrl = smart_social_cards_service()->resolveOgImageUrl(request(), $view->getData());
        if (!$ogUrl) {
            return;
        }

        $og = $seo->og;
        $og['image'] = $ogUrl;

        $twitter = $seo->twitter;
        $twitter['image'] = $ogUrl;
        $twitter['card'] = 'summary_large_image';

        $newPayload = new SeoPayload(
            title: $seo->title,
            description: $seo->description,
            keywords: $seo->keywords,
            canonical_url: $seo->canonical_url,
            robots: $seo->robots,
            og: $og,
            twitter: $twitter,
            schema_blocks: $seo->schema_blocks,
            head_snippets: $seo->head_snippets,
            indexable: $seo->indexable,
            lastmod: $seo->lastmod,
        );

        $view->with('seo', $newPayload);
    } catch (\Throwable $e) {
        // Suppress errors to guarantee zero page disruption
    }
});

// ─── 6. Public Image Delivery Routes ───────────────────────────────────────────
Route::get('/og-image/preview', [OgImageController::class, 'preview'])->name('og_image.preview');
Route::get('/og-image/{type}/{id}', [OgImageController::class, 'show'])
    ->where('type', 'topic|product|video|news|directory|page')
    ->where('id', '[a-zA-Z0-9_\-\.\%]+')
    ->name('og_image.show');

// ─── 7. Admin Routes ───────────────────────────────────────────────────────────
Route::middleware(['web', 'auth', 'admin'])->group(function () {
    Route::get('/admin/smart-social-cards', [AdminSocialCardsController::class, 'index'])->name('admin.smart_social_cards.index');
    Route::post('/admin/smart-social-cards/settings', [AdminSocialCardsController::class, 'saveSettings'])->name('admin.smart_social_cards.settings');
    Route::post('/admin/smart-social-cards/clear-cache', [AdminSocialCardsController::class, 'clearCache'])->name('admin.smart_social_cards.clear_cache');
});
