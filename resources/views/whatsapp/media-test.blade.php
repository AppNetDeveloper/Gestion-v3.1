@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold mb-6">{{ __('Prueba de Medios de WhatsApp') }}</h1>
        
        <div class="mb-6">
            <form id="test-form" class="space-y-4">
                <div>
                    <label for="sessionId" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Session ID</label>
                    <input type="text" id="sessionId" name="sessionId" value="{{ Auth::id() }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                </div>
                
                <div>
                    <label for="jid" class="block text-sm font-medium text-gray-700 dark:text-gray-300">JID (con o sin @s.whatsapp.net)</label>
                    <input type="text" id="jid" name="jid" placeholder="555123456789" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                </div>
                
                <div>
                    <label for="messageId" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Message ID</label>
                    <input type="text" id="messageId" name="messageId" placeholder="ABCDEF1234567890" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                </div>
                
                <div>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        {{ __('Probar URL') }}
                    </button>
                </div>
            </form>
        </div>
        
        <div id="result" class="hidden">
            <h2 class="text-xl font-semibold mb-2">{{ __('Resultado') }}</h2>
            
            <div class="mb-4">
                <h3 class="text-lg font-medium mb-1">{{ __('URL generada:') }}</h3>
                <div id="url-display" class="bg-gray-100 dark:bg-slate-700 p-2 rounded break-all font-mono text-sm"></div>
                <button id="copy-url" class="mt-2 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                    {{ __('Copiar URL') }}
                </button>
                <a id="open-url" target="_blank" class="ml-4 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                    {{ __('Abrir en nueva pestaña') }}
                </a>
            </div>
            
            <div class="mb-4">
                <h3 class="text-lg font-medium mb-1">{{ __('Vista previa:') }}</h3>
                <div id="preview" class="bg-gray-100 dark:bg-slate-700 p-4 rounded flex items-center justify-center min-h-[200px]">
                    <div id="loading" class="flex items-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                        <span class="ml-2">{{ __('Cargando...') }}</span>
                    </div>
                    <img id="preview-image" class="hidden max-w-full max-h-[400px] rounded shadow" alt="Vista previa">
                    <video id="preview-video" class="hidden max-w-full max-h-[400px] rounded shadow" controls></video>
                    <audio id="preview-audio" class="hidden w-full" controls></audio>
                    <div id="preview-error" class="hidden text-red-600 dark:text-red-400"></div>
                </div>
            </div>
            
            <div class="mb-4">
                <h3 class="text-lg font-medium mb-1">{{ __('Respuesta HTTP:') }}</h3>
                <div id="http-response" class="bg-gray-100 dark:bg-slate-700 p-2 rounded font-mono text-sm overflow-auto max-h-[300px]"></div>
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
        const testForm = document.getElementById('test-form');
        const result = document.getElementById('result');
        const error = document.getElementById('error');
        const errorMessage = document.getElementById('error-message');
        const urlDisplay = document.getElementById('url-display');
        const copyUrl = document.getElementById('copy-url');
        const openUrl = document.getElementById('open-url');
        const preview = document.getElementById('preview');
        const loading = document.getElementById('loading');
        const previewImage = document.getElementById('preview-image');
        const previewVideo = document.getElementById('preview-video');
        const previewAudio = document.getElementById('preview-audio');
        const previewError = document.getElementById('preview-error');
        const httpResponse = document.getElementById('http-response');
        
        testForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const sessionId = document.getElementById('sessionId').value.trim();
            const jid = document.getElementById('jid').value.trim();
            const messageId = document.getElementById('messageId').value.trim();
            
            if (!sessionId || !jid || !messageId) {
                error.classList.remove('hidden');
                errorMessage.textContent = 'Por favor, complete todos los campos.';
                result.classList.add('hidden');
                return;
            }
            
            error.classList.add('hidden');
            result.classList.remove('hidden');
            loading.classList.remove('hidden');
            previewImage.classList.add('hidden');
            previewVideo.classList.add('hidden');
            previewAudio.classList.add('hidden');
            previewError.classList.add('hidden');
            
            // Codificar el JID para la URL
            const encodedJid = encodeURIComponent(jid);
            
            // Construir la URL
            const url = `{{ url('/whatsapp/media') }}/${sessionId}/${encodedJid}/${messageId}`;
            
            // Mostrar la URL
            urlDisplay.textContent = url;
            openUrl.href = url;
            
            // Hacer la solicitud
            fetch(url)
                .then(response => {
                    // Guardar la información de la respuesta
                    const contentType = response.headers.get('Content-Type');
                    const status = response.status;
                    const statusText = response.statusText;
                    
                    // Mostrar la información de la respuesta HTTP
                    httpResponse.innerHTML = `Status: ${status} ${statusText}<br>Content-Type: ${contentType}`;
                    
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(`HTTP ${status} ${statusText}: ${text}`);
                        });
                    }
                    
                    // Verificar el tipo de contenido
                    if (contentType.startsWith('image/')) {
                        return response.blob().then(blob => {
                            const imageUrl = URL.createObjectURL(blob);
                            previewImage.src = imageUrl;
                            previewImage.classList.remove('hidden');
                            return { type: 'image', url: imageUrl };
                        });
                    } else if (contentType.startsWith('video/')) {
                        return response.blob().then(blob => {
                            const videoUrl = URL.createObjectURL(blob);
                            previewVideo.src = videoUrl;
                            previewVideo.classList.remove('hidden');
                            return { type: 'video', url: videoUrl };
                        });
                    } else if (contentType.startsWith('audio/')) {
                        return response.blob().then(blob => {
                            const audioUrl = URL.createObjectURL(blob);
                            previewAudio.src = audioUrl;
                            previewAudio.classList.remove('hidden');
                            return { type: 'audio', url: audioUrl };
                        });
                    } else {
                        return response.text().then(text => {
                            return { type: 'other', content: text };
                        });
                    }
                })
                .then(result => {
                    loading.classList.add('hidden');
                    
                    if (result.type === 'other') {
                        previewError.textContent = 'No se puede mostrar una vista previa para este tipo de contenido.';
                        previewError.classList.remove('hidden');
                        httpResponse.innerHTML += `<br><br>Contenido (primeros 500 caracteres):<br>${result.content.substring(0, 500)}`;
                    }
                })
                .catch(err => {
                    loading.classList.add('hidden');
                    previewError.textContent = err.message;
                    previewError.classList.remove('hidden');
                    console.error('Error:', err);
                });
        });
        
        copyUrl.addEventListener('click', function() {
            const url = urlDisplay.textContent;
            navigator.clipboard.writeText(url).then(() => {
                copyUrl.textContent = 'URL copiada!';
                setTimeout(() => {
                    copyUrl.textContent = 'Copiar URL';
                }, 2000);
            });
        });
    });
</script>
@endsection
