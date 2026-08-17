<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class SsoController extends Controller
{
    /**
     * Redirect user to SIPETRA OAuth server.
     */
    public function redirect()
    {
        return Socialite::driver('sipetra')->redirect();
    }

    /**
     * Handle callback from SIPETRA OAuth server.
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            Log::warning('[SSO SIPETRA] Login canceled or returned error', [
                'error' => $request->get('error'),
                'error_description' => $request->get('error_description'),
            ]);
            return redirect()->route('login')->with('error', 'Login SSO SIPETRA Dibatalkan.');
        }

        try {
            $ssoUser = Socialite::driver('sipetra')->user();
        } catch (\Throwable $e) {
            Log::error('[SSO SIPETRA] Failed to retrieve user data', [
                'message' => $e->getMessage(),
            ]);
            return redirect()->route('login')->with('error', 'Gagal mengambil data autentikasi dari SIPETRA SSO: ' . $e->getMessage());
        }

        $rawData = $ssoUser->getRaw();

        // Cari user berdasarkan sipetra_id atau email
        $user = User::where('sipetra_id', $ssoUser->getId())->first()
             ?? User::where('email', $ssoUser->getEmail())->first();

        $data = [
            'sipetra_id'             => $ssoUser->getId(),
            'name'                   => $ssoUser->getName() ?? ($rawData['name'] ?? 'User SIPETRA'),
            'email'                  => $ssoUser->getEmail() ?? ($rawData['email'] ?? null),
            'sipetra_token'          => $ssoUser->token,
            'sipetra_refresh_token'  => $ssoUser->refreshToken ?? null,
            'nip'                    => $rawData['nip'] ?? ($rawData['identity']['nip'] ?? null),
            'jabatan'                => $rawData['jabatan'] ?? ($rawData['employee']['jabatan'] ?? null),
            'avatar'                 => $ssoUser->getAvatar() ?? ($rawData['avatar'] ?? null),
        ];

        if ($user) {
            $user->update($data);
        } else {
            $data['password'] = null;
            $user = User::create($data);
        }

        Auth::login($user, true);

        return redirect()->intended(route('dashboard.pengolahan'));
    }
}
