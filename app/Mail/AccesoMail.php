<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Empleados;
use App\Models\User;

class AccesoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $usuario;
    public $contrasena;
    public $subject;
    public $esEmpleado;

    public function __construct(Empleados|User $usuario, string $contrasena)
    {
        $this->usuario = $usuario;
        $this->contrasena = $contrasena;
        $this->subject = 'Acceso al Portal SGCR';
        $this->esEmpleado = $usuario instanceof Empleados;
    }

    public function build()
    {
        // Obtener el template del correo
        $cuerpoCorreo = \App\Models\CuerpoCorreo::where('tipo', 'acceso')->first();
        
        if (!$cuerpoCorreo) {
            throw new \Exception('Template de correo no encontrado');
        }

        // Obtener datos según el tipo de usuario
        if ($this->esEmpleado) {
            $nombre = $this->usuario->nombres . ' ' . $this->usuario->apellido_paterno . ' ' . $this->usuario->apellido_materno;
            $correo = $this->usuario->correo;
            $puesto = $this->usuario->puestoTrabajo->nombre ?? 'No especificado';
            $fechaIngreso = $this->usuario->fecha_ingreso ? \Carbon\Carbon::parse($this->usuario->fecha_ingreso)->format('d/m/Y') : 'No especificada';
        } else {
            // Es un User
            $nombre = $this->usuario->name;
            $correo = $this->usuario->email;
            $puesto = 'Usuario del Sistema';
            $fechaIngreso = 'N/A';
        }

        // Reemplazar placeholders
        $htmlContent = $cuerpoCorreo->cuerpo_html;
        $htmlContent = str_replace('{{nombre}}', $nombre, $htmlContent);
        $htmlContent = str_replace('{{correo}}', $correo, $htmlContent);
        $htmlContent = str_replace('{{contraseña}}', $this->contrasena, $htmlContent);
        $htmlContent = str_replace('{{puesto}}', $puesto, $htmlContent);
        $htmlContent = str_replace('{{fecha_ingreso}}', $fechaIngreso, $htmlContent);
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
