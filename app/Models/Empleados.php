<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Normalizer;

class Empleados extends Model
{
    use SoftDeletes;

    protected $table = 'empleados';
    protected $primaryKey = 'id_empleado';

    protected $fillable = [
        'nombres',
        'apellido_materno',
        'apellido_paterno',
        'puesto_trabajo_id',
        'correo',
        'telefono',
        'fecha_ingreso',
        'fecha_nacimiento',
    ];


    public function puestoTrabajo(): BelongsTo
    {
        return $this->belongsTo(PuestoTrabajo::class, 'puesto_trabajo_id', 'id_puesto_trabajo');
    }

    /**
     * Generar contraseña automática según el patrón especificado
     * 1er letra mayúscula + 3 primeras letras del apellido paterno + 4 letras del nombre + # aleatoria del 0 al 9
     */
    public function generarContrasenaAutomatica(): string
    {
        $nombresSinAcento = $this->removeAccents($this->nombres);
        $apellidoPaternoSinAcento = $this->removeAccents($this->apellido_paterno);

        $primeraLetra = strtoupper(mb_substr($nombresSinAcento, 0, 1));
        $apellidoPaterno = mb_substr($apellidoPaternoSinAcento, 0, 3);
        $nombre = mb_substr($nombresSinAcento, 0, 4);

        $numeroAleatorio = rand(0, 9);

        $contrasena = $primeraLetra . $apellidoPaterno . $nombre . $numeroAleatorio;

        return $contrasena;
    }

    private function removeAccents($string)
    {
        $accents = array(
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'ñ' => 'n',
            'Ñ' => 'N',
            '¿' => '',
            '¡' => ''
        );

        return strtr($string, $accents);
    }
}
