<?php

declare(strict_types=1);

namespace MyAds\Plugins\SocialAutoPoster\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdstnPosterService
{
    public const DEFAULT_BASE_URL = 'https://www.adstn.ovh';
    protected const CONTENT_ENDPOINT = '/api/developer/v1/me/content';
    protected const PROFILE_ENDPOINT = '/api/developer/v1/me/profile';
    protected const ME_ENDPOINT = '/api/developer/v1/me';

    /**
     * Send post to ADStn Platform (https://www.adstn.ovh) via Developer API v1.
     */
    public function sendPost(
        string $apiBaseUrl,
        string $accessToken,
        string $content,
        string $privacy = 'public'
    ): array {
        $apiBaseUrl = rtrim(trim($apiBaseUrl ?: self::DEFAULT_BASE_URL), '/');
        $accessToken = trim($accessToken);
        $content = trim($content);

        if (empty($accessToken)) {
            return [
                'success' => false,
                'status_code' => 400,
                'error' => 'ADStn Developer Access Token is required.',
                'response' => null,
            ];
        }

        if (empty($content)) {
            return [
                'success' => false,
                'status_code' => 400,
                'error' => 'Post content is empty.',
                'response' => null,
            ];
        }

        try {
            $endpoint = $apiBaseUrl . self::CONTENT_ENDPOINT;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout(12)
            ->withoutVerifying()
            ->post($endpoint, [
                'content' => mb_substr($content, 0, 5000),
                'privacy' => in_array($privacy, ['public', 'followers', 'private'], true) ? $privacy : 'public',
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status_code' => $response->status(),
                    'error' => null,
                    'response' => $response->json(),
                ];
            }

            $body = $response->json();
            $errorDesc = $body['message'] ?? ($body['error'] ?? ('HTTP Error ' . $response->status()));

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
     * Test connection to ADStn Developer Platform.
     */
    public function testConnection(string $apiBaseUrl, string $accessToken): array
    {
        $apiBaseUrl = rtrim(trim($apiBaseUrl ?: self::DEFAULT_BASE_URL), '/');
        $accessToken = trim($accessToken);

        if (empty($accessToken)) {
            return [
                'success' => false,
                'status_code' => 400,
                'message' => 'ADStn Access Token is empty.',
            ];
        }

        try {
            $endpoint = $apiBaseUrl . self::PROFILE_ENDPOINT;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
            ])
            ->timeout(8)
            ->withoutVerifying()
            ->get($endpoint);

            if ($response->successful()) {
                $data = $response->json()['data'] ?? [];
                $username = $data['username'] ?? 'Developer';
                $pts = isset($data['points']) ? (' (' . $data['points'] . ' PTS)') : '';
                return [
                    'success' => true,
                    'status_code' => 200,
                    'message' => 'Success! Verified ADStn account: @' . $username . $pts,
                ];
            }

            // Fallback to /api/developer/v1/me if profile endpoint requires different scope
            $meResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
            ])
            ->timeout(8)
            ->withoutVerifying()
            ->get($apiBaseUrl . self::ME_ENDPOINT);

            if ($meResponse->successful()) {
                $data = $meResponse->json()['data'] ?? [];
                $username = $data['username'] ?? 'Developer';
                return [
                    'success' => true,
                    'status_code' => 200,
                    'message' => 'Success! Verified ADStn account: @' . $username,
                ];
            }

            $body = $response->json();
            $errorMsg = $body['message'] ?? ($body['error'] ?? 'Authentication failed.');

            return [
                'success' => false,
                'status_code' => $response->status(),
                'message' => 'ADStn API Error: ' . $errorMsg,
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
