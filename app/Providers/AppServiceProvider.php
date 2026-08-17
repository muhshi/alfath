<?php

namespace App\Providers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (class_exists(\Laravel\Socialite\SocialiteServiceProvider::class)) {
            try {
                $socialite = $this->app->make(\Laravel\Socialite\Contracts\Factory::class);
                $socialite->extend('sipetra', function ($app) use ($socialite) {
                    $config = $app['config']['services.sipetra'] ?? [];
                    return $socialite->buildProvider(\App\Providers\SipetraSocialiteProvider::class, $config);
                });
            } catch (\Throwable $e) {
                // Ignore if container is building during package discovery
            }
        }

        // Dynamic APP_URL matching to eliminate 401 signature mismatches
        if (!app()->runningInConsole() && request()->header('host')) {
            $scheme = request()->getScheme() ?: 'http';
            $host = request()->header('host');
            config(['app.url' => "{$scheme}://{$host}"]);
        }

        // Detailed logging for Livewire file upload requests
        if (!app()->runningInConsole() && (request()->is('livewire/upload-file') || request()->is('livewire/upload-file/*'))) {
            $files = request()->allFiles();
            $fileDetails = [];
            foreach ($files as $key => $fileGroup) {
                $fileList = is_array($fileGroup) ? $fileGroup : [$fileGroup];
                foreach ($fileList as $f) {
                    if ($f instanceof UploadedFile) {
                        $fileDetails[] = [
                            'original_name' => $f->getClientOriginalName(),
                            'mime_type' => $f->getClientMimeType(),
                            'size_bytes' => $f->getSize(),
                            'error_code' => $f->getError(),
                            'error_message' => $f->getErrorMessage(),
                        ];
                    }
                }
            }

            Log::info('[LIVEWIRE UPLOAD REQUEST INCOMING]', [
                'full_url' => request()->fullUrl(),
                'has_valid_signature' => request()->hasValidSignature(),
                'files' => $fileDetails,
                'php_upload_max_filesize' => ini_get('upload_max_filesize'),
                'php_post_max_size' => ini_get('post_max_size'),
                'livewire_tmp_writable' => is_writable(storage_path('app/private/livewire-tmp')) || is_writable(storage_path('app/livewire-tmp')),
            ]);
        }
    }
}
