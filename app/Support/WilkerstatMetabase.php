<?php

namespace App\Support;

use Firebase\JWT\JWT;

class WilkerstatMetabase
{
    private const SITE_URL = 'http://metabase.bpsdemak.com';
    private const SECRET = 'd5b19a0ae96796c9fd9f854b952ccd1dab4cff3d39fb68b9acb81c2ab0cff407';

    public static function dashboard(int $id, array $params = [], int $ttlMin = 10): string
    {
        // jika tidak ada parameter, tetap kirim object kosong, bukan array numerik
        $params = empty($params) ? (object) [] : $params;

        $payload = [
            'resource' => ['dashboard' => $id],
            'params' => $params,
            'exp' => now()->addMinutes($ttlMin)->timestamp,
        ];

        $token = JWT::encode($payload, self::SECRET, 'HS256');

        return self::SITE_URL . '/embed/dashboard/' . $token . '#bordered=true&titled=true';
    }
}