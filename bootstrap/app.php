<?php

use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\NoindexPage;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        // Browser-owned consent and Meta cookies must not be decrypted by
        // Laravel; they are read only for consented CAPI matching.
        $middleware->encryptCookies(except: ['merza_meta_consent', '_fbp', '_fbc']);
        $middleware->alias([
            'noindex' => NoindexPage::class,
            'api.key' => AuthenticateApiKey::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'webhook/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
