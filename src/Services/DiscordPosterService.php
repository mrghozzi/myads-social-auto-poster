<?php

declare(strict_types=1);

namespace MyAds\Plugins\SocialAutoPoster\Services;

use Illuminate\Support\Facades\Http;

class DiscordPosterService
{
    /**
     * Send post to Discord Channel via Webhook.
     */
    public function sendPost(
        string $webhookUrl,
        string $title,
        string $description,
        string $url,
        ?string $imageUrl = null,
        ?string $authorName = null,
        ?string $siteName = null
    ): array {
        $webhookUrl = trim($webhookUrl);

        if (empty($webhookUrl) || !filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'status_code' => 400,
                'error' => 'Valid Discord Webhook URL is required.',
                'response' => null,
            ];
        }

        try {
            $embed = [
                'title' => mb_substr($title, 0, 256),
                'description' => mb_substr($description, 0, 2048),
                'url' => $url,
                'color' => 0x5865F2, // Discord Blurple
                'footer' => [
                    'text' => $siteName ? ($siteName . ' • Auto Poster') : 'MYADS Auto Poster',
                ],
                'timestamp' => date('c'),
            ];

            if (!empty($authorName)) {
                $embed['author'] = [
                    'name' => mb_substr($authorName, 0, 256),
                ];
            }

            if (!empty($imageUrl) && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $embed['image'] = [
                    'url' => $imageUrl,
                ];
            }

            $payload = [
                'embeds' => [$embed],
            ];

            $response = Http::timeout(10)->withoutVerifying()->post($webhookUrl, $payload);

            if ($response->successful() || $response->status() === 204) {
                return [
                    'success' => true,
                    'status_code' => $response->status(),
                    'error' => null,
                    'response' => $response->json() ?? ['status' => 204, 'message' => 'No Content (Success)'],
                ];
            }

            $body = $response->json();
            $errorDesc = $body['message'] ?? ('HTTP Error ' . $response->status());

            return [
                'success' => false,
                'status_code' => $response->status(),
                'error' => $errorDesc,
                'response' => $body,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status_code' => 500,
                'error' => $e->getMessage(),
                'response' => null,
            ];
        }
    }

    /**
     * Test connection to Discord Webhook.
     */
    public function testConnection(string $webhookUrl): array
    {
        $webhookUrl = trim($webhookUrl);

        if (empty($webhookUrl) || !filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'status_code' => 400,
                'message' => 'Valid Discord Webhook URL is required.',
            ];
        }

        try {
            $testEmbed = [
                'title' => '⚡ [MYADS Auto Poster] Connection Test',
                'description' => 'Your Discord Webhook is configured properly and ready to broadcast real-time updates!',
                'url' => url('/'),
                'color' => 0x57F287, // Green
                'footer' => [
                    'text' => 'MYADS Social Auto Poster • ' . date('Y-m-d H:i:s'),
                ],
            ];

            $response = Http::timeout(8)->withoutVerifying()->post($webhookUrl, [
                'embeds' => [$testEmbed],
            ]);

            if ($response->successful() || $response->status() === 204) {
                return [
                    'success' => true,
                    'status_code' => 200,
                    'message' => 'Success! Test card sent to Discord successfully.',
                ];
            }

            $body = $response->json();
            $errorMsg = $body['message'] ?? ('HTTP ' . $response->status());

            return [
                'success' => false,
                'status_code' => $response->status(),
                'message' => 'Discord Webhook Error: ' . $errorMsg,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }
}
