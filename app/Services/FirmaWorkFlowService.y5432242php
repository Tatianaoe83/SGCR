<?php

namespace App\Services;

use App\Jobs\EnviarFirmaMail;
use App\Models\Firmas;
use Illuminate\Support\Facades\DB;

class FirmaWorkFlowService
{
    private function hasRejected(int $elementoID): bool
    {
        return Firmas::where('elemento_id', $elementoID)
            ->where('is_active', true)
            ->where('estatus', 'Rechazado')
            ->exists();
    }

    public function getActivePriorityFirma(int $elementoID): ?int
    {
        if ($this->hasRejected($elementoID)) {
            return null;
        }

        return Firmas::where('elemento_id', $elementoID)
            ->where('is_active', true)
            ->where('estatus', 'Pendiente')
            ->min('prioridad');
    }

    public function dispatchPendingForElemento(int $elementoID): void
    {
        $activePriority = $this->getActivePriorityFirma($elementoID);
        if ($activePriority === null) {
            return;
        }

        $representantes = Firmas::query()
            ->select(DB::raw('MIN(id) as id'))
            ->where('elemento_id', $elementoID)
            ->where('is_active', true)
            ->where('prioridad', $activePriority)
            ->where('estatus', 'Pendiente')
            ->whereNull('email_sent_at')
            ->groupBy('empleado_id')
            ->pluck('id');

        foreach ($representantes as $firmaId) {
            EnviarFirmaMail::dispatch((int) $firmaId);
        }
    }

    public function canSendFirma(int $firmaID): bool
    {
        $firma = Firmas::select(['id', 'elemento_id', 'prioridad', 'estatus', 'email_sent_at'])
            ->find($firmaID);

        if (!$firma) return false;
        if ($firma->estatus !== 'Pendiente') return false;
        if ($firma->email_sent_at !== null) return false;
        if ($this->hasRejected((int) $firma->elemento_id)) return false;

        $activePriority = $this->getActivePriorityFirma((int) $firma->elemento_id);

        return $activePriority !== null && (int) $firma->prioridad === (int) $activePriority;
    }
}