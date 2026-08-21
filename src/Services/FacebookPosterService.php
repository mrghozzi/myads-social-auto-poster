<?php

declare(strict_types=1);

namespace MyAds\Plugins\SocialAutoPoster\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookPosterService
{
    protected const GRAPH_API_VERSION = 'v19.0';
    protected const GRAPH_BASE = 'https://graph.facebook.com/';

    /**
     * Send post to Facebook Page.
     */
    public function sendPost(
        string $pageId,
        string $pageAccessToken,
        string $message,
        ?string $link = null,
        ?string $imageUrl = null
    ): array {
        $pageId = trim($pageId);
        $pageAccessToken = trim($pageAccessToken);

        if (empty($pageId) || empty($pageAccessToken)) {
            return [
                'success' => false,
                'status_code' => 400,
                'error' => 'Facebook Page ID and Page Access Token are required.',
                'response' => null,
            ];
        }

        try {
            // If image URL is provided, try publishing to /{page_id}/photos
            if (!empty($imageUrl) && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $photoEndpoint = self::GRAPH_BASE . self::GRAPH_API_VERSION . '/' . $pageId . '/photos';
                $photoParams = [
                    'url' => $imageUrl,
                    'caption' => $message . ($link ? "\n\n" . $link : ''),
                    'access_token' => $pageAccessToken,
                ];

                $response = Http::timeout(12)->withoutVerifying()->post($photoEndpoint, $photoParams);

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'status_code' => $response->status(),
                        'error' => null,
                        'response' => $response->json(),
                    ];
                }

                Log::warning('Facebook photos post failed, falling back to feed: ' . $response->body());
            }

            // Publish to /{page_id}/feed
            $feedEndpoint = self::GRAPH_BASE . self::GRAPH_API_VERSION . '/' . $pageId . '/feed';
            $feedParams = [
                'message' => $message,
                'access_token' => $pageAccessToken,
            ];

            if (!empty($link)) {
                $feedParams['link'] = $link;
            }

            $response = Http::timeout(12)->withoutVerifying()->post($feedEndpoint, $feedParams);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status_code' => $response->status(),
                    'error' => null,
                    'response' => $response->json(),
                ];
            }

            $body = $response->json();
            $errorDesc = $body['error']['message'] ?? ('HTTP Error ' . $response->status());

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
     * Test connection to Facebook Graph API.
     */
    public function testConnection(string $pageId, string $pageAccessToken): array
    {
        $pageId = trim($pageId);
        $pageAccessToken = trim($pageAccessToken);

        if (empty($pageId) || empty($pageAccessToken)) {
            return [
                'success' => false,
                'status_code' => 400,
                'message' => 'Page ID or Access Token is missing.',
            ];
        }

        try {
            $endpoint = self::GRAPH_BASE . self::GRAPH_API_VERSION . '/' . $pageId;
            $response = Http::timeout(8)->withoutVerifying()->get($endpoint, [
                'fields' => 'id,name,link',
                'access_token' => $pageAccessToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $pageName = $data['name'] ?? ('ID ' . $pageId);
                return [
                    'success' => true,
                    'status_code' => 200,
                    'message' => 'Success! Verified Facebook Page: ' . $pageName,
                ];
            }

            $body = $response->json();
            $errorMsg = $body['error']['message'] ?? 'Authentication failed.';

            return [
                'success' => false,
                'status_code' => $response->status(),
                'message' => 'Facebook API Error: ' . $errorMsg,
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
