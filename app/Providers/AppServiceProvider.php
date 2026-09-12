<?php

namespace App\Providers;

use App\Models\Setting;
use App\Support\MailConfig;
use App\Support\StudentSidebarTrail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\View;
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
        MailConfig::apply();

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            $name = ($notifiable->role ?? '') === 'admin'
                ? 'admin.password.reset'
                : 'password.reset';

            return url(route($name, [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]));
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            MailConfig::apply();

            $name = ($notifiable->role ?? '') === 'admin'
                ? 'admin.password.reset'
                : 'password.reset';

            $url = url(route($name, [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]));

            return (new MailMessage)
                ->subject('Reset your Gses Chaturji password')
                ->view('emails.password-reset', [
                    'user' => $notifiable,
                    'url' => $url,
                ]);
        });

        View::composer([
            'layouts.front',
            'layouts.auth-direct',
            'layouts.partials.front-header',
            'layouts.partials.front-footer',
            'front.*',
        ], function ($view) {
            $view->with('settings', Setting::allCached());
        });

        View::composer('layouts.partials.student-sidebar', function ($view) {
            try {
                $view->with('studentNavTrail', StudentSidebarTrail::resolve());
            } catch (\Throwable $e) {
                report($e);
                $view->with('studentNavTrail', collect());
            }
        });
    }
}
