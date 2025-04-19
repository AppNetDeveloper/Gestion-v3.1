{{-- resources/views/map/index.blade.php --}}
<x-app-layout>
    {{-- Incluir CSS de Leaflet --}}
    @push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
     crossorigin=""/>
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
            &:before { border: none !important; }
        }
        #userFilterContainer label { margin-right: 0.5rem; }
        #userFilterSelect { padding: 0.3rem 0.5rem; border-radius: 0.25rem; border: 1px solid #ccc; }

        /* Estilo para los círculos de puntos de control */
        .control-point-circle {
            /* Puedes definir estilos aquí si usas className en L.circle */
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
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
     integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
     crossorigin=""></script>

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
            const initialCoords = [40.416775, -3.703790];
            const initialZoom = 5; // Zoom inicial
            const mapUpdateInterval = 30000; // Intervalo de actualización de usuarios
            const locationsApiUrl = "{{ route('api.locations.latest') }}";
            const controlPointsApiUrl = "{{ route('api.control-points') }}"; // Nueva URL API
            const userFilterSelect = document.getElementById('userFilterSelect');

            const map = L.map('liveMap').setView(initialCoords, initialZoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            let userMarkers = {}; // Almacena marcadores L.marker de usuarios
            let controlPointLayers = {}; // Almacena capas de círculos L.circle
            let lastLocationsData = []; // Últimos datos de usuarios recibidos
            let knownUsers = {}; // Usuarios conocidos para el filtro
            let isInitialLoad = true;

            // --- Función para Cargar y Dibujar Puntos de Control (Círculos) ---
            async function loadAndDrawControlPoints() {
                console.log("Cargando puntos de control...");
                try {
                    const response = await fetch(controlPointsApiUrl);
                    if (!response.ok) {
                        throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
                    }
                    const controlPoints = await response.json();

                    // Limpiar círculos anteriores si existieran (por si se recarga)
                    Object.values(controlPointLayers).forEach(layer => map.removeLayer(layer));
                    controlPointLayers = {};

                    if (!controlPoints || controlPoints.length === 0) {
                        console.log("No se recibieron puntos de control.");
                        return;
                    }

                    controlPoints.forEach(point => {
                         // Validar datos del punto de control
                        if (point.id == null || point.latitude == null || point.longitude == null || point.radius == null) {
                            console.warn("Punto de control recibido con datos incompletos:", point);
                            return; // Saltar este punto
                        }

                        // Asegurarse de que las coordenadas y el radio son números
                        const lat = parseFloat(point.latitude);
                        const lon = parseFloat(point.longitude);
                        const radius = parseFloat(point.radius); // El radio en metros

                        if (isNaN(lat) || isNaN(lon) || isNaN(radius) || radius <= 0) {
                             console.warn(`Datos inválidos para punto de control ID ${point.id}:`, point);
                             return;
                        }

                        const center = [lat, lon];
                        const pointName = point.name || `Punto ${point.id}`;

                        // Crear el círculo
                        const circle = L.circle(center, {
                            radius: radius, // Radio en metros
                            color: '#007bff', // Color del borde (azul)
                            weight: 1, // Grosor del borde
                            fillColor: '#007bff', // Color de relleno
                            fillOpacity: 0.15 // Opacidad del relleno (semi-transparente)
                            // className: 'control-point-circle' // Clase CSS opcional
                        }).addTo(map);

                        // Añadir popup con el nombre
                        circle.bindPopup(`<b>Punto de Control:</b><br/>${pointName}<br/>Radio: ${radius}m`);

                        // Guardar referencia a la capa del círculo
                        controlPointLayers[point.id] = circle;
                    });
                    console.log(`${Object.keys(controlPointLayers).length} puntos de control dibujados.`);

                } catch (error) {
                    console.error("Error al cargar o dibujar los puntos de control:", error);
                }
            } // Fin loadAndDrawControlPoints


            // --- Función para (Re)Dibujar Marcadores de Usuario ---
            function redrawMap() {
                const selectedUserId = userFilterSelect.value;
                const currentlyDisplayedUserIds = new Set();

                lastLocationsData.forEach(location => {
                    if (location.user_id == null || location.latitude == null || location.longitude == null) return;
                    const userId = location.user_id;
                    const userName = location.user_name || `Usuario ${userId}`;

                    if (selectedUserId !== 'all' && String(userId) !== selectedUserId) {
                         if (userMarkers[userId]) { map.removeLayer(userMarkers[userId]); delete userMarkers[userId]; }
                        return;
                    }
                    currentlyDisplayedUserIds.add(userId);

                    const lat = parseFloat(location.latitude);
                    const lon = parseFloat(location.longitude);
                    if (isNaN(lat) || isNaN(lon)) { console.warn(`Coordenadas inválidas para user_id ${userId}`); return; }
                    const latLng = [lat, lon];

                    let recordedAtText = "Fecha desconocida";
                    if (location.recorded_at) { try { const d = new Date((location.recorded_at.endsWith('Z') ? location.recorded_at : location.recorded_at + 'Z')); if (!isNaN(d)) recordedAtText = d.toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'medium' }); } catch (e) { console.error("Error parseando fecha:", location.recorded_at, e); } }
                    const popupContent = `<b>${userName}</b><br/>Lat: ${latLng[0].toFixed(5)}<br/>Lon: ${latLng[1].toFixed(5)}<br/>Última vez: ${recordedAtText}`;

                    if (userMarkers[userId]) {
                        userMarkers[userId].setLatLng(latLng).setPopupContent(popupContent);
                    } else {
                        userMarkers[userId] = L.marker(latLng).addTo(map).bindPopup(popupContent)
                            .bindTooltip(userName, { permanent: true, direction: 'bottom', offset: [0, 10], className: 'user-label-tooltip' });
                        console.log(`Marcador creado para user_id: ${userId}`);
                    }
                });

                 Object.keys(userMarkers).forEach(existingUserId => {
                    const numericUserId = parseInt(existingUserId);
                    if (!currentlyDisplayedUserIds.has(numericUserId)) {
                        map.removeLayer(userMarkers[numericUserId]); delete userMarkers[numericUserId];
                        console.log(`Marcador eliminado (filtrado/inactivo) para user_id: ${numericUserId}`);
                    }
                });

                 // *** IMPORTANTE: fitBounds sigue usando SOLO los marcadores de usuario ***
                 if (isInitialLoad && Object.keys(userMarkers).length > 0) {
                     const visibleMarkers = Object.values(userMarkers);
                     if (visibleMarkers.length > 0) {
                         const group = new L.featureGroup(visibleMarkers);
                         map.fitBounds(group.getBounds().pad(0.3));
                         isInitialLoad = false;
                     }
                 }
            } // Fin redrawMap

            // --- Función para Actualizar el Select del Filtro ---
            function updateFilterOptions() {
                const currentSelectedValue = userFilterSelect.value;
                let optionsChanged = false; let newKnownUsers = {};
                lastLocationsData.forEach(loc => { if (loc.user_id != null) newKnownUsers[loc.user_id] = loc.user_name || `Usuario ${loc.user_id}`; });
                const newUserIds = Object.keys(newKnownUsers); const oldUserIds = Object.keys(knownUsers);
                if (newUserIds.length !== oldUserIds.length || newUserIds.some(id => !knownUsers[id])) { optionsChanged = true; knownUsers = newKnownUsers; }
                if (optionsChanged) {
                    console.log("Actualizando opciones del filtro de usuario...");
                    while (userFilterSelect.options.length > 1) { userFilterSelect.remove(1); }
                    Object.entries(knownUsers).sort(([, nameA], [, nameB]) => nameA.localeCompare(nameB))
                        .forEach(([id, name]) => { const option = document.createElement('option'); option.value = id; option.textContent = name; userFilterSelect.appendChild(option); });
                    userFilterSelect.value = knownUsers[currentSelectedValue] ? currentSelectedValue : 'all';
                }
            } // Fin updateFilterOptions

            // --- Función Principal de Actualización (Fetch Usuarios + Redraw) ---
            async function updateDataAndMap() {
                console.log(`[${new Date().toLocaleTimeString()}] Obteniendo datos de usuarios...`);
                try {
                    const response = await fetch(locationsApiUrl);
                    if (!response.ok) throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
                    lastLocationsData = await response.json() || [];
                    updateFilterOptions();
                    redrawMap();
                } catch (error) {
                    console.error("Error al obtener o procesar las ubicaciones de usuarios:", error);
                    lastLocationsData = []; redrawMap();
                }
            } // Fin updateDataAndMap

            // --- Event Listener para el Filtro ---
            userFilterSelect.addEventListener('change', () => {
                console.log(`Filtro cambiado a: ${userFilterSelect.value}`);
                isInitialLoad = false; // Evitar reajuste de zoom al cambiar filtro
                redrawMap(); // Redibujar inmediatamente con los datos existentes
            });

            // --- Carga Inicial y Intervalo ---
            loadAndDrawControlPoints(); // Cargar los círculos una vez al inicio
            updateDataAndMap(); // Cargar usuarios y dibujar mapa al inicio
            const intervalId = setInterval(updateDataAndMap, mapUpdateInterval); // Actualizar usuarios periódicamente

        }); // Fin DOMContentLoaded
    </script>
    @endpush
</x-app-layout>

