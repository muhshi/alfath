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
        $siteUrl ??= rtrim(config('services.metabase.site_url'), '/');
        $secret ??= config('services.metabase.secret_key');

        $params = empty($params) ? (object) [] : $params;

        $payload = [
            'resource' => ['dashboard' => $dashboardId],
            'params' => $params,
            'exp' => now()->addMinutes($ttlMin)->timestamp,
        ];

        $token = JWT::encode($payload, $secret, 'HS256');

        return $siteUrl . '/embed/dashboard/' . $token . '#bordered=true&titled=true';
    }
}