<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;

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
        Event::listen(Login::class, function ($event) {
            activity('auth')
                ->causedBy($event->user)
                ->tap(function($activity) {
                    $activity->event = 'login';
                })
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('User logged in');
        });

        Event::listen(Logout::class, function ($event) {
            if ($event->user) {
                activity('auth')
                    ->causedBy($event->user)
                    ->tap(function($activity) {
                        $activity->event = 'logout';
                    })
                    ->withProperties([
                        'ip' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ])
                    ->log('User logged out');
            }
        });

        Event::listen(Failed::class, function ($event) {
            activity('auth')
                ->tap(function($activity) {
                    $activity->event = 'failed';
                })
                ->withProperties([
                    'email' => $event->credentials['email'] ?? 'unknown',
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Failed login attempt');
        });
    }
}
