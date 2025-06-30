@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold mb-6">{{ __('Explorador de API de WhatsApp') }}</h1>
        
        <div class="mb-6">
            <button id="explore-btn" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                {{ __('Explorar API') }}
            </button>
            <button id="clear-btn" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded ml-2">
                {{ __('Limpiar resultados') }}
            </button>
        </div>
        
        <div id="loading" class="hidden">
            <div class="flex items-center justify-center p-4">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                <span class="ml-2">{{ __('Explorando API...') }}</span>
            </div>
        </div>
        
        <div id="results" class="hidden">
            <div class="mb-6">
                <h2 class="text-xl font-semibold mb-2">{{ __('Información de la sesión') }}</h2>
                <div id="session-info" class="bg-gray-50 dark:bg-slate-700 p-4 rounded-lg overflow-auto max-h-60"></div>
            </div>
            
            <div class="mb-6">
                <h2 class="text-xl font-semibold mb-2">{{ __('Rutas de medios') }}</h2>
                <p class="text-sm text-gray-500 mb-2">{{ __('Las rutas con estado 200 y tipo de contenido de medios son las correctas para obtener archivos multimedia.') }}</p>
                <div id="media-routes" class="bg-gray-50 dark:bg-slate-700 p-4 rounded-lg overflow-auto max-h-96"></div>
            </div>
            
            <div class="mb-6">
                <h2 class="text-xl font-semibold mb-2">{{ __('Otros endpoints') }}</h2>
                <div id="other-endpoints" class="bg-gray-50 dark:bg-slate-700 p-4 rounded-lg overflow-auto max-h-60"></div>
            </div>
        </div>
        
        <div id="error" class="hidden">
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded" role="alert">
                <p id="error-message"></p>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const exploreBtn = document.getElementById('explore-btn');
        const clearBtn = document.getElementById('clear-btn');
        const loading = document.getElementById('loading');
        const results = document.getElementById('results');
        const error = document.getElementById('error');
        const errorMessage = document.getElementById('error-message');
        const sessionInfo = document.getElementById('session-info');
        const mediaRoutes = document.getElementById('media-routes');
        const otherEndpoints = document.getElementById('other-endpoints');
        
        exploreBtn.addEventListener('click', function() {
            loading.classList.remove('hidden');
            results.classList.add('hidden');
            error.classList.add('hidden');
            
            fetch('{{ route('whatsapp.api-explorer') }}')
                .then(response => response.json())
                .then(data => {
                    loading.classList.add('hidden');
                    
                    if (data.success) {
                        results.classList.remove('hidden');
                        
                        // Mostrar información de la sesión
                        sessionInfo.innerHTML = `<pre class="text-xs">${syntaxHighlight(data.sessionInfo)}</pre>`;
                        
                        // Mostrar rutas de medios
                        let mediaRoutesHtml = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
                        
                        for (const [route, info] of Object.entries(data.mediaRoutes)) {
                            const statusClass = info.isSuccess ? 'bg-green-100 dark:bg-green-900/20' : 'bg-red-100 dark:bg-red-900/20';
                            const statusTextClass = info.isSuccess ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400';
                            const mediaClass = info.isMedia ? 'bg-blue-100 dark:bg-blue-900/20 border-blue-500' : '';
                            
                            mediaRoutesHtml += `
                                <div class="border rounded-lg p-3 ${statusClass} ${mediaClass}">
                                    <p class="font-semibold">${route}</p>
                                    <p class="text-sm">URL: <span class="font-mono text-xs break-all">${info.url}</span></p>
                                    <p class="text-sm ${statusTextClass}">Status: ${info.status || 'Error'}</p>
                                    ${info.contentType ? `<p class="text-sm">Content-Type: ${info.contentType}</p>` : ''}
                                    ${info.contentLength ? `<p class="text-sm">Content-Length: ${info.contentLength}</p>` : ''}
                                    ${info.error ? `<p class="text-sm text-red-600">Error: ${info.error}</p>` : ''}
                                    ${info.isSuccess && info.isMedia ? 
                                        `<p class="mt-2"><a href="${info.url}" target="_blank" class="text-blue-600 hover:underline">Abrir en nueva pestaña</a></p>` : ''}
                                </div>
                            `;
                        }
                        
                        mediaRoutesHtml += '</div>';
                        mediaRoutes.innerHTML = mediaRoutesHtml;
                        
                        // Mostrar otros endpoints
                        let endpointsHtml = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
                        
                        for (const [endpoint, info] of Object.entries(data.otherEndpoints)) {
                            const statusClass = info.isSuccess ? 'bg-green-100 dark:bg-green-900/20' : 'bg-red-100 dark:bg-red-900/20';
                            const statusTextClass = info.isSuccess ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400';
                            
                            endpointsHtml += `
                                <div class="border rounded-lg p-3 ${statusClass}">
                                    <p class="font-semibold">${endpoint}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">${info.description}</p>
                                    <p class="text-sm">URL: <span class="font-mono text-xs break-all">${info.url}</span></p>
                                    <p class="text-sm ${statusTextClass}">Status: ${info.status || 'Error'}</p>
                                    ${info.error ? `<p class="text-sm text-red-600">Error: ${info.error}</p>` : ''}
                                </div>
                            `;
                        }
                        
                        endpointsHtml += '</div>';
                        otherEndpoints.innerHTML = endpointsHtml;
                    } else {
                        error.classList.remove('hidden');
                        errorMessage.textContent = data.error || 'Error desconocido al explorar la API';
                    }
                })
                .catch(err => {
                    loading.classList.add('hidden');
                    error.classList.remove('hidden');
                    errorMessage.textContent = 'Error de conexión: ' + err.message;
                });
        });
        
        clearBtn.addEventListener('click', function() {
            results.classList.add('hidden');
            error.classList.add('hidden');
            sessionInfo.innerHTML = '';
            mediaRoutes.innerHTML = '';
            otherEndpoints.innerHTML = '';
        });
        
        // Función para resaltar la sintaxis JSON
        function syntaxHighlight(json) {
            if (typeof json !== 'string') {
                json = JSON.stringify(json, undefined, 2);
            }
            
            json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            
            return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function(match) {
                let cls = 'text-purple-600 dark:text-purple-400';
                if (/^"/.test(match)) {
                    if (/:$/.test(match)) {
                        cls = 'text-red-600 dark:text-red-400';
                    } else {
                        cls = 'text-green-600 dark:text-green-400';
                    }
                } else if (/true|false/.test(match)) {
                    cls = 'text-blue-600 dark:text-blue-400';
                } else if (/null/.test(match)) {
                    cls = 'text-gray-600 dark:text-gray-400';
                }
                return `<span class="${cls}">${match}</span>`;
            });
        }
    });
</script>
@endsection
