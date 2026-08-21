<?php

declare(strict_types=1);

namespace MyAds\Plugins\SocialAutoPoster\Services;

use App\Models\Option;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use MyAds\Plugins\SocialAutoPoster\Models\SocialPosterLog;

class SocialAutoPosterService
{
    protected const OPTION_TYPE = 'social_auto_poster_settings';

    protected ?TelegramPosterService $telegramService = null;
    protected ?FacebookPosterService $facebookService = null;
    protected ?TwitterPosterService $twitterService = null;
    protected ?DiscordPosterService $discordService = null;
    protected ?AdstnPosterService $adstnService = null;

    public function telegram(): TelegramPosterService
    {
        if ($this->telegramService === null) {
            $this->telegramService = new TelegramPosterService();
        }
        return $this->telegramService;
    }

    public function facebook(): FacebookPosterService
    {
        if ($this->facebookService === null) {
            $this->facebookService = new FacebookPosterService();
        }
        return $this->facebookService;
    }

    public function twitter(): TwitterPosterService
    {
        if ($this->twitterService === null) {
            $this->twitterService = new TwitterPosterService();
        }
        return $this->twitterService;
    }

    public function discord(): DiscordPosterService
    {
        if ($this->discordService === null) {
            $this->discordService = new DiscordPosterService();
        }
        return $this->discordService;
    }

    public function adstn(): AdstnPosterService
    {
        if ($this->adstnService === null) {
            $this->adstnService = new AdstnPosterService();
        }
        return $this->adstnService;
    }

    /**
     * Get plugin configuration options.
     */
    public function getConfig(): array
    {
        $defaults = [
            // Channels
            'telegram_enabled' => false,
            'telegram_bot_token' => '',
            'telegram_chat_id' => '',
            'telegram_template' => "<b>{title}</b>\n\n{summary}\n\n{hashtags}",

            'facebook_enabled' => false,
            'facebook_page_id' => '',
            'facebook_access_token' => '',
            'facebook_template' => "{title}\n\n{summary}\n\n{hashtags}",

            'twitter_enabled' => false,
            'twitter_api_key' => '',
            'twitter_api_secret' => '',
            'twitter_access_token' => '',
            'twitter_access_token_secret' => '',
            'twitter_bearer_token' => '',
            'twitter_template' => "{title}\n\n{summary}\n\n{url} {hashtags}",

            'discord_enabled' => false,
            'discord_webhook_url' => '',
            'discord_template' => "{summary}",

            'adstn_enabled' => false,
            'adstn_api_url' => AdstnPosterService::DEFAULT_BASE_URL,
            'adstn_access_token' => '',
            'adstn_template' => "{title}\n\n{summary}\n\n{url} {hashtags}",
            'adstn_privacy' => 'public',

            // Auto-triggers
            'auto_topic' => true,
            'auto_video' => true,
            'auto_product' => true,
            'auto_news' => true,
            'auto_directory' => false,

            // General & Formatting
            'utm_source_enabled' => true,
            'default_hashtags' => '#MYADS #Community',
            'button_text' => 'اقرأ المزيد ↗ / Read More',
            'include_button' => true,
            'max_summary_length' => 200,
        ];

        try {
            $options = Option::where('o_type', self::OPTION_TYPE)->get();
            $config = $defaults;

            foreach ($options as $option) {
                $val = $option->o_valuer;
                if (in_array($option->name, ['telegram_enabled', 'facebook_enabled', 'twitter_enabled', 'discord_enabled', 'adstn_enabled', 'auto_topic', 'auto_video', 'auto_product', 'auto_news', 'auto_directory', 'utm_source_enabled', 'include_button'], true)) {
                    $config[$option->name] = (bool) (int) $val;
                } elseif (in_array($option->name, ['max_summary_length'], true)) {
                    $config[$option->name] = (int) $val;
                } else {
                    $config[$option->name] = (string) $val;
                }
            }

            return $config;
        } catch (\Throwable $e) {
            return $defaults;
        }
    }

    /**
     * Save plugin configuration options.
     */
    public function saveConfig(array $settings): void
    {
        $booleanKeys = [
            'telegram_enabled',
            'facebook_enabled',
            'twitter_enabled',
            'discord_enabled',
            'adstn_enabled',
            'auto_topic',
            'auto_video',
            'auto_product',
            'auto_news',
            'auto_directory',
            'utm_source_enabled',
            'include_button',
        ];

        foreach ($settings as $key => $value) {
            if (in_array($key, $booleanKeys, true)) {
                $storedVal = !empty($value) ? '1' : '0';
            } else {
                $storedVal = is_array($value) ? json_encode($value) : (string) $value;
            }

            Option::updateOrCreate(
                ['name' => $key, 'o_type' => self::OPTION_TYPE],
                ['o_valuer' => $storedVal]
            );
        }
    }

    /**
     * Build message text by interpolating placeholders.
     */
    public function buildMessage(string $template, array $data): string
    {
        $placeholders = [
            '{title}' => $data['title'] ?? '',
            '{url}' => $data['url'] ?? '',
            '{summary}' => $data['summary'] ?? '',
            '{excerpt}' => $data['summary'] ?? '',
            '{sitename}' => $data['sitename'] ?? (config('app.name') ?: 'MYADS'),
            '{author}' => $data['author'] ?? '',
            '{hashtags}' => $data['hashtags'] ?? '',
        ];

        $message = str_replace(array_keys($placeholders), array_values($placeholders), $template);

        // Normalize excessive newlines
        return trim(preg_replace("/[\r\n]{3,}/", "\n\n", $message));
    }

    /**
     * Format tags string into proper social hashtags.
     */
    public function formatHashtags(?string $tags, ?string $fallbackTags = null): string
    {
        $combined = trim(($tags ?? '') . ' ' . ($fallbackTags ?? ''));
        if (empty($combined)) {
            return '';
        }

        $parts = preg_split('/[\s,#]+/', $combined, -1, PREG_SPLIT_NO_EMPTY);
        $hashtags = [];

        foreach ($parts as $part) {
            $cleaned = preg_replace('/[^\p{L}\p{N}_]/u', '', $part);
            if (!empty($cleaned)) {
                $hashtags[] = '#' . $cleaned;
            }
        }

        return implode(' ', array_unique(array_slice($hashtags, 0, 5)));
    }

    /**
     * Central dispatch method to publish content across enabled channels.
     */
    public function publish(
        string $contentType,
        ?int $contentId,
        string $title,
        string $url,
        ?string $imageUrl = null,
        ?string $summary = null,
        ?string $author = null,
        ?string $tags = null,
        ?array $targetChannels = null
    ): array {
        $config = $this->getConfig();
        $siteName = config('app.name') ?: 'MYADS';
        $summaryText = Str::limit(strip_tags($summary ?? ''), $config['max_summary_length'] ?? 200);
        $hashtags = $this->formatHashtags($tags, $config['default_hashtags'] ?? '#MYADS #Community');

        $baseData = [
            'title' => $title,
            'summary' => $summaryText,
            'author' => $author ?? $siteName,
            'sitename' => $siteName,
            'hashtags' => $hashtags,
        ];

        $channels = $targetChannels ?? ['telegram', 'facebook', 'twitter', 'discord', 'adstn'];
        $results = [];

        // 1. Telegram
        if (in_array('telegram', $channels, true) && (!empty($targetChannels) || $config['telegram_enabled'])) {
            $tUrl = $this->appendUtm($url, 'telegram', $config['utm_source_enabled']);
            $tData = array_merge($baseData, ['url' => $tUrl]);
            $tMessage = $this->buildMessage($config['telegram_template'], $tData);

            $buttonText = $config['include_button'] ? ($config['button_text'] ?: 'اقرأ المزيد ↗') : null;
            $buttonUrl = $config['include_button'] ? $tUrl : null;

            $res = $this->telegram()->sendPost(
                $config['telegram_bot_token'],
                $config['telegram_chat_id'],
                $tMessage,
                $imageUrl,
                $buttonText,
                $buttonUrl
            );

            $this->logTransmission('telegram', $contentType, $contentId, $title, $tUrl, $res);
            $results['telegram'] = $res;
        }

        // 2. Facebook
        if (in_array('facebook', $channels, true) && (!empty($targetChannels) || $config['facebook_enabled'])) {
            $fUrl = $this->appendUtm($url, 'facebook', $config['utm_source_enabled']);
            $fData = array_merge($baseData, ['url' => $fUrl]);
            $fMessage = $this->buildMessage($config['facebook_template'], $fData);

            $res = $this->facebook()->sendPost(
                $config['facebook_page_id'],
                $config['facebook_access_token'],
                $fMessage,
                $fUrl,
                $imageUrl
            );

            $this->logTransmission('facebook', $contentType, $contentId, $title, $fUrl, $res);
            $results['facebook'] = $res;
        }

        // 3. X (Twitter)
        if (in_array('twitter', $channels, true) && (!empty($targetChannels) || $config['twitter_enabled'])) {
            $xUrl = $this->appendUtm($url, 'twitter', $config['utm_source_enabled']);
            $xData = array_merge($baseData, ['url' => $xUrl]);
            $xMessage = $this->buildMessage($config['twitter_template'], $xData);

            $res = $this->twitter()->sendPost(
                $config['twitter_api_key'],
                $config['twitter_api_secret'],
                $config['twitter_access_token'],
                $config['twitter_access_token_secret'],
                $xMessage,
                $config['twitter_bearer_token']
            );

            $this->logTransmission('twitter', $contentType, $contentId, $title, $xUrl, $res);
            $results['twitter'] = $res;
        }

        // 4. Discord
        if (in_array('discord', $channels, true) && (!empty($targetChannels) || $config['discord_enabled'])) {
            $dUrl = $this->appendUtm($url, 'discord', $config['utm_source_enabled']);
            $dData = array_merge($baseData, ['url' => $dUrl]);
            $dDescription = $this->buildMessage($config['discord_template'], $dData);

            $res = $this->discord()->sendPost(
                $config['discord_webhook_url'],
                $title,
                $dDescription,
                $dUrl,
                $imageUrl,
                $author,
                $siteName
            );

            $this->logTransmission('discord', $contentType, $contentId, $title, $dUrl, $res);
            $results['discord'] = $res;
        }

        // 5. ADStn Network
        if (in_array('adstn', $channels, true) && (!empty($targetChannels) || $config['adstn_enabled'])) {
            $adstnUrl = $this->appendUtm($url, 'adstn', $config['utm_source_enabled']);
            $adstnData = array_merge($baseData, ['url' => $adstnUrl]);
            $adstnContent = $this->buildMessage($config['adstn_template'], $adstnData);

            $res = $this->adstn()->sendPost(
                $config['adstn_api_url'] ?? AdstnPosterService::DEFAULT_BASE_URL,
                $config['adstn_access_token'],
                $adstnContent,
                $config['adstn_privacy'] ?? 'public'
            );

            $this->logTransmission('adstn', $contentType, $contentId, $title, $adstnUrl, $res);
            $results['adstn'] = $res;
        }

        return $results;
    }

    /**
     * Append UTM tracking parameters to URL if enabled.
     */
    protected function appendUtm(string $url, string $channel, bool $enabled): string
    {
        if (!$enabled || empty($url)) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';
        return $url . $separator . 'utm_source=' . urlencode($channel) . '&utm_medium=social_autopost&utm_campaign=myads';
    }

    /**
     * Log transmission status to database.
     */
    protected function logTransmission(
        string $channel,
        string $contentType,
        ?int $contentId,
        string $title,
        string $url,
        array $result
    ): SocialPosterLog {
        return SocialPosterLog::create([
            'channel' => $channel,
            'content_type' => $contentType,
            'content_id' => $contentId,
            'title' => Str::limit($title, 450),
            'url' => Str::limit($url, 950),
            'status' => !empty($result['success']) ? 'success' : 'failed',
            'status_code' => $result['status_code'] ?? null,
            'response_data' => $result['response'] ?? null,
            'error_message' => $result['error'] ?? null,
            'published_at' => !empty($result['success']) ? now() : null,
        ]);
    }

    /**
     * Retry a previous log transmission.
     */
    public function retryLog(int $logId): array
    {
        $log = SocialPosterLog::find($logId);
        if (!$log) {
            return [
                'success' => false,
                'message' => 'Log record not found.',
            ];
        }

        $config = $this->getConfig();
        $res = ['success' => false, 'error' => 'Unknown channel'];

        if ($log->channel === 'telegram') {
            $res = $this->telegram()->sendPost(
                $config['telegram_bot_token'],
                $config['telegram_chat_id'],
                $log->title . "\n\n" . $log->url,
                null,
                'عرض المحتوى',
                $log->url
            );
        } elseif ($log->channel === 'facebook') {
            $res = $this->facebook()->sendPost(
                $config['facebook_page_id'],
                $config['facebook_access_token'],
                $log->title,
                $log->url
            );
        } elseif ($log->channel === 'twitter') {
            $res = $this->twitter()->sendPost(
                $config['twitter_api_key'],
                $config['twitter_api_secret'],
                $config['twitter_access_token'],
                $config['twitter_access_token_secret'],
                $log->title . "\n" . $log->url,
                $config['twitter_bearer_token']
            );
        } elseif ($log->channel === 'discord') {
            $res = $this->discord()->sendPost(
                $config['discord_webhook_url'],
                $log->title ?? 'Post',
                $log->url ?? '',
                $log->url ?? '',
                null,
                'MYADS'
            );
        } elseif ($log->channel === 'adstn') {
            $res = $this->adstn()->sendPost(
                $config['adstn_api_url'] ?? AdstnPosterService::DEFAULT_BASE_URL,
                $config['adstn_access_token'],
                $log->title . "\n\n" . $log->url,
                $config['adstn_privacy'] ?? 'public'
            );
        }

        $log->update([
            'status' => !empty($res['success']) ? 'success' : 'failed',
            'status_code' => $res['status_code'] ?? null,
            'response_data' => $res['response'] ?? null,
            'error_message' => $res['error'] ?? null,
            'published_at' => !empty($res['success']) ? now() : $log->published_at,
        ]);

        return $res;
    }

    /**
     * Get aggregate statistics for dashboard.
     */
    public function getStats(): array
    {
        try {
            $total = SocialPosterLog::count();
            $success = SocialPosterLog::where('status', 'success')->count();
            $failed = SocialPosterLog::where('status', 'failed')->count();
            $today = SocialPosterLog::whereDate('created_at', today())->count();

            $rate = $total > 0 ? round(($success / $total) * 100, 1) : 100.0;

            $byChannel = [
                'telegram' => SocialPosterLog::where('channel', 'telegram')->count(),
                'facebook' => SocialPosterLog::where('channel', 'facebook')->count(),
                'twitter' => SocialPosterLog::where('channel', 'twitter')->count(),
                'discord' => SocialPosterLog::where('channel', 'discord')->count(),
                'adstn' => SocialPosterLog::where('channel', 'adstn')->count(),
            ];

            return [
                'total_broadcasts' => $total,
                'total_success' => $success,
                'total_failed' => $failed,
                'success_rate' => $rate,
                'today_count' => $today,
                'by_channel' => $byChannel,
            ];
        } catch (\Throwable $e) {
            return [
                'total_broadcasts' => 0,
                'total_success' => 0,
                'total_failed' => 0,
                'success_rate' => 100.0,
                'today_count' => 0,
                'by_channel' => ['telegram' => 0, 'facebook' => 0, 'twitter' => 0, 'discord' => 0, 'adstn' => 0],
            ];
        }
    }
}
