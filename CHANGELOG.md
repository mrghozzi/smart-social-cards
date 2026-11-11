# Changelog — Smart Social OG Cards

All notable changes to the **Smart Social OG Cards** (`smart-social-cards`) plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.2.0] - 2026-11-11

### Added / Enhanced
- **Community Groups Dynamic OpenGraph Cards Support:**
  - Added `/og-image/group/{idOrSlug}` endpoint to generate 1200x630 cards for community groups.
  - Automatically renders group name, localized category badge (`GROUP: CATEGORY`), member and post count statistics, and group description.
  - Generates immutable ETag cached preview cards for social media platforms.

## [1.1.0] - 2026-11-05

### Added / Enhanced
- **Full Compatibility with MYADS v4.7.0 & Laravel 12:**
  - Tested and validated OpenGraph cards generation under Dual DocumentRoot environments and zero-trust CSP headers.
  - Bumped `max_myads` requirement to `4.7.x`.
  - Maintained high-performance tiered caching, GD font shaper, and .superdesign admin preview simulator.

## [1.0.0] - 2026-09-16

### Added
- **Initial Release** of Smart Social OG Cards for MYADS v4.5.6.
- **Dynamic 1200x630 OpenGraph Image Generator**:
  - Native PHP GD engine producing high-definition 1200x630 cards matching Facebook, Twitter/X, LinkedIn, WhatsApp, Telegram, and Discord standards.
  - 4 built-in aesthetic themes: Dark Midnight (default), Cyberpunk Neon, Sunset Violet, and Classic Brand.
  - Multi-content support: Forum Topics (`/topic/{id}`), Store Products (`/store/{name}`), Videos & Clips (`/video/{id}`), News Articles (`/news/{id}`), Directory listings, and Static Pages.
  - Dynamic visual elements: Brand badge, resource category pill, auto-wrapped typography, author avatar/initials, PTS price badge for store items, and domain watermark.
- **Self-Contained Cross-Platform Font Engine**:
  - Bundled universal TrueType font (`assets/fonts/font.ttf`) with native Arabic & Latin character support.
  - Integrated `ArabicGlyphShaper` for automatic cursive joining and right-to-left bidirectional rendering in PHP GD.
- **High-Performance Tiered Caching**:
  - Generated cards saved to `storage/app/public/smart_social_cards/` with instant sub-5ms streaming on subsequent requests.
  - Full HTTP 30-day immutable cache headers (`Cache-Control: public, max-age=2592000, immutable`), `ETag`, and `Last-Modified`.
- **Automatic Frontend View Composer Integration**:
  - Non-intrusive `theme::layouts.master` View Composer dynamically replacing `og:image` and `twitter:image` tags.
  - Automatically forces `twitter:card` to `summary_large_image` for large rich media cards.
- **Interactive .superdesign Admin Hub (`/admin/smart-social-cards`)**:
  - Live real-time card preview simulator with dynamic text input, theme switcher, and content type switcher.
  - KPI statistics: Total cached cards, disk usage footprint, and active content channels.
  - Granular toggles per content type.
  - 1-click cache purge tool.
- **Automated Test Coverage**:
  - PHPUnit feature tests verifying plugin discovery, image generation, HTTP headers, caching, view composer, and admin actions.
