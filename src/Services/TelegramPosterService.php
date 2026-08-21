<?php

declare(strict_types=1);

namespace MyAds\Plugins\SocialAutoPoster\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramPosterService
{
    protected const API_BASE = 'https://api.telegram.org/bot';

    /**
     * Send post to Telegram Channel or Chat.
     */
    public function sendPost(
        string $botToken,
        string $chatId,
        string $text,
        ?string $imageUrl = null,
        ?string $buttonText = null,
        ?string $buttonUrl = null
    ): array {
        $botToken = trim($botToken);
        $chatId = trim($chatId);

        if (empty($botToken) || empty($chatId)) {
            return [
                'success' => false,
                'status_code' => 400,
                'error' => 'Telegram Bot Token and Chat ID are required.',
                'response' => null,
            ];
        }

        $replyMarkup = null;
        if (!empty($buttonText) && !empty($buttonUrl)) {
            $replyMarkup = json_encode([
                'inline_keyboard' => [
                    [
                        [
                            'text' => $buttonText,
                            'url' => $buttonUrl,
                        ]
                    ]
                ]
            ]);
        }

        try {
            // Attempt to send photo if image URL is available
            if (!empty($imageUrl) && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $photoEndpoint = self::API_BASE . $botToken . '/sendPhoto';
                $photoParams = [
                    'chat_id' => $chatId,
                    'photo' => $imageUrl,
                    'caption' => mb_substr($text, 0, 1024), // Telegram caption limit 1024 chars
                    'parse_mode' => 'HTML',
                ];

                if ($replyMarkup) {
                    $photoParams['reply_markup'] = $replyMarkup;
                }

                $response = Http::timeout(10)->withoutVerifying()->post($photoEndpoint, $photoParams);

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'status_code' => $response->status(),
                        'error' => null,
                        'response' => $response->json(),
                    ];
                }

                // If sending photo failed, fall back to text message
                Log::warning('Telegram sendPhoto failed, falling back to sendMessage: ' . $response->body());
            }

            // Send text message
            $messageEndpoint = self::API_BASE . $botToken . '/sendMessage';
            $messageParams = [
                'chat_id' => $chatId,
                'text' => mb_substr($text, 0, 4096), // Telegram message limit 4096 chars
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => false,
            ];

            if ($replyMarkup) {
                $messageParams['reply_markup'] = $replyMarkup;
            }

            $response = Http::timeout(10)->withoutVerifying()->post($messageEndpoint, $messageParams);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status_code' => $response->status(),
                    'error' => null,
                    'response' => $response->json(),
                ];
            }

            $body = $response->json();
            $errorDesc = $body['description'] ?? ('HTTP Error ' . $response->status());

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
     * Test connection to Telegram Bot API.
     */
    public function testConnection(string $botToken, ?string $chatId = null): array
    {
        $botToken = trim($botToken);
        if (empty($botToken)) {
            return [
                'success' => false,
                'status_code' => 400,
                'message' => 'Telegram Bot Token is empty.',
            ];
        }

        try {
            $meResponse = Http::timeout(6)->withoutVerifying()->get(self::API_BASE . $botToken . '/getMe');
            if (!$meResponse->successful()) {
                $err = $meResponse->json()['description'] ?? 'Invalid Bot Token';
                return [
                    'success' => false,
                    'status_code' => $meResponse->status(),
                    'message' => 'Telegram Bot verification failed: ' . $err,
                ];
            }

            $botData = $meResponse->json()['result'] ?? [];
            $botUsername = $botData['username'] ?? 'UnknownBot';

            if (!empty($chatId)) {
                $chatId = trim($chatId);
                $testText = "<b>[MYADS Auto Poster]</b>\nTest broadcast connection successful! \nBot: @" . $botUsername . "\nTime: " . date('Y-m-d H:i:s');
                $sendRes = $this->sendPost($botToken, $chatId, $testText, null, 'Test Link', url('/'));

                if (!$sendRes['success']) {
                    return [
                        'success' => false,
                        'status_code' => $sendRes['status_code'],
                        'message' => 'Bot verified (@' . $botUsername . '), but sending message to Chat ID ' . $chatId . ' failed: ' . ($sendRes['error'] ?? 'Unknown error'),
                    ];
                }

                return [
                    'success' => true,
                    'status_code' => 200,
                    'message' => 'Success! Bot verified (@' . $botUsername . ') and test message delivered to ' . $chatId,
                ];
            }

            return [
                'success' => true,
                'status_code' => 200,
                'message' => 'Success! Bot verified as @' . $botUsername,
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
