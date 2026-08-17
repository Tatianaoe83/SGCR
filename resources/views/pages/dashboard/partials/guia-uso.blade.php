{{--
    Guía de uso de Bob (asistente del SGC).
    Contenido del modal #guiaModal del dashboard.
    Las capacidades descritas corresponden a lo que hoy hace HybridChatbotService.
--}}

@php
    $guiaSecciones = [
        ['id' => 'g-resumen', 'titulo' => 'Resumen'],
        ['id' => 'g-mapa', 'titulo' => 'Mapa de funcionalidades'],
        ['id' => 'g-capacidades', 'titulo' => 'Capacidades y casos de uso'],
        ['id' => 'g-limites', 'titulo' => 'Limitaciones'],
        ['id' => 'g-preguntar', 'titulo' => 'Ejemplos de preguntas'],
    ];
@endphp

<div class="flex h-full min-h-0">

    {{-- Índice lateral --}}
    <nav class="hidden lg:flex w-64 shrink-0 flex-col border-r border-slate-200 dark:border-slate-700">
        <div class="p-3 border-b border-slate-200 dark:border-slate-700">
            <label for="guiaBuscador" class="sr-only">Buscar en la guía</label>
            <div class="relative">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                </svg>
                <input
                    type="search"
                    id="guiaBuscador"
                    placeholder="Buscar en la guía…"
                    autocomplete="off"
                    class="w-full h-9 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 pl-9 pr-3 text-[13px] text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-amber-300" />
            </div>
        </div>

        <div class="flex-1 min-h-0 overflow-y-auto p-3">
            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500 px-2 mb-1.5">
                Contenido
            </div>
            <ol class="space-y-0.5">
                @foreach ($guiaSecciones as $i => $sec)
                    <li data-guia-nav-item="{{ $sec['id'] }}">
                        <button
                            type="button"
                            data-guia-anchor="{{ $sec['id'] }}"
                            class="guia-nav-link w-full text-left flex items-start gap-2 rounded-xl px-2 py-1.5 text-[13px] text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 transition-colors cursor-pointer">
                            <span class="text-[11px] font-mono text-slate-400 dark:text-slate-500 pt-0.5">{{ $i + 1 }}</span>
                            <span>{{ $sec['titulo'] }}</span>
                        </button>
                    </li>
                @endforeach
            </ol>
            <p id="guiaSinResultados" class="hidden px-2 py-3 text-[12px] text-slate-500 dark:text-slate-400">
                Sin coincidencias en el índice.
            </p>
        </div>
    </nav>

    {{-- Contenido --}}
    <div class="flex-1 min-h-0 flex flex-col">

        {{-- Navegación compacta en móvil/tablet --}}
        <div class="lg:hidden shrink-0 border-b border-slate-200 dark:border-slate-700 p-3">
            <label for="guiaSelector" class="sr-only">Ir a una sección</label>
            <select
                id="guiaSelector"
                class="w-full h-10 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-[13px] text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-amber-300 cursor-pointer">
                @foreach ($guiaSecciones as $i => $sec)
                    <option value="{{ $sec['id'] }}">{{ $i + 1 }}. {{ $sec['titulo'] }}</option>
                @endforeach
            </select>
        </div>

        <div id="guiaContenido" class="flex-1 min-h-0 overflow-y-auto px-5 sm:px-6 py-5 space-y-8 text-sm text-slate-700 dark:text-slate-300 leading-relaxed">

        {{-- 1. RESUMEN EJECUTIVO --}}
        <section id="g-resumen" class="scroll-mt-4 space-y-3">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">1. Resumen ejecutivo</h3>


            <div class="rounded-2xl border border-amber-200 dark:border-amber-800/50 bg-amber-50 dark:bg-amber-950/20 p-4">
                <div class="font-semibold text-slate-900 dark:text-slate-100 mb-1"></div>
                Bob no es un buscador de internet ni un ChatGPT general: es un lector experto de <em>tus</em> documentos.
                Mientras más concreta sea tu pregunta (nombre, folio, área, puesto), mejor responde.
            </div>

            <ul class="list-disc pl-5 space-y-1">
                <li><strong>Sirve para:</strong> encontrar el documento correcto, entender su contenido, saber qué te aplica según tu puesto y quién es responsable de qué.</li>
                <li><strong>No sirve para:</strong> conocimiento general, cálculos de negocio, redactar documentos nuevos, ni consultar información que no esté cargada en el SGC.</li>
            </ul>
        </section>

        {{-- 2. MAPA DE FUNCIONALIDADES --}}
        <section id="g-mapa" class="scroll-mt-4 space-y-3">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">2. Mapa de funcionalidades</h3>

            <div class="overflow-x-auto">
                <table class="w-full text-[13px] rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700">
                    <thead class="bg-slate-100 dark:bg-slate-800">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold text-slate-900 dark:text-slate-100">Función</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-900 dark:text-slate-100">Qué hace</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-900 dark:text-slate-100">Cómo se activa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <tr class="align-top">
                            <td class="px-3 py-2 font-medium text-slate-900 dark:text-slate-100">Búsqueda de documentos</td>
                            <td class="px-3 py-2">Localiza elementos del SGC por nombre, folio o tema.</td>
                            <td class="px-3 py-2 font-mono text-[12px]">
                                <ul class="list-disc pl-4 space-y-0.5">
                                    <li>"procedimiento de contratación de personal"</li>
                                    <li>"muéstrame el PAA01-PR04"</li>
                                    <li>"procedimiento para dar de alta al personal"</li>
                                </ul>
                            </td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-medium text-slate-900 dark:text-slate-100">Lectura del documento</td>
                            <td class="px-3 py-2">Contesta sobre objetivo, alcance, pasos, riesgos, definiciones del documento en foco.</td>
                            <td class="px-3 py-2 font-mono text-[12px]">
                                <ul class="list-disc pl-4 space-y-0.5">
                                    <li>"¿cuál es el alcance del PAA01-PR04?"</li>
                                    <li>"dame las actividades del PAA01-PR04"</li>
                                    <li>"¿qué riesgos menciona el PAA01-PR04?"</li>
                                </ul>
                            </td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-medium text-slate-900 dark:text-slate-100">Metadatos del documento</td>
                            <td class="px-3 py-2">Datos de ficha: folio, versión, responsable, unidades, puestos vinculados y fechas.</td>
                            <td class="px-3 py-2 font-mono text-[12px]">
                                <ul class="list-disc pl-4 space-y-0.5">
                                    <li>"¿qué versión tiene el PAA01-PR04?"</li>
                                    <li>"¿a qué unidades de negocio aplica el PAA01-PR04?"</li>
                                    <li>"¿quién es el responsable del PAA01-PR04?"</li>
                                </ul>
                            </td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-medium text-slate-900 dark:text-slate-100">Mis documentos</td>
                            <td class="px-3 py-2">Lista los elementos ligados al puesto con el que iniciaste sesión.</td>
                            <td class="px-3 py-2 font-mono text-[12px]">
                                <ul class="list-disc pl-4 space-y-0.5">
                                    <li>"mis procedimientos"</li>
                                    <li>"mis documentos"</li>
                                    <li>"documento del puesto Analista de Calidad"</li>
                                </ul>
                            </td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-medium text-slate-900 dark:text-slate-100">Listas por área</td>
                            <td class="px-3 py-2">Documentos de un área organizacional, con filtro por tipo.</td>
                            <td class="px-3 py-2 font-mono text-[12px]">
                                <ul class="list-disc pl-4 space-y-0.5">
                                    <li>"procedimientos del área de Calidad"</li>
                                    <li>"listado por área de Jurídico"</li>
                                    <li>"procedimientos del área de TI"</li>
                                </ul>
                            </td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-medium text-slate-900 dark:text-slate-100">Listas por unidad</td>
                            <td class="px-3 py-2">Documentos de una unidad de negocio.</td>
                            <td class="px-3 py-2 font-mono text-[12px]">
                                <ul class="list-disc pl-4 space-y-0.5">
                                    <li>"documentos de la unidad Corporativo"</li>
                                    <li>"lista los procedimientos de la unidad Corporativo"</li>
                                </ul>
                            </td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-medium text-slate-900 dark:text-slate-100">Listas por puesto</td>
                            <td class="px-3 py-2">Documentos ligados a un puesto concreto, no al tuyo.</td>
                            <td class="px-3 py-2 font-mono text-[12px]">
                                <ul class="list-disc pl-4 space-y-0.5">
                                    <li>"documento del puesto Analista de Calidad"</li>
                                    <li>"qué procedimiento tiene el puesto Analista de Calidad"</li>
                                </ul>
                            </td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-medium text-slate-900 dark:text-slate-100">Documentos relacionados</td>
                            <td class="px-3 py-2">Elementos vinculados al documento en foco: relacionados, padres e hijos.</td>
                            <td class="px-3 py-2 font-mono text-[12px]">
                                <ul class="list-disc pl-4 space-y-0.5">
                                    <li>"documentos relacionados del PAA01-PR04"</li>
                                    <li>"documentos vinculados al PAA01-PR02"</li>
                                </ul>
                            </td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-medium text-slate-900 dark:text-slate-100">Directorio</td>
                            <td class="px-3 py-2">Quién ocupa un puesto y quién es responsable de un área o unidad.</td>
                            <td class="px-3 py-2 font-mono text-[12px]">
                                <ul class="list-disc pl-4 space-y-0.5">
                                    <li>"¿quién es el coordinador de TI?"</li>
                                    <li>"¿quién es el director de Construcción?"</li>
                                    <li>"¿quién ocupa el puesto de Analista de Calidad?"</li>
                                </ul>
                            </td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-medium text-slate-900 dark:text-slate-100">Estructura de la empresa</td>
                            <td class="px-3 py-2">Catálogo de unidades, divisiones y directores.</td>
                            <td class="px-3 py-2 font-mono text-[12px]">
                                <ul class="list-disc pl-4 space-y-0.5">
                                    <li>"¿qué unidades de negocio hay en la empresa?"</li>
                                    <li>"¿quiénes son los directores de las unidades?"</li>
                                    <li>"¿qué puestos hay en el área de Calidad?"</li>
                                </ul>
                            </td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-medium text-slate-900 dark:text-slate-100">Tu identidad</td>
                            <td class="px-3 py-2">Te dice tu usuario, puesto y unidad según tu sesión.</td>
                            <td class="px-3 py-2 font-mono text-[12px]">
                                <ul class="list-disc pl-4 space-y-0.5">
                                    <li>"¿quién soy?"</li>
                                    <li>"¿cómo me llamo?"</li>
                                    <li>"¿cuál es mi nombre?"</li>
                                </ul>
                            </td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-medium text-slate-900 dark:text-slate-100">Menú inicial</td>
                            <td class="px-3 py-2">Reinicia la conversación y muestra qué puede hacer.</td>
                            <td class="px-3 py-2 font-mono text-[12px]">
                                <ul class="list-disc pl-4 space-y-0.5">
                                    <li>"hola"</li>
                                    <li>"inicio"</li>
                                </ul>
                            </td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-medium text-slate-900 dark:text-slate-100">Reiniciar contexto</td>
                            <td class="px-3 py-2">Borra el documento en foco y empieza de cero.</td>
                            <td class="px-3 py-2 font-mono text-[12px]">
                                <ul class="list-disc pl-4 space-y-0.5">
                                    <li>"olvida"</li>
                                    <li>"reinicia"</li>
                                    <li>"limpia"</li>
                                </ul>
                            </td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-medium text-slate-900 dark:text-slate-100">Ficha + PDF</td>
                            <td class="px-3 py-2">Muestra tarjeta del documento con folio, versión y botón <em>Ver Documento</em>.</td>
                            <td class="px-3 py-2">Automático al identificar el documento</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- 3. CAPACIDADES Y CASOS DE USO --}}
        <section id="g-capacidades" class="scroll-mt-4 space-y-4">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">3. Capacidades y casos de uso</h3>

            <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 p-4 space-y-2">
                <div class="font-semibold text-slate-900 dark:text-slate-100">a) Encontrar el documento correcto</div>
                <p>Puedes buscar de tres formas, de la más precisa a la más vaga:</p>
                <ul class="list-disc pl-5 space-y-1">
                    <li><strong>Por folio</strong> (lo más exacto): <span class="font-mono text-[12px]">"muéstrame el PAA01-PR04"</span>.</li>
                    <li><strong>Por nombre</strong>: <span class="font-mono text-[12px]">"procedimiento de contratación de personal"</span>.</li>
                    <li><strong>Por tema</strong>: <span class="font-mono text-[12px]">"procedimiento para dar de alta al personal"</span>.</li>
                </ul>
                <p class="text-[13px] text-slate-500 dark:text-slate-400">
                    Recomendación: si conoces el folio, úsalo. La búsqueda por tema usa coincidencia de texto y similitud
                    semántica, por lo que puede traer varios candidatos y pedirte que elijas.
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 p-4 space-y-2">
                <div class="font-semibold text-slate-900 dark:text-slate-100">b) Entender el contenido de un documento</div>
                <p>
                    Cuando Bob ya tiene un documento en foco, las preguntas siguientes se responden sobre ese texto.
                    Funciona bien con: <strong>objetivo, alcance, definiciones, responsables, pasos o actividades, riesgos,
                    criterios y registros</strong>.
                </p>
                <p class="text-[13px] text-slate-500 dark:text-slate-400">
                    Si preguntas algo que no está en el documento, te lo dice y limpia el contexto en lugar de inventar.
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 p-4 space-y-2">
                <div class="font-semibold text-slate-900 dark:text-slate-100">c) Saber qué te aplica a ti</div>
                <p>
                    <span class="font-mono text-[12px]">"mis procedimientos"</span> usa el puesto ligado a tu usuario.
                    Si tu usuario no tiene puesto asignado, Bob no puede deducirlo: pídelo por nombre de puesto
                    (<span class="font-mono text-[12px]">"documento del puesto Analista de Calidad"</span>).
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 p-4 space-y-2">
                <div class="font-semibold text-slate-900 dark:text-slate-100">d) Listas y catálogos</div>
                <ul class="list-disc pl-5 space-y-1">
                    <li>Por área: <span class="font-mono text-[12px]">"procedimientos del área de Calidad"</span></li>
                    <li>Por unidad de negocio: <span class="font-mono text-[12px]">"documentos de la unidad Corporativo"</span></li>
                    <li>Por puesto: <span class="font-mono text-[12px]">"qué procedimiento tiene el puesto Analista de Calidad"</span></li>
                    <li>Relacionados: <span class="font-mono text-[12px]">"documentos relacionados del PAA01-PR04"</span></li>
                </ul>
                <p class="text-[13px] text-slate-500 dark:text-slate-400">
                    Hoy el inventario solo tiene procesos, procedimientos, evidencias y políticas. Pedir "formatos",
                    "manuales" o "registros" no filtra nada: te devolverá procedimientos igual.
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 p-4 space-y-2">
                <div class="font-semibold text-slate-900 dark:text-slate-100">e) Directorio y organización</div>
                <p>
                    Quién ocupa un puesto, quiénes son los directores, qué puestos tiene un área o unidad.
                    Bob distingue entre <em>"responsable de un procedimiento"</em> (dato del documento) y
                    <em>"responsable de un área"</em> (dato del directorio); si mezclas ambos, pídelo en dos consultas.
                </p>
            </div>
        </section>

        {{-- 4. LIMITACIONES --}}
        <section id="g-limites" class="scroll-mt-4 space-y-3">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">4. Limitaciones</h3>

            <div class="rounded-2xl border border-rose-200 dark:border-rose-900/50 bg-rose-50 dark:bg-rose-950/20 p-4">
                <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">Lo que Bob no puede hacer</div>
                <ul class="list-disc pl-5 space-y-1">
                    <li><strong>No consulta internet</strong> ni usa conocimiento general: su única fuente es el SGC.</li>
                    <li><strong>No ve documentos que no estén cargados y publicados</strong> en el sistema. Si un procedimiento existe en papel o en el correo, para Bob no existe.</li>
                    <li><strong>No crea, edita ni firma documentos</strong>, no cambia versiones ni estatus.</li>
                    <li><strong>No recibe archivos adjuntos</strong> ni imágenes: solo texto escrito o dictado.</li>
                    <li><strong>No hace cálculos de negocio</strong> (costos, nómina, avances de obra) ni analiza bases de datos fuera del SGC.</li>
                    <li><strong>No da información personal</strong> más allá de la relación puesto–persona del directorio.</li>
                </ul>
            </div>

            <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 p-4 space-y-2">
                <div class="font-semibold text-slate-900 dark:text-slate-100">Preguntas sin sentido: no esperes una respuesta lógica</div>
                <p>
                    Bob no razona por su cuenta: <strong>busca</strong> en los documentos y el directorio, y redacta con
                    lo que encuentra. Si la pregunta no apunta a nada que exista ahí, la búsqueda regresa vacía o, peor,
                    engancha un documento parecido que no tiene que ver. La respuesta se ve segura, pero no sirve.
                </p>
                <ul class="list-disc pl-5 space-y-1">
                    <li><strong>Sin referencia real:</strong> <span class="font-mono text-[12px]">"¿y eso cómo?"</span>, <span class="font-mono text-[12px]">"dime algo"</span>, <span class="font-mono text-[12px]">"asdf"</span> → no hay tema que buscar.</li>
                    <li><strong>Fuera del SGC:</strong> <span class="font-mono text-[12px]">"¿cuánto cuesta el cemento?"</span>, <span class="font-mono text-[12px]">"¿cómo va la obra?"</span> → esos datos no viven en los documentos.</li>
                    <li><strong>Opiniones o predicciones:</strong> <span class="font-mono text-[12px]">"¿está bien hecho este procedimiento?"</span>, <span class="font-mono text-[12px]">"¿me van a auditar?"</span> → no hay fuente que consultar.</li>
                    <li><strong>Mezcla de temas:</strong> directorio y contenido de un documento en el mismo mensaje → se queda con uno y el otro se pierde.</li>
                    <li><strong>Documentos inventados:</strong> pedir un folio que no existe → dirá que no lo encontró, no lo va a suponer.</li>
                </ul>
                <p class="text-[13px] text-slate-500 dark:text-slate-400">
                    Si te contesta con un documento que no era, no insistas con la misma frase: escribe
                    <span class="font-mono text-[12px]">"olvida"</span> y vuelve a preguntar nombrando el documento o el folio.
                </p>
            </div>
        </section>

        {{-- 5. PREGUNTAS QUE FUNCIONAN --}}
        <section id="g-preguntar" class="scroll-mt-4 space-y-3">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">5. Ejemplos de preguntas</h3>

            <div class="overflow-x-auto">
                <table class="w-full text-[13px] rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700">
                    <thead class="bg-slate-100 dark:bg-slate-800">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold text-rose-700 dark:text-rose-300">Consulta incompleta</th>
                            <th class="px-3 py-2 text-left font-semibold text-emerald-700 dark:text-emerald-300">Consulta correcta</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <tr class="align-top">
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-500 dark:text-slate-400">"info de nómina"</td>
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-900 dark:text-slate-100">"muéstrame el PAA01-PR04"</td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-500 dark:text-slate-400">"¿y el alcance?"</td>
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-900 dark:text-slate-100">"¿cuál es el alcance del PAA01-PR04?"</td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-500 dark:text-slate-400">"los pasos"</td>
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-900 dark:text-slate-100">"dame las actividades del PAA01-PR04"</td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-500 dark:text-slate-400">"¿qué versión?"</td>
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-900 dark:text-slate-100">"¿qué versión tiene el PAA01-PR04?"</td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-500 dark:text-slate-400">"¿quién lo hace?"</td>
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-900 dark:text-slate-100">"¿quién es el responsable del PAA01-PR04?"</td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-500 dark:text-slate-400">"documentos parecidos"</td>
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-900 dark:text-slate-100">"documentos relacionados del PAA01-PR04"</td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-500 dark:text-slate-400">"¿qué me toca?"</td>
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-900 dark:text-slate-100">"mis procedimientos"</td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-500 dark:text-slate-400">"cosas de calidad"</td>
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-900 dark:text-slate-100">"procedimientos del área de Calidad"</td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-500 dark:text-slate-400">"cosas de sistemas"</td>
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-900 dark:text-slate-100">"procedimientos del área de TI"</td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-500 dark:text-slate-400">"corporativo"</td>
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-900 dark:text-slate-100">"documentos de la unidad Corporativo"</td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-500 dark:text-slate-400">"los del gerente"</td>
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-900 dark:text-slate-100">"qué procedimiento tiene el puesto Analista de Calidad"</td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-500 dark:text-slate-400">"¿quién es el responsable?"</td>
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-900 dark:text-slate-100">"¿quién ocupa el puesto de Analista de Calidad?"</td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-500 dark:text-slate-400">"dime de la empresa"</td>
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-900 dark:text-slate-100">"¿qué unidades de negocio hay en la empresa?"</td>
                        </tr>
                        <tr class="align-top">
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-500 dark:text-slate-400">"¿cuál es mi puesto?"</td>
                            <td class="px-3 py-2 font-mono text-[12px] text-slate-900 dark:text-slate-100">"¿quién soy?"</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="font-semibold text-slate-900 dark:text-slate-100 pt-2">Más preguntas que puedes hacerle</div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 p-4">
                    <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">Sobre un documento</div>
                    <ul class="list-disc pl-4 space-y-0.5 font-mono text-[12px] text-slate-700 dark:text-slate-300">
                        <li>"dame el objetivo del PAA01-PR02"</li>
                        <li>"¿qué definiciones tiene el PAA01-PR04?"</li>
                        <li>"¿en qué unidades aplica el PAA01-PR02?"</li>
                        <li>"¿qué documento es el PAA04-PR01?"</li>
                        <li>"¿qué riesgos menciona el PAA01-PR04?"</li>
                        <li>"documentos relacionados del PAA01-PR02"</li>
                        <li>"documentos vinculados al PAA01-PR02"</li>
                    </ul>
                </div>
                <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 p-4">
                    <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">Listas y catálogos</div>
                    <ul class="list-disc pl-4 space-y-0.5 font-mono text-[12px] text-slate-700 dark:text-slate-300">
                        <li>"procedimientos del área de Capital Humano"</li>
                        <li>"procedimientos del área de TI"</li>
                        <li>"listado por área de Jurídico"</li>
                        <li>"lista los procedimientos de la unidad Corporativo"</li>
                        <li>"qué procedimiento tiene el puesto Analista de Calidad"</li>
                        <li>"documento del puesto Analista de Calidad"</li>
                        <li>"mis documentos"</li>
                    </ul>
                </div>
                <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 p-4">
                    <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">Personas y organización</div>
                    <ul class="list-disc pl-4 space-y-0.5 font-mono text-[12px] text-slate-700 dark:text-slate-300">
                        <li>"¿quién es el coordinador de TI?"</li>
                        <li>"¿quién es el director de Construcción?"</li>
                        <li>"¿quién es el jefe de Calidad de Concretos?"</li>
                        <li>"¿qué puestos hay en el área de Calidad?"</li>
                        <li>"¿quiénes son los directores de las unidades?"</li>
                        <li>"¿cómo me llamo?"</li>
                    </ul>
                </div>
                <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/40 p-4">
                    <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">Manejo de la conversación</div>
                    <ul class="list-disc pl-4 space-y-0.5 font-mono text-[12px] text-slate-700 dark:text-slate-300">
                        <li>"hola"</li>
                        <li>"inicio"</li>
                        <li>"olvida"</li>
                        <li>"reinicia"</li>
                        <li>"limpia"</li>
                    </ul>
                </div>
            </div>
        </section>
