<?php

namespace App\Jobs;

use App\Mail\RecordatorioMail;
use App\Models\CuerpoCorreo;
use App\Models\Elemento;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendScheduleMail implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $elementoId;
    /**
     * Create a new job instance.
     */
    public function __construct($elementoID)
    {
        $this->elementoId = $elementoID;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $elemento = Elemento::with('puestoResponsable')->findOrFail($this->elementoId);
        $correo = CuerpoCorreo::where('tipo', 'recordatorio')->first();

        if (!$correo) {
            return;
        }

        $responsable = $elemento->puestoResponsable;

        $copiasFijas = ['acastanares@proser.com.mx', 'ssauri@proser.com.mx'];

        $sendEmail = new RecordatorioMail($elemento, $correo);
        Mail::to($responsable->email)
            ->cc($copiasFijas)
            ->send($sendEmail);
    }
}
