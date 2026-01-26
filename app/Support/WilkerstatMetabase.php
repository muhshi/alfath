<?php

namespace App\Support;

use Firebase\JWT\JWT;

class WilkerstatMetabase
{
    public static function dashboard(int $id, array $params = [], int $ttlMin = 10): string
    {
        $siteUrl = rtrim(config('services.metabase.site_url'), '/');
        $secret = config('services.metabase.secret_key');

        // jika tidak ada parameter, tetap kirim object kosong, bukan array numerik
        $params = empty($params) ? (object) [] : $params;

        $payload = [
            'resource' => ['dashboard' => $id],
            'params' => $params,
            'exp' => now()->addMinutes($ttlMin)->timestamp,
        ];

        $token = JWT::encode($payload, $secret, 'HS256');

        return $siteUrl . '/embed/dashboard/' . $token . '#bordered=true&titled=true';
    }
}