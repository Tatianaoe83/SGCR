<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
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
        Fortify::authenticateUsing(function (Request $request) {
            // Fortify llama este callback dos veces cuando 2FA está activo
            // (RedirectIfTwoFactorAuthenticatable + AttemptToAuthenticate).
            // El token de Turnstile es de un solo uso, así que solo lo
            // validamos una vez por request HTTP.
            if (! $request->attributes->get('turnstile_verified')) {
                // El token de Turnstile es de un solo uso y Fortify llama este
                // callback dos veces cuando 2FA está activo, por eso solo lo
                // verificamos una vez por request. Lo hacemos directo contra
                // Cloudflare porque el Client del paquete tiene la lógica
                // invertida y nunca devuelve éxito en HTTP 200.
                $cf = \Illuminate\Support\Facades\Http::asForm()
                    ->acceptJson()
                    ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                        'secret' => config('services.turnstile.secret'),
                        'response' => $request->input('cf-turnstile-response'),
                        'remoteip' => $request->ip(),
                    ]);

                if (! ($cf->ok() && $cf->json('success') === true)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'cf-turnstile-response' => __('La verificación falló. Inténtalo de nuevo.'),
                    ]);
                }

                $request->attributes->set('turnstile_verified', true);
            }

            $user = User::where('email', $request->email)->first();

            if ($user && Hash::check($request->password, $user->password)) {
                return $user;
            }

            return null;
        });

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
