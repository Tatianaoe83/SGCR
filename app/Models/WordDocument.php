<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class WordDocument extends Model
{
    use HasFactory;

    protected $table = 'word_documents';

    protected $fillable = [
        'elemento_id',
        'contenido_texto',
        'contenido_markdown',
        'contenido_estructurado',
        'estado',
        'error_mensaje'
    ];

    protected $casts = [
        'contenido_estructurado' => 'array'
    ];

    /**
     * Relación con el modelo Elemento
     */
    public function elemento(): BelongsTo
    {
        return $this->belongsTo(Elemento::class, 'elemento_id', 'id_elemento');
    }

    /**
     * Obtener la URL del archivo
     */
    public function getUrlAttribute()
    {
        return Storage::url($this->elemento->archivo_formato);
    }

    /**
     * Obtener el tamaño del archivo
     */
    public function getTamanioAttribute()
    {
        if (Storage::exists($this->elemento->archivo_formato)) {
            return Storage::size($this->elemento->archivo_formato);
        }
        return 0;
    }

    /**
     * Obtener el tamaño del archivo formateado
     */
    public function getTamanioFormateadoAttribute()
    {
        $bytes = $this->tamanio;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Verificar si el archivo existe
     */
    public function getExisteAttribute()
    {
        return Storage::exists($this->elemento->archivo_formato);
    }

    /**
     * Obtener el estado formateado
     */
    public function getEstadoFormateadoAttribute()
    {
        $estados = [
            'procesado' => 'Procesado',
            'error' => 'Error',
            'pendiente' => 'Pendiente'
        ];
        
        return $estados[$this->estado] ?? $this->estado;
    }
    /**
     * Eliminar el archivo físico al eliminar el registro
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($wordDocument) {
            if (Storage::exists($wordDocument->elemento->archivo_formato)) {
                Storage::delete($wordDocument->elemento->archivo_formato);
            }
        });
    }
}
