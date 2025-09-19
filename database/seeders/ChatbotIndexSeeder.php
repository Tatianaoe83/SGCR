<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SmartIndex;
use App\Services\NLPProcessor;

class ChatbotIndexSeeder extends Seeder
{
    public function run()
    {
        $nlpProcessor = new NLPProcessor();
        
        $sampleQueries = [
            [
                'query' => '¿Cuáles son los horarios de atención?',
                'response' => 'Nuestros horarios de atención son de lunes a viernes de 8:00 AM a 6:00 PM, y sábados de 9:00 AM a 2:00 PM. Estamos cerrados los domingos y días festivos.'
            ],
            [
                'query' => '¿Qué servicios ofrecen?',
                'response' => 'Ofrecemos servicios de gestión de calidad, consultoría empresarial, auditorías, certificaciones ISO, capacitación en sistemas de gestión y desarrollo de procesos organizacionales.'
            ],
            [
                'query' => '¿Cuál es su ubicación?',
                'response' => 'Nos encontramos ubicados en el centro de la ciudad, en la Av. Principal #123, Edificio Corporativo, Piso 5. Contamos con estacionamiento disponible para nuestros clientes.'
            ],
            [
                'query' => '¿Cómo puedo contactarlos?',
                'response' => 'Puedes contactarnos por teléfono al (555) 123-4567, por email a info@sgcr.com, o visitando nuestra oficina. También puedes usar este chat para consultas inmediatas.'
            ],
            [
                'query' => '¿Qué es un sistema de gestión de calidad?',
                'response' => 'Un sistema de gestión de calidad (SGC) es un conjunto de políticas, procesos y procedimientos que una organización implementa para asegurar que sus productos o servicios cumplan consistentemente con los requisitos del cliente y las regulaciones aplicables.'
            ],
            [
                'query' => '¿Qué certificaciones ISO manejan?',
                'response' => 'Trabajamos con diversas certificaciones ISO incluyendo ISO 9001 (Gestión de Calidad), ISO 14001 (Gestión Ambiental), ISO 45001 (Seguridad y Salud Ocupacional), e ISO 27001 (Seguridad de la Información).'
            ],
            [
                'query' => '¿Cuánto tiempo toma implementar ISO 9001?',
                'response' => 'La implementación de ISO 9001 típicamente toma entre 6 a 12 meses, dependiendo del tamaño de la organización, la complejidad de sus procesos y el nivel de compromiso del equipo. Incluye fases de diagnóstico, documentación, implementación y auditoría.'
            ],
            [
                'query' => '¿Ofrecen capacitación?',
                'response' => 'Sí, ofrecemos programas de capacitación en sistemas de gestión, auditorías internas, interpretación de normas ISO, mejora continua y herramientas de calidad. Las capacitaciones pueden ser presenciales o virtuales.'
            ],
            [
                'query' => '¿Cuáles son sus precios?',
                'response' => 'Nuestros precios varían según el tipo de servicio y las necesidades específicas de cada cliente. Te recomendamos contactarnos para una cotización personalizada y sin compromiso.'
            ],
            [
                'query' => '¿Tienen experiencia en mi sector?',
                'response' => 'Hemos trabajado con empresas de diversos sectores incluyendo manufactura, servicios, salud, educación, tecnología, construcción y comercio. Nuestro equipo adapta las soluciones a las particularidades de cada industria.'
            ]
        ];

        foreach ($sampleQueries as $item) {
            $normalizedQuery = $nlpProcessor->normalize($item['query']);
            $keywords = $nlpProcessor->extractKeywords($normalizedQuery);
            $entities = $nlpProcessor->extractEntities($normalizedQuery);

            SmartIndex::create([
                'original_query' => $item['query'],
                'normalized_query' => $normalizedQuery,
                'keywords' => $keywords,
                'entities' => $entities,
                'response' => $item['response'],
                'confidence_score' => 0.9,
                'auto_generated' => false,
                'verified' => true,
                'usage_count' => 0,
                'last_used_at' => now()
            ]);
        }

        $this->command->info('Chatbot index seeded with ' . count($sampleQueries) . ' sample queries.');
    }
}
