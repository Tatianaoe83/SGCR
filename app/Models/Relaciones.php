<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Relaciones extends Model
{
    use SoftDeletes;

    public $table = 'puestos_relacion';
    protected $primaryKey = 'relacionID';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $dates = ['deleted_at'];

    public $fillable = [
        'nombreRelacion',
        'puestos_trabajo',
        'elementoID',
    ];

    protected $casts = [
        'relacionID' => 'integer',
        'puestos_trabajo' => 'array',
    ];

    public function elemento(): BelongsTo
    {
        return $this->belongsTo(Elemento::class, 'elementoID', 'id_elemento');
    }
}
