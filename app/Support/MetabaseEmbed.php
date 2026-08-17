<?php
namespace App\Support;

use Firebase\JWT\JWT;

class MetabaseEmbed
{
    public static function dashboard(
        int $dashboardId,
        array $params = [],
        string $siteUrl = null,
        string $secret = null,
        int $ttlMin = 10
    ): string {
        $siteUrl ??= rtrim(config('services.metabase.site_url') ?? '', '/');
        $secret ??= config('services.metabase.secret_key');

        if (empty($siteUrl) || empty($secret) || strlen((string) $secret) < 32) {
            \Log::warning('Metabase embedding secret key is not configured or too short (minimum 32 characters required for HS256). Please check METABASE_SECRET_KEY in .env.');
            return '';
        }

        $params = empty($params) ? (object) [] : $params;

        $payload = [
            'resource' => ['dashboard' => $dashboardId],
            'params' => $params,
            'exp' => now()->addMinutes($ttlMin)->timestamp,
        ];

        try {
            $token = JWT::encode($payload, $secret, 'HS256');
            return $siteUrl . '/embed/dashboard/' . $token . '#bordered=true&titled=true';
        } catch (\Throwable $e) {
            \Log::error('Failed to encode Metabase JWT: ' . $e->getMessage());
            return '';
        }
    }
}