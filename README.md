# 🖼️ Smart Social OG Cards for MYADS — v1.0.0

**Smart Social OG Cards** is an autonomous dynamic OpenGraph & Twitter share card generation extension for the **MYADS v4.5.6+** platform. It automatically transforms plain social media links into eye-catching, high-resolution (1200x630) branded preview cards across Facebook, Twitter/X, Telegram, WhatsApp, LinkedIn, and Discord.

---

## ✨ Key Features

1. **Dynamic High-Resolution Rendering (1200x630)**:
   - Pixel-perfect OpenGraph cards rendered using native PHP GD.
   - 4 premium design themes: **Dark Midnight**, **Cyberpunk Neon**, **Sunset Violet**, and **Classic Brand**.
   - Auto-adaptive text scaling and multi-line wrapping with truncation safety.

2. **Full Multi-Content Channel Support**:
   - **Forum Topics** (`/topic/{id}`): Displays topic title, forum category pill, author name/avatar, and reply/view statistics.
   - **Store Products** (`/store/{name}`): Displays product name, category, vibrant emerald PTS price tag (`XXX PTS`), and author.
   - **Videos & Shorts** (`/video/{id}`): Displays video title, video badge, play icon overlay, and view count.
   - **News Articles** (`/news/{id}`): Displays news headline, category, and publication date.
   - **Directory Listings** & **Pages**: Clean branded display with site watermark.

3. **Self-Contained & Universal Font Engine**:
   - Bundles a universal TrueType font (`assets/fonts/font.ttf`) supporting both **Arabic** and **Latin** character sets.
   - Built-in `ArabicGlyphShaper` handling letter joining (isolated, initial, medial, final) and right-to-left layout in GD.
   - Zero external font CDN calls or paid third-party dependencies.

4. **Instant Sub-5ms Caching Engine**:
   - Cards are rendered on-demand and cached in `storage/app/public/smart_social_cards/`.
   - Served with `Cache-Control: public, max-age=2592000, immutable`, `ETag`, and `Last-Modified`.
   - Subsequent crawler and visitor requests take less than 5ms!

5. **Automated Head Meta Tag Injection**:
   - Automatically decorates `theme::layouts.master` to inject `og:image` and `twitter:image` pointing to the card URL.
   - Upgrades `twitter:card` to `summary_large_image` for maximum visibility in social feeds.

6. **Interactive .superdesign Admin Hub (`/admin/smart-social-cards`)**:
   - Live real-time card preview simulator directly in the dashboard.
   - Toggle individual content types on or off.
   - Cache size and card count monitor with a 1-click cache purge button.

---

## 📋 Requirements

- **MYADS Platform**: v4.5.6 or higher
- **PHP**: 8.2+ with `gd` and `mbstring` extensions enabled

---

## 🛠️ Installation

1. Place the `smart-social-cards` folder into `plugins/`.
2. Navigate to **Admin Panel → Settings → Plugins**.
3. Activate **Smart Social OG Cards**.
4. Configure options and preview cards at `/admin/smart-social-cards`.

---

## 👤 Author

- **Author**: MrGhozzi
- **Website**: [https://github.com/mrghozzi](https://github.com/mrghozzi)
- **License**: MIT
