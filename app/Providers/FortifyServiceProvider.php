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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
            $this->verifyTurnstile($request);

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

    /**
     * Verifica el token de Cloudflare Turnstile contra el endpoint oficial.
     *
     * Cubre todos los casos: token ausente, fallo de red, respuesta inválida,
     * token expirado/duplicado. Falla cerrado (rechaza) ante cualquier duda.
     *
     * Se verifica una sola vez por request porque Fortify ejecuta el callback
     * de autenticación dos veces cuando 2FA está activo, y el token es de un
     * solo uso. Verificamos directo contra Cloudflare porque el Client del
     * paquete tiene la lógica invertida y nunca devuelve éxito en HTTP 200.
     */
    protected function verifyTurnstile(Request $request): void
    {
        if ($request->attributes->get('turnstile_verified')) {
            return;
        }

        $token = $request->input('cf-turnstile-response');

        if (blank($token)) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => __('Completa la verificación de seguridad.'),
            ]);
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(10)
                ->retry(2, 200)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => config('services.turnstile.secret'),
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);
        } catch (\Throwable $e) {
            // Cloudflare inalcanzable: fallar cerrado, no dejar pasar sin verificar.
            Log::warning('Turnstile siteverify no disponible', ['error' => $e->getMessage()]);

            throw ValidationException::withMessages([
                'cf-turnstile-response' => __('No pudimos verificar la seguridad. Inténtalo de nuevo.'),
            ]);
        }

        $success = $response->ok() && $response->json('success') === true;

        if (! $success) {
            $errors = $response->json('error-codes') ?? [];

            // timeout-or-duplicate => token vencido o reusado; mensaje claro.
            $expired = in_array('timeout-or-duplicate', $errors, true);

            Log::info('Turnstile verificación rechazada', [
                'error_codes' => $errors,
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'cf-turnstile-response' => $expired
                    ? __('La verificación expiró. Recarga la página e inténtalo de nuevo.')
                    : __('La verificación de seguridad falló. Inténtalo de nuevo.'),
            ]);
        }

        $request->attributes->set('turnstile_verified', true);
    }
}
