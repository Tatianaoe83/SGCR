<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoElemento extends Model
{
    protected $table = 'tipo_elementos';
    protected $primaryKey = 'id_tipo_elemento';
    
    protected $fillable = [
        'nombre',
        'descripcion'
    ];

    public function elementos(): HasMany
    {
        return $this->hasMany(Elemento::class, 'tipo_elemento_id', 'id_tipo_elemento');
    }
}
