{{-- resources/views/map/index.blade.php --}}
<x-app-layout>
    {{-- Incluir CSS de Leaflet y Plugin Fullscreen --}}
    @push('styles')
    {{-- Leaflet CSS (CDN Proporcionado por Usuario) --}}
    <link href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.css" rel="stylesheet">
    {{-- CSS Plugin Fullscreen v4.0.0 (CDN Proporcionado por Usuario) --}}
    <link href="https://cdn.jsdelivr.net/npm/leaflet.fullscreen@4.0.0/Control.FullScreen.min.css" rel="stylesheet">

    {{-- Estilos opcionales --}}
    <style>
        #liveMap {
            height: 65vh; /* Altura del mapa ajustable */
            border-radius: 0.375rem; /* Esquinas redondeadas (md) */
        }
        .leaflet-popup-content-wrapper { }
        .leaflet-popup-content { margin: 10px; font-size: 13px; line-height: 1.4; }
        .leaflet-popup-content b { display: block; margin-bottom: 5px; color: #333; font-weight: 600; }
        .leaflet-tooltip.user-label-tooltip {
            background-color: rgba(255, 255, 255, 0.8); border: none; box-shadow: none;
            padding: 2px 5px; font-size: 11px; font-weight: 500; color: #333;
            white-space: nowrap; border-radius: 3px;
            /* Eliminar la flecha del tooltip por defecto */
            &:before { border: none !important; }
        }
        #userFilterContainer label { margin-right: 0.5rem; }
        #userFilterSelect { padding: 0.3rem 0.5rem; border-radius: 0.25rem; border: 1px solid #ccc; }
        /* Asegurar que el botón fullscreen se vea bien en tema oscuro si aplica */
        .leaflet-control-fullscreen a {
            background-color: #fff; /* Fondo blanco por defecto */
        }
        /* Estilos para el botón fullscreen en modo oscuro (si tu plantilla lo soporta) */
        .dark .leaflet-control-fullscreen a {
             background-color: #4a5568; /* Un fondo oscuro para tema oscuro */
             /* Icono SVG blanco para entrar a fullscreen */
             background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNiIgaGVpZ2h0PSIxNiIgZmlsbD0iI2ZmZiIgY2xhc3M9ImJpIGJpLWZ1bGxzY3JlZW4iIHZpZXdCb3g9IjAgMCAxNiAxNiI+CiAgPHBhdGggZD0iTTQuNSAxYTEgMSAwIDAgMCAtMSAxVjZIMVY0LjVhMSAxIDAgMCAwLTEtMUgyYTEgMSAwIDAgMCAwIDJoLjVhLjUuNSAwIDAgMSAuNS41VjZhLjUuNSAwIDAgMCAuNS41aDEuNWEuNS41IDAgMCAwIC41LS41VjJhMSAxIDAgMCAwLTEtMXptNiAwYTEgMSAwIDAgMCAxIDFoMS41YS41LjUgMCAwIDEgLjUuNVY2YS41LjUgMCAwIDAgLjUuNWguNWEuNS41IDAgMCAwIC41LS41VjJhMSAxIDAgMCAwLTEtMWgtMS41em0tMS41IDkuNWExIDEgMCAwIDAgLTEgMVYxNEgxdi0xLjVhMSAxIDAgMCAwLTEtMUgyYTEgMSAwIDAgMCAwIDJoLjVhLjUuNSAwIDAgMSAuNS41VjE0YS41LjUgMCAwIDAgLjUuNWgxLjVhLjUuNSAwIDAgMCAuNS0uNVYxMmExIDEgMCAwIDAtMS0xem03IDBhMSAxIDAgMCAwIC0xIDF2MS41YS41LjUgMCAwIDEgLS41LjVIOS41YS41LjUgMCAwIDAgLS41LjVWMTRhLjUuNSAwIDAgMCAuNS41aDEuNWEuNS41IDAgMCAwIC41LS41VjEyYTEgMSAwIDAgMCAtMS0xeiIvPgo8L3N2Zz4=');
        }
        /* Estilos para el botón fullscreen cuando está activo (modo oscuro) */
        .dark .leaflet-control-fullscreen.leaflet-control-fullscreen-on a {
             /* Icono SVG blanco para salir de fullscreen */
             background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNiIgaGVpZ2h0PSIxNiIgZmlsbD0iI2ZmZiIgY2xhc3M9ImJpIGJpLWZ1bGxzY3JlZW4tZXhpdCIgdmlld0JveD0iMCAwIDE2IDE2Ij4KICA8cGF0aCBkPSJNMCA0LjVhMSAxIDAgMCAxIDEtMVYyYS41LjUgMCAwIDEgLjUtLjVoMS41YS41LjUgMCAwIDEgLjUuNVY0YTEgMSAwIDAgMS0xIDFoLTV6bTEwIDBhMSAxIDAgMCAxIDEtMVYyYS41LjUgMCAwIDEgLjUtLjVoMS41YS41LjUgMCAwIDEgLjUuNVY0YTEgMSAwIDAgMS0xIDFoLTV6TTQuNSA5LjVhLjUuNSAwIDAgMCAwIDFoNVYuOTVhLjUuNSAwIDAgMCAwLTFoLTV6bTcgMGExIDEgMCAwIDEgMSAxdjEuNWEuNS41IDAgMCAxLS41LjVINDEuNWEuNS41IDAgMCAxLS41LS41VjEwLjVhMSAxIDAgMCAxIDEtMWgzem0tNyA0LjVhMSAxIDAgMCAxIDEtMVYxMGEuNS41IDAgMCAxIC41LS41aDEuNWEuNS41IDAgMCAxIC41LjV2My41YTEgMSAwIDAgMS0xIDFoLTV6Ii8+Cjwvc3ZnPg==');
        }

    </style>
    @endpush

    <div class="space-y-8">
        {{-- Cabecera --}}
        <div class="flex justify-between flex-wrap items-center mb-6">
            <h4 class="font-medium lg:text-2xl text-xl capitalize text-slate-900 inline-block ltr:pr-4 rtl:pl-4 mb-3 sm:mb-0 dark:text-slate-300">Mapa en Tiempo Real</h4>
        </div>

        {{-- Contenedor del Mapa --}}
        <div class="card">
            <div class="card-header flex justify-between items-center">
                <h4 class="card-title dark:text-slate-300">Ubicaciones Actuales de Usuarios</h4>
                {{-- Contenedor del Filtro --}}
                <div id="userFilterContainer" class="text-sm">
                    <label for="userFilterSelect" class="dark:text-slate-300">Filtrar Usuario:</label>
                    <select id="userFilterSelect" class="dark:bg-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-600">
                        <option value="all">Todos los usuarios</option>
                        {{-- Las opciones de usuario se añadirán aquí por JS --}}
                    </select>
                </div>
            </div>
            <div class="card-body p-6">
                {{-- Div donde se renderizará el mapa Leaflet --}}
                <div id="liveMap"></div>
                 <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">El mapa se actualiza automáticamente cada 30 segundos.</p>
            </div>
        </div>
    </div>

    {{-- Incluir JS de Leaflet y el script personalizado --}}
    @push('scripts')
    {{-- Leaflet JS (CDN Proporcionado por Usuario) --}}
    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.js"></script>
    {{-- Plugin Fullscreen JS v4.0.0 (CDN Proporcionado por Usuario) --}}
    <script src="https://cdn.jsdelivr.net/npm/leaflet.fullscreen@4.0.0/Control.FullScreen.min.js"></script>

    <script type="module">
        document.addEventListener('DOMContentLoaded', function () {

            // --- FIX: Specify CDN paths for default Leaflet marker icons ---
            delete L.Icon.Default.prototype._getIconUrl;
            L.Icon.Default.mergeOptions({
              iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
              iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
              shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            });
            // --- END FIX ---

            // --- Configuración e Inicialización ---
            const initialCoords = [40.416775, -3.703790]; // Coordenadas iniciales
            const initialZoom = 5; // Zoom inicial ajustado
            const mapUpdateInterval = 30000; // Intervalo de actualización (30s)
            const locationsApiUrl = "{{ route('api.locations.latest') }}"; // API de usuarios
            const controlPointsApiUrl = "{{ route('api.control-points') }}"; // API de puntos de control
            const userFilterSelect = document.getElementById('userFilterSelect'); // Selector del filtro

            // Crear instancia del mapa
            const map = L.map('liveMap').setView(initialCoords, initialZoom);

            // --- Añadir Control Fullscreen (con retraso) ---
            // Retrasamos ligeramente para dar tiempo a que el script del plugin se inicialice
            setTimeout(() => {
                if (L.Control.Fullscreen) { // Intenta con mayúscula primero
                    map.addControl(new L.Control.Fullscreen({
                        title: { // Títulos opcionales en español para el tooltip
                            'false': 'Ver pantalla completa',
                            'true': 'Salir de pantalla completa'
                        }
                    }));
                    console.log("Fullscreen control añadido usando L.Control.Fullscreen.");
                } else if (L.control.fullscreen) { // Intenta con minúscula como alternativa
                     map.addControl(L.control.fullscreen({
                        title: {
                            'false': 'Ver pantalla completa',
                            'true': 'Salir de pantalla completa'
                        }
                     }));
                     console.log("Fullscreen control añadido usando L.control.fullscreen.");
                }
                 else {
                    // Si sigue sin funcionar después del retraso, el problema es otro
                    console.error("Error: Leaflet Fullscreen control sigue sin estar disponible después del retraso. Verifica la compatibilidad de la v4.0.0 o posibles conflictos.");
                }
            }, 500); // Esperar 500 milisegundos (medio segundo) antes de intentar añadir el control
            // --- Fin Control Fullscreen ---


            // Añadir capa base de OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            // Variables para almacenar estado
            let userMarkers = {}; // Almacena marcadores L.marker de usuarios {userId: marker}
            let controlPointLayers = {}; // Almacena capas de círculos L.circle {pointId: circle}
            let lastLocationsData = []; // Almacena los últimos datos de usuarios recibidos de la API
            let knownUsers = {}; // Almacena usuarios conocidos {id: name} para poblar el filtro
            let isInitialLoad = true; // Para controlar el ajuste de zoom inicial

            // --- Función para Cargar y Dibujar Puntos de Control (Círculos) ---
            async function loadAndDrawControlPoints() {
                console.log("Cargando puntos de control...");
                try {
                    const response = await fetch(controlPointsApiUrl);
                    if (!response.ok) throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
                    const controlPoints = await response.json();

                    Object.values(controlPointLayers).forEach(layer => map.removeLayer(layer));
                    controlPointLayers = {};

                    if (!controlPoints || controlPoints.length === 0) { console.log("No se recibieron puntos de control."); return; }

                    controlPoints.forEach(point => {
                        if (point.id == null || point.latitude == null || point.longitude == null || point.radius == null) { console.warn("Punto de control con datos incompletos:", point); return; }
                        const lat = parseFloat(point.latitude); const lon = parseFloat(point.longitude); const radius = parseFloat(point.radius);
                        if (isNaN(lat) || isNaN(lon) || isNaN(radius) || radius <= 0) { console.warn(`Datos inválidos para punto de control ID ${point.id}:`, point); return; }
                        const center = [lat, lon]; const pointName = point.name || `Punto ${point.id}`;
                        const circle = L.circle(center, { radius: radius, color: '#007bff', weight: 1, fillColor: '#007bff', fillOpacity: 0.15 }).addTo(map);
                        circle.bindPopup(`<b>Punto de Control:</b><br/>${pointName}<br/>Radio: ${radius}m`);
                        controlPointLayers[point.id] = circle;
                    });
                    console.log(`${Object.keys(controlPointLayers).length} puntos de control dibujados.`);
                } catch (error) { console.error("Error al cargar o dibujar los puntos de control:", error); }
            }

            // --- Función para (Re)Dibujar Marcadores de Usuario (aplicando filtro) ---
            function redrawMap() {
                const selectedUserId = userFilterSelect.value; const currentlyDisplayedUserIds = new Set();
                lastLocationsData.forEach(location => {
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
                    if (userMarkers[userId]) { userMarkers[userId].setLatLng(latLng).setPopupContent(popupContent); }
                    else { userMarkers[userId] = L.marker(latLng).addTo(map).bindPopup(popupContent).bindTooltip(userName, { permanent: true, direction: 'bottom', offset: [0, 10], className: 'user-label-tooltip' }); console.log(`Marcador creado para user_id: ${userId}`); }
                });
                 Object.keys(userMarkers).forEach(existingUserId => { const numericUserId = parseInt(existingUserId); if (!currentlyDisplayedUserIds.has(numericUserId)) { map.removeLayer(userMarkers[numericUserId]); delete userMarkers[numericUserId]; console.log(`Marcador eliminado (filtrado/inactivo) para user_id: ${numericUserId}`); } });
                 if (isInitialLoad && Object.keys(userMarkers).length > 0) { const visibleMarkers = Object.values(userMarkers); if (visibleMarkers.length > 0) { const group = new L.featureGroup(visibleMarkers); map.fitBounds(group.getBounds().pad(0.3)); isInitialLoad = false; } }
            }

            // --- Función para Actualizar las Opciones del Filtro Desplegable ---
            function updateFilterOptions() {
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

            // --- Función Principal de Actualización (Llama a API Usuarios y Redibuja) ---
            async function updateDataAndMap() {
                console.log(`[${new Date().toLocaleTimeString()}] Obteniendo datos de usuarios...`);
                try {
                    const response = await fetch(locationsApiUrl); if (!response.ok) throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
                    lastLocationsData = await response.json() || []; updateFilterOptions(); redrawMap();
                } catch (error) { console.error("Error al obtener o procesar las ubicaciones de usuarios:", error); lastLocationsData = []; redrawMap(); }
            }

            // --- Event Listener para Cambios en el Filtro ---
            userFilterSelect.addEventListener('change', () => { console.log(`Filtro cambiado a: ${userFilterSelect.value}`); isInitialLoad = false; redrawMap(); });

            // --- Carga Inicial y Configuración del Intervalo ---
            loadAndDrawControlPoints(); updateDataAndMap();
            const intervalId = setInterval(updateDataAndMap, mapUpdateInterval);

        }); // Fin DOMContentLoaded
    </script>
    @endpush
</x-app-layout>
