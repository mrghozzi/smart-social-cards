<?php

declare(strict_types=1);

namespace MyAds\Plugins\SmartSocialCards\Services;

use App\Models\Option;
use App\Models\Setting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use MyAds\Plugins\SmartSocialCards\Support\ArabicGlyphShaper;

class SocialCardGeneratorService
{
    public const WIDTH = 1200;
    public const HEIGHT = 630;

    protected string $cacheDir;
    protected string $fontPath;

    public function __construct()
    {
        $baseStorage = (function_exists('app') && app()->bound('path.storage'))
            ? storage_path('app/public/smart_social_cards')
            : dirname(__DIR__, 4) . '/storage/app/public/smart_social_cards';

        $this->cacheDir = $baseStorage;
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }

        // Bundled font priority, fallback to system fonts
        $bundledFont = dirname(__DIR__, 2) . '/assets/fonts/font.ttf';
        if (file_exists($bundledFont)) {
            $this->fontPath = $bundledFont;
        } elseif (file_exists('C:/Windows/Fonts/tahoma.ttf')) {
            $this->fontPath = 'C:/Windows/Fonts/tahoma.ttf';
        } elseif (file_exists('/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf')) {
            $this->fontPath = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
        } else {
            $this->fontPath = $bundledFont;
        }
    }

    /**
     * Get plugin configuration.
     */
    public function getConfig(): array
    {
        $defaults = [
            'theme' => 'midnight',
            'enabled_topics' => true,
            'enabled_products' => true,
            'enabled_videos' => true,
            'enabled_news' => true,
            'enabled_directory' => true,
            'enabled_pages' => true,
            'watermark' => '',
            'cache_enabled' => true,
        ];

        try {
            if (class_exists(Option::class)) {
                $raw = Option::where('name', 'smart_social_cards_config')->where('o_type', 'smart_social_cards')->value('o_valuer');
                if ($raw) {
                    $decoded = json_decode((string) $raw, true);
                    return is_array($decoded) ? array_merge($defaults, $decoded) : $defaults;
                }
            }
        } catch (\Throwable $e) {
            // Standalone or DB not migrated
        }

        return $defaults;
    }

    /**
     * Save plugin configuration.
     */
    public function saveConfig(array $config): void
    {
        Option::updateOrCreate(
            ['name' => 'smart_social_cards_config'],
            ['o_valuer' => json_encode($config), 'o_type' => 'smart_social_cards']
        );
    }

    /**
     * Resolve OG image URL for a given route/context.
     */
    public function resolveOgImageUrl(?\Illuminate\Http\Request $request = null, array $viewData = []): ?string
    {
        $request ??= request();
        $routeName = $request->route()?->getName() ?? '';
        $config = $this->getConfig();

        // 1. Forum Topic
        if ($config['enabled_topics'] && ($routeName === 'forum.topic' || str_starts_with($routeName, 'forum.topic'))) {
            $id = $request->route('id') ?? $viewData['topic']->id ?? null;
            if ($id) {
                return url('/og-image/topic/' . $id . '.png');
            }
        }

        // 2. Store Product
        if ($config['enabled_products'] && ($routeName === 'store.show' || str_starts_with($routeName, 'store.show'))) {
            $name = $request->route('name') ?? $viewData['product']->name ?? null;
            if ($name) {
                return url('/og-image/product/' . urlencode((string) $name) . '.png');
            }
        }

        // 3. Video / Status
        if ($config['enabled_videos'] && ($routeName === 'video.show' || $routeName === 'video')) {
            $id = $request->route('id') ?? $viewData['video']->id ?? null;
            if ($id) {
                return url('/og-image/video/' . $id . '.png');
            }
        }

        // 4. News
        if ($config['enabled_news'] && ($routeName === 'news.show' || str_starts_with($routeName, 'news.show'))) {
            $id = $request->route('id') ?? $viewData['news']->id ?? null;
            if ($id) {
                return url('/og-image/news/' . $id . '.png');
            }
        }

        // 5. Directory
        if ($config['enabled_directory'] && ($routeName === 'directory.show' || str_starts_with($routeName, 'directory.show'))) {
            $id = $request->route('id') ?? $viewData['directory']->id ?? null;
            if ($id) {
                return url('/og-image/directory/' . $id . '.png');
            }
        }

        // 6. Page
        if ($config['enabled_pages'] && $routeName === 'page.show') {
            $slug = $request->route('slug') ?? $viewData['page']->slug ?? null;
            if ($slug) {
                return url('/og-image/page/' . urlencode((string) $slug) . '.png');
            }
        }

        return null;
    }

    /**
     * Generate or fetch cached card for entity.
     *
     * @param array $payload [title, category, author, price, stats, subtitle, theme]
     */
    public function renderCard(array $payload, ?string $cacheKey = null): string
    {
        $config = $this->getConfig();
        $useCache = $config['cache_enabled'] && !empty($cacheKey);
        $cacheFile = $cacheKey ? $this->cacheDir . '/' . md5($cacheKey) . '.png' : null;

        if ($useCache && $cacheFile && file_exists($cacheFile)) {
            return $cacheFile;
        }

        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        $theme = $payload['theme'] ?? $config['theme'] ?? 'midnight';
        $this->drawBackground($image, $theme);
        $this->drawGlassCard($image);
        $this->drawHeader($image, $payload);
        $this->drawTitleAndBody($image, $payload);
        $this->drawFooter($image, $payload);

        $targetFile = $cacheFile ?: $this->cacheDir . '/temp_' . uniqid() . '.png';
        imagepng($image, $targetFile, 6);
        imagedestroy($image);

        return $targetFile;
    }

    /**
     * Draw background with gradient aesthetic.
     */
    protected function drawBackground(\GdImage $image, string $theme): void
    {
        $colors = match ($theme) {
            'cyberpunk' => [
                'bg_top' => [10, 10, 18],
                'bg_bottom' => [24, 12, 38],
                'glow_1' => [236, 72, 153, 90], // Neon pink
                'glow_2' => [6, 182, 212, 95], // Neon cyan
            ],
            'sunset' => [
                'bg_top' => [24, 8, 40],
                'bg_bottom' => [59, 13, 70],
                'glow_1' => [244, 63, 94, 95], // Rose
                'glow_2' => [249, 115, 22, 100], // Orange
            ],
            'brand' => [
                'bg_top' => [15, 23, 42],
                'bg_bottom' => [30, 27, 75],
                'glow_1' => [99, 102, 241, 95], // Indigo
                'glow_2' => [16, 185, 129, 105], // Emerald
            ],
            default => [ // midnight
                'bg_top' => [11, 15, 25],
                'bg_bottom' => [18, 26, 45],
                'glow_1' => [79, 70, 229, 95], // Indigo glow
                'glow_2' => [14, 165, 233, 100], // Sky blue glow
            ],
        };

        // Linear vertical gradient background
        for ($y = 0; $y < self::HEIGHT; $y++) {
            $ratio = $y / self::HEIGHT;
            $r = (int) ($colors['bg_top'][0] + ($colors['bg_bottom'][0] - $colors['bg_top'][0]) * $ratio);
            $g = (int) ($colors['bg_top'][1] + ($colors['bg_bottom'][1] - $colors['bg_top'][1]) * $ratio);
            $b = (int) ($colors['bg_top'][2] + ($colors['bg_bottom'][2] - $colors['bg_top'][2]) * $ratio);

            $lineColor = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $y, self::WIDTH, $y, $lineColor);
        }

        // Ambient radial glowing circles
        $this->drawGlowCircle($image, 150, 100, 350, $colors['glow_1']);
        $this->drawGlowCircle($image, 1050, 520, 380, $colors['glow_2']);
    }

    /**
     * Draw soft ambient glow circle.
     */
    protected function drawGlowCircle(\GdImage $image, int $cx, int $cy, int $radius, array $rgba): void
    {
        $steps = 25;
        for ($r = $radius; $r > 0; $r -= (int) ($radius / $steps)) {
            $alpha = min(127, (int) ($rgba[3] + (127 - $rgba[3]) * ($r / $radius)));
            $col = imagecolorallocatealpha($image, $rgba[0], $rgba[1], $rgba[2], $alpha);
            imagefilledellipse($image, $cx, $cy, $r * 2, $r * 2, $col);
        }
    }

    /**
     * Draw modern glassmorphic rounded card container.
     */
    protected function drawGlassCard(\GdImage $image): void
    {
        $x1 = 50;
        $y1 = 45;
        $x2 = self::WIDTH - 50;
        $y2 = self::HEIGHT - 45;
        $radius = 24;

        // Frosted card background
        $cardBg = imagecolorallocatealpha($image, 255, 255, 255, 120); // semi-transparent
        $this->drawFilledRoundedRectangle($image, $x1, $y1, $x2, $y2, $radius, $cardBg);

        // Subtle inner border
        $borderCol = imagecolorallocatealpha($image, 255, 255, 255, 108);
        $this->drawRoundedRectangleBorder($image, $x1, $y1, $x2, $y2, $radius, $borderCol);
    }

    /**
     * Draw top header (Brand title + Resource category pill).
     */
    protected function drawHeader(\GdImage $image, array $payload): void
    {
        $siteTitle = 'MyAds';
        try {
            if (class_exists(Setting::class)) {
                $siteTitle = Setting::first()?->titer ?: ((function_exists('app') && app()->bound('config')) ? config('app.name', 'MyAds') : 'MyAds');
            }
        } catch (\Throwable $e) {
            $siteTitle = 'MyAds';
        }
        $siteTitle = trim((string) $siteTitle) !== '' ? $siteTitle : 'MyAds';

        // 1. Site Brand badge (top-left)
        $brandBg = imagecolorallocatealpha($image, 99, 102, 241, 30); // Vibrant indigo pill
        $this->drawFilledRoundedRectangle($image, 90, 85, 90 + 170, 85 + 46, 12, $brandBg);

        // Dot indicator
        $dotCol = imagecolorallocate($image, 99, 102, 241);
        imagefilledellipse($image, 115, 108, 12, 12, $dotCol);

        // Brand text
        $brandTextCol = imagecolorallocate($image, 248, 250, 252);
        $this->drawText($image, 16, 134, 115, $brandTextCol, $siteTitle);

        // 2. Resource category pill (top-right)
        $category = !empty($payload['category']) ? mb_strtoupper((string) $payload['category'], 'UTF-8') : 'OFFICIAL';
        $pillBg = imagecolorallocatealpha($image, 255, 255, 255, 115);
        $pillWidth = max(130, mb_strlen($category) * 14 + 40);
        $pillX = self::WIDTH - 90 - $pillWidth;

        $this->drawFilledRoundedRectangle($image, $pillX, 85, $pillX + $pillWidth, 85 + 46, 12, $pillBg);
        $catTextCol = imagecolorallocate($image, 226, 232, 240);
        $this->drawText($image, 14, $pillX + 20, 114, $catTextCol, $category);
    }

    /**
     * Draw main title and body content.
     */
    protected function drawTitleAndBody(\GdImage $image, array $payload): void
    {
        $title = trim((string) ($payload['title'] ?? 'MyAds Platform'));
        $titleColor = imagecolorallocate($image, 255, 255, 255);

        // Word wrap for titles (max width ~1000px, 3 lines max)
        $lines = $this->wrapText($title, 42, 980);
        $lines = array_slice($lines, 0, 3);

        $startY = 240;
        $lineHeight = 64;

        foreach ($lines as $i => $line) {
            $y = $startY + ($i * $lineHeight);
            $this->drawText($image, 38, 90, $y, $titleColor, $line);
        }

        // Subtitle / Excerpt if provided and space permits
        if (!empty($payload['subtitle']) && count($lines) <= 2) {
            $subtitle = Str::limit(strip_tags((string) $payload['subtitle']), 95);
            $subColor = imagecolorallocate($image, 148, 163, 184); // slate-400
            $subY = $startY + (count($lines) * $lineHeight) + 15;
            $this->drawText($image, 19, 90, $subY, $subColor, $subtitle);
        }
    }

    /**
     * Draw bottom metadata footer (author, price badge, stats, watermark).
     */
    protected function drawFooter(\GdImage $image, array $payload): void
    {
        $footerY = self::HEIGHT - 120;
        $author = trim((string) ($payload['author'] ?? ''));
        $price = $payload['price'] ?? null;
        $stats = trim((string) ($payload['stats'] ?? ''));

        $currX = 90;

        // 1. Author Badge
        if ($author !== '') {
            $initial = mb_strtoupper(mb_substr($author, 0, 1, 'UTF-8'), 'UTF-8');

            // Avatar circle
            $avatarBg = imagecolorallocate($image, 79, 70, 229);
            imagefilledellipse($image, $currX + 22, $footerY + 18, 44, 44, $avatarBg);

            $avatarTxtCol = imagecolorallocate($image, 255, 255, 255);
            $this->drawText($image, 18, $currX + 14, $footerY + 25, $avatarTxtCol, $initial);

            // Author name
            $authorNameCol = imagecolorallocate($image, 241, 245, 249);
            $this->drawText($image, 18, $currX + 54, $footerY + 25, $authorNameCol, '@' . $author);

            $currX += 70 + (mb_strlen($author) * 12);
        }

        // 2. Price Tag (for Store Products)
        if ($price !== null && $price !== '') {
            $priceStr = is_numeric($price) ? number_format((float) $price, 0) . ' PTS' : (string) $price;
            $priceBadgeWidth = max(110, mb_strlen($priceStr) * 14 + 30);

            $priceBg = imagecolorallocatealpha($image, 16, 185, 129, 20); // Emerald green
            $this->drawFilledRoundedRectangle($image, $currX + 15, $footerY - 3, $currX + 15 + $priceBadgeWidth, $footerY + 41, 10, $priceBg);

            $priceTextCol = imagecolorallocate($image, 52, 211, 153);
            $this->drawText($image, 17, $currX + 30, $footerY + 26, $priceTextCol, '🏷️ ' . $priceStr);

            $currX += $priceBadgeWidth + 30;
        }

        // 3. Stats / Meta tag
        if ($stats !== '') {
            $statsCol = imagecolorallocate($image, 148, 163, 184);
            $this->drawText($image, 16, $currX + 20, $footerY + 25, $statsCol, '•  ' . $stats);
        }

        // 4. Domain Watermark (right side)
        $config = $this->getConfig();
        $appUrl = (function_exists('app') && app()->bound('config')) ? config('app.url', 'myads.net') : 'myads.net';
        $watermark = !empty($config['watermark']) ? $config['watermark'] : parse_url((string) $appUrl, PHP_URL_HOST);
        $watermark = $watermark ?: 'myads.net';

        $wmCol = imagecolorallocate($image, 100, 116, 139); // slate-500
        $wmWidth = mb_strlen($watermark) * 11;
        $wmX = self::WIDTH - 90 - $wmWidth;
        $this->drawText($image, 16, $wmX, $footerY + 25, $wmCol, $watermark);
    }

    /**
     * Draw text with FreeType TTF or fallback.
     */
    protected function drawText(\GdImage $image, float $size, int $x, int $y, int $color, string $text): void
    {
        $text = trim($text);
        if ($text === '') {
            return;
        }

        // Check if Arabic text and apply shaping
        if (ArabicGlyphShaper::isArabic($text)) {
            $text = ArabicGlyphShaper::shape($text);
        }

        if (file_exists($this->fontPath)) {
            imagettftext($image, $size, 0, $x, $y, $color, $this->fontPath, $text);
        } else {
            // GD built-in fallback
            $gdFont = ($size > 20) ? 5 : 4;
            imagestring($image, $gdFont, $x, $y - 20, $text, $color);
        }
    }

    /**
     * Wrap text into lines that fit within maxWidth pixels.
     */
    protected function wrapText(string $text, float $fontSize, int $maxWidth): array
    {
        $words = preg_split('/\s+/u', trim($text));
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = $currentLine === '' ? $word : $currentLine . ' ' . $word;

            if (file_exists($this->fontPath)) {
                $box = imagettfbbox($fontSize, 0, $this->fontPath, $testLine);
                $lineWidth = abs($box[4] - $box[0]);
            } else {
                $lineWidth = mb_strlen($testLine) * ($fontSize * 0.75);
            }

            if ($lineWidth > $maxWidth && $currentLine !== '') {
                $lines[] = $currentLine;
                $currentLine = $word;
            } else {
                $currentLine = $testLine;
            }
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return $lines;
    }

    /**
     * Helper: Draw filled rounded rectangle.
     */
    protected function drawFilledRoundedRectangle(\GdImage $im, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        imagefilledrectangle($im, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($im, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);

        imagefilledarc($im, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $color, IMG_ARC_PIE);
        imagefilledarc($im, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $color, IMG_ARC_PIE);
        imagefilledarc($im, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 90, 180, $color, IMG_ARC_PIE);
        imagefilledarc($im, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 0, 90, $color, IMG_ARC_PIE);
    }

    /**
     * Helper: Draw rounded rectangle border.
     */
    protected function drawRoundedRectangleBorder(\GdImage $im, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        imageline($im, $x1 + $radius, $y1, $x2 - $radius, $y1, $color);
        imageline($im, $x1 + $radius, $y2, $x2 - $radius, $y2, $color);
        imageline($im, $x1, $y1 + $radius, $x1, $y2 - $radius, $color);
        imageline($im, $x2, $y1 + $radius, $x2, $y2 - $radius, $color);

        imagearc($im, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $color);
        imagearc($im, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $color);
        imagearc($im, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 90, 180, $color);
        imagearc($im, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 0, 90, $color);
    }

    /**
     * Clear all cached social cards.
     */
    public function clearCache(): int
    {
        $count = 0;
        if (is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '/*.png');
            if ($files) {
                foreach ($files as $file) {
                    if (unlink($file)) {
                        $count++;
                    }
                }
            }
        }
        return $count;
    }

    /**
     * Get caching stats.
     */
    public function getCacheStats(): array
    {
        $count = 0;
        $totalBytes = 0;

        if (is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '/*.png');
            if ($files) {
                $count = count($files);
                foreach ($files as $file) {
                    $totalBytes += filesize($file);
                }
            }
        }

        return [
            'count' => $count,
            'size_bytes' => $totalBytes,
            'size_formatted' => round($totalBytes / (1024 * 1024), 2) . ' MB',
        ];
    }
}
