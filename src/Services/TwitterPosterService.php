<?php

declare(strict_types=1);

namespace MyAds\Plugins\SocialAutoPoster\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwitterPosterService
{
    protected const API_ENDPOINT = 'https://api.twitter.com/2/tweets';
    protected const ME_ENDPOINT = 'https://api.twitter.com/2/users/me';

    /**
     * Post a tweet to X (Twitter) API v2.
     */
    public function sendPost(
        string $apiKey,
        string $apiSecret,
        string $accessToken,
        string $accessTokenSecret,
        string $text,
        ?string $bearerToken = null
    ): array {
        $apiKey = trim($apiKey);
        $apiSecret = trim($apiSecret);
        $accessToken = trim($accessToken);
        $accessTokenSecret = trim($accessTokenSecret);
        $bearerToken = trim($bearerToken ?? '');

        // Truncate text to 280 characters max
        $tweetText = mb_substr($text, 0, 280);

        if (empty($tweetText)) {
            return [
                'success' => false,
                'status_code' => 400,
                'error' => 'Tweet text is empty.',
                'response' => null,
            ];
        }

        try {
            $headers = ['Content-Type' => 'application/json'];

            if (!empty($apiKey) && !empty($apiSecret) && !empty($accessToken) && !empty($accessTokenSecret)) {
                $authHeader = $this->buildOAuth1Header(
                    'POST',
                    self::API_ENDPOINT,
                    $apiKey,
                    $apiSecret,
                    $accessToken,
                    $accessTokenSecret
                );
                $headers['Authorization'] = $authHeader;
            } elseif (!empty($bearerToken)) {
                $headers['Authorization'] = 'Bearer ' . $bearerToken;
            } else {
                return [
                    'success' => false,
                    'status_code' => 400,
                    'error' => 'X (Twitter) API credentials missing. Provide OAuth 1.0a keys or Bearer Token.',
                    'response' => null,
                ];
            }

            $response = Http::withHeaders($headers)
                ->timeout(12)
                ->withoutVerifying()
                ->post(self::API_ENDPOINT, [
                    'text' => $tweetText,
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
            $errorDesc = $body['detail'] ?? ($body['title'] ?? ('HTTP Error ' . $response->status()));

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
     * Test connection to X (Twitter) API.
     */
    public function testConnection(
        string $apiKey,
        string $apiSecret,
        string $accessToken,
        string $accessTokenSecret,
        ?string $bearerToken = null
    ): array {
        $apiKey = trim($apiKey);
        $apiSecret = trim($apiSecret);
        $accessToken = trim($accessToken);
        $accessTokenSecret = trim($accessTokenSecret);
        $bearerToken = trim($bearerToken ?? '');

        try {
            $headers = [];
            if (!empty($apiKey) && !empty($apiSecret) && !empty($accessToken) && !empty($accessTokenSecret)) {
                $headers['Authorization'] = $this->buildOAuth1Header(
                    'GET',
                    self::ME_ENDPOINT,
                    $apiKey,
                    $apiSecret,
                    $accessToken,
                    $accessTokenSecret
                );
            } elseif (!empty($bearerToken)) {
                $headers['Authorization'] = 'Bearer ' . $bearerToken;
            } else {
                return [
                    'success' => false,
                    'status_code' => 400,
                    'message' => 'Credentials missing. Provide OAuth 1.0a keys or Bearer Token.',
                ];
            }

            $response = Http::withHeaders($headers)
                ->timeout(8)
                ->withoutVerifying()
                ->get(self::ME_ENDPOINT);

            if ($response->successful()) {
                $data = $response->json()['data'] ?? [];
                $username = $data['username'] ?? ($data['name'] ?? 'Authorized User');
                return [
                    'success' => true,
                    'status_code' => 200,
                    'message' => 'Success! Verified X (Twitter) account: @' . $username,
                ];
            }

            $body = $response->json();
            $errorMsg = $body['detail'] ?? ($body['title'] ?? 'Authentication failed.');

            return [
                'success' => false,
                'status_code' => $response->status(),
                'message' => 'X API Error: ' . $errorMsg,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate standard OAuth 1.0a Authorization header.
     */
    protected function buildOAuth1Header(
        string $method,
        string $url,
        string $consumerKey,
        string $consumerSecret,
        string $token,
        string $tokenSecret
    ): string {
        $oauth = [
            'oauth_consumer_key' => $consumerKey,
            'oauth_nonce' => bin2hex(random_bytes(16)),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => (string) time(),
            'oauth_token' => $token,
            'oauth_version' => '1.0',
        ];

        $baseParams = $oauth;
        ksort($baseParams);

        $paramString = http_build_query($baseParams, '', '&', PHP_QUERY_RFC3986);
        $signatureBaseString = strtoupper($method) . '&' . rawurlencode($url) . '&' . rawurlencode($paramString);

        $signingKey = rawurlencode($consumerSecret) . '&' . rawurlencode($tokenSecret);
        $signature = base64_encode(hash_hmac('sha1', $signatureBaseString, $signingKey, true));

        $oauth['oauth_signature'] = $signature;
        ksort($oauth);

        $headerPairs = [];
        foreach ($oauth as $k => $v) {
            $headerPairs[] = rawurlencode($k) . '="' . rawurlencode((string) $v) . '"';
        }

        return 'OAuth ' . implode(', ', $headerPairs);
    }
}
