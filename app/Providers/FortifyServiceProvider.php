<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
            // Con 2FA activo Fortify ejecuta este callback dos veces por request y el
            // token de Turnstile es de un solo uso, por eso se valida una sola vez.
            if (config('services.turnstile.secret') && ! $request->attributes->get('turnstile_verified')) {
                $this->verifyTurnstile($request);
                $request->attributes->set('turnstile_verified', true);
            }

            $user = User::where(Fortify::username(), $request->input(Fortify::username()))->first();

            if ($user && Hash::check($request->input('password'), $user->password)) {
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

    private function verifyTurnstile(Request $request): void
    {
        $token = $request->input('cf-turnstile-response');

        try {
            $response = Http::asForm()->acceptJson()->timeout(10)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => config('services.turnstile.secret'),
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);

            $success = $token && $response->ok() && $response->json('success') === true;
        } catch (ConnectionException $e) {
            $success = false;
        }

        if (! $success) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'La verificación de seguridad falló. Inténtalo de nuevo.',
            ]);
        }
    }
}
