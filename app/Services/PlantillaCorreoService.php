<?php

namespace App\Services;

class PlantillaCorreoService
{
    /**
     * Variables disponibles para cada tipo de correo
     */
    const VARIABLES = [
        'acceso' => [
            'nombre' => 'Nombre del usuario',
            'correo' => 'Correo electrónico del usuario',
            'contraseña' => 'Contraseña temporal',
            'link' => 'Enlace de acceso al portal'
        ],
        'implementacion' => [
            'elemento' => 'Nombre del elemento implementado',
            'link' => 'Enlace al elemento'
        ],
        'agradecimiento' => [
            'elemento' => 'Nombre del elemento',
            'link' => 'Enlace al elemento'
        ]
    ];

    /**
     * Obtener variables disponibles para un tipo específico
     */
    public static function getVariablesPorTipo(string $tipo): array
    {
        return self::VARIABLES[$tipo] ?? [];
    }

    /**
     * Obtener todas las variables disponibles
     */
    public static function getAllVariables(): array
    {
        return self::VARIABLES;
    }

    /**
     * Reemplazar variables en una plantilla
     */
    public static function reemplazarVariables(string $plantilla, array $datos): string
    {
        $plantillaProcesada = $plantilla;
        
        foreach ($datos as $variable => $valor) {
            $plantillaProcesada = str_replace("{{" . $variable . "}}", $valor, $plantillaProcesada);
        }
        
        return $plantillaProcesada;
    }

    /**
     * Validar que todas las variables requeridas estén presentes
     */
    public static function validarVariables(string $plantilla, string $tipo): array
    {
        $variablesRequeridas = self::getVariablesPorTipo($tipo);
        $variablesFaltantes = [];
        
        foreach ($variablesRequeridas as $variable => $descripcion) {
            if (strpos($plantilla, "{{" . $variable . "}}") === false) {
                $variablesFaltantes[] = $variable;
            }
        }
        
        return $variablesFaltantes;
    }

    /**
     * Generar vista previa con datos de ejemplo
     */
    public static function generarVistaPrevia(string $plantilla, string $tipo): string
    {
        $datosEjemplo = self::getDatosEjemplo($tipo);
        return self::reemplazarVariables($plantilla, $datosEjemplo);
    }

    /**
     * Obtener datos de ejemplo para cada tipo
     */
    private static function getDatosEjemplo(string $tipo): array
    {
        switch ($tipo) {
            case 'acceso':
                return [
                    'nombre' => 'Juan Pérez',
                    'correo' => 'juan.perez@empresa.com',
                    'contraseña' => 'Temp123!',
                    'link' => 'https://portal.empresa.com/login'
                ];
            
            case 'implementacion':
                return [
                    'elemento' => 'Procedimiento de Calidad ISO 9001',
                    'link' => 'https://portal.empresa.com/elementos/123'
                ];
            
            case 'agradecimiento':
                return [
                    'elemento' => 'Manual de Procedimientos',
                    'link' => 'https://portal.empresa.com/elementos/456'
                ];
            
            default:
                return [];
        }
    }

    /**
     * Obtener descripción del tipo de correo
     */
    public static function getDescripcionTipo(string $tipo): string
    {
        $descripciones = [
            'acceso' => 'Correo enviado cuando se crea una nueva cuenta de usuario en el portal',
            'implementacion' => 'Correo enviado cuando se implementa un nuevo elemento en el sistema',
            'agradecimiento' => 'Correo de agradecimiento enviado después de completar un proceso'
        ];
        
        return $descripciones[$tipo] ?? 'Tipo de correo no definido';
    }

    /**
     * Generar plantilla HTML de ejemplo
     */
    public static function getPlantillaEjemplo(string $tipo): string
    {
        switch ($tipo) {
            case 'acceso':
                return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #2563eb; border-bottom: 2px solid #2563eb; padding-bottom: 10px;">
        Bienvenido al Sistema SGCR
    </h2>
    
    <p>Estimado <strong>{{nombre}}</strong>,</p>
    
    <p>Se ha creado tu cuenta en el Sistema de Gestión de Calidad y Responsabilidades (SGCR) con las siguientes credenciales:</p>
    
    <div style="background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;">
        <p><strong>Correo electrónico:</strong> {{correo}}</p>
        <p><strong>Contraseña temporal:</strong> {{contraseña}}</p>
    </div>
    
    <p>Para acceder al sistema, haz clic en el siguiente enlace:</p>
    
    <div style="text-align: center; margin: 25px 0;">
        <a href="{{link}}" style="background-color: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">
            Acceder al Portal SGCR
        </a>
    </div>
    
    <p><strong>Importante:</strong> Te recomendamos cambiar tu contraseña después del primer inicio de sesión.</p>
    
    <p>Si tienes alguna pregunta, no dudes en contactar al equipo de soporte.</p>
    
    <p>Saludos cordiales,<br>
    <strong>Equipo SGCR</strong></p>
</div>';
            
            case 'implementacion':
                return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #059669; border-bottom: 2px solid #059669; padding-bottom: 10px;">
        Nuevo Elemento Implementado
    </h2>
    
    <p>Estimado equipo,</p>
    
    <p>Se ha implementado exitosamente un nuevo elemento en el sistema SGCR:</p>
    
    <div style="background-color: #f0fdf4; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #059669;">
        <h3 style="color: #059669; margin-top: 0;">{{elemento}}</h3>
        <p>Este elemento ya está disponible para su consulta y uso en el sistema.</p>
    </div>
    
    <p>Para acceder al elemento, utiliza el siguiente enlace:</p>
    
    <div style="text-align: center; margin: 25px 0;">
        <a href="{{link}}" style="background-color: #059669; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">
            Ver Elemento
        </a>
    </div>
    
    <p>Saludos,<br>
    <strong>Equipo SGCR</strong></p>
</div>';
            
            case 'agradecimiento':
                return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #7c3aed; border-bottom: 2px solid #7c3aed; padding-bottom: 10px;">
        Agradecimiento
    </h2>
    
    <p>Estimado equipo,</p>
    
    <p>Queremos expresar nuestro agradecimiento por su participación en el proceso relacionado con:</p>
    
    <div style="background-color: #faf5ff; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #7c3aed;">
        <h3 style="color: #7c3aed; margin-top: 0;">{{elemento}}</h3>
        <p>Su colaboración ha sido fundamental para el éxito de este proyecto.</p>
    </div>
    
    <p>Para revisar los detalles del elemento, puede acceder a través del siguiente enlace:</p>
    
    <div style="text-align: center; margin: 25px 0;">
        <a href="{{link}}" style="background-color: #7c3aed; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">
            Revisar Elemento
        </a>
    </div>
    
    <p>Gracias nuevamente por su valioso aporte.</p>
    
    <p>Saludos cordiales,<br>
    <strong>Equipo SGCR</strong></p>
</div>';
            
            default:
                return '';
        }
    }

    /**
     * Generar plantilla de texto plano de ejemplo
     */
    public static function getPlantillaTextoEjemplo(string $tipo): string
    {
        switch ($tipo) {
            case 'acceso':
                return "Bienvenido al Sistema SGCR\n\nEstimado {{nombre}},\n\nSe ha creado tu cuenta en el Sistema de Gestión de Calidad y Responsabilidades (SGCR) con las siguientes credenciales:\n\nCorreo electrónico: {{correo}}\nContraseña temporal: {{contraseña}}\n\nPara acceder al sistema, visita: {{link}}\n\nImportante: Te recomendamos cambiar tu contraseña después del primer inicio de sesión.\n\nSi tienes alguna pregunta, no dudes en contactar al equipo de soporte.\n\nSaludos cordiales,\nEquipo SGCR";
            
            case 'implementacion':
                return "Nuevo Elemento Implementado\n\nEstimado equipo,\n\nSe ha implementado exitosamente un nuevo elemento en el sistema SGCR:\n\n{{elemento}}\n\nEste elemento ya está disponible para su consulta y uso en el sistema.\n\nPara acceder al elemento, visita: {{link}}\n\nSaludos,\nEquipo SGCR";
            
            case 'agradecimiento':
                return "Agradecimiento\n\nEstimado equipo,\n\nQueremos expresar nuestro agradecimiento por su participación en el proceso relacionado con:\n\n{{elemento}}\n\nSu colaboración ha sido fundamental para el éxito de este proyecto.\n\nPara revisar los detalles del elemento, visite: {{link}}\n\nGracias nuevamente por su valioso aporte.\n\nSaludos cordiales,\nEquipo SGCR";
            
            default:
                return '';
        }
    }
}
