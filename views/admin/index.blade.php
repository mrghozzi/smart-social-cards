@extends('admin::layouts.admin')

@section('title', __('smart_social_cards::messages.dashboard_title'))
@section('admin_shell_header_mode', 'hidden')

@php
    $config = $config ?? [];
    $cacheStats = $cacheStats ?? ['count' => 0, 'size_formatted' => '0 MB'];
    $activeChannels = 0;
    foreach (['enabled_topics', 'enabled_products', 'enabled_videos', 'enabled_news', 'enabled_directory', 'enabled_pages'] as $k) {
        if (!empty($config[$k])) {
            $activeChannels++;
        }
    }
@endphp

@push('styles')
<style>
    .og-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        background: rgba(99, 102, 241, 0.12);
        color: #6366f1;
        border-radius: 20px;
        font-size: 0.82rem;
        font-weight: 600;
    }
    .og-kpi-card {
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 14px;
        padding: 1.25rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .og-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    }
    .og-kpi-title {
        font-size: 0.82rem;
        color: #64748b;
        font-weight: 500;
        margin-bottom: 0.35rem;
    }
    .og-kpi-val {
        font-size: 1.75rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
    }
    .og-preview-container {
        background: #090d16;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        overflow: hidden;
        position: relative;
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.25);
    }
    .og-preview-container img {
        width: 100%;
        height: auto;
        display: block;
        transition: opacity 0.2s ease;
    }
    .og-preview-loader {
        position: absolute;
        inset: 0;
        background: rgba(9, 13, 22, 0.7);
        backdrop-filter: blur(4px);
        display: none;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 1.1rem;
        gap: 10px;
        z-index: 10;
    }
    .og-card {
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 14px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.02);
    }
    .og-channel-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.85rem 0;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    .og-channel-row:last-child {
        border-bottom: none;
    }
</style>
@endpush

@section('content')
<div class="admin-page og-cards-page pb-5">

    {{-- HERO HEADER --}}
    <section class="admin-hero mb-4">
        <div class="admin-hero__content">
            <ul class="admin-breadcrumb">
                <li><a href="{{ route('admin.index') }}">{{ __('messages.board') }}</a></li>
                <li>{{ __('messages.plugins') }}</li>
                <li>{{ __('smart_social_cards::messages.menu_title') }}</li>
            </ul>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="og-hero-badge">
                    <i class="feather-image"></i> {{ __('smart_social_cards::messages.dashboard_eyebrow') }}
                </span>
                <span class="badge bg-soft-success text-success"><i class="feather-check-circle me-1"></i> PHP GD Active</span>
                <span class="badge bg-soft-primary text-primary"><i class="feather-zap me-1"></i> 1200x630 HD</span>
            </div>
            <h1 class="admin-hero__title">{{ __('smart_social_cards::messages.dashboard_title') }}</h1>
            <p class="admin-hero__copy">
                {{ __('smart_social_cards::messages.dashboard_desc') }}
            </p>
        </div>
        <div class="admin-hero__actions">
            <div class="row g-3 w-100">
                <div class="col-6 col-md-3">
                    <div class="og-kpi-card">
                        <div class="og-kpi-title">{{ __('smart_social_cards::messages.stat_cached_cards') }}</div>
                        <div class="og-kpi-val text-primary">{{ number_format($cacheStats['count']) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="og-kpi-card">
                        <div class="og-kpi-title">{{ __('smart_social_cards::messages.stat_disk_usage') }}</div>
                        <div class="og-kpi-val text-info">{{ $cacheStats['size_formatted'] }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="og-kpi-card">
                        <div class="og-kpi-title">{{ __('smart_social_cards::messages.stat_active_channels') }}</div>
                        <div class="og-kpi-val text-success">{{ $activeChannels }} / 6</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="og-kpi-card">
                        <div class="og-kpi-title">{{ __('smart_social_cards::messages.stat_performance') }}</div>
                        <div class="og-kpi-val text-warning">&lt; 5 ms</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4 d-flex align-items-center gap-2" role="alert">
            <i class="feather-check-circle fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- MAIN WORKSPACE GRID --}}
    <div class="row g-4">
        {{-- LEFT COLUMN: LIVE CARD SIMULATOR (8 COLS) --}}
        <div class="col-lg-8">
            <div class="card og-card mb-4">
                <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title mb-1 fw-bold"><i class="feather-airplay text-primary me-2"></i>{{ __('smart_social_cards::messages.live_preview_title') }}</h5>
                        <p class="text-muted small mb-0">{{ __('smart_social_cards::messages.live_preview_desc') }}</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-refresh-preview">
                        <i class="feather-rotate-cw me-1"></i> {{ __('smart_social_cards::messages.preview_refresh') }}
                    </button>
                </div>
                <div class="card-body p-4">
                    {{-- PREVIEW IMAGE CONTAINER --}}
                    <div class="og-preview-container mb-4">
                        <div class="og-preview-loader" id="og-preview-loader">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                            <span>Generating Card...</span>
                        </div>
                        <img id="og-preview-img" src="{{ route('og_image.preview', ['title' => 'مرحبا بكم في منصة ماي آدز للإعلانات الذكية', 'category' => 'المنتدى العام', 'author' => 'mrghozzi', 'theme' => $config['theme'] ?? 'midnight', 'stats' => '14 رد • 520 مشاهدة']) }}" alt="OG Card Preview">
                    </div>

                    {{-- SIMULATOR INPUTS --}}
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">{{ __('smart_social_cards::messages.preview_title') }}</label>
                            <input type="text" class="form-control" id="sim-title" value="مرحبا بكم في منصة ماي آدز للإعلانات الذكية">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">{{ __('smart_social_cards::messages.preview_category') }}</label>
                            <input type="text" class="form-control" id="sim-category" value="المنتدى العام">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">{{ __('smart_social_cards::messages.preview_author') }}</label>
                            <input type="text" class="form-control" id="sim-author" value="mrghozzi">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">{{ __('smart_social_cards::messages.preview_price') }}</label>
                            <input type="text" class="form-control" id="sim-price" placeholder="مثال: 250 PTS">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">{{ __('smart_social_cards::messages.preview_theme') }}</label>
                            <select class="form-select" id="sim-theme">
                                <option value="midnight" {{ ($config['theme'] ?? '') === 'midnight' ? 'selected' : '' }}>Dark Midnight</option>
                                <option value="cyberpunk" {{ ($config['theme'] ?? '') === 'cyberpunk' ? 'selected' : '' }}>Cyberpunk Neon</option>
                                <option value="sunset" {{ ($config['theme'] ?? '') === 'sunset' ? 'selected' : '' }}>Sunset Violet</option>
                                <option value="brand" {{ ($config['theme'] ?? '') === 'brand' ? 'selected' : '' }}>Classic Brand</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN: SETTINGS & CONTROLS (4 COLS) --}}
        <div class="col-lg-4">
            <form action="{{ route('admin.smart_social_cards.settings') }}" method="POST">
                @csrf

                {{-- SETTINGS CARD --}}
                <div class="card og-card mb-4">
                    <div class="card-header bg-transparent border-bottom py-3">
                        <h6 class="card-title mb-0 fw-bold"><i class="feather-sliders text-primary me-2"></i>{{ __('smart_social_cards::messages.general_settings') }}</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">{{ __('smart_social_cards::messages.preview_theme') }}</label>
                            <select name="theme" id="setting-theme" class="form-select">
                                <option value="midnight" {{ ($config['theme'] ?? '') === 'midnight' ? 'selected' : '' }}>{{ __('smart_social_cards::messages.theme_midnight') }}</option>
                                <option value="cyberpunk" {{ ($config['theme'] ?? '') === 'cyberpunk' ? 'selected' : '' }}>{{ __('smart_social_cards::messages.theme_cyberpunk') }}</option>
                                <option value="sunset" {{ ($config['theme'] ?? '') === 'sunset' ? 'selected' : '' }}>{{ __('smart_social_cards::messages.theme_sunset') }}</option>
                                <option value="brand" {{ ($config['theme'] ?? '') === 'brand' ? 'selected' : '' }}>{{ __('smart_social_cards::messages.theme_brand') }}</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">{{ __('smart_social_cards::messages.watermark_label') }}</label>
                            <input type="text" name="watermark" class="form-control" value="{{ $config['watermark'] ?? '' }}" placeholder="مثال: myads.net">
                            <small class="text-muted d-block mt-1">{{ __('smart_social_cards::messages.watermark_help') }}</small>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="cache_enabled" value="1" id="setting_cache_enabled" {{ !empty($config['cache_enabled']) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold small" for="setting_cache_enabled">
                                {{ __('smart_social_cards::messages.cache_enabled') }}
                            </label>
                            <small class="text-muted d-block mt-1">{{ __('smart_social_cards::messages.cache_help') }}</small>
                        </div>
                    </div>
                </div>

                {{-- CHANNELS TOGGLE CARD --}}
                <div class="card og-card mb-4">
                    <div class="card-header bg-transparent border-bottom py-3">
                        <h6 class="card-title mb-0 fw-bold"><i class="feather-layers text-primary me-2"></i>{{ __('smart_social_cards::messages.channels_title') }}</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="og-channel-row">
                            <label class="form-check-label small fw-semibold" for="ch_topics">{{ __('smart_social_cards::messages.channel_topics') }}</label>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" name="enabled_topics" value="1" id="ch_topics" {{ !empty($config['enabled_topics']) ? 'checked' : '' }}>
                            </div>
                        </div>

                        <div class="og-channel-row">
                            <label class="form-check-label small fw-semibold" for="ch_products">{{ __('smart_social_cards::messages.channel_products') }}</label>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" name="enabled_products" value="1" id="ch_products" {{ !empty($config['enabled_products']) ? 'checked' : '' }}>
                            </div>
                        </div>

                        <div class="og-channel-row">
                            <label class="form-check-label small fw-semibold" for="ch_videos">{{ __('smart_social_cards::messages.channel_videos') }}</label>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" name="enabled_videos" value="1" id="ch_videos" {{ !empty($config['enabled_videos']) ? 'checked' : '' }}>
                            </div>
                        </div>

                        <div class="og-channel-row">
                            <label class="form-check-label small fw-semibold" for="ch_news">{{ __('smart_social_cards::messages.channel_news') }}</label>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" name="enabled_news" value="1" id="ch_news" {{ !empty($config['enabled_news']) ? 'checked' : '' }}>
                            </div>
                        </div>

                        <div class="og-channel-row">
                            <label class="form-check-label small fw-semibold" for="ch_directory">{{ __('smart_social_cards::messages.channel_directory') }}</label>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" name="enabled_directory" value="1" id="ch_directory" {{ !empty($config['enabled_directory']) ? 'checked' : '' }}>
                            </div>
                        </div>

                        <div class="og-channel-row">
                            <label class="form-check-label small fw-semibold" for="ch_pages">{{ __('smart_social_cards::messages.channel_pages') }}</label>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" name="enabled_pages" value="1" id="ch_pages" {{ !empty($config['enabled_pages']) ? 'checked' : '' }}>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="feather-save me-1"></i> {{ __('smart_social_cards::messages.save_settings') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            {{-- CACHE PURGE CARD --}}
            <div class="card og-card">
                <div class="card-body p-3">
                    <form action="{{ route('admin.smart_social_cards.clear_cache') }}" method="POST" onsubmit="return confirm('{{ __('smart_social_cards::messages.clear_cache_confirm') }}');">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="feather-trash-2 me-1"></i> {{ __('smart_social_cards::messages.clear_cache_btn') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const previewImg = document.getElementById('og-preview-img');
    const previewLoader = document.getElementById('og-preview-loader');
    const btnRefresh = document.getElementById('btn-refresh-preview');

    const inputTitle = document.getElementById('sim-title');
    const inputCategory = document.getElementById('sim-category');
    const inputAuthor = document.getElementById('sim-author');
    const inputPrice = document.getElementById('sim-price');
    const selectTheme = document.getElementById('sim-theme');
    const settingTheme = document.getElementById('setting-theme');

    let debounceTimer = null;

    function updatePreview() {
        if (!previewImg) return;

        if (previewLoader) previewLoader.style.display = 'flex';

        const params = new URLSearchParams({
            title: inputTitle.value || 'MyAds Community',
            category: inputCategory.value || 'FORUM TOPIC',
            author: inputAuthor.value || 'mrghozzi',
            price: inputPrice.value || '',
            theme: selectTheme.value || 'midnight',
            _t: Date.now()
        });

        const newUrl = "{{ route('og_image.preview') }}?" + params.toString();

        const preloader = new Image();
        preloader.onload = function () {
            previewImg.src = newUrl;
            if (previewLoader) previewLoader.style.display = 'none';
        };
        preloader.onerror = function () {
            if (previewLoader) previewLoader.style.display = 'none';
        };
        preloader.src = newUrl;
    }

    // Sync theme selects
    selectTheme.addEventListener('change', function () {
        if (settingTheme) settingTheme.value = selectTheme.value;
        updatePreview();
    });

    if (settingTheme) {
        settingTheme.addEventListener('change', function () {
            selectTheme.value = settingTheme.value;
            updatePreview();
        });
    }

    [inputTitle, inputCategory, inputAuthor, inputPrice].forEach(el => {
        el.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(updatePreview, 400);
        });
    });

    if (btnRefresh) {
        btnRefresh.addEventListener('click', updatePreview);
    }
});
</script>
@endpush
