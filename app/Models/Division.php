<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;

    protected $table = 'divisions';
    protected $primaryKey = 'id_division';

    protected $fillable = ['nombre'];

    public function unidadesNegocio()
    {
        return $this->hasMany(UnidadNegocio::class, 'division_id', 'id_division');
    }
}
