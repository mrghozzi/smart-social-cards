<?php

declare(strict_types=1);

namespace MyAds\Plugins\SmartSocialCards\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use MyAds\Plugins\SmartSocialCards\Services\SocialCardGeneratorService;

class AdminSocialCardsController extends Controller
{
    protected SocialCardGeneratorService $cardService;

    public function __construct(?SocialCardGeneratorService $cardService = null)
    {
        $this->cardService = $cardService ?? smart_social_cards_service();
    }

    /**
     * Admin Management Dashboard.
     */
    public function index(): View
    {
        $config = $this->cardService->getConfig();
        $cacheStats = $this->cardService->getCacheStats();

        return view('smart-social-cards::admin.index', [
            'config' => $config,
            'cacheStats' => $cacheStats,
        ]);
    }

    /**
     * Save Plugin Configuration.
     */
    public function saveSettings(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'theme' => 'required|string|in:midnight,cyberpunk,sunset,brand',
            'watermark' => 'nullable|string|max:100',
            'enabled_topics' => 'nullable|boolean',
            'enabled_products' => 'nullable|boolean',
            'enabled_videos' => 'nullable|boolean',
            'enabled_news' => 'nullable|boolean',
            'enabled_directory' => 'nullable|boolean',
            'enabled_pages' => 'nullable|boolean',
            'cache_enabled' => 'nullable|boolean',
        ]);

        $config = [
            'theme' => $validated['theme'],
            'watermark' => trim((string) ($validated['watermark'] ?? '')),
            'enabled_topics' => $request->boolean('enabled_topics'),
            'enabled_products' => $request->boolean('enabled_products'),
            'enabled_videos' => $request->boolean('enabled_videos'),
            'enabled_news' => $request->boolean('enabled_news'),
            'enabled_directory' => $request->boolean('enabled_directory'),
            'enabled_pages' => $request->boolean('enabled_pages'),
            'cache_enabled' => $request->boolean('cache_enabled'),
        ];

        $this->cardService->saveConfig($config);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('smart_social_cards::messages.settings_saved'),
            ]);
        }

        return redirect()->route('admin.smart_social_cards.index')
            ->with('success', __('smart_social_cards::messages.settings_saved'));
    }

    /**
     * Clear all cached cards.
     */
    public function clearCache(Request $request): RedirectResponse|JsonResponse
    {
        $cleared = $this->cardService->clearCache();
        $message = __('smart_social_cards::messages.cache_cleared', ['count' => $cleared]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'cleared_count' => $cleared,
            ]);
        }

        return redirect()->route('admin.smart_social_cards.index')
            ->with('success', $message);
    }
}
