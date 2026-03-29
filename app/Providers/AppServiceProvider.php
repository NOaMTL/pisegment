<?php

namespace App\Providers;

use App\Services\Http\GuzzleHttpClient;
use App\Services\Http\HttpClientInterface;
use App\Services\Mail\CustomMailService;
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
        // ============================================
        // Mail Service - Choisissez votre implémentation
        // ============================================
        
        // Option 1: Laravel Mail (par défaut)
        $this->app->bind(MailServiceInterface::class, LaravelMailService::class);
        
        // Option 2: Votre classe Mail personnalisée
        // $this->app->bind(MailServiceInterface::class, CustomMailService::class);
        
        // Option 3: PHPMailer (pour tests)
        // $this->app->bind(MailServiceInterface::class, function ($app) {
        //     return new \App\Services\Mail\PHPMailerService([
        //         'host' => config('mail.mailers.smtp.host'),
        //         'port' => config('mail.mailers.smtp.port'),
        //         'username' => config('mail.mailers.smtp.username'),
        //         'password' => config('mail.mailers.smtp.password'),
        //         'encryption' => config('mail.mailers.smtp.encryption'),
        //         'from' => [
        //             'email' => config('mail.from.address'),
        //             'name' => config('mail.from.name'),
        //         ],
        //     ]);
        // });
        
        // 💡 Pour basculer d'implémentation, changez juste la ligne ci-dessus !
        //    Aucun changement dans vos contrôleurs ou services.

        // ============================================
        // HTTP Client
        // ============================================
        
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
