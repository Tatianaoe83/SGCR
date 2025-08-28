<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CuerpoCorreo;

class CuerposCorreoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cuerpos = [
            [
                'nombre' => 'Bienvenida - Nuevo Usuario',
                'tipo' => 'acceso',
                'cuerpo_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
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
</div>',
                'cuerpo_texto' => "Bienvenido al Sistema SGCR\n\nEstimado {{nombre}},\n\nSe ha creado tu cuenta en el Sistema de Gestión de Calidad y Responsabilidades (SGCR) con las siguientes credenciales:\n\nCorreo electrónico: {{correo}}\nContraseña temporal: {{contraseña}}\n\nPara acceder al sistema, visita: {{link}}\n\nImportante: Te recomendamos cambiar tu contraseña después del primer inicio de sesión.\n\nSi tienes alguna pregunta, no dudes en contactar al equipo de soporte.\n\nSaludos cordiales,\nEquipo SGCR",
                'activo' => true
            ],
            [
                'nombre' => 'Notificación - Elemento Implementado',
                'tipo' => 'implementacion',
                'cuerpo_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
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
</div>',
                'cuerpo_texto' => "Nuevo Elemento Implementado\n\nEstimado equipo,\n\nSe ha implementado exitosamente un nuevo elemento en el sistema SGCR:\n\n{{elemento}}\n\nEste elemento ya está disponible para su consulta y uso en el sistema.\n\nPara acceder al elemento, visita: {{link}}\n\nSaludos,\nEquipo SGCR",
                'activo' => true
            ],
            [
                'nombre' => 'Agradecimiento - Colaboración',
                'tipo' => 'agradecimiento',
                'cuerpo_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
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
</div>',
                'cuerpo_texto' => "Agradecimiento\n\nEstimado equipo,\n\nQueremos expresar nuestro agradecimiento por su participación en el proceso relacionado con:\n\n{{elemento}}\n\nSu colaboración ha sido fundamental para el éxito de este proyecto.\n\nPara revisar los detalles del elemento, visite: {{link}}\n\nGracias nuevamente por su valioso aporte.\n\nSaludos cordiales,\nEquipo SGCR",
                'activo' => true
            ]
        ];

        foreach ($cuerpos as $cuerpo) {
            CuerpoCorreo::create($cuerpo);
        }
    }
}
