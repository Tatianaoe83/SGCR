<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoElemento extends Model
{
    use SoftDeletes;
    
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
    
    public function camposRequeridos(): HasMany
    {
        return $this->hasMany(CampoRequeridoTipoElemento::class, 'tipo_elemento_id', 'id_tipo_elemento');
    }

    /**
     * Indica si este tipo acepta video/audio en el archivo del elemento.
     */
    public function permiteMultimedia(): bool
    {
        $permitidos = array_map(
            static fn ($nombre) => mb_strtolower($nombre),
            (array) config('uploads.video.tipos_permitidos', [])
        );

        return in_array(mb_strtolower((string) $this->nombre), $permitidos, true);
    }

    public static function idsQuePermitenMultimedia(): array
    {
        return static::query()
            ->whereIn('nombre', (array) config('uploads.video.tipos_permitidos', []))
            ->pluck('id_tipo_elemento')
            ->all();
    }
}
