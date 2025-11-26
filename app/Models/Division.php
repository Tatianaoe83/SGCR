<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Division extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'divisions';
    protected $primaryKey = 'id_division';
    
    protected $dates = ['deleted_at'];

    protected $fillable = ['nombre'];

    public function unidadesNegocio()
    {
        return $this->hasMany(UnidadNegocio::class, 'division_id', 'id_division');
    }
}
