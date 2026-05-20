<?php

namespace App\Providers;

use App\Services\FileStorageService;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
     $this->app->singleton(FileStorageService::class, function ($app) {
        return new FileStorageService();
    });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        LogViewer::auth(function ($request) {
            $user = $request->user();

            return $user && $user->is_master_admin();
        });
    }
}
