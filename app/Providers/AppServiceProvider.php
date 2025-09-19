<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\WordDocument;
use App\Observers\WordDocumentObserver;

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
        // Registrar Observer para WordDocument
        WordDocument::observe(WordDocumentObserver::class);
    }
}
