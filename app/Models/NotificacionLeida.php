<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificacionLeida extends Model
{
    public const TIPO_FIRMA_RECHAZADA = 'firma_rechazada';

    protected $table = 'notificaciones_leidas';

    protected $fillable = [
        'user_id',
        'tipo',
        'elemento_id',
        'evento_at',
    ];

    protected $casts = [
        'evento_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function elemento(): BelongsTo
    {
        return $this->belongsTo(Elemento::class, 'elemento_id', 'id_elemento');
    }
}
