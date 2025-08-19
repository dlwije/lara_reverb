<?php

namespace App\Providers;

use App\Events\AttachmentEvent;
use App\Listeners\AttachmentEventListener;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->extend(
            \Illuminate\Translation\Translator::class,
            fn ($translator) => new \App\Core\Translator($translator->getLoader(), $translator->getLocale())
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        App::useLangPath(base_path('lang'));
        if(! env('APP_INSTALLED', false)) {
            redirect()->to('/install');
        } elseif (function_exists('get_settings')) {
            app()->useLangPath(base_path('lang'));

            Event::listen(
                AttachmentEvent::class,
                AttachmentEventListener::class,
            );

            try {
                $settings = get_settings(['mail', 'payment', 'barcode', 'timezone', 'default_locale']);

                Log::info('Settings: ' . json_encode($settings));

                $settings['timezone'] ??= 'UTC';
                config(['app.timezone' => $settings['timezone']]);
                Carbon::setLocale($settings['default_locale'] ?? 'en');

                $mail = (array) ($settings['mail']['mail'] ?? []);
                if(! empty($mail)) {
                    $mail = array_replace_recursive(config('mail'), $mail);
                    config(['mail' => $mail]);
                }

                $services = (array) ($settings['mail']['services'] ?? []);
                if (! empty($services)) {
                    $services = array_replace_recursive(config('services'), $services);
                    config(['services' => $services]);
                }

                $services = (array) ($settings['payment']['services'] ?? []);
                if (! empty($services)) {
                    $services = array_replace_recursive(config('services'), $services);
                    config(['services' => $services]);
                }
            }catch (\Exception $e){
                logger('Provider settings error: ' . $e->getMessage());
            }

            Gate::before(function ($user, $ability) {
                return $user->hasRole('Super Admin') ? true : null;
            });
        }
        Passport::enablePasswordGrant();

        Passport::tokensExpireIn(CarbonInterval::days(1));
        Passport::refreshTokensExpireIn(CarbonInterval::days(15));
        Passport::personalAccessTokensExpireIn(CarbonInterval::months(6));
    }
}
