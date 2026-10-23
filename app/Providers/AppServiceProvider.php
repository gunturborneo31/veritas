<?php

namespace App\Providers;

use App\Support\Payments\FakeQrisGateway;
use App\Support\Payments\InteractiveQrisGateway;
use App\Support\Payments\QrisGateway;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Driver simulasi tidak pernah dipakai di produksi, apa pun isi env-nya.
        $this->app->bind(QrisGateway::class, fn (): QrisGateway => config('kartukita.payment.qris.driver') === 'fake' && ! $this->app->isProduction()
            ? new FakeQrisGateway
            : new InteractiveQrisGateway);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
