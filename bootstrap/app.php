<?php

use App\Http\Middleware\ServeParkedDomainPage;
use App\Http\Middleware\SetLocale;
use App\Models\Redirect;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
        ]);

        $middleware->web(prepend: [
            ServeParkedDomainPage::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Admin-managed redirects: only consulted when no route matches.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || ! $request->isMethod('GET')) {
                return null;
            }

            $path = '/'.ltrim($request->path(), '/');

            $redirects = cache()->remember(
                'active_redirects',
                now()->addHour(),
                fn () => Redirect::active()->pluck('to_path', 'from_path')
                    ->mapWithKeys(fn ($to, $from) => [$from => $to])
                    ->all(),
            );

            if (isset($redirects[$path])) {
                $redirect = Redirect::active()->where('from_path', $path)->first();

                if ($redirect) {
                    $redirect->increment('hits');

                    return redirect($redirect->to_path, $redirect->status_code);
                }
            }

            return null;
        });
    })->create();
