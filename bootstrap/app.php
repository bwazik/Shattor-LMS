<?php

use Jubaer\Zoom\Facades\Zoom;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Middleware\RestrictFinancials;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\EnsureTeacherIsSubscribed;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'localize'                => LaravelLocalizationRoutes::class,
            'localizationRedirect'    => LaravelLocalizationRedirectFilter::class,
            'localeSessionRedirect'   => LocaleSessionRedirect::class,
            'localeCookieRedirect'    => LocaleCookieRedirect::class,
            'localeViewPath'          => LaravelLocalizationViewPath::class,
            'DataTables' => DataTables::class,
            'Zoom' => Zoom::class,
            'subscribed' => EnsureTeacherIsSubscribed::class,
            'auth' => Authenticate::class,
            'guest' => RedirectIfAuthenticated::class,
            'Excel' => Excel::class,
            'restrict.financials' => RestrictFinancials::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
