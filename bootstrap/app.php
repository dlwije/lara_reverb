<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
//        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    // and add this to authorize your private channels
    ->withBroadcasting(
        __DIR__ . '/../routes/channels.php',
        ['prefix' => 'api', 'middleware' => ['auth:api']],
    )
    ->withMiddleware(function (Middleware $middleware) {
        // run CORS before everything else
        $middleware->prependToGroup('api',HandleCors::class);
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'scope' => \Laravel\Passport\Http\Middleware\CheckTokenForAnyScope::class,
            'scopes' => \Laravel\Passport\Http\Middleware\CheckToken::class,
            'teamowner' => \Mpociot\Teamwork\Middleware\TeamOwner::class,
//            'Sms' => Prgayman\Sms\Facades\Sms::class,
//            'SmsHistory' => Prgayman\Sms\Facades\SmsHistory::class,
//            'Countries' => Webpatser\Countries\CountriesFacade::class,
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {

            // Pure API (Postman, mobile, etc.)
            if ($request->is('api/*') || $request->expectsJson()) {

//                print_r($request->header('Authorization'));
                $status = $response->getStatusCode();

                if (in_array($status, [500, 403]) && ! app()->environment(['local', 'testing'])) {

                    return response()->json([
                        'status' => false,
                        'message' => $status . ': ' . $exception->getMessage(),
                        'errors' => [],
                    ], $status);
                }

                if ($status === 419) {

                    return response()->json([
                        'status' => false,
                        'message' => 'The page expired, please try again.',
                        'errors' => [],
                    ], 419);
                }

                if ($status === 404) {
                    return response()->json([
                        'status' => false,
                        'message' => $exception->getMessage() ?: 'Not Found',
                        'errors' => [],
                    ], 404);
                }

                return response()->json([
                    'status' => false,
                    'message' => $exception->getMessage(),
                    'errors' => [],
                ], $status ?: 400);
            }

            // Web & Inertia routes: fallback to default Laravel behavior
            return $response;
        });
    })->create();
