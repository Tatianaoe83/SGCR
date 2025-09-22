<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel de Control de Algolia') }}
        </h2>
    </x-slot>

    @push('scripts')
        <meta name="csrf-token" content="{{ csrf_token() }}">
    @endpush

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">
                            🔍 Panel de Control de Algolia
                        </h2>
                        <div class="flex space-x-2">
                            <button id="refresh-status" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Actualizar
                            </button>
                            <button id="reindex-all" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Reindexar Todo
                            </button>
                        </div>
                    </div>

                    <!-- Estado de Configuración -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold mb-2">🛠️ Configuración</h3>
                            <div id="config-status" class="space-y-2">
                                <div class="flex justify-between">
                                    <span>Driver:</span>
                                    <span id="scout-driver" class="font-mono text-sm bg-gray-200 px-2 py-1 rounded">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>App ID:</span>
                                    <span id="algolia-app-id" class="font-mono text-sm bg-gray-200 px-2 py-1 rounded">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Estado:</span>
                                    <span id="algolia-status" class="font-bold">-</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold mb-2">📊 Estadísticas del Índice</h3>
                            <div id="index-stats" class="space-y-2">
                                <div class="flex justify-between">
                                    <span>Registros:</span>
                                    <span id="total-records" class="font-bold text-blue-600">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Tamaño:</span>
                                    <span id="index-size" class="font-mono text-sm">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Última construcción:</span>
                                    <span id="last-build" class="text-sm text-gray-600">-</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold mb-2">📚 Documentos</h3>
                            <div id="document-stats" class="space-y-2">
                                <div class="flex justify-between">
                                    <span>Total:</span>
                                    <span id="total-docs" class="font-bold">-</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Indexables:</span>
                                    <span id="searchable-docs" class="font-bold text-green-600">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Búsqueda de Prueba -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-4">🔍 Prueba de Búsqueda</h3>
                        <div class="flex space-x-4">
                            <input 
                                type="text" 
                                id="search-input" 
                                placeholder="Escribe tu consulta aquí..."
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                            <button id="search-btn" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg">
                                Buscar
                            </button>
                        </div>
                        
                        <div id="search-results" class="mt-4 hidden">
                            <h4 class="font-semibold mb-2">Resultados:</h4>
                            <div id="results-container" class="space-y-2 max-h-96 overflow-y-auto"></div>
                        </div>
                    </div>

                    <!-- Documentos Indexados -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">📋 Documentos Indexados</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white border border-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                                        <th class="px-6 py-3 border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contenido</th>
                                        <th class="px-6 py-3 border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Palabras Clave</th>
                                        <th class="px-6 py-3 border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody id="documents-table" class="bg-white divide-y divide-gray-200">
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                            Cargando documentos...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div id="pagination" class="mt-4 flex justify-center space-x-2 hidden">
                            <button id="prev-page" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 disabled:opacity-50" disabled>
                                Anterior
                            </button>
                            <span id="page-info" class="px-4 py-2">Página 1</span>
                            <button id="next-page" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 disabled:opacity-50">
                                Siguiente
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentPage = 0;
        const perPage = 10;
        
        // Función para obtener headers con autenticación
        function getHeaders(includeCSRF = false) {
            const headers = {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            };
            
            if (includeCSRF) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                if (csrfToken) {
                    headers['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
                }
            }
            
            return headers;
        }
        
        // Función para manejar errores de respuesta
        async function handleResponse(response) {
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        }
        
        // Mostrar mensaje de error al usuario
        function showError(message, element = null) {
            console.error('Error:', message);
            if (element) {
                element.innerHTML = `<span class="text-red-500">Error: ${message}</span>`;
            }
            
            // Mostrar notificación temporal
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
            notification.textContent = `Error: ${message}`;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }

        // Cargar configuración y estado inicial
        async function loadConfiguration() {
            try {
                const response = await fetch('/api/algolia/config', {
                    method: 'GET',
                    headers: getHeaders()
                });
                
                const data = await handleResponse(response);
                
                document.getElementById('scout-driver').textContent = data.scout.driver;
                document.getElementById('algolia-app-id').textContent = data.algolia.app_id || 'No configurado';
                document.getElementById('algolia-status').textContent = data.algolia.configured ? '✅ Configurado' : '❌ No configurado';
                document.getElementById('algolia-status').className = data.algolia.configured ? 'font-bold text-green-600' : 'font-bold text-red-600';
                
                document.getElementById('total-docs').textContent = data.models.WordDocument.total_records;
                document.getElementById('searchable-docs').textContent = data.models.WordDocument.searchable_records;
            } catch (error) {
                showError(error.message);
                // Mostrar valores por defecto en caso de error
                document.getElementById('scout-driver').textContent = 'Error';
                document.getElementById('algolia-app-id').textContent = 'Error';
                document.getElementById('algolia-status').textContent = '❌ Error';
                document.getElementById('algolia-status').className = 'font-bold text-red-600';
            }
        }

        // Cargar información del índice
        async function loadIndexInfo() {
            try {
                const response = await fetch('/api/algolia/index-info', {
                    method: 'GET',
                    headers: getHeaders()
                });
                
                const data = await handleResponse(response);
                
                if (data.success) {
                    document.getElementById('total-records').textContent = data.statistics.total_records;
                    document.getElementById('index-size').textContent = Math.round(data.statistics.size_bytes / 1024) + ' KB';
                    document.getElementById('last-build').textContent = data.statistics.last_build_time ? 
                        new Date(data.statistics.last_build_time * 1000).toLocaleString() : 'N/A';
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            } catch (error) {
                showError(`Error cargando información del índice: ${error.message}`);
                document.getElementById('total-records').textContent = 'Error';
                document.getElementById('index-size').textContent = 'Error';
                document.getElementById('last-build').textContent = 'Error';
            }
        }

        // Cargar documentos indexados
        async function loadDocuments(page = 0) {
            try {
                const response = await fetch(`/api/algolia/documents?page=${page}&limit=${perPage}`, {
                    method: 'GET',
                    headers: getHeaders()
                });
                
                const data = await handleResponse(response);
                const tbody = document.getElementById('documents-table');
                tbody.innerHTML = '';
                
                if (data.success && data.data.length > 0) {
                    data.data.forEach(doc => {
                        const row = tbody.insertRow();
                        row.innerHTML = `
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${doc.id}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${doc.title || 'Sin título'}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="max-w-xs truncate" title="${doc.content_preview || ''}">
                                    ${doc.content_preview || 'Sin contenido'}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="flex flex-wrap gap-1">
                                    ${(doc.keywords || []).slice(0, 3).map(keyword => 
                                        `<span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">${keyword}</span>`
                                    ).join('')}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ${doc.created_at ? new Date(doc.created_at).toLocaleDateString() : 'N/A'}
                            </td>
                        `;
                    });
                    
                    // Actualizar paginación
                    document.getElementById('pagination').classList.remove('hidden');
                    document.getElementById('page-info').textContent = `P\u00E1gina ${page + 1} de ${data.pagination.total_pages}`;
                    document.getElementById('prev-page').disabled = page === 0;
                    document.getElementById('next-page').disabled = page >= data.pagination.total_pages - 1;
                    currentPage = page;
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">No hay documentos indexados</td></tr>';
                    document.getElementById('pagination').classList.add('hidden');
                }
            } catch (error) {
                showError(`Error cargando documentos: ${error.message}`);
                document.getElementById('documents-table').innerHTML = 
                    '<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">Error cargando documentos</td></tr>';
                document.getElementById('pagination').classList.add('hidden');
            }
        }

        // Realizar búsqueda
        async function performSearch() {
            const query = document.getElementById('search-input').value.trim();
            if (!query) {
                showError('Por favor ingresa una consulta de b\u00FAsqueda');
                return;
            }

            const searchBtn = document.getElementById('search-btn');
            const originalText = searchBtn.textContent;
            searchBtn.textContent = 'Buscando...';
            searchBtn.disabled = true;

            try {
                const response = await fetch('/api/algolia/search', {
                    method: 'POST',
                    headers: getHeaders(true),
                    body: JSON.stringify({ query, limit: 5 })
                });
                
                const data = await handleResponse(response);
                const resultsContainer = document.getElementById('results-container');
                const searchResults = document.getElementById('search-results');
                
                resultsContainer.innerHTML = '';
                
                if (data.success && data.results.data.length > 0) {
                    data.results.data.forEach(doc => {
                        const resultDiv = document.createElement('div');
                        resultDiv.className = 'p-4 border border-gray-200 rounded-lg hover:bg-gray-50';
                        resultDiv.innerHTML = `
                            <h5 class="font-semibold text-blue-600">${doc.title || 'Sin título'}</h5>
                            <p class="text-sm text-gray-600 mt-1">${doc.content_preview || 'Sin contenido'}</p>
                            <div class="mt-2 flex flex-wrap gap-1">
                                ${(doc.keywords || []).slice(0, 5).map(keyword => 
                                    `<span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded">${keyword}</span>`
                                ).join('')}
                            </div>
                            <div class="mt-2 text-xs text-gray-500">
                                ID: ${doc.id} | Fecha: ${doc.created_at ? new Date(doc.created_at).toLocaleDateString() : 'N/A'}
                            </div>
                        `;
                        resultsContainer.appendChild(resultDiv);
                    });
                    searchResults.classList.remove('hidden');
                } else {
                    resultsContainer.innerHTML = '<p class="text-gray-500 text-center py-4">No se encontraron resultados para tu b\u00FAsqueda</p>';
                    searchResults.classList.remove('hidden');
                }
            } catch (error) {
                showError(`Error en la b\u00FAsqueda: ${error.message}`);
                document.getElementById('search-results').classList.add('hidden');
            } finally {
                searchBtn.textContent = originalText;
                searchBtn.disabled = false;
            }
        }

        // Event listeners
        document.getElementById('refresh-status').addEventListener('click', () => {
            const btn = document.getElementById('refresh-status');
            const originalText = btn.textContent;
            btn.textContent = 'Actualizando...';
            btn.disabled = true;
            
            Promise.all([
                loadConfiguration(),
                loadIndexInfo(),
                loadDocuments(0)
            ]).finally(() => {
                btn.textContent = originalText;
                btn.disabled = false;
            });
        });

        document.getElementById('reindex-all').addEventListener('click', async () => {
            if (confirm('\u00BFEst\u00E1s seguro de que quieres reindexar todos los documentos? Este proceso puede tardar varios minutos.')) {
                const btn = document.getElementById('reindex-all');
                const originalText = btn.textContent;
                btn.textContent = 'Reindexando...';
                btn.disabled = true;
                
                try {
                    const response = await fetch('/api/algolia/reindex', { 
                        method: 'POST',
                        headers: getHeaders(true)
                    });
                    
                    const data = await handleResponse(response);
                    
                    if (data.success) {
                        // Mostrar notificación de éxito
                        const notification = document.createElement('div');
                        notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
                        notification.textContent = `Reindexaci\u00F3n completada: ${data.indexed_documents} documentos indexados`;
                        document.body.appendChild(notification);
                        
                        setTimeout(() => {
                            notification.remove();
                        }, 5000);
                        
                        // Recargar información
                        loadIndexInfo();
                        loadDocuments(0);
                    } else {
                        throw new Error(data.message || 'Error en la reindexaci\u00F3n');
                    }
                } catch (error) {
                    showError(`Error durante la reindexaci\u00F3n: ${error.message}`);
                } finally {
                    btn.textContent = originalText;
                    btn.disabled = false;
                }
            }
        });

        document.getElementById('search-btn').addEventListener('click', performSearch);
        document.getElementById('search-input').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') performSearch();
        });

        document.getElementById('prev-page').addEventListener('click', () => {
            if (currentPage > 0) loadDocuments(currentPage - 1);
        });

        document.getElementById('next-page').addEventListener('click', () => {
            loadDocuments(currentPage + 1);
        });

        // Cargar datos iniciales
        loadConfiguration();
        loadIndexInfo();
        loadDocuments(0);
    </script>
</x-app-layout>
