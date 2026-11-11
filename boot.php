<?php

declare(strict_types=1);

use App\Helpers\Hooks;
use App\Models\Directory;
use App\Models\ForumTopic;
use App\Models\News;
use App\Models\Product;
use App\Models\Status;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use MyAds\Plugins\SocialAutoPoster\Controllers\AdminSocialPosterController;
use MyAds\Plugins\SocialAutoPoster\Services\SocialAutoPosterService;

// ─── 1. Autoloader ─────────────────────────────────────────────────────────────
$socialPosterBasePath = __DIR__;
$socialPosterNamespace = 'MyAds\\Plugins\\SocialAutoPoster\\';

if (!function_exists('myads_social_auto_poster_autoload_registered')) {
    function myads_social_auto_poster_autoload_registered(string $namespace, string $basePath): bool
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

myads_social_auto_poster_autoload_registered($socialPosterNamespace, $socialPosterBasePath);

// ─── 2. Service Singleton Helper ───────────────────────────────────────────────
if (!function_exists('social_auto_poster_service')) {
    function social_auto_poster_service(): SocialAutoPosterService
    {
        static $instance = null;
        if (!$instance instanceof SocialAutoPosterService) {
            $instance = new SocialAutoPosterService();
        }
        return $instance;
    }
}

// ─── 3. Register Views & Translations ──────────────────────────────────────────
View::addNamespace('social_auto_poster', __DIR__ . '/views');
app('translator')->addNamespace('social_auto_poster', __DIR__ . '/lang');

// ─── 4. Admin Sidebar Hook ─────────────────────────────────────────────────────
Hooks::add_action('admin_sidebar_menu', function (): void {
    $url = route('admin.social_auto_poster.index');
    $isActive = request()->routeIs('admin.social_auto_poster.*');
    $linkClass = $isActive ? 'nxl-link active' : 'nxl-link';

    echo '<li class="nxl-item">'
        . '<a href="' . e($url) . '" class="' . e($linkClass) . '">'
        . '<span class="nxl-micon"><i class="feather-share-2"></i></span>'
        . '<span class="nxl-mtext">' . __('social_auto_poster::messages.dashboard_title') . '</span>'
        . '</a>'
        . '</li>';
});

// ─── 5. Automated Content Observers / Eloquent Event Listeners ─────────────────
if (!app()->runningInConsole() || app()->environment('testing')) {

    // A. Forum Topics
    ForumTopic::saved(function ($topic) {
        try {
            $config = social_auto_poster_service()->getConfig();
            if (empty($config['auto_topic'])) {
                return;
            }

            if ((int) ($topic->statu ?? 1) === 1 && empty($topic->group_id)) {
                $url = route('forum.topic', $topic->id);
                $imageUrl = !empty($topic->image_post) ? url($topic->image_post) : null;
                $summary = !empty($topic->content) ? $topic->content : ($topic->title ?? '');
                $author = $topic->user->username ?? (config('app.name') ?: 'MYADS');

                social_auto_poster_service()->publish(
                    'forum_topic',
                    (int) $topic->id,
                    (string) ($topic->title ?? 'New Topic'),
                    $url,
                    $imageUrl,
                    (string) $summary,
                    (string) $author,
                    (string) ($topic->tags ?? '')
                );
            }
        } catch (\Throwable $e) {
            // Suppress errors to prevent user flow disruption
        }
    });

    // B. Videos / Clips (Status model)
    Status::saved(function ($status) {
        try {
            $config = social_auto_poster_service()->getConfig();
            if (empty($config['auto_video'])) {
                return;
            }

            $videoTypes = [10, 14, 2, 4, 100];
            if (in_array((int) ($status->s_type ?? 0), $videoTypes, true) && (int) ($status->statu ?? 1) === 1 && empty($status->group_id)) {
                $url = route('forum.topic', $status->id);
                $imageUrl = !empty($status->image_post) ? url($status->image_post) : (!empty($status->video_thumbnail) ? url($status->video_thumbnail) : null);
                $title = !empty($status->video_title) ? $status->video_title : (!empty($status->title) ? $status->title : 'New Video');
                $summary = !empty($status->content) ? $status->content : $title;
                $author = $status->user->username ?? (config('app.name') ?: 'MYADS');

                social_auto_poster_service()->publish(
                    'status_video',
                    (int) $status->id,
                    (string) $title,
                    $url,
                    $imageUrl,
                    (string) $summary,
                    (string) $author
                );
            }
        } catch (\Throwable $e) {
            // Suppress
        }
    });

    // C. Store Products
    Product::saved(function ($product) {
        try {
            $config = social_auto_poster_service()->getConfig();
            if (empty($config['auto_product'])) {
                return;
            }

            if (($product->o_type ?? '') === 'store' && !empty($product->name)) {
                $url = route('store.show', $product->name);
                $imageUrl = !empty($product->img) ? url($product->img) : null;
                $title = !empty($product->title) ? $product->title : ($product->name ?? 'New Product');
                $summary = !empty($product->content) ? $product->content : $title;
                $author = $product->user->username ?? (config('app.name') ?: 'MYADS');

                social_auto_poster_service()->publish(
                    'product',
                    (int) $product->id,
                    (string) $title,
                    $url,
                    $imageUrl,
                    (string) $summary,
                    (string) $author,
                    (string) ($product->tags ?? '')
                );
            }
        } catch (\Throwable $e) {
            // Suppress
        }
    });

    // D. News
    News::saved(function ($news) {
        try {
            $config = social_auto_poster_service()->getConfig();
            if (empty($config['auto_news'])) {
                return;
            }

            if ((int) ($news->statu ?? 1) === 1) {
                $url = route('news.show', $news->id);
                $imageUrl = !empty($news->img) ? url($news->img) : null;
                $title = !empty($news->title) ? $news->title : 'Latest News';
                $summary = !empty($news->content) ? $news->content : $title;

                social_auto_poster_service()->publish(
                    'news',
                    (int) $news->id,
                    (string) $title,
                    $url,
                    $imageUrl,
                    (string) $summary
                );
            }
        } catch (\Throwable $e) {
            // Suppress
        }
    });

    // E. Directory Sites
    Directory::saved(function ($dir) {
        try {
            $config = social_auto_poster_service()->getConfig();
            if (empty($config['auto_directory'])) {
                return;
            }

            if ((int) ($dir->statu ?? 1) === 1) {
                $url = route('directory.show', $dir->id);
                $imageUrl = !empty($dir->img) ? url($dir->img) : null;
                $title = !empty($dir->title) ? $dir->title : 'New Directory Website';
                $summary = !empty($dir->content) ? $dir->content : $title;

                social_auto_poster_service()->publish(
                    'directory',
                    (int) $dir->id,
                    (string) $title,
                    $url,
                    $imageUrl,
                    (string) $summary
                );
            }
        } catch (\Throwable $e) {
            // Suppress
        }
    });

    // F. Community Groups (New Public Groups)
    if (class_exists(\App\Models\Group::class)) {
        \App\Models\Group::created(function ($group) {
            try {
                $config = social_auto_poster_service()->getConfig();
                if (empty($config['auto_group'])) {
                    return;
                }

                if ($group->status === \App\Models\Group::STATUS_ACTIVE && $group->privacy === \App\Models\Group::PRIVACY_PUBLIC && !empty($group->slug)) {
                    $url = route('groups.show', $group->slug);
                    $imageUrl = $group->avatarUrl();
                    $categoryLabel = \App\Models\Group::categoryLabel($group->category);
                    $title = "🚀 New Community Group: " . $group->name . " [" . $categoryLabel . "]";
                    $summary = !empty($group->short_description) ? $group->short_description : ($group->description ?? $title);
                    $author = $group->owner->username ?? (config('app.name') ?: 'MYADS');

                    social_auto_poster_service()->publish(
                        'group',
                        (int) $group->id,
                        (string) $title,
                        $url,
                        $imageUrl,
                        (string) $summary,
                        (string) $author,
                        (string) $group->category
                    );
                }
            } catch (\Throwable $e) {
                // Suppress
            }
        });
    }
}

// ─── 6. Admin Routes ───────────────────────────────────────────────────────────
Route::middleware(['web', 'auth', 'admin'])->group(function () {
    Route::get('/admin/social-auto-poster', [AdminSocialPosterController::class, 'index'])->name('admin.social_auto_poster.index');
    Route::post('/admin/social-auto-poster/settings', [AdminSocialPosterController::class, 'saveSettings'])->name('admin.social_auto_poster.settings');
    Route::post('/admin/social-auto-poster/test-channel', [AdminSocialPosterController::class, 'testChannel'])->name('admin.social_auto_poster.test_channel');
    Route::post('/admin/social-auto-poster/manual', [AdminSocialPosterController::class, 'publishManual'])->name('admin.social_auto_poster.manual');
    Route::post('/admin/social-auto-poster/retry/{id}', [AdminSocialPosterController::class, 'retryLog'])->name('admin.social_auto_poster.retry');
    Route::post('/admin/social-auto-poster/clear-logs', [AdminSocialPosterController::class, 'clearLogs'])->name('admin.social_auto_poster.clear_logs');
    Route::get('/admin/social-auto-poster/export', [AdminSocialPosterController::class, 'exportLogs'])->name('admin.social_auto_poster.export');
});
