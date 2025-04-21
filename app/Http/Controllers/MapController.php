{{-- resources/views/map/index.blade.php --}}
<x-app-layout>
    {{-- Incluir CSS de Leaflet y Plugin Fullscreen --}}
    @push('styles')
    {{-- Leaflet CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.css" rel="stylesheet">
    {{-- CSS Plugin Fullscreen v4.0.0 --}}
    <link href="https://cdn.jsdelivr.net/npm/leaflet.fullscreen@4.0.0/Control.FullScreen.min.css" rel="stylesheet">

    {{-- Estilos opcionales --}}
    <style>
        #liveMap {
            height: 65vh; /* Altura del mapa ajustable */
            border-radius: 0.375rem; /* Esquinas redondeadas (md) */
            position: relative; /* Necesario para posicionar controles encima */
        }
        .leaflet-popup-content-wrapper { }
        .leaflet-popup-content { margin: 10px; font-size: 13px; line-height: 1.4; }
        .leaflet-popup-content b { display: block; margin-bottom: 5px; color: #333; font-weight: 600; }
        .leaflet-tooltip.user-label-tooltip {
            background-color: rgba(255, 255, 255, 0.8); border: none; box-shadow: none;
            padding: 2px 5px; font-size: 11px; font-weight: 500; color: #333;
            white-space: nowrap; border-radius: 3px;
            &:before { border: none !important; }
        }

        /* Controles de modo y filtro */
        #mapControls {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem; /* Espacio entre controles */
            align-items: center;
            margin-bottom: 0.5rem; /* Espacio antes del mapa si se pone fuera del card-header */
        }
        #mapControls label { margin-right: 0.25rem; font-size: 0.875rem; }
        #mapControls select, #mapControls input[type="date"] {
             padding: 0.3rem 0.5rem; border-radius: 0.25rem; border: 1px solid #ccc;
             font-size: 0.875rem;
        }
        /* Estilos básicos para el conmutador (requiere JS para la funcionalidad) */
        .toggle-switch { position: relative; display: inline-block; width: 50px; height: 24px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px; }
        .toggle-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .toggle-slider { background-color: #2196F3; } /* Color azul cuando está activo */
        input:checked + .toggle-slider:before { transform: translateX(26px); }

        /* Ocultar controles de historial por defecto */
        #historyControls { display: none; gap: 0.5rem; align-items: center;}
        body.history-mode #historyControls { display: flex; } /* Mostrar cuando el body tenga la clase */

        /* Estilo para la línea de historial */
        .history-polyline {
            /* stroke: #ff0000; */ /* Puedes definirlo aquí o en L.polyline */
        }

        /* Ajustes para tema oscuro */
        .dark #mapControls select, .dark #mapControls input[type="date"] {
            background-color: #4a5568; color: #e2e8f0; border-color: #5a667a;
        }
        .dark .leaflet-control-fullscreen a { background-color: #4a5568; background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNiIgaGVpZ2h0PSIxNiIgZmlsbD0iI2ZmZiIgY2xhc3M9ImJpIGJpLWZ1bGxzY3JlZW4iIHZpZXdCb3g9IjAgMCAxNiAxNiI+CiAgPHBhdGggZD0iTTQuNSAxYTEgMSAwIDAgMCAtMSAxVjZIMVY0LjVhMSAxIDAgMCAwLTEtMUgyYTEgMSAwIDAgMCAwIDJoLjVhLjUuNSAwIDAgMSAuNS41VjZhLjUuNSAwIDAgMCAuNS41aDEuNWEuNS41IDAgMCAwIC41LS41VjJhMSAxIDAgMCAwLTEtMXptNiAwYTEgMSAwIDAgMCAxIDFoMS41YS41LjUgMCAwIDEgLjUuNVY2YS41LjUgMCAwIDAgLjUuNWguNWEuNS41IDAgMCAwIC41LS41VjJhMSAxIDAgMCAwLTEtMWgtMS41em0tMS41IDkuNWExIDEgMCAwIDAgLTEgMVYxNEgxdi0xLjVhMSAxIDAgMCAwLTEtMUgyYTEgMSAwIDAgMCAwIDJoLjVhLjUuNSAwIDAgMSAuNS41VjE0YS41LjUgMCAwIDAgLjUuNWgxLjVhLjUuNSAwIDAgMCAuNS0uNVYxMmExIDEgMCAwIDAtMS0xem03IDBhMSAxIDAgMCAwIC0xIDF2MS41YS41LjUgMCAwIDEgLS41LjVIOS41YS41LjUgMCAwIDAgLS41LjVWMTRhLjUuNSAwIDAgMCAuNS41aDEuNWEuNS41IDAgMCAwIC41LS41VjEyYTEgMSAwIDAgMCAtMS0xeiIvPgo8L3N2Zz4='); }
        .dark .leaflet-control-fullscreen.leaflet-control-fullscreen-on a { background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNiIgaGVpZ2h0PSIxNiIgZmlsbD0iI2ZmZiIgY2xhc3M9ImJpIGJpLWZ1bGxzY3JlZW4tZXhpdCIgdmlld0JveD0iMCAwIDE2IDE2Ij4KICA8cGF0aCBkPSJNMCA0LjVhMSAxIDAgMCAxIDEtMVYyYS41LjUgMCAwIDEgLjUtLjVoMS41YS41LjUgMCAwIDEgLjUuNVY0YTEgMSAwIDAgMS0xIDFoLTV6bTEwIDBhMSAxIDAgMCAxIDEtMVYyYS41LjUgMCAwIDEgLjUtLjVoMS41YS41LjUgMCAwIDEgLjUuNVY0YTEgMSAwIDAgMS0xIDFoLTV6TTQuNSA5LjVhLjUuNSAwIDAgMCAwIDFoNVYuOTVhLjUuNSAwIDAgMCAwLTFoLTV6bTcgMGExIDEgMCAwIDEgMSAxdjEuNWEuNS41IDAgMCAxLS41LjVINDEuNWEuNS41IDAgMCAxLS41LS41VjEwLjVhMSAxIDAgMCAxIDEtMWgzem0tNyA0LjVhMSAxIDAgMCAxIDEtMVYxMGEuNS41IDAgMCAxIC41LS41aDEuNWEuNS41IDAgMCAxIC41LjV2My41YTEgMSAwIDAgMS0xIDFoLTV6Ii8+Cjwvc3ZnPg=='); }
    </style>
    @endpush

    <div class="space-y-8">
        {{-- Cabecera --}}
        <div class="flex justify-between flex-wrap items-center mb-6">
            <h4 class="font-medium lg:text-2xl text-xl capitalize text-slate-900 inline-block ltr:pr-4 rtl:pl-4 mb-3 sm:mb-0 dark:text-slate-300">Mapa en Tiempo Real / Historial</h4>
        </div>

        {{-- Contenedor del Mapa y Controles --}}
        <div class="card">
            <div class="card-header">
                {{-- Controles encima del mapa --}}
                <div id="mapControls" class="dark:text-slate-300">
                    {{-- Conmutador Live/Historial --}}
                    <div>
                        <label for="modeToggle" class="font-medium">Modo:</label>
                        <span class="text-sm mr-1">En Vivo</span>
                        <label class="toggle-switch">
                            <input type="checkbox" id="modeToggle">
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="text-sm ml-1">Historial</span>
                    </div>

                    {{-- Filtro de Usuario (visible en ambos modos, pero principalmente para historial) --}}
                    <div id="userFilterContainer">
                        <label for="userFilterSelect">Usuario:</label>
                        <select id="userFilterSelect" class="dark:bg-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-600">
                            <option value="all">Todos los usuarios</option>
                            {{-- Opciones generadas por JS --}}
                        </select>
                    </div>

                    {{-- Controles específicos de Historial (ocultos por defecto) --}}
                    <div id="historyControls">
                        <label for="historyDate">Fecha:</label>
                        <input type="date" id="historyDate" class="dark:bg-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-600 dark:[color-scheme:dark]">
                        <button id="loadHistoryButton" class="btn btn-sm inline-flex justify-center bg-blue-500 text-white">Cargar Historial</button>
                    </div>
                </div>
            </div>
            <div class="card-body p-6">
                {{-- Div donde se renderizará el mapa Leaflet --}}
                <div id="liveMap"></div>
                 <p id="mapStatusText" class="text-sm text-slate-500 dark:text-slate-400 mt-2">Modo: En Vivo. Actualizando cada 30 segundos.</p>
            </div>
        </div>
    </div>

    {{-- Incluir JS de Leaflet y el script personalizado --}}
    @push('scripts')
    {{-- Leaflet JS --}}
    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.js"></script>
    {{-- Plugin Fullscreen JS v4.0.0 --}}
    <script src="https://cdn.jsdelivr.net/npm/leaflet.fullscreen@4.0.0/Control.FullScreen.min.js"></script>

    <script type="module">
        document.addEventListener('DOMContentLoaded', function () {

            // --- FIX: Icon Paths ---
            delete L.Icon.Default.prototype._getIconUrl;
            L.Icon.Default.mergeOptions({
              iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
              iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
              shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            });
            // --- END FIX ---

            // --- Configuración e Inicialización ---
            const initialCoords = [40.416775, -3.703790];
            const initialZoom = 5;
            const mapUpdateInterval = 30000; // Intervalo para modo LIVE
            const locationsApiUrl = "{{ route('api.locations.latest') }}";
            const controlPointsApiUrl = "{{ route('api.control-points') }}";
            const historyApiUrlBase = "/api/history/user/{userId}/date/{date}"; // Base URL para historial

            // Elementos del DOM
            const mapElement = document.getElementById('liveMap');
            const userFilterSelect = document.getElementById('userFilterSelect');
            const modeToggle = document.getElementById('modeToggle');
            const historyControls = document.getElementById('historyControls');
            const historyDateInput = document.getElementById('historyDate');
            const loadHistoryButton = document.getElementById('loadHistoryButton');
            const mapStatusText = document.getElementById('mapStatusText');

            // Crear mapa
            const map = L.map(mapElement).setView(initialCoords, initialZoom);

            // Añadir Control Fullscreen (con retraso y comprobación)
            setTimeout(() => {
                if (L.Control.Fullscreen) { map.addControl(new L.Control.Fullscreen({ title: { 'false': 'Ver pantalla completa', 'true': 'Salir de pantalla completa' } })); console.log("Fullscreen control añadido (L.Control.Fullscreen)."); }
                else if (L.control.fullscreen) { map.addControl(L.control.fullscreen({ title: { 'false': 'Ver pantalla completa', 'true': 'Salir de pantalla completa' } })); console.log("Fullscreen control añadido (L.control.fullscreen)."); }
                else { console.error("Error: Leaflet Fullscreen control no disponible."); }
            }, 500);

            // Añadir capa base
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> contributors' }).addTo(map);

            // Variables de estado y capas
            let currentMode = 'live'; // 'live' o 'history'
            let liveUpdateIntervalId = null; // ID del intervalo para modo live
            let userMarkers = {}; // Marcadores de usuario en vivo {userId: marker}
            let controlPointLayers = {}; // Círculos de puntos de control {pointId: circle}
            let historyLayerGroup = L.layerGroup().addTo(map); // Grupo para capas de historial (polyline, markers)
            let lastLocationsData = []; // Últimos datos live recibidos
            let knownUsers = {}; // Usuarios para el filtro
            let isInitialLoad = true; // Para ajuste de zoom inicial

            // --- Funciones ---

            // Cargar Puntos de Control (Círculos) - Sin cambios
            async function loadAndDrawControlPoints() { /* ... Mismo código que antes ... */
                console.log("Cargando puntos de control...");
                try {
                    const response = await fetch(controlPointsApiUrl);
                    if (!response.ok) throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
                    const controlPoints = await response.json();
                    Object.values(controlPointLayers).forEach(layer => map.removeLayer(layer)); controlPointLayers = {};
                    if (!controlPoints || controlPoints.length === 0) { console.log("No se recibieron puntos de control."); return; }
                    controlPoints.forEach(point => {
                        if (point.id == null || point.latitude == null || point.longitude == null || point.radius == null) { console.warn("Punto de control con datos incompletos:", point); return; }
                        const lat = parseFloat(point.latitude); const lon = parseFloat(point.longitude); const radius = parseFloat(point.radius);
                        if (isNaN(lat) || isNaN(lon) || isNaN(radius) || radius <= 0) { console.warn(`Datos inválidos para punto de control ID ${point.id}:`, point); return; }
                        const center = [lat, lon]; const pointName = point.name || `Punto ${point.id}`;
                        const circle = L.circle(center, { radius: radius, color: '#007bff', weight: 1, fillColor: '#007bff', fillOpacity: 0.15 }).addTo(map);
                        circle.bindPopup(`<b>Punto de Control:</b><br/>${pointName}<br/>Radio: ${radius}m`);
                        controlPointLayers[point.id] = circle;
                    }); console.log(`${Object.keys(controlPointLayers).length} puntos de control dibujados.`);
                } catch (error) { console.error("Error al cargar o dibujar los puntos de control:", error); }
            }

            // Limpiar marcadores de usuario en vivo
            function clearLiveUserMarkers() {
                Object.values(userMarkers).forEach(marker => map.removeLayer(marker));
                userMarkers = {};
            }

            // Limpiar capas de historial
            function clearHistoryLayers() {
                historyLayerGroup.clearLayers(); // Limpia todas las capas dentro del grupo
            }

            // Dibujar Marcadores de Usuario en Vivo (Modo Live)
            function drawLiveMarkers() {
                if (currentMode !== 'live') return; // Solo dibujar si estamos en modo live

                const selectedUserId = userFilterSelect.value;
                const currentlyDisplayedUserIds = new Set();

                lastLocationsData.forEach(location => {
                    // ... (Validaciones y parseo igual que antes) ...
                     if (location.user_id == null || location.latitude == null || location.longitude == null) return;
                    const userId = location.user_id; const userName = location.user_name || `Usuario ${userId}`;
                    if (selectedUserId !== 'all' && String(userId) !== selectedUserId) { if (userMarkers[userId]) { map.removeLayer(userMarkers[userId]); delete userMarkers[userId]; } return; }
                    currentlyDisplayedUserIds.add(userId);
                    const lat = parseFloat(location.latitude); const lon = parseFloat(location.longitude);
                    if (isNaN(lat) || isNaN(lon)) { console.warn(`Coordenadas inválidas para user_id ${userId}`); return; }
                    const latLng = [lat, lon];
                    let recordedAtText = "Fecha desconocida";
                    if (location.recorded_at) { try { const d = new Date((location.recorded_at.endsWith('Z') ? location.recorded_at : location.recorded_at + 'Z')); if (!isNaN(d)) recordedAtText = d.toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'medium' }); } catch (e) { console.error("Error parseando fecha:", location.recorded_at, e); } }
                    const popupContent = `<b>${userName}</b><br/>Lat: ${latLng[0].toFixed(5)}<br/>Lon: ${latLng[1].toFixed(5)}<br/>Última vez: ${recordedAtText}`;

                    // Actualizar o crear marcador
                    if (userMarkers[userId]) {
                        userMarkers[userId].setLatLng(latLng).setPopupContent(popupContent);
                    } else {
                        userMarkers[userId] = L.marker(latLng).addTo(map).bindPopup(popupContent)
                            .bindTooltip(userName, { permanent: true, direction: 'bottom', offset: [0, 10], className: 'user-label-tooltip' });
                        console.log(`Marcador LIVE creado para user_id: ${userId}`);
                    }
                });

                // Eliminar marcadores inactivos o filtrados
                 Object.keys(userMarkers).forEach(existingUserId => {
                    const numericUserId = parseInt(existingUserId);
                    if (!currentlyDisplayedUserIds.has(numericUserId)) {
                        map.removeLayer(userMarkers[numericUserId]); delete userMarkers[numericUserId];
                        console.log(`Marcador LIVE eliminado (filtrado/inactivo) para user_id: ${numericUserId}`);
                    }
                });

                // Ajustar zoom inicial
                 if (isInitialLoad && Object.keys(userMarkers).length > 0) {
                     const visibleMarkers = Object.values(userMarkers);
                     if (visibleMarkers.length > 0) {
                         const group = new L.featureGroup(visibleMarkers);
                         map.fitBounds(group.getBounds().pad(0.3));
                         isInitialLoad = false;
                     }
                 }
            } // Fin drawLiveMarkers

            // Actualizar opciones del filtro (sin cambios)
            function updateFilterOptions() { /* ... Mismo código que antes ... */
                const currentSelectedValue = userFilterSelect.value; let optionsChanged = false; let newKnownUsers = {};
                lastLocationsData.forEach(loc => { if (loc.user_id != null) newKnownUsers[loc.user_id] = loc.user_name || `Usuario ${loc.user_id}`; });
                const newUserIds = Object.keys(newKnownUsers); const oldUserIds = Object.keys(knownUsers);
                if (newUserIds.length !== oldUserIds.length || newUserIds.some(id => !knownUsers[id])) { optionsChanged = true; knownUsers = newKnownUsers; }
                if (optionsChanged) {
                    console.log("Actualizando opciones del filtro de usuario..."); while (userFilterSelect.options.length > 1) { userFilterSelect.remove(1); }
                    Object.entries(knownUsers).sort(([, nameA], [, nameB]) => nameA.localeCompare(nameB)) .forEach(([id, name]) => { const option = document.createElement('option'); option.value = id; option.textContent = name; userFilterSelect.appendChild(option); });
                    userFilterSelect.value = knownUsers[currentSelectedValue] ? currentSelectedValue : 'all';
                }
             }

            // Obtener datos LIVE y redibujar
            async function updateLiveData() {
                if (currentMode !== 'live') return; // No hacer nada si no estamos en modo live
                console.log(`[${new Date().toLocaleTimeString()}] Obteniendo datos LIVE...`);
                try {
                    const response = await fetch(locationsApiUrl);
                    if (!response.ok) throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
                    lastLocationsData = await response.json() || [];
                    updateFilterOptions(); // Actualizar filtro por si hay usuarios nuevos/viejos
                    drawLiveMarkers(); // Dibujar marcadores live
                } catch (error) {
                    console.error("Error al obtener ubicaciones LIVE:", error);
                    lastLocationsData = []; // Limpiar datos
                    drawLiveMarkers(); // Intentar redibujar (eliminará marcadores)
                }
            } // Fin updateLiveData

            // Obtener y dibujar datos de HISTORIAL
            async function fetchAndDrawHistory(userId, dateString) {
                if (currentMode !== 'history' || !userId || userId === 'all' || !dateString) {
                    clearHistoryLayers(); // Limpiar por si acaso
                    return;
                }
                console.log(`Obteniendo historial para User ID: ${userId}, Fecha: ${dateString}`);
                mapStatusText.textContent = `Modo: Historial - Cargando historial para ${knownUsers[userId] || `Usuario ${userId}`} el ${dateString}...`;
                clearHistoryLayers(); // Limpiar historial anterior

                // Construir URL de la API de historial
                const apiUrl = historyApiUrlBase.replace('{userId}', userId).replace('{date}', dateString);

                try {
                    const response = await fetch(apiUrl);
                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({ error: `Error HTTP: ${response.status}` }));
                        throw new Error(errorData.error || `Error HTTP: ${response.status}`);
                    }
                    const historyData = await response.json();

                    if (!historyData || historyData.length === 0) {
                        mapStatusText.textContent = `Modo: Historial - No se encontraron datos para ${knownUsers[userId] || `Usuario ${userId}`} el ${dateString}.`;
                        console.log("No se encontraron datos de historial.");
                        return;
                    }

                    const latLngs = [];
                    historyData.forEach(point => {
                        const lat = parseFloat(point.latitude);
                        const lon = parseFloat(point.longitude);
                        if (!isNaN(lat) && !isNaN(lon)) {
                            latLngs.push([lat, lon]);
                            // Opcional: Añadir marcadores pequeños para cada punto del historial
                            // L.circleMarker([lat, lon], { radius: 3, color: 'red' }).addTo(historyLayerGroup);
                        }
                    });

                    if (latLngs.length > 0) {
                        // Dibujar la línea (polyline)
                        const polyline = L.polyline(latLngs, { color: 'red', weight: 3 }).addTo(historyLayerGroup);
                        console.log(`Historial dibujado con ${latLngs.length} puntos.`);
                        // Ajustar mapa a la línea del historial
                        map.fitBounds(polyline.getBounds().pad(0.1));
                         mapStatusText.textContent = `Modo: Historial - Mostrando historial para ${knownUsers[userId] || `Usuario ${userId}`} el ${dateString}.`;
                    } else {
                         mapStatusText.textContent = `Modo: Historial - No se encontraron puntos válidos para ${knownUsers[userId] || `Usuario ${userId}`} el ${dateString}.`;
                    }

                } catch (error) {
                    console.error("Error al obtener o dibujar el historial:", error);
                    mapStatusText.textContent = `Modo: Historial - Error al cargar historial: ${error.message}`;
                }
            } // Fin fetchAndDrawHistory

            // --- Función para Cambiar de Modo ---
            function switchMode(newMode) {
                if (newMode === currentMode) return; // No hacer nada si ya está en ese modo

                currentMode = newMode;
                isInitialLoad = true; // Reiniciar flag de zoom inicial para el nuevo modo

                if (currentMode === 'live') {
                    console.log("Cambiando a modo LIVE");
                    document.body.classList.remove('history-mode'); // Quitar clase para ocultar controles historial
                    mapStatusText.textContent = 'Modo: En Vivo. Actualizando cada 30 segundos.';
                    clearHistoryLayers(); // Limpiar capas de historial
                    if (liveUpdateIntervalId) clearInterval(liveUpdateIntervalId); // Asegurar que no haya intervalo previo
                    updateLiveData(); // Cargar datos live inmediatamente
                    liveUpdateIntervalId = setInterval(updateLiveData, mapUpdateInterval); // Iniciar intervalo live
                } else { // Cambiando a modo 'history'
                    console.log("Cambiando a modo HISTORY");
                    document.body.classList.add('history-mode'); // Añadir clase para mostrar controles historial
                    if (liveUpdateIntervalId) clearInterval(liveUpdateIntervalId); // Detener intervalo live
                    liveUpdateIntervalId = null;
                    clearLiveUserMarkers(); // Limpiar marcadores live
                    // Poner fecha de hoy por defecto en el date picker
                    if (!historyDateInput.value) {
                        historyDateInput.valueAsDate = new Date();
                    }
                    mapStatusText.textContent = 'Modo: Historial - Selecciona usuario y fecha y pulsa "Cargar Historial".';
                    // No cargamos historial hasta que se pulse el botón o cambie fecha/usuario
                    clearHistoryLayers();
                }
            } // Fin switchMode

            // --- Event Listeners ---

            // Listener para el conmutador de modo
            modeToggle.addEventListener('change', (event) => {
                switchMode(event.target.checked ? 'history' : 'live');
            });

            // Listener para el botón de cargar historial
            loadHistoryButton.addEventListener('click', () => {
                if (currentMode === 'history') {
                    const selectedUserId = userFilterSelect.value;
                    const selectedDate = historyDateInput.value; // Formato YYYY-MM-DD
                    if (selectedUserId === 'all') {
                         alert("Por favor, selecciona un usuario específico para ver el historial.");
                         return;
                    }
                    if (!selectedDate) {
                        alert("Por favor, selecciona una fecha para ver el historial.");
                        return;
                    }
                    fetchAndDrawHistory(selectedUserId, selectedDate);
                }
            });

            // Listener para el filtro de usuario (redibuja live o historial según modo)
             userFilterSelect.addEventListener('change', () => {
                console.log(`Filtro cambiado a: ${userFilterSelect.value}`);
                isInitialLoad = true; // Permitir reajuste de zoom al cambiar usuario
                if (currentMode === 'live') {
                    drawLiveMarkers(); // Redibujar marcadores live con filtro aplicado
                } else {
                    // En modo historial, no hacemos nada aquí, se usa el botón "Cargar"
                     mapStatusText.textContent = 'Modo: Historial - Pulsa "Cargar Historial" para ver la nueva selección.';
                     clearHistoryLayers(); // Limpiar historial anterior al cambiar usuario/fecha sin cargar
                }
            });

             // Listener para el cambio de fecha (opcional: cargar automáticamente al cambiar fecha)
             /*
             historyDateInput.addEventListener('change', () => {
                 if (currentMode === 'history') {
                     const selectedUserId = userFilterSelect.value;
                     const selectedDate = historyDateInput.value;
                     if (selectedUserId !== 'all' && selectedDate) {
                         fetchAndDrawHistory(selectedUserId, selectedDate);
                     }
                 }
             });
             */


            // --- Carga Inicial ---
            loadAndDrawControlPoints(); // Cargar círculos
            switchMode('live'); // Iniciar en modo live por defecto

        }); // Fin DOMContentLoaded
    </script>
    @endpush
</x-app-layout>
