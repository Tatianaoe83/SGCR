{{--
    Tips de uso de Bob (asistente del SGC).
    Contenido del modal #guiaModal del dashboard.
    Las preguntas de ejemplo están verificadas contra HybridChatbotService y la BD.
--}}

@php
    // Preguntas clave por función. Una o dos por bloque: es una chuleta, no un manual.
    $noHacer = [
        ["Preguntar sin contexto", "<span class=\"font-mono\">\"¿y los pasos?\"</span> sin folio ni tema."],
        ["Fuera del SGC", "Costos, obras o cosas de internet."],
        ["Opiniones", "Si algo está bien hecho o qué te auditarán."],
        ["Dos cosas a la vez", "Una pregunta por mensaje."],
        ["Que escriba o firme", "Solo consulta documentos."],
        ["Si te da información errónea o se repite", "Escribe olvida y reformula la pregunta."],
    ];
@endphp

<div id="guiaContenido" class="h-full min-h-0 overflow-y-auto px-5 sm:px-6 py-5 space-y-5 text-sm text-slate-700 dark:text-slate-300 leading-relaxed">

    <div class="space-y-1">
        <p>Bob solo responde con lo que existe en el SGC: documentos publicados, inventario y directorio.</p>
        <p>Lo más exacto es darle el <strong>folio</strong> (<span class="font-mono text-[12px]">PAA01-PR04</span>); también entiende el nombre o el tema.</p>
    </div>

    <div>
        <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-400 mb-3">
            Cómo preguntarle
        </div>

        <ul class="list-disc pl-5 space-y-3 text-[13px] leading-relaxed marker:text-slate-400 dark:marker:text-slate-500">
            <li>
                <span class="font-semibold text-slate-900 dark:text-slate-100">Encontrar el documento:</span> dale el folio si lo tienes, o descríbelo por su tema.
                <span class="block mt-0.5 font-mono text-[12px] text-slate-600 dark:text-slate-300">¿me muestras el procedimiento PAA01-PR04?</span>
                <span class="block mt-0.5 font-mono text-[12px] text-slate-600 dark:text-slate-300">¿qué procedimiento habla de la contratación de personal?</span>
            </li>
            <li>
                <span class="font-semibold text-slate-900 dark:text-slate-100">Preguntar por ese documento:</span> una vez identificado, pídele su contenido, los datos de su ficha o los documentos ligados a él.
                <span class="block mt-0.5 font-mono text-[12px] text-slate-600 dark:text-slate-300">¿cuáles son las actividades del PAA01-PR04?</span>
                <span class="block mt-0.5 font-mono text-[12px] text-slate-600 dark:text-slate-300">¿quién es el responsable del PAA01-PR04?</span>
                <span class="block mt-0.5 font-mono text-[12px] text-slate-600 dark:text-slate-300">¿qué documentos están relacionados con el PAA01-PR04?</span>
            </li>
            <li>
                <span class="font-semibold text-slate-900 dark:text-slate-100">Pedir un listado:</span> agrupa por área, unidad de negocio, proceso o puesto; con el tuyo usa el de tu sesión.
                <span class="block mt-0.5 font-mono text-[12px] text-slate-600 dark:text-slate-300">¿qué procedimientos hay del área de Calidad?</span>
                <span class="block mt-0.5 font-mono text-[12px] text-slate-600 dark:text-slate-300">¿qué documentos hay de la unidad Corporativo?</span>
                <span class="block mt-0.5 font-mono text-[12px] text-slate-600 dark:text-slate-300">¿cuáles son mis procedimientos?</span>
            </li>
            <li>
                <span class="font-semibold text-slate-900 dark:text-slate-100">Personas y comandos:</span> el directorio te dice quién ocupa un puesto; <span class="font-mono text-[12px] text-slate-600 dark:text-slate-300">hola</span> abre el menú y <span class="font-mono text-[12px] text-slate-600 dark:text-slate-300">olvida</span> suelta el documento en curso.
                <span class="block mt-0.5 font-mono text-[12px] text-slate-600 dark:text-slate-300">¿quién es el coordinador de TI?</span>
                <span class="block mt-0.5 font-mono text-[12px] text-slate-600 dark:text-slate-300">¿qué unidades de negocio hay en la empresa?</span>
            </li>
        </ul>
    </div>

    <div class="rounded-2xl border border-rose-200 dark:border-rose-800/70 bg-rose-50 dark:bg-rose-950/40 p-4">
        <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">Qué no hacer</div>

        <ul class="grid gap-x-8 gap-y-1.5 sm:grid-cols-2 list-disc pl-5 text-[13px] leading-relaxed marker:text-rose-400 dark:marker:text-rose-400">
            @foreach ($noHacer as [$etiqueta, $detalle])
                <li>
                    <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $etiqueta }}.</span>
                    {!! $detalle !!}
                </li>
            @endforeach
        </ul>
    </div>
</div>
