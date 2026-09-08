<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Exception;

class TestMailCommand extends Command
{
    protected $signature = 'mail:test {email}';
    protected $description = 'Probar la configuración de correo y mostrar el diálogo SMTP real';

    public function handle()
    {
        $email = $this->argument('email');
        $mailer = config('mail.default');

        $this->info('Configuración efectiva (la que usa la app, no la del .env):');
        $this->line('  MAIL_MAILER   : ' . $mailer);
        $this->line('  Transport     : ' . config("mail.mailers.$mailer.transport"));
        $this->line('  Host          : ' . config("mail.mailers.$mailer.host"));
        $this->line('  Puerto        : ' . config("mail.mailers.$mailer.port"));
        $this->line('  Encriptación  : ' . config("mail.mailers.$mailer.encryption"));
        $this->line('  Usuario       : ' . config("mail.mailers.$mailer.username"));
        $this->line('  From          : ' . config('mail.from.address'));
        $this->line('  Config cacheada: ' . (app()->configurationIsCached() ? 'SÍ' : 'no'));
        $this->newLine();

        if ($mailer !== 'smtp') {
            $this->warn("El mailer activo es '$mailer', no 'smtp'. El correo no sale por SMTP.");
        }

        try {
            $sent = Mail::raw('Prueba de conexión SMTP desde SGCR.', function ($message) use ($email) {
                $message->to($email)->subject('Prueba de SMTP - SGCR');
            });

            $this->info('Envío aceptado por el transport.');
            $this->line('  Message-ID: ' . ($sent?->getMessageId() ?? 'sin id'));
            $this->newLine();
            $this->line('Diálogo SMTP:');
            $this->line($this->redact($sent?->getDebug()) ?: '(vacío: el transport no habló con ningún servidor SMTP)');

            return 0;
        } catch (Exception $e) {
            $this->error('Error al enviar: ' . $e->getMessage());
            $this->line($e->getFile() . ':' . $e->getLine());

            return 1;
        }
    }
    /**
     * Enmascara las credenciales en base64 que Symfony vuelca en el diálogo SMTP
     * (respuesta del cliente a los desafíos 334 de AUTH LOGIN/PLAIN).
     */
    private function redact(?string $debug): ?string
    {
        if ($debug === null || $debug === '') {
            return $debug;
        }

        $lines = array_values(array_filter(
            array_map('trim', preg_split('/\R/', $debug) ?: []),
            fn ($line) => $line !== ''
        ));

        $expectingCredential = false;

        foreach ($lines as $i => $line) {
            if ($expectingCredential && str_contains($line, '> ')) {
                $lines[$i] = preg_replace('/> \S+$/', '> [REDACTADO]', $line);
                $expectingCredential = false;
                continue;
            }

            if (preg_match('/< 33[45]/', $line)) {
                $expectingCredential = true;
            }

            if (preg_match('/> AUTH (PLAIN|LOGIN)\s+\S+/i', $line)) {
                $lines[$i] = preg_replace('/(> AUTH (?:PLAIN|LOGIN))\s+\S+/i', '$1 [REDACTADO]', $line);
            }
        }

        return implode(PHP_EOL, $lines);
    }
}
