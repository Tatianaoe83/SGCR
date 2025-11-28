<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        <!-- Page header -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8 mt-11">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Información del Elemento</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $elemento->nombre_elemento }}</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('elementos.show', $elemento->id_elemento) }}" class="btn bg-gray-500 hover:bg-gray-600 text-white">
                    <span>Ver Detalles</span>
                </a>
                <a href="{{ route('elementos.index') }}" class="btn bg-gray-500 hover:bg-gray-600 text-white">
                    <span>Volver a la Lista</span>
                </a>
            </div>
        </div>

        <!-- Información del Elemento -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700 mb-6 p-4">
            <div class="flex items-center space-x-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">{{ $elemento->nombre_elemento }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $elemento->tipoElemento->nombre ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Pestañas -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Navegación de pestañas -->
            <div class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                <nav class="flex" aria-label="Tabs">
                    <button onclick="showTab('historial')"
                        id="tab-historial"
                        class="tab-button flex-1 px-6 py-4 text-sm font-semibold border-b-3 transition-all duration-200 relative group"
                        data-tab="historial">
                        <div class="flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Historial de seguimiento</span>
                        </div>
                    </button>
                    <button onclick="showTab('recordatorios')"
                        id="tab-recordatorios"
                        class="tab-button flex-1 px-6 py-4 text-sm font-semibold border-b-3 transition-all duration-200 relative group"
                        data-tab="recordatorios">
                        <div class="flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            <span>Recordatorios</span>
                        </div>
                    </button>
                    <button onclick="showTab('periodo')"
                        id="tab-periodo"
                        class="tab-button flex-1 px-6 py-4 text-sm font-semibold border-b-3 transition-all duration-200 relative group"
                        data-tab="periodo">
                        <div class="flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span>Periodo de revisión</span>
                        </div>
                    </button>
                </nav>
            </div>

            <!-- Contenido de las pestañas -->
            <div class="p-6">
                <!-- Pestaña: Historial de seguimiento -->
                <div id="content-historial" class="tab-content hidden">
                    <div class="mb-4">
                        <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-1">Historial del Procedimiento</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Seguimiento de participantes y responsables</p>
                    </div>
                    <div class="space-y-4">
                        <!-- Ejemplo de entrada de historial -->
                        <div class="flex items-start space-x-3 pb-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">María González</p>
                                        <span class="inline-block px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 mt-1">Responsable</span>
                                    </div>
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300">Firmado</span>
                                </div>
                                <div class="mt-2 flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span>12 Oct 2025</span>
                                    <svg class="w-4 h-4 ml-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>09:30 AM</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3 pb-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">Juan Pérez</p>
                                        <span class="inline-block px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 mt-1">Responsable</span>
                                    </div>
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300">Firmado</span>
                                </div>
                                <div class="mt-2 flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span>13 Oct 2025</span>
                                    <svg class="w-4 h-4 ml-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>14:15 PM</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3 pb-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">Carlos Ramírez</p>
                                        <span class="inline-block px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 mt-1">Participante</span>
                                    </div>
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300">Rechazado</span>
                                </div>
                                <div class="mt-2 flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span>14 Oct 2025</span>
                                    <svg class="w-4 h-4 ml-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>11:45 AM</span>
                                </div>
                                <div class="mt-3 bg-red-50 dark:bg-red-900/20 rounded-lg p-3">
                                    <div class="flex items-start">
                                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                        <div>
                                            <p class="font-medium text-red-800 dark:text-red-300 text-sm">Motivo del rechazo:</p>
                                            <p class="text-sm text-red-700 dark:text-red-400 mt-1">Los términos de pago no son aceptables. Se requiere un anticipo del 50%.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">Laura Sánchez</p>
                                        <span class="inline-block px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 mt-1">Responsable</span>
                                    </div>
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">Pendiente</span>
                                </div>
                                <div class="mt-2 flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <span class="text-gray-400">Sin fecha asignada</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pestaña: Recordatorios -->
                <div id="content-recordatorios" class="tab-content hidden">
                    <div class="mb-4 flex justify-between items-center">
                        <div>
                            <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-1">Recordatorios</h2>
                        </div>
                        <button class="btn bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-200 dark:hover:bg-gray-600 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            <span>Nuevo Recordatorio</span>
                        </button>
                    </div>
                    <div class="space-y-4">
                        <!-- Recordatorio 1 -->
                        <div class="bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 p-4 shadow-sm">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-600 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </div>
                                    <div class="flex space-x-2">
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-500 text-white">Automático</span>
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-500 text-white">Enviado</span>
                                    </div>
                                </div>
                                <button class="text-gray-400 hover:text-red-600 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                            <p class="text-gray-800 dark:text-gray-100 mb-3">Recordatorio automático: Tiene un documento pendiente de firma desde hace 2 días.</p>
                            <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400 mb-3">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <span>1 destinatario(s)</span>
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span>16 Oct 2025</span>
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>09:00 AM</span>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300">Roberto Méndez</span>
                            </div>
                        </div>

                        <!-- Recordatorio 2 -->
                        <div class="bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 p-4 shadow-sm">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-600 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div class="flex space-x-2">
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-500 text-white">Programado</span>
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-500 text-white">Programado</span>
                                    </div>
                                </div>
                                <button class="text-gray-400 hover:text-red-600 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                            <p class="text-gray-800 dark:text-gray-100 mb-3">Por favor, revisen y firmen el documento a la brevedad.</p>
                            <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400 mb-3">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <span>2 destinatario(s)</span>
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span>18 Oct 2025</span>
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>10:00 AM</span>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300">Roberto Méndez</span>
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300">Laura Sánchez</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pestaña: Periodo de revisión -->
                <div id="content-periodo" class="tab-content hidden">
                    <div class="mb-4 flex justify-between items-center">
                        <div>
                            <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-1">Período de Revisión</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Manejo de vencimiento del procedimiento</p>
                        </div>
                        <button class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Estado -->
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 mb-6">
                        <div class="flex items-center mb-2">
                            <!-- Definir el color según el estado -->
                            <div class="w-3 h-3 rounded-full {{ $daysLeft <= 0 ? 'bg-red-500' : ($monthsLeft <= 1 ? 'bg-yellow-500' : ($monthsLeft <= 6 ? 'bg-yellow-500' : ($monthsLeft <= 12 ? 'bg-green-500' : 'bg-blue-500'))) }} mr-2"></div>
                            <span class="font-medium text-gray-800 dark:text-gray-100">
                                @if($daysLeft <= 0)
                                    Periodo de revisión pasado, revisa inmediatamente
                                    @elseif($monthsLeft <=1)
                                    Próxima revisión en {{ $daysLeft }} días
                                    @else
                                    {{ $daysLeft }} días restantes ({{ $monthsLeft }} meses)
                                    @endif
                                    </span>
                        </div>
                    </div>

                    <!-- Leyenda del Semáforo -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
                        <h4 class="font-medium text-gray-800 dark:text-gray-100 mb-3">Leyenda del Semáforo:</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                            <div class="flex items-center space-x-2">
                                <div class="w-4 h-4 rounded-full bg-red-500"></div>
                                <span class="text-sm text-gray-700 dark:text-gray-300">Crítico: ≤ 2 meses</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="w-4 h-4 rounded-full bg-yellow-500"></div>
                                <span class="text-sm text-gray-700 dark:text-gray-300">Advertencia: 4-6 meses</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="w-4 h-4 rounded-full bg-green-500"></div>
                                <span class="text-sm text-gray-700 dark:text-gray-300">Normal: 6-12 meses</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="w-4 h-4 rounded-full bg-blue-500"></div>
                                <span class="text-sm text-gray-700 dark:text-gray-300">Lejano: > 1 año</span>
                            </div>
                        </div>
                    </div>

                    <!-- Detalles del Período -->
                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-700 dark:text-gray-300">Fecha de inicio</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                {{ \Carbon\Carbon::now()->format('d/m/Y') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-700 dark:text-gray-300">Fecha de fin</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                @if($elemento->periodo_revision)
                                {{ \Carbon\Carbon::parse($elemento->periodo_revision)->format('d/m/Y') }}
                                @else
                                Sin fecha
                                @endif
                            </span>
                        </div>
                        <!-- <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-700 dark:text-gray-300">Próxima revisión</span>
                            <span class="font-medium text-green-600 dark:text-green-400">
                                @if($elemento->periodo_revision)
                                {{ \Carbon\Carbon::parse($elemento->periodo_revision)->addMonths(9)->format('d/m/Y') }}
                                @else
                                Sin fecha
                                @endif
                            </span>
                        </div> -->
                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-700 dark:text-gray-300">Responsable</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                {{ $elemento->puestoResponsable->nombre ?? 'Sin responsable asignado' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-gray-700 dark:text-gray-300">Recordatorios</span>
                            <button class="px-3 py-1 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Activos</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Función para mostrar la pestaña seleccionada
        function showTab(tabName) {
            // Ocultar todos los contenidos
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });

            // Remover estilos activos de todas las pestañas
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove(
                    'border-blue-500',
                    'text-blue-600',
                    'dark:text-blue-400',
                    'bg-white',
                    'dark:bg-gray-800'
                );
                button.classList.add(
                    'border-transparent',
                    'text-gray-500',
                    'hover:text-gray-700',
                    'hover:border-gray-300',
                    'dark:text-gray-400',
                    'dark:hover:text-gray-300'
                );
            });

            // Mostrar el contenido seleccionado
            const content = document.getElementById('content-' + tabName);
            if (content) {
                content.classList.remove('hidden');
            }

            // Activar la pestaña seleccionada
            const button = document.getElementById('tab-' + tabName);
            if (button) {
                button.classList.remove(
                    'border-transparent',
                    'text-gray-500',
                    'hover:text-gray-700',
                    'hover:border-gray-300',
                    'dark:text-gray-400',
                    'dark:hover:text-gray-300'
                );
                button.classList.add(
                    'border-blue-500',
                    'text-blue-600',
                    'dark:text-blue-400',
                    'bg-white',
                    'dark:bg-gray-800'
                );
            }

            // Usar hash para mantener el estado sin mostrar en la URL
            window.location.hash = tabName;
        }

        // Mostrar la pestaña inicial basada en el hash o el valor del servidor
        document.addEventListener('DOMContentLoaded', function() {
            // Primero intentar leer del hash
            let tab = window.location.hash.replace('#', '');
            // Si no hay hash, usar el valor del servidor
            if (!tab || !['historial', 'recordatorios', 'periodo'].includes(tab)) {
                tab = '{{ $tab }}';
            }
            showTab(tab);
        });

        // Escuchar cambios en el hash para navegación con botones del navegador
        window.addEventListener('hashchange', function() {
            let tab = window.location.hash.replace('#', '');
            if (['historial', 'recordatorios', 'periodo'].includes(tab)) {
                showTab(tab);
            }
        });
    </script>

    <style>
        .tab-button {
            cursor: pointer;
            position: relative;
        }

        .tab-button::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background-color: transparent;
            transition: background-color 0.2s;
        }

        .tab-button:hover::after {
            background-color: rgba(59, 130, 246, 0.3);
        }

        .tab-button.border-blue-500::after {
            background-color: rgb(59, 130, 246);
        }

        .tab-button:not(.border-blue-500):hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .dark .tab-button:not(.border-blue-500):hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
    </style>
</x-app-layout>