<?php

namespace App\Providers;

use App\Services\Http\GuzzleHttpClient;
use App\Services\Http\HttpClientInterface;
use App\Services\Mail\LaravelMailService;
use App\Services\Mail\MailServiceInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind MailServiceInterface to LaravelMailService
        $this->app->bind(MailServiceInterface::class, LaravelMailService::class);

        // Bind HttpClientInterface to GuzzleHttpClient
        $this->app->bind(HttpClientInterface::class, GuzzleHttpClient::class);

        // You can also use singleton if you want only one instance
        // $this->app->singleton(MailServiceInterface::class, LaravelMailService::class);
        // $this->app->singleton(HttpClientInterface::class, GuzzleHttpClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
