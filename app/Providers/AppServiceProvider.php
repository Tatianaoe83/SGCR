<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\WordDocument;
use App\Models\TipoElemento;
use App\Observers\WordDocumentObserver;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;


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
        WordDocument::observe(WordDocumentObserver::class);

        View::composer('components.modal-suggets-change-control', function ($view) {
            $view->with('tiposElemento', TipoElemento::select('id_tipo_elemento', 'nombre')->orderBy('nombre')->get());
        });

        // Duración de la cookie "Recordarme" (en minutos). 
        // setRememberDuration() vive en SessionGuard (clase concreta), no en
        // la interfaz Guard; el instanceof satisface al análisis estático.
        $guard = Auth::guard('web');
        if ($guard instanceof \Illuminate\Auth\SessionGuard) {
            $guard->setRememberDuration(60 * 24 * 1);
        }
    }
}
