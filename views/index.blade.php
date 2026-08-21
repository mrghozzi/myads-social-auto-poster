@extends('admin::layouts.admin')

@section('title', __('social_auto_poster::messages.dashboard_title'))
@section('admin_shell_header_mode', 'hidden')

@php
    $stats = $stats ?? [
        'total_broadcasts' => 0,
        'total_success' => 0,
        'total_failed' => 0,
        'success_rate' => 100.0,
        'today_count' => 0,
        'by_channel' => ['telegram' => 0, 'facebook' => 0, 'twitter' => 0, 'discord' => 0, 'adstn' => 0],
    ];
    $config = $config ?? [];
    $logs = $logs ?? collect();
@endphp

@push('styles')
<style>
    .social-poster-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 14px;
        background: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
        border-radius: 20px;
        font-size: 0.82rem;
        font-weight: 600;
    }
    .social-kpi-card {
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 12px;
        padding: 1.25rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .social-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.06);
    }
    .social-kpi-title {
        font-size: 0.85rem;
        color: #6c757d;
        font-weight: 500;
        margin-bottom: 0.4rem;
    }
    .social-kpi-val {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.2;
    }
    .social-kpi-meta {
        font-size: 0.75rem;
        color: #94a3b8;
        margin-top: 0.35rem;
    }
    .channel-box {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .channel-box:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    }
    .channel-icon-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .channel-icon-badge {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        color: #ffffff;
    }
    .channel-icon-badge.telegram { background: #0088cc; }
    .channel-icon-badge.facebook { background: #1877f2; }
    .channel-icon-badge.twitter { background: #000000; }
    .channel-icon-badge.discord { background: #5865f2; }
    .channel-icon-badge.adstn { background: rgb(177, 151, 252); color: #1e1b4b; }

    .social-nav-tabs .nav-link {
        font-weight: 600;
        color: #64748b;
        padding: 0.85rem 1.35rem;
        border: none;
        border-bottom: 2px solid transparent;
    }
    .social-nav-tabs .nav-link.active {
        color: #0d6efd;
        background: transparent;
        border-bottom: 2px solid #0d6efd;
    }
    .preview-card-social {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .preview-card-social .preview-img {
        width: 100%;
        height: 190px;
        object-fit: cover;
        background: #e2e8f0;
    }
    .preview-body {
        padding: 1.2rem;
    }
    .preview-domain {
        font-size: 0.78rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .preview-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0.3rem 0;
    }
    .preview-desc {
        font-size: 0.88rem;
        color: #475569;
        line-height: 1.45;
    }
</style>
@endpush

@section('content')
<div class="admin-page pb-5">

    {{-- HERO SECTION --}}
    <section class="admin-hero mb-4">
        <div class="admin-hero__content">
            <ul class="admin-breadcrumb">
                <li><a href="{{ route('admin.index') }}">{{ __('messages.board') }}</a></li>
                <li>{{ __('messages.plugins') }}</li>
                <li>{{ __('social_auto_poster::messages.dashboard_title') }}</li>
            </ul>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="social-poster-hero-badge">
                    <i class="feather-share-2 text-primary"></i> MYADS Auto Poster v1.0
                </span>
                @if(!empty($config['telegram_enabled']))
                    <span class="badge bg-soft-info text-info"><i class="feather-check me-1"></i> Telegram</span>
                @endif
                @if(!empty($config['facebook_enabled']))
                    <span class="badge bg-soft-primary text-primary"><i class="feather-check me-1"></i> Facebook</span>
                @endif
                @if(!empty($config['twitter_enabled']))
                    <span class="badge bg-soft-dark text-dark"><i class="feather-check me-1"></i> X (Twitter)</span>
                @endif
                @if(!empty($config['discord_enabled']))
                    <span class="badge bg-soft-secondary text-secondary"><i class="feather-check me-1"></i> Discord</span>
                @endif
                @if(!empty($config['adstn_enabled']))
                    <span class="badge" style="background: rgba(177, 151, 252, 0.2); color: rgb(105, 65, 230); border: 1px solid rgba(177, 151, 252, 0.4);"><i class="fa-brands fa-buysellads me-1"></i> ADStn Network</span>
                @endif
            </div>
            <h1 class="admin-hero__title">{{ __('social_auto_poster::messages.dashboard_title') }}</h1>
            <p class="admin-hero__copy">
                {{ __('social_auto_poster::messages.dashboard_subtitle') }}
            </p>
        </div>
        <div class="admin-hero__actions">
            <div class="row g-3 w-100">
                <div class="col-6 col-md-3">
                    <div class="social-kpi-card">
                        <div class="social-kpi-title">{{ __('social_auto_poster::messages.total_broadcasts') }}</div>
                        <div class="social-kpi-val text-primary">{{ number_format($stats['total_broadcasts']) }}</div>
                        <div class="social-kpi-meta">{{ __('social_auto_poster::messages.today_broadcasts') }}: {{ number_format($stats['today_count']) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="social-kpi-card">
                        <div class="social-kpi-title">{{ __('social_auto_poster::messages.successful_posts') }}</div>
                        <div class="social-kpi-val text-success">{{ number_format($stats['total_success']) }}</div>
                        <div class="social-kpi-meta">{{ __('social_auto_poster::messages.success_rate') }}: {{ $stats['success_rate'] }}%</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="social-kpi-card">
                        <div class="social-kpi-title">{{ __('social_auto_poster::messages.failed_posts') }}</div>
                        <div class="social-kpi-val {{ $stats['total_failed'] > 0 ? 'text-danger' : 'text-muted' }}">{{ number_format($stats['total_failed']) }}</div>
                        <div class="social-kpi-meta">Errors / Retries</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="social-kpi-card">
                        <div class="social-kpi-title">{{ __('social_auto_poster::messages.active_channels') }}</div>
                        <div class="social-kpi-val text-info">
                            {{ collect([$config['telegram_enabled'] ?? false, $config['facebook_enabled'] ?? false, $config['twitter_enabled'] ?? false, $config['discord_enabled'] ?? false, $config['adstn_enabled'] ?? false])->filter()->count() }} / 5
                        </div>
                        <div class="social-kpi-meta">TG: {{ $stats['by_channel']['telegram'] ?? 0 }} | X: {{ $stats['by_channel']['twitter'] ?? 0 }} | ADStn: {{ $stats['by_channel']['adstn'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="feather-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="feather-alert-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- NAVIGATION TABS --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom-0 pb-0">
            <ul class="nav nav-tabs social-nav-tabs" id="socialTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-channels-btn" data-bs-toggle="tab" data-bs-target="#tab-channels" type="button" role="tab">
                        <i class="feather-radio me-1"></i> {{ __('social_auto_poster::messages.tab_channels') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-triggers-btn" data-bs-toggle="tab" data-bs-target="#tab-triggers" type="button" role="tab">
                        <i class="feather-cpu me-1"></i> {{ __('social_auto_poster::messages.tab_triggers') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-manual-btn" data-bs-toggle="tab" data-bs-target="#tab-manual" type="button" role="tab">
                        <i class="feather-send me-1"></i> {{ __('social_auto_poster::messages.tab_manual') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-logs-btn" data-bs-toggle="tab" data-bs-target="#tab-logs" type="button" role="tab">
                        <i class="feather-list me-1"></i> {{ __('social_auto_poster::messages.tab_logs') }}
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-4">
            <div class="tab-content" id="socialTabsContent">

                {{-- TAB 1: CHANNELS CONFIGURATION --}}
                <div class="tab-pane fade show active" id="tab-channels" role="tabpanel">
                    <form action="{{ route('admin.social_auto_poster.settings') }}" method="POST">
                        @csrf

                        <div class="row">
                            {{-- 1. TELEGRAM --}}
                            <div class="col-lg-6">
                                <div class="channel-box">
                                    <div class="channel-icon-header">
                                        <div class="channel-icon-badge telegram">
                                            <i class="feather-send"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="fw-bold mb-0">{{ __('social_auto_poster::messages.telegram_title') }}</h5>
                                            <p class="text-muted small mb-0">{{ __('social_auto_poster::messages.telegram_desc') }}</p>
                                        </div>
                                        <div class="form-check form-switch fs-5">
                                            <input class="form-check-input" type="checkbox" name="telegram_enabled" value="1" id="tg_enable" {{ !empty($config['telegram_enabled']) ? 'checked' : '' }}>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="tg_token">{{ __('social_auto_poster::messages.telegram_bot_token') }}</label>
                                        <input type="text" class="form-control font-monospace" id="tg_token" name="telegram_bot_token" value="{{ $config['telegram_bot_token'] ?? '' }}" placeholder="123456789:ABCDefgh-IJKLMnopQRstuvWXyz">
                                        <div class="form-text small">{{ __('social_auto_poster::messages.telegram_bot_token_help') }}</div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="tg_chat">{{ __('social_auto_poster::messages.telegram_chat_id') }}</label>
                                        <input type="text" class="form-control" id="tg_chat" name="telegram_chat_id" value="{{ $config['telegram_chat_id'] ?? '' }}" placeholder="@my_channel_username OR -100123456789">
                                        <div class="form-text small">{{ __('social_auto_poster::messages.telegram_chat_id_help') }}</div>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <button type="button" class="btn btn-outline-info btn-sm btn-test-channel" data-channel="telegram">
                                            <i class="feather-check-circle me-1"></i> {{ __('social_auto_poster::messages.test_connection') }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- 2. FACEBOOK --}}
                            <div class="col-lg-6">
                                <div class="channel-box">
                                    <div class="channel-icon-header">
                                        <div class="channel-icon-badge facebook">
                                            <i class="feather-facebook"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="fw-bold mb-0">{{ __('social_auto_poster::messages.facebook_title') }}</h5>
                                            <p class="text-muted small mb-0">{{ __('social_auto_poster::messages.facebook_desc') }}</p>
                                        </div>
                                        <div class="form-check form-switch fs-5">
                                            <input class="form-check-input" type="checkbox" name="facebook_enabled" value="1" id="fb_enable" {{ !empty($config['facebook_enabled']) ? 'checked' : '' }}>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="fb_page_id">{{ __('social_auto_poster::messages.facebook_page_id') }}</label>
                                        <input type="text" class="form-control" id="fb_page_id" name="facebook_page_id" value="{{ $config['facebook_page_id'] ?? '' }}" placeholder="102938475610293">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="fb_token">{{ __('social_auto_poster::messages.facebook_access_token') }}</label>
                                        <input type="password" class="form-control font-monospace" id="fb_token" name="facebook_access_token" value="{{ $config['facebook_access_token'] ?? '' }}" placeholder="EAAxxxxxx...">
                                        <div class="form-text small">{{ __('social_auto_poster::messages.facebook_access_token_help') }}</div>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <button type="button" class="btn btn-outline-primary btn-sm btn-test-channel" data-channel="facebook">
                                            <i class="feather-check-circle me-1"></i> {{ __('social_auto_poster::messages.test_connection') }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- 3. X (TWITTER) --}}
                            <div class="col-lg-6">
                                <div class="channel-box">
                                    <div class="channel-icon-header">
                                        <div class="channel-icon-badge twitter">
                                            <i class="feather-twitter"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="fw-bold mb-0">{{ __('social_auto_poster::messages.twitter_title') }}</h5>
                                            <p class="text-muted small mb-0">{{ __('social_auto_poster::messages.twitter_desc') }}</p>
                                        </div>
                                        <div class="form-check form-switch fs-5">
                                            <input class="form-check-input" type="checkbox" name="twitter_enabled" value="1" id="tw_enable" {{ !empty($config['twitter_enabled']) ? 'checked' : '' }}>
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label fw-semibold" for="tw_api_key">{{ __('social_auto_poster::messages.twitter_api_key') }}</label>
                                            <input type="text" class="form-control form-control-sm font-monospace" id="tw_api_key" name="twitter_api_key" value="{{ $config['twitter_api_key'] ?? '' }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-semibold" for="tw_api_secret">{{ __('social_auto_poster::messages.twitter_api_secret') }}</label>
                                            <input type="password" class="form-control form-control-sm font-monospace" id="tw_api_secret" name="twitter_api_secret" value="{{ $config['twitter_api_secret'] ?? '' }}">
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label fw-semibold" for="tw_acc_token">{{ __('social_auto_poster::messages.twitter_access_token') }}</label>
                                            <input type="text" class="form-control form-control-sm font-monospace" id="tw_acc_token" name="twitter_access_token" value="{{ $config['twitter_access_token'] ?? '' }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-semibold" for="tw_acc_secret">{{ __('social_auto_poster::messages.twitter_access_token_secret') }}</label>
                                            <input type="password" class="form-control form-control-sm font-monospace" id="tw_acc_secret" name="twitter_access_token_secret" value="{{ $config['twitter_access_token_secret'] ?? '' }}">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="tw_bearer">{{ __('social_auto_poster::messages.twitter_bearer_token') }}</label>
                                        <input type="password" class="form-control form-control-sm font-monospace" id="tw_bearer" name="twitter_bearer_token" value="{{ $config['twitter_bearer_token'] ?? '' }}" placeholder="Optional Bearer Token">
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <button type="button" class="btn btn-outline-dark btn-sm btn-test-channel" data-channel="twitter">
                                            <i class="feather-check-circle me-1"></i> {{ __('social_auto_poster::messages.test_connection') }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- 4. DISCORD --}}
                            <div class="col-lg-6">
                                <div class="channel-box">
                                    <div class="channel-icon-header">
                                        <div class="channel-icon-badge discord">
                                            <i class="feather-message-square"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="fw-bold mb-0">{{ __('social_auto_poster::messages.discord_title') }}</h5>
                                            <p class="text-muted small mb-0">{{ __('social_auto_poster::messages.discord_desc') }}</p>
                                        </div>
                                        <div class="form-check form-switch fs-5">
                                            <input class="form-check-input" type="checkbox" name="discord_enabled" value="1" id="dc_enable" {{ !empty($config['discord_enabled']) ? 'checked' : '' }}>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="dc_webhook">{{ __('social_auto_poster::messages.discord_webhook_url') }}</label>
                                        <input type="url" class="form-control font-monospace" id="dc_webhook" name="discord_webhook_url" value="{{ $config['discord_webhook_url'] ?? '' }}" placeholder="https://discord.com/api/webhooks/1234567890/abcdefghij...">
                                    </div>

                                    <div class="d-flex justify-content-end mt-4 pt-3">
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn-test-channel" data-channel="discord">
                                            <i class="feather-check-circle me-1"></i> {{ __('social_auto_poster::messages.test_connection') }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- 5. ADSTN NETWORK --}}
                            <div class="col-lg-12">
                                <div class="channel-box" style="border-left: 4px solid rgb(177, 151, 252);">
                                    <div class="channel-icon-header">
                                        <div class="channel-icon-badge adstn">
                                            <i class="fa-brands fa-buysellads"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="fw-bold mb-0">{{ __('social_auto_poster::messages.adstn_title') }}</h5>
                                            <p class="text-muted small mb-0">{{ __('social_auto_poster::messages.adstn_desc') }}</p>
                                        </div>
                                        <div class="form-check form-switch fs-5">
                                            <input class="form-check-input" type="checkbox" name="adstn_enabled" value="1" id="adstn_enable" {{ !empty($config['adstn_enabled']) ? 'checked' : '' }}>
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-5">
                                            <label class="form-label fw-semibold" for="adstn_url">{{ __('social_auto_poster::messages.adstn_api_url') }}</label>
                                            <input type="url" class="form-control font-monospace" id="adstn_url" name="adstn_api_url" value="{{ $config['adstn_api_url'] ?? 'https://www.adstn.ovh' }}" placeholder="https://www.adstn.ovh">
                                        </div>
                                        <div class="col-md-7">
                                            <label class="form-label fw-semibold" for="adstn_token">{{ __('social_auto_poster::messages.adstn_access_token') }}</label>
                                            <input type="password" class="form-control font-monospace" id="adstn_token" name="adstn_access_token" value="{{ $config['adstn_access_token'] ?? '' }}" placeholder="Developer OAuth Access Token (user.content.write)">
                                            <div class="form-text small">{{ __('social_auto_poster::messages.adstn_access_token_help') }}</div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end mt-3">
                                        <button type="button" class="btn btn-sm fw-bold btn-test-channel" style="background: rgb(177, 151, 252); color: #1e1b4b; border-color: rgb(177, 151, 252);" data-channel="adstn">
                                            <i class="feather-check-circle me-1"></i> {{ __('social_auto_poster::messages.test_connection') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-primary px-4 py-2 fw-bold">
                                <i class="feather-save me-1"></i> {{ __('social_auto_poster::messages.save_settings') }}
                            </button>
                        </div>
                    </form>
                </div>

                {{-- TAB 2: TRIGGERS & TEMPLATES --}}
                <div class="tab-pane fade" id="tab-triggers" role="tabpanel">
                    <form action="{{ route('admin.social_auto_poster.settings') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="channel-box">
                                    <h5 class="fw-bold mb-3"><i class="feather-zap text-warning me-2"></i>{{ __('social_auto_poster::messages.triggers_title') }}</h5>
                                    <p class="text-muted small mb-4">{{ __('social_auto_poster::messages.triggers_desc') }}</p>

                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="auto_topic" value="1" id="tr_topic" {{ !empty($config['auto_topic']) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="tr_topic">{{ __('social_auto_poster::messages.auto_topic') }}</label>
                                    </div>

                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="auto_video" value="1" id="tr_video" {{ !empty($config['auto_video']) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="tr_video">{{ __('social_auto_poster::messages.auto_video') }}</label>
                                    </div>

                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="auto_product" value="1" id="tr_prod" {{ !empty($config['auto_product']) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="tr_prod">{{ __('social_auto_poster::messages.auto_product') }}</label>
                                    </div>

                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="auto_news" value="1" id="tr_news" {{ !empty($config['auto_news']) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="tr_news">{{ __('social_auto_poster::messages.auto_news') }}</label>
                                    </div>

                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="auto_directory" value="1" id="tr_dir" {{ !empty($config['auto_directory']) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="tr_dir">{{ __('social_auto_poster::messages.auto_directory') }}</label>
                                    </div>

                                    <hr class="my-4">

                                    <h5 class="fw-bold mb-3"><i class="feather-sliders text-info me-2"></i>{{ __('social_auto_poster::messages.formatting_options') }}</h5>

                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="utm_source_enabled" value="1" id="tr_utm" {{ !empty($config['utm_source_enabled']) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="tr_utm">{{ __('social_auto_poster::messages.utm_tracking') }}</label>
                                        <div class="form-text small">{{ __('social_auto_poster::messages.utm_tracking_help') }}</div>
                                    </div>

                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="include_button" value="1" id="tr_btn" {{ !empty($config['include_button']) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="tr_btn">{{ __('social_auto_poster::messages.include_button') }}</label>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="btn_text">{{ __('social_auto_poster::messages.button_text') }}</label>
                                        <input type="text" class="form-control" id="btn_text" name="button_text" value="{{ $config['button_text'] ?? 'اقرأ المزيد ↗' }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="def_hashtags">{{ __('social_auto_poster::messages.default_hashtags') }}</label>
                                        <input type="text" class="form-control font-monospace" id="def_hashtags" name="default_hashtags" value="{{ $config['default_hashtags'] ?? '#MYADS #Community' }}">
                                        <div class="form-text small">{{ __('social_auto_poster::messages.default_hashtags_help') }}</div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="max_summary">{{ __('social_auto_poster::messages.max_summary_length') }}</label>
                                        <input type="number" class="form-control" id="max_summary" name="max_summary_length" value="{{ $config['max_summary_length'] ?? 200 }}" min="50" max="1000">
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="channel-box">
                                    <h5 class="fw-bold mb-2"><i class="feather-file-text text-primary me-2"></i>قوالب الرسائل المخصصة</h5>
                                    <div class="alert alert-info py-2 px-3 small mb-3">
                                        <i class="feather-info me-1"></i> {{ __('social_auto_poster::messages.template_tags_hint') }}
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="tmpl_tg">{{ __('social_auto_poster::messages.telegram_template') }}</label>
                                        <textarea class="form-control font-monospace" id="tmpl_tg" name="telegram_template" rows="2">{{ $config['telegram_template'] ?? "<b>{title}</b>\n\n{summary}\n\n{hashtags}" }}</textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="tmpl_fb">{{ __('social_auto_poster::messages.facebook_template') }}</label>
                                        <textarea class="form-control font-monospace" id="tmpl_fb" name="facebook_template" rows="2">{{ $config['facebook_template'] ?? "{title}\n\n{summary}\n\n{hashtags}" }}</textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="tmpl_tw">{{ __('social_auto_poster::messages.twitter_template') }}</label>
                                        <textarea class="form-control font-monospace" id="tmpl_tw" name="twitter_template" rows="2">{{ $config['twitter_template'] ?? "{title}\n\n{summary}\n\n{url} {hashtags}" }}</textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="tmpl_dc">{{ __('social_auto_poster::messages.discord_template') }}</label>
                                        <textarea class="form-control font-monospace" id="tmpl_dc" name="discord_template" rows="2">{{ $config['discord_template'] ?? "{summary}" }}</textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="tmpl_adstn">{{ __('social_auto_poster::messages.adstn_template') }}</label>
                                        <textarea class="form-control font-monospace" id="tmpl_adstn" name="adstn_template" rows="2">{{ $config['adstn_template'] ?? "{title}\n\n{summary}\n\n{url} {hashtags}" }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-primary px-4 py-2 fw-bold">
                                <i class="feather-save me-1"></i> {{ __('social_auto_poster::messages.save_settings') }}
                            </button>
                        </div>
                    </form>
                </div>

                {{-- TAB 3: MANUAL BROADCAST --}}
                <div class="tab-pane fade" id="tab-manual" role="tabpanel">
                    <form action="{{ route('admin.social_auto_poster.manual') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-lg-7">
                                <div class="channel-box">
                                    <h5 class="fw-bold mb-3"><i class="feather-send text-primary me-2"></i>{{ __('social_auto_poster::messages.manual_broadcast_title') }}</h5>
                                    <p class="text-muted small mb-4">{{ __('social_auto_poster::messages.manual_broadcast_desc') }}</p>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="m_title">{{ __('social_auto_poster::messages.content_title') }} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="m_title" name="title" required placeholder="عنوان الموضوع أو المنشور..." value="شاهد أحدث التحديثات والمواضيع المميزة في منصة MYADS!">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="m_url">{{ __('social_auto_poster::messages.content_url') }} <span class="text-danger">*</span></label>
                                        <input type="url" class="form-control" id="m_url" name="url" required placeholder="https://example.com/topic/123" value="{{ url('/') }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="m_summary">{{ __('social_auto_poster::messages.content_summary') }}</label>
                                        <textarea class="form-control" id="m_summary" name="summary" rows="3" placeholder="وصف موجز للمحتوى لجذب التفاعل...">انضم الآن وشارك في النقاشات الحصرية، واربح نقاط المكافآت مع مجتمع MYADS المتكامل.</textarea>
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-7">
                                            <label class="form-label fw-semibold" for="m_image">{{ __('social_auto_poster::messages.content_image_url') }}</label>
                                            <input type="url" class="form-control" id="m_image" name="image_url" placeholder="https://example.com/cover.jpg">
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label fw-semibold" for="m_tags">{{ __('social_auto_poster::messages.content_tags') }}</label>
                                            <input type="text" class="form-control" id="m_tags" name="tags" placeholder="#MYADS #أخبار">
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">{{ __('social_auto_poster::messages.select_channels') }} <span class="text-danger">*</span></label>
                                        <div class="d-flex flex-wrap gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="channels[]" value="telegram" id="ch_tg" {{ !empty($config['telegram_enabled']) ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="ch_tg"><i class="feather-send text-info me-1"></i> Telegram</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="channels[]" value="facebook" id="ch_fb" {{ !empty($config['facebook_enabled']) ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="ch_fb"><i class="feather-facebook text-primary me-1"></i> Facebook</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="channels[]" value="twitter" id="ch_tw" {{ !empty($config['twitter_enabled']) ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="ch_tw"><i class="feather-twitter text-dark me-1"></i> X (Twitter)</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="channels[]" value="discord" id="ch_dc" {{ !empty($config['discord_enabled']) ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="ch_dc"><i class="feather-message-square text-secondary me-1"></i> Discord</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="channels[]" value="adstn" id="ch_adstn" {{ !empty($config['adstn_enabled']) ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="ch_adstn" style="color: rgb(120, 85, 230);"><i class="fa-brands fa-buysellads me-1"></i> ADStn Network</label>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-success px-4 py-2 fw-bold">
                                        {{ __('social_auto_poster::messages.broadcast_now') }}
                                    </button>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <div class="channel-box">
                                    <h5 class="fw-bold mb-3"><i class="feather-eye text-info me-2"></i>{{ __('social_auto_poster::messages.live_preview') }}</h5>

                                    <div class="preview-card-social" id="livePreviewCard">
                                        <img src="{{ asset('assets/images/default-cover.jpg') }}" alt="Preview" class="preview-img" id="previewImgEl" onerror="this.style.display='none'">
                                        <div class="preview-body">
                                            <div class="preview-domain" id="previewDomainEl">{{ parse_url(url('/'), PHP_URL_HOST) }}</div>
                                            <div class="preview-title" id="previewTitleEl">شاهد أحدث التحديثات والمواضيع المميزة في منصة MYADS!</div>
                                            <div class="preview-desc" id="previewDescEl">انضم الآن وشارك في النقاشات الحصرية، واربح نقاط المكافآت مع مجتمع MYADS المتكامل.</div>
                                            <div class="mt-3">
                                                <span class="badge bg-primary px-3 py-2 text-white">اقرأ المزيد ↗</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- TAB 4: LOGS & HISTORY --}}
                <div class="tab-pane fade" id="tab-logs" role="tabpanel">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                        <div>
                            <h5 class="fw-bold mb-1">{{ __('social_auto_poster::messages.logs_title') }}</h5>
                            <p class="text-muted small mb-0">سجل لحظي مفصل لكل إرسال على الشبكات الاجتماعية وحالة الاستجابة.</p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('admin.social_auto_poster.export') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="feather-download me-1"></i> {{ __('social_auto_poster::messages.export_csv') }}
                            </a>
                            <form action="{{ route('admin.social_auto_poster.clear_logs') }}" method="POST" onsubmit="return confirm('{{ __('social_auto_poster::messages.confirm_clear_logs') }}')">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="feather-trash-2 me-1"></i> {{ __('social_auto_poster::messages.clear_logs') }}
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- FILTERS --}}
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <a href="{{ route('admin.social_auto_poster.index', ['channel' => '']) }}" class="badge {{ !request('channel') ? 'bg-primary text-white' : 'bg-light text-dark' }} text-decoration-none py-2 px-3">الكل</a>
                        <a href="{{ route('admin.social_auto_poster.index', ['channel' => 'telegram']) }}" class="badge {{ request('channel') === 'telegram' ? 'bg-info text-white' : 'bg-light text-dark' }} text-decoration-none py-2 px-3"><i class="feather-send me-1"></i> Telegram</a>
                        <a href="{{ route('admin.social_auto_poster.index', ['channel' => 'facebook']) }}" class="badge {{ request('channel') === 'facebook' ? 'bg-primary text-white' : 'bg-light text-dark' }} text-decoration-none py-2 px-3"><i class="feather-facebook me-1"></i> Facebook</a>
                        <a href="{{ route('admin.social_auto_poster.index', ['channel' => 'twitter']) }}" class="badge {{ request('channel') === 'twitter' ? 'bg-dark text-white' : 'bg-light text-dark' }} text-decoration-none py-2 px-3"><i class="feather-twitter me-1"></i> X (Twitter)</a>
                        <a href="{{ route('admin.social_auto_poster.index', ['channel' => 'discord']) }}" class="badge {{ request('channel') === 'discord' ? 'bg-secondary text-white' : 'bg-light text-dark' }} text-decoration-none py-2 px-3"><i class="feather-message-square me-1"></i> Discord</a>
                        <a href="{{ route('admin.social_auto_poster.index', ['channel' => 'adstn']) }}" class="badge text-decoration-none py-2 px-3" style="{{ request('channel') === 'adstn' ? 'background: rgb(177, 151, 252); color: #1e1b4b;' : 'background: #f1f5f9; color: #334155;' }}"><i class="fa-brands fa-buysellads me-1"></i> ADStn Network</a>
                    </div>

                    {{-- TABLE --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#ID</th>
                                    <th>{{ __('social_auto_poster::messages.channel') }}</th>
                                    <th>{{ __('social_auto_poster::messages.content_type') }}</th>
                                    <th>{{ __('social_auto_poster::messages.title') }}</th>
                                    <th>{{ __('social_auto_poster::messages.status') }}</th>
                                    <th>{{ __('social_auto_poster::messages.published_at') }}</th>
                                    <th class="text-end">{{ __('social_auto_poster::messages.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    <tr>
                                        <td><span class="badge bg-light text-muted">#{{ $log->id }}</span></td>
                                        <td>
                                            @if($log->channel === 'telegram')
                                                <span class="badge bg-soft-info text-info"><i class="feather-send me-1"></i> Telegram</span>
                                            @elseif($log->channel === 'facebook')
                                                <span class="badge bg-soft-primary text-primary"><i class="feather-facebook me-1"></i> Facebook</span>
                                            @elseif($log->channel === 'twitter')
                                                <span class="badge bg-soft-dark text-dark"><i class="feather-twitter me-1"></i> X (Twitter)</span>
                                            @elseif($log->channel === 'discord')
                                                <span class="badge bg-soft-secondary text-secondary"><i class="feather-message-square me-1"></i> Discord</span>
                                            @elseif($log->channel === 'adstn')
                                                <span class="badge" style="background: rgb(177, 151, 252); color: #1e1b4b;"><i class="fa-brands fa-buysellads me-1"></i> ADStn</span>
                                            @else
                                                <span class="badge bg-light text-dark">{{ ucfirst($log->channel) }}</span>
                                            @endif
                                        </td>
                                        <td><span class="badge bg-light text-dark">{{ $log->content_type }}</span></td>
                                        <td>
                                            <div class="fw-semibold text-truncate" style="max-width: 320px;" title="{{ $log->title }}">{{ $log->title ?: 'No Title' }}</div>
                                            @if($log->url)
                                                <a href="{{ $log->url }}" target="_blank" class="small text-muted text-decoration-none text-truncate d-inline-block" style="max-width: 320px;">
                                                    <i class="feather-external-link me-1"></i> {{ $log->url }}
                                                </a>
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->status === 'success')
                                                <span class="badge bg-soft-success text-success"><i class="feather-check-circle me-1"></i> {{ __('social_auto_poster::messages.status_success') }} ({{ $log->status_code ?? 200 }})</span>
                                            @else
                                                <span class="badge bg-soft-danger text-danger" title="{{ $log->error_message }}"><i class="feather-x-circle me-1"></i> {{ __('social_auto_poster::messages.status_failed') }}</span>
                                                @if($log->error_message)
                                                    <div class="small text-danger text-truncate" style="max-width: 180px;">{{ $log->error_message }}</div>
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            <span class="small text-muted">{{ $log->published_at ? $log->published_at->diffForHumans() : ($log->created_at ? $log->created_at->diffForHumans() : '-') }}</span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                @if($log->status === 'failed')
                                                    <form action="{{ route('admin.social_auto_poster.retry', $log->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-warning btn-sm py-1 px-2" title="{{ __('social_auto_poster::messages.retry') }}">
                                                            <i class="feather-rotate-cw"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                @if($log->response_data)
                                                    <button type="button" class="btn btn-outline-info btn-sm py-1 px-2 btn-view-payload" data-payload="{{ json_encode($log->response_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}">
                                                        <i class="feather-code"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="feather-inbox fs-1 d-block mb-2 text-muted opacity-50"></i>
                                            {{ __('social_auto_poster::messages.no_logs_found') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(method_exists($logs, 'links'))
                        <div class="mt-4">
                            {{ $logs->links() }}
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>

</div>

{{-- MODAL FOR JSON PAYLOAD --}}
<div class="modal fade" id="payloadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="feather-code me-2"></i>استجابة الخادم (Response Payload)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <pre class="bg-dark text-info p-3 rounded mb-0" style="max-height: 400px; overflow-y: auto;" id="payloadModalContent"></pre>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Live Preview Syncer
    const mTitle = document.getElementById('m_title');
    const mUrl = document.getElementById('m_url');
    const mSummary = document.getElementById('m_summary');
    const mImage = document.getElementById('m_image');

    const previewTitle = document.getElementById('previewTitleEl');
    const previewDomain = document.getElementById('previewDomainEl');
    const previewDesc = document.getElementById('previewDescEl');
    const previewImg = document.getElementById('previewImgEl');

    function updatePreview() {
        if (previewTitle && mTitle) previewTitle.textContent = mTitle.value || 'عنوان المحتوى...';
        if (previewDesc && mSummary) previewDesc.textContent = mSummary.value || 'وصف المحتوى...';
        if (previewDomain && mUrl) {
            try {
                const parsed = new URL(mUrl.value);
                previewDomain.textContent = parsed.hostname;
            } catch (e) {
                previewDomain.textContent = 'example.com';
            }
        }
        if (previewImg && mImage) {
            if (mImage.value && mImage.value.startsWith('http')) {
                previewImg.src = mImage.value;
                previewImg.style.display = 'block';
            }
        }
    }

    if (mTitle) mTitle.addEventListener('input', updatePreview);
    if (mUrl) mUrl.addEventListener('input', updatePreview);
    if (mSummary) mSummary.addEventListener('input', updatePreview);
    if (mImage) mImage.addEventListener('input', updatePreview);

    // 2. Channel Test Connection AJAX
    document.querySelectorAll('.btn-test-channel').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const channel = this.getAttribute('data-channel');
            const originalHtml = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> جاري الفحص...';

            const payload = {
                channel: channel,
                _token: '{{ csrf_token() }}'
            };

            if (channel === 'telegram') {
                payload.telegram_bot_token = document.getElementById('tg_token').value;
                payload.telegram_chat_id = document.getElementById('tg_chat').value;
            } else if (channel === 'facebook') {
                payload.facebook_page_id = document.getElementById('fb_page_id').value;
                payload.facebook_access_token = document.getElementById('fb_token').value;
            } else if (channel === 'twitter') {
                payload.twitter_api_key = document.getElementById('tw_api_key').value;
                payload.twitter_api_secret = document.getElementById('tw_api_secret').value;
                payload.twitter_access_token = document.getElementById('tw_acc_token').value;
                payload.twitter_access_token_secret = document.getElementById('tw_acc_secret').value;
                payload.twitter_bearer_token = document.getElementById('tw_bearer').value;
            } else if (channel === 'discord') {
                payload.discord_webhook_url = document.getElementById('dc_webhook').value;
            } else if (channel === 'adstn') {
                payload.adstn_api_url = document.getElementById('adstn_url').value;
                payload.adstn_access_token = document.getElementById('adstn_token').value;
            }

            fetch('{{ route("admin.social_auto_poster.test_channel") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                if (data.success) {
                    alert('✅ ' + data.message);
                } else {
                    alert('❌ ' + (data.message || 'فشل الاتصال'));
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                alert('❌ خطأ في الاتصال بالخادم: ' + err.message);
            });
        });
    });

    // 3. Payload Viewer Modal
    const payloadModalEl = document.getElementById('payloadModal');
    if (payloadModalEl && typeof bootstrap !== 'undefined') {
        const payloadModal = new bootstrap.Modal(payloadModalEl);
        document.querySelectorAll('.btn-view-payload').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const raw = this.getAttribute('data-payload');
                document.getElementById('payloadModalContent').textContent = raw;
                payloadModal.show();
            });
        });
    }
});
</script>
@endpush
