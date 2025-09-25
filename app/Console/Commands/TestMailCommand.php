<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Exception;

class TestMailCommand extends Command
{
    protected $signature = 'mail:test {email}';
    protected $description = 'Probar la configuración de correo SMTP';

    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info('Probando configuración SMTP...');
        $this->info('Host: ' . config('mail.mailers.smtp.host'));
        $this->info('Puerto: ' . config('mail.mailers.smtp.port'));
        $this->info('Encriptación: ' . config('mail.mailers.smtp.encryption'));
        $this->info('Usuario: ' . config('mail.mailers.smtp.username'));
        
        try {
            Mail::raw('Esta es una prueba de conexión SMTP desde SGCR.', function ($message) use ($email) {
                $message->to($email)
                        ->subject('Prueba de SMTP - SGCR');
            });
            
            $this->info('✅ Email enviado exitosamente a: ' . $email);
            
        } catch (Exception $e) {
            $this->error('❌ Error al enviar email:');
            $this->error($e->getMessage());
            
            // Sugerencias de solución
            $this->warn('💡 Sugerencias:');
            $this->warn('1. Verifica que la contraseña sea correcta');
            $this->warn('2. Prueba cambiar el puerto a 587 con TLS');
            $this->warn('3. Verifica que el firewall no bloquee la conexión');
            $this->warn('4. Confirma que la autenticación SMTP esté habilitada');
            
            return 1;
        }
        
        return 0;
    }
}
