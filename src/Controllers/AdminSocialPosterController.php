<?php

declare(strict_types=1);

namespace MyAds\Plugins\SocialAutoPoster\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use MyAds\Plugins\SocialAutoPoster\Models\SocialPosterLog;

class AdminSocialPosterController extends Controller
{
    /**
     * Display Social Auto Poster admin dashboard.
     */
    public function index(Request $request): View
    {
        $service = social_auto_poster_service();
        $stats = $service->getStats();
        $config = $service->getConfig();

        $query = SocialPosterLog::query()->latest();

        if ($request->filled('channel')) {
            $query->where('channel', $request->input('channel'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('content_type')) {
            $query->where('content_type', $request->input('content_type'));
        }

        $logs = $query->paginate(15)->withQueryString();

        return view('social_auto_poster::index', compact('stats', 'config', 'logs'));
    }

    /**
     * Save plugin configuration.
     */
    public function saveSettings(Request $request): RedirectResponse
    {
        $data = $request->except(['_token']);

        social_auto_poster_service()->saveConfig($data);

        return redirect()->route('admin.social_auto_poster.index')
            ->with('success', __('social_auto_poster::messages.settings_saved_success'));
    }

    /**
     * Test connection to a social channel.
     */
    public function testChannel(Request $request): JsonResponse
    {
        $channel = $request->input('channel');
        $service = social_auto_poster_service();
        $config = $service->getConfig();

        $result = ['success' => false, 'message' => 'Invalid channel specified.'];

        switch ($channel) {
            case 'telegram':
                $token = $request->input('telegram_bot_token', $config['telegram_bot_token']);
                $chatId = $request->input('telegram_chat_id', $config['telegram_chat_id']);
                $result = $service->telegram()->testConnection($token, $chatId);
                break;

            case 'facebook':
                $pageId = $request->input('facebook_page_id', $config['facebook_page_id']);
                $token = $request->input('facebook_access_token', $config['facebook_access_token']);
                $result = $service->facebook()->testConnection($pageId, $token);
                break;

            case 'twitter':
                $apiKey = $request->input('twitter_api_key', $config['twitter_api_key']);
                $apiSecret = $request->input('twitter_api_secret', $config['twitter_api_secret']);
                $accessToken = $request->input('twitter_access_token', $config['twitter_access_token']);
                $tokenSecret = $request->input('twitter_access_token_secret', $config['twitter_access_token_secret']);
                $bearer = $request->input('twitter_bearer_token', $config['twitter_bearer_token']);
                $result = $service->twitter()->testConnection($apiKey, $apiSecret, $accessToken, $tokenSecret, $bearer);
                break;

            case 'discord':
                $webhook = $request->input('discord_webhook_url', $config['discord_webhook_url']);
                $result = $service->discord()->testConnection($webhook);
                break;

            case 'adstn':
                $apiUrl = $request->input('adstn_api_url', $config['adstn_api_url'] ?? \MyAds\Plugins\SocialAutoPoster\Services\AdstnPosterService::DEFAULT_BASE_URL);
                $token = $request->input('adstn_access_token', $config['adstn_access_token']);
                $result = $service->adstn()->testConnection($apiUrl, $token);
                break;
        }

        return response()->json($result);
    }

    /**
     * Manually broadcast a message to selected channels.
     */
    public function publishManual(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:300',
            'url' => 'required|url|max:1000',
            'summary' => 'nullable|string|max:1000',
            'image_url' => 'nullable|url|max:1000',
            'tags' => 'nullable|string|max:200',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:telegram,facebook,twitter,discord,adstn',
        ]);

        $service = social_auto_poster_service();

        $results = $service->publish(
            'manual',
            null,
            $validated['title'],
            $validated['url'],
            $validated['image_url'] ?? null,
            $validated['summary'] ?? null,
            auth()->user()->username ?? 'Admin',
            $validated['tags'] ?? null,
            $validated['channels']
        );

        $successCount = 0;
        foreach ($results as $res) {
            if (!empty($res['success'])) {
                $successCount++;
            }
        }

        if ($successCount > 0) {
            return redirect()->route('admin.social_auto_poster.index')
                ->with('success', __('social_auto_poster::messages.manual_publish_success', ['count' => $successCount]));
        }

        return redirect()->route('admin.social_auto_poster.index')
            ->with('error', __('social_auto_poster::messages.manual_publish_failed'));
    }

    /**
     * Retry a single log item.
     */
    public function retryLog(int $id): RedirectResponse
    {
        $result = social_auto_poster_service()->retryLog($id);

        if (!empty($result['success'])) {
            return redirect()->back()->with('success', __('social_auto_poster::messages.retry_success'));
        }

        return redirect()->back()->with('error', __('social_auto_poster::messages.retry_failed', ['error' => $result['error'] ?? 'Unknown error']));
    }

    /**
     * Clear transmission logs.
     */
    public function clearLogs(): RedirectResponse
    {
        SocialPosterLog::truncate();

        return redirect()->route('admin.social_auto_poster.index')
            ->with('success', __('social_auto_poster::messages.logs_cleared_success'));
    }

    /**
     * Export transmission logs as CSV.
     */
    public function exportLogs(): Response
    {
        $logs = SocialPosterLog::latest()->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename=social_auto_poster_logs_' . date('Y-m-d_His') . '.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($logs) {
            $handle = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel Arabic compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Channel',
                'Content Type',
                'Content ID',
                'Title',
                'URL',
                'Status',
                'Status Code',
                'Error Message',
                'Published At',
                'Created At',
            ]);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->channel,
                    $log->content_type,
                    $log->content_id,
                    $log->title,
                    $log->url,
                    $log->status,
                    $log->status_code,
                    $log->error_message,
                    $log->published_at ? $log->published_at->toDateTimeString() : '',
                    $log->created_at ? $log->created_at->toDateTimeString() : '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
