<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (\Throwable $e) {
            $req = request();
            if ($req && ($req->is('livewire/*') || $req->is('livewire-upload') || $req->hasHeader('X-Livewire'))) {
                Log::error("[LIVEWIRE UPLOAD ERROR] " . $e->getMessage(), [
                    'url' => $req->fullUrl(),
                    'method' => $req->method(),
                    'exception_class' => get_class($e),
                    'file' => $e->getFile() . ':' . $e->getLine(),
                    'has_valid_signature' => $req->hasValidSignature(),
                    'files' => array_keys($req->allFiles()),
                ]);
            }
        });
    })->create();
