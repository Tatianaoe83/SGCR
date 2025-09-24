<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Empleados;

class AccesoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $empleado;
    public $contrasena;
    public $subject;

    public function __construct(Empleados $empleado, string $contrasena)
    {
        $this->empleado = $empleado;
        $this->contrasena = $contrasena;
        $this->subject = 'Acceso al Portal SGCR';
    }

    public function build()
    {
        // Obtener el template del correo
        $cuerpoCorreo = \App\Models\CuerpoCorreo::where('tipo', 'acceso')->first();
        
        if (!$cuerpoCorreo) {
            throw new \Exception('Template de correo no encontrado');
        }

        // Reemplazar placeholders
        $htmlContent = $cuerpoCorreo->cuerpo_html;
        $htmlContent = str_replace('{{nombre}}', $this->empleado->nombres . ' ' . $this->empleado->apellido_paterno . ' ' . $this->empleado->apellido_materno, $htmlContent);
        $htmlContent = str_replace('{{correo}}', $this->empleado->correo, $htmlContent);
        $htmlContent = str_replace('{{contraseña}}', $this->contrasena, $htmlContent);
        $htmlContent = str_replace('{{puesto}}', $this->empleado->puestoTrabajo->nombre ?? 'No especificado', $htmlContent);
        $htmlContent = str_replace('{{fecha_ingreso}}', $this->empleado->fecha_ingreso ? \Carbon\Carbon::parse($this->empleado->fecha_ingreso)->format('d/m/Y') : 'No especificada', $htmlContent);
        // Generar URL absoluta para el login
        $loginUrl = $this->generateLoginUrl();
        $htmlContent = str_replace('{{link}}', $loginUrl, $htmlContent);

     
        return $this->subject($this->subject)
                    ->html($htmlContent);
    }

    /**
     * Generar URL de login de forma robusta
     */
    private function generateLoginUrl(): string
    {
        try {
            // Obtener la URL base de la aplicación
            $baseUrl = config('app.url', 'http://localhost');
            
            // Limpiar la URL base
            $baseUrl = rtrim($baseUrl, '/');
            
            // Intentar usar la función route() de Laravel
            $loginPath = route('login');
            
            // Si la ruta ya es absoluta, usarla directamente
            if (str_starts_with($loginPath, 'http')) {
                return $loginPath;
            }
            
            // Construir URL absoluta
            return $baseUrl . $loginPath;
            
        } catch (\Exception $e) {
            // Si falla, usar config('app.url') + '/login'
            $baseUrl = config('app.url', 'http://localhost');
            return rtrim($baseUrl, '/') . '/login';
        }
    }
}
