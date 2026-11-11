<?php

declare(strict_types=1);

namespace MyAds\Plugins\SmartSocialCards\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Directory;
use App\Models\ForumTopic;
use App\Models\News;
use App\Models\Page;
use App\Models\Product;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use MyAds\Plugins\SmartSocialCards\Services\SocialCardGeneratorService;

class OgImageController extends Controller
{
    protected SocialCardGeneratorService $cardService;

    public function __construct(?SocialCardGeneratorService $cardService = null)
    {
        $this->cardService = $cardService ?? smart_social_cards_service();
    }

    /**
     * Render and stream OpenGraph card for any content type.
     */
    public function show(Request $request, string $type, string $id): Response
    {
        // Strip .png extension if passed in route
        $id = preg_replace('/\.png$/i', '', $id);
        $cleanType = strtolower(trim($type));

        $payload = $this->resolveEntityPayload($cleanType, $id, $request);
        $cacheKey = $cleanType . '_' . $id . '_' . ($payload['updated_at'] ?? '1');

        $imagePath = $this->cardService->renderCard($payload, $cacheKey);

        if (!file_exists($imagePath)) {
            abort(404);
        }

        $content = file_get_contents($imagePath);
        $etag = md5_file($imagePath);

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304);
        }

        return response($content, 200, [
            'Content-Type' => 'image/png',
            'Content-Length' => strlen($content),
            'Cache-Control' => 'public, max-age=2592000, immutable',
            'ETag' => $etag,
        ]);
    }

    /**
     * Dynamic preview generator for admin testing & simulation.
     */
    public function preview(Request $request): Response
    {
        $payload = [
            'title' => $request->input('title', 'Smart Social OG Cards for MYADS'),
            'category' => $request->input('category', 'FORUM TOPIC'),
            'author' => $request->input('author', 'mrghozzi'),
            'price' => $request->input('price', null),
            'stats' => $request->input('stats', '14 replies • 520 views'),
            'subtitle' => $request->input('subtitle', 'Dynamic high-resolution OpenGraph cards generator.'),
            'theme' => $request->input('theme', 'midnight'),
        ];

        $imagePath = $this->cardService->renderCard($payload, null);

        $content = file_get_contents($imagePath);
        @unlink($imagePath); // Don't persist temporary preview images

        return response($content, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Resolve payload from database entities.
     */
    protected function resolveEntityPayload(string $type, string $id, Request $request): array
    {
        return match ($type) {
            'topic' => $this->resolveTopicPayload($id),
            'product' => $this->resolveProductPayload($id),
            'video' => $this->resolveVideoPayload($id),
            'news' => $this->resolveNewsPayload($id),
            'directory' => $this->resolveDirectoryPayload($id),
            'page' => $this->resolvePagePayload($id),
            'group' => $this->resolveGroupPayload($id),
            default => [
                'title' => $request->input('title', 'MyAds Community'),
                'category' => strtoupper($type),
                'author' => 'MyAds',
                'stats' => '',
                'subtitle' => '',
                'updated_at' => '1',
            ],
        };
    }

    protected function resolveTopicPayload(string $id): array
    {
        $topic = ForumTopic::with(['user', 'category'])->find($id);
        if (!$topic) {
            return [
                'title' => 'Forum Discussion Topic #' . $id,
                'category' => 'FORUM TOPIC',
                'author' => 'Community',
                'stats' => '',
                'subtitle' => 'Join the discussion on MyAds',
                'updated_at' => '1',
            ];
        }

        $views = (int) ($topic->views ?? $topic->hits ?? 0);
        $replies = (int) ($topic->replies_count ?? $topic->comments_count ?? 0);
        $stats = ($replies > 0 ? $replies . ' replies • ' : '') . $views . ' views';

        return [
            'title' => (string) ($topic->title ?? $topic->subject ?? $topic->txt),
            'category' => $topic->category?->name ?? 'FORUM TOPIC',
            'author' => $topic->user?->username ?? 'Member',
            'stats' => $stats,
            'subtitle' => !empty($topic->txt) ? strip_tags((string) $topic->txt) : '',
            'updated_at' => (string) ($topic->updated_at?->timestamp ?? '1'),
        ];
    }

    protected function resolveProductPayload(string $identifier): array
    {
        $product = Product::with(['user'])
            ->where('name', $identifier)
            ->orWhere('id', is_numeric($identifier) ? (int) $identifier : 0)
            ->first();

        if (!$product) {
            return [
                'title' => 'Store Product: ' . str_replace('-', ' ', $identifier),
                'category' => 'STORE PRODUCT',
                'author' => 'Store Seller',
                'price' => '0 PTS',
                'stats' => 'Verified Product',
                'subtitle' => 'Available on MyAds Marketplace',
                'updated_at' => '1',
            ];
        }

        $price = $product->price ?? $product->price_pts ?? $product->pts ?? null;

        return [
            'title' => (string) $product->name,
            'category' => 'STORE PRODUCT',
            'author' => $product->user?->username ?? 'Seller',
            'price' => $price,
            'stats' => 'Digital Product • Instant Delivery',
            'subtitle' => (string) ($product->o_valuer ?? $product->description ?? ''),
            'updated_at' => (string) ($product->updated_at?->timestamp ?? '1'),
        ];
    }

    protected function resolveVideoPayload(string $id): array
    {
        $video = Status::with('user')->find($id);
        if (!$video) {
            return [
                'title' => 'Video Clip #' . $id,
                'category' => 'VIDEO & SHORT',
                'author' => 'Creator',
                'stats' => '',
                'subtitle' => 'Watch on MyAds Videos',
                'updated_at' => '1',
            ];
        }

        $views = (int) ($video->views ?? 0);

        return [
            'title' => (string) ($video->txt ?? $video->title ?? 'Watch Video on MyAds'),
            'category' => 'VIDEO & SHORT',
            'author' => $video->user?->username ?? 'Creator',
            'stats' => $views . ' views',
            'subtitle' => 'Trending on MyAds Videos Hub',
            'updated_at' => (string) ($video->updated_at?->timestamp ?? '1'),
        ];
    }

    protected function resolveNewsPayload(string $id): array
    {
        $news = News::find($id);
        if (!$news) {
            return [
                'title' => 'News Article #' . $id,
                'category' => 'NEWS',
                'author' => 'Editorial',
                'stats' => '',
                'subtitle' => 'Read on MyAds News',
                'updated_at' => '1',
            ];
        }

        return [
            'title' => (string) $news->title,
            'category' => 'NEWS',
            'author' => 'Editorial',
            'stats' => $news->created_at?->format('M d, Y') ?? '',
            'subtitle' => !empty($news->content) ? strip_tags((string) $news->content) : '',
            'updated_at' => (string) ($news->updated_at?->timestamp ?? '1'),
        ];
    }

    protected function resolveDirectoryPayload(string $id): array
    {
        $dir = Directory::find($id);
        if (!$dir) {
            return [
                'title' => 'Directory Listing #' . $id,
                'category' => 'DIRECTORY',
                'author' => 'Webmaster',
                'stats' => '',
                'subtitle' => 'Explore listings on MyAds',
                'updated_at' => '1',
            ];
        }

        return [
            'title' => (string) ($dir->title ?? $dir->name ?? 'Directory Listing'),
            'category' => 'DIRECTORY',
            'author' => $dir->user?->username ?? 'Webmaster',
            'stats' => 'Verified Listing',
            'subtitle' => (string) ($dir->description ?? ''),
            'updated_at' => (string) ($dir->updated_at?->timestamp ?? '1'),
        ];
    }

    protected function resolvePagePayload(string $slug): array
    {
        $page = Page::where('slug', $slug)->first();
        if (!$page) {
            return [
                'title' => ucwords(str_replace(['-', '_'], ' ', $slug)),
                'category' => 'OFFICIAL PAGE',
                'author' => 'MyAds',
                'stats' => '',
                'subtitle' => 'Official Page on MyAds',
                'updated_at' => '1',
            ];
        }

        return [
            'title' => (string) $page->title,
            'category' => 'OFFICIAL PAGE',
            'author' => 'MyAds',
            'stats' => '',
            'subtitle' => '',
            'updated_at' => (string) ($page->updated_at?->timestamp ?? '1'),
        ];
    }

    protected function resolveGroupPayload(string $idOrSlug): array
    {
        $group = null;
        if (class_exists(\App\Models\Group::class)) {
            $group = \App\Models\Group::with(['owner'])
                ->where('slug', $idOrSlug)
                ->orWhere('id', is_numeric($idOrSlug) ? (int) $idOrSlug : 0)
                ->first();
        }

        if (!$group) {
            return [
                'title' => 'Community Group: ' . str_replace('-', ' ', $idOrSlug),
                'category' => 'COMMUNITY GROUP',
                'author' => 'Community',
                'stats' => '',
                'subtitle' => 'Join the discussion on MyAds Groups',
                'updated_at' => '1',
            ];
        }

        $categoryLabel = class_exists(\App\Models\Group::class) ? \App\Models\Group::categoryLabel($group->category) : 'COMMUNITY';
        $stats = $group->members_count . ' members • ' . $group->posts_count . ' posts';

        return [
            'title' => (string) $group->name,
            'category' => 'GROUP: ' . strtoupper($categoryLabel),
            'author' => $group->owner?->username ?? 'Community',
            'stats' => $stats,
            'subtitle' => !empty($group->short_description) ? $group->short_description : (string) ($group->description ?? ''),
            'updated_at' => (string) ($group->updated_at?->timestamp ?? '1'),
        ];
    }
}
