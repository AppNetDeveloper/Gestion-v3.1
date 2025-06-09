@can('labcalendar show')
<x-app-layout>
    @push('styles')
        <style>
            /* Estilos para el grid de calendarios */
            .year-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 1rem;
            }
            .month-calendar {
                @apply rounded-lg shadow-md p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700;
            }
            .month-header {
                @apply text-center font-bold mb-2 text-gray-800 dark:text-gray-200;
            }
            /* Estilo para eventos festivos */
            .fc-event.holiday-event {
                background-color: #ffcccc !important;
                border: none !important;
                color: #900 !important;
                font-weight: bold;
                font-size: 0.85rem;
            }
        </style>
    @endpush

    <div class="space-y-8">
        <!-- Contenedor principal similar a tu layout original -->
        <div class="dashcode-calender">
            <h4 class="font-medium lg:text-2xl text-xl capitalize text-slate-900 dark:text-gray-100 inline-block ltr:pr-4 rtl:pl-4 mb-1 sm:mb-0 mb-6">
                {{ __("Calendario Laboral") }}
            </h4>
            <div class="grid grid-cols-12 gap-4">
                <!-- Sidebar de filtros y navegación -->
                <div class="col-span-12 lg:col-span-3 card p-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col space-y-4">
                        <label class="flex items-center text-gray-800 dark:text-gray-200">
                            <input type="checkbox" id="saturdayOff" class="mr-2">
                            {{ __("Sábado sin trabajar") }}
                        </label>
                        <label class="flex items-center text-gray-800 dark:text-gray-200">
                            <input type="checkbox" id="sundayOff" class="mr-2">
                            {{ __("Domingo sin trabajar") }}
                        </label>
                        <div class="flex items-center space-x-2 mt-4">
                            <button id="prevYear" class="bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-3 py-1 rounded">
                                «
                            </button>
                            <span id="currentYear" class="font-bold text-xl text-gray-900 dark:text-gray-100"></span>
                            <button id="nextYear" class="bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-3 py-1 rounded">
                                »
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Contenedor de los 12 mini-calendarios -->
                <div class="col-span-12 lg:col-span-9 card p-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                    <div class="year-grid">
                        @for ($m = 1; $m <= 12; $m++)
                            <div class="month-calendar">
                                <div class="month-header" id="month-label-{{ $m }}"></div>
                                <div id="calendar-{{ $m }}"></div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <!-- SweetAlert2 for dialogs -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        
        <!-- Load app.js to get FullCalendar modules -->
        @vite(['resources/js/app.js'])
        
        <script>
            // Variables de permiso (obtenidas desde el backend)
            var canCreate = @json(auth()->user()->can('labcalendar create'));
            var canUpdate = @json(auth()->user()->can('labcalendar update'));
            var canDelete = @json(auth()->user()->can('labcalendar delete'));

            document.addEventListener('DOMContentLoaded', function() {
                // Definir URLs seguras basadas en APP_URL (sin barra final y forzando HTTPS)
                var baseUrl = "{{ rtrim(config('app.url'), '/') }}".replace("http://", "https://");
                var fetchUrl = "{{ str_replace('http://', 'https://', rtrim(route('labor-calendar.fetch'), '/')) }}";
                var storeUrl = "{{ str_replace('http://', 'https://', rtrim(route('labor-calendar.store'), '/')) }}";
                var destroyUrlTemplate = "{{ str_replace('http://', 'https://', rtrim(route('labor-calendar.destroy', ['id' => '__id__']), '/')) }}";
                var saveNonWorkingUrl = "{{ str_replace('http://', 'https://', rtrim(route('labor-calendar.saveNonWorking'), '/')) }}";

                let currentYear = new Date().getFullYear();
                document.getElementById('currentYear').textContent = currentYear;

                const monthNames = [
                    "{{ __('January') }}", "{{ __('February') }}", "{{ __('March') }}", "{{ __('April') }}",
                    "{{ __('May') }}", "{{ __('June') }}", "{{ __('July') }}", "{{ __('August') }}",
                    "{{ __('September') }}", "{{ __('October') }}", "{{ __('November') }}", "{{ __('December') }}"
                ];

                let calendars = [];

                function renderAllCalendars() {
                    calendars.forEach(cal => cal.destroy());
                    calendars = [];

                    for (let m = 1; m <= 12; m++) {
                        let calEl = document.getElementById('calendar-' + m);
                        let monthLabel = document.getElementById('month-label-' + m);
                        monthLabel.textContent = monthNames[m - 1];

                        let initialDate = new Date(currentYear, m - 1, 1);

                        let calendar = new FullCalendar.Calendar(calEl, {
                            plugins: [ FullCalendar.dayGridPlugin, FullCalendar.interactionPlugin ],
                            initialView: 'dayGridMonth',
                            headerToolbar: false,
                            initialDate: initialDate,
                            events: {
                                url: fetchUrl,
                                method: 'GET',
                                extraParams: { year: currentYear },
                                success: function(events) {
                                    console.log('Eventos cargados:', events);
                                },
                                failure: function(error) {
                                    console.error('Error cargando eventos:', error);
                                }
                            },
                            eventDidMount: function(info) {
                                if (info.event.extendedProps.is_holiday) {
                                    info.el.classList.add('holiday-event');
                                }
                            },
                            // Al hacer click en una fecha vacía, preguntar si se asigna como festivo (solo si tiene permiso de crear)
                            dateClick: function(info) {
                                if (!canCreate) return;
                                Swal.fire({
                                    title: "{{ __('Asignar día festivo') }}",
                                    text: "{{ __('¿Desea asignar el') }} " + info.dateStr + " {{ __('como festivo?') }}",
                                    icon: "question",
                                    showCancelButton: true,
                                    confirmButtonText: "{{ __('Si, asignar') }}",
                                    cancelButtonText: "{{ __('No, cancelar') }}"
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        Swal.fire({
                                            title: "{{ __('Nombre del festivo') }}",
                                            input: 'text',
                                            inputPlaceholder: "{{ __('Introduce el nombre del festivo') }}",
                                            showCancelButton: true,
                                            confirmButtonText: "{{ __('Guardar') }}",
                                            cancelButtonText: "{{ __('Cancelar') }}"
                                        }).then((result2) => {
                                            if (result2.isConfirmed && result2.value.trim() !== "") {
                                                fetch(storeUrl, {
                                                    method: "POST",
                                                    headers: {
                                                        "Content-Type": "application/json",
                                                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                                    },
                                                    body: JSON.stringify({
                                                        title: result2.value,
                                                        start_date: info.dateStr,
                                                        end_date: info.dateStr,
                                                        is_holiday: true,
                                                        auto_generated: false,
                                                        description: ""
                                                    })
                                                })
                                                .then(response => response.json())
                                                .then(data => {
                                                    if (data.success) {
                                                        Swal.fire("{{ __('Guardado') }}", "{{ __('El día festivo ha sido asignado') }}", "success");
                                                        calendars.forEach(cal => cal.refetchEvents());
                                                    } else {
                                                        Swal.fire("{{ __('Error') }}", "{{ __('No se pudo asignar el festivo') }}", "error");
                                                    }
                                                })
                                                .catch(err => {
                                                    Swal.fire("{{ __('Error') }}", "{{ __('Error en la conexión') }}", "error");
                                                });
                                            }
                                        });
                                    }
                                });
                            },
                            // Al hacer click en un evento festivo, preguntar si se desea eliminarlo (solo si tiene permiso para eliminar)
                            eventClick: function(info) {
                                console.log("Evento clickeado:", info);
                                console.log("extendedProps:", info.event.extendedProps);
                                if (!canDelete) return;
                                if (info.event.extendedProps.is_holiday) {
                                    Swal.fire({
                                        title: "{{ __('Eliminar festivo') }}",
                                        text: "{{ __('¿Desea eliminar el festivo') }} " + info.event.title + " {{ __('en') }} " + info.event.startStr + " ?",
                                        icon: "warning",
                                        showCancelButton: true,
                                        confirmButtonText: "{{ __('Sí, eliminar') }}",
                                        cancelButtonText: "{{ __('Cancelar') }}"
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            let url = destroyUrlTemplate.replace('__id__', info.event.id);
                                            fetch(url, {
                                                method: "DELETE",
                                                headers: {
                                                    "Content-Type": "application/json",
                                                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                                }
                                            })
                                            .then(response => response.json())
                                            .then(data => {
                                                if (data.success) {
                                                    Swal.fire("{{ __('Eliminado') }}", "{{ __('El festivo ha sido eliminado') }}", "success");
                                                    info.event.remove();
                                                } else {
                                                    Swal.fire("{{ __('Error') }}", "{{ __('No se pudo eliminar el festivo') }}", "error");
                                                }
                                            })
                                            .catch(err => {
                                                Swal.fire("{{ __('Error') }}", "{{ __('Error en la conexión') }}", "error");
                                            });
                                        }
                                    });
                                }
                            },
                            aspectRatio: 1.2,
                        });
                        calendar.render();
                        calendars.push(calendar);
                    }
                }

                renderAllCalendars();
                
                // Cargar estado inicial de los checkboxes
                fetch(fetchUrl + '?year=' + currentYear + '&config=1', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    }
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Datos de configuración recibidos:', data);
                    
                    // Establecer valores por defecto
                    document.getElementById('saturdayOff').checked = true;
                    document.getElementById('sundayOff').checked = true;
                    
                    // Si hay datos de configuración, usar esos valores
                    if (data && data.config) {
                        console.log('Configuración de sábado:', data.config.saturday_off);
                        console.log('Configuración de domingo:', data.config.sunday_off);
                        
                        document.getElementById('saturdayOff').checked = data.config.saturday_off == 1;
                        document.getElementById('sundayOff').checked = data.config.sunday_off == 1;
                    }
                })
                .catch(error => {
                    console.error('Error cargando configuración:', error);
                    // En caso de error, marcar los checkboxes por defecto
                    document.getElementById('saturdayOff').checked = true;
                    document.getElementById('sundayOff').checked = true;
                });

                // Navegación entre años
                document.getElementById('prevYear').addEventListener('click', function() {
                    currentYear--;
                    document.getElementById('currentYear').textContent = currentYear;
                    renderAllCalendars();
                });
                document.getElementById('nextYear').addEventListener('click', function() {
                    currentYear++;
                    document.getElementById('currentYear').textContent = currentYear;
                    renderAllCalendars();
                });

                // Función para guardar configuración de días no laborables (auto-generados)
                function saveNonWorking(day, status) {
                    fetch(saveNonWorkingUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({ day: day, year: currentYear, status: status })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            calendars.forEach(cal => cal.refetchEvents());
                        } else {
                            Swal.fire("{{ __('Error') }}", "{{ __('Error al guardar la configuración') }}", "error");
                        }
                    })
                    .catch(error => {
                        console.error(error);
                        Swal.fire("{{ __('Error') }}", "{{ __('Error al conectar con el servidor') }}", "error");
                    });
                }

                // Checkboxes para sábado y domingo sin trabajar
                document.getElementById('saturdayOff').addEventListener('change', function() {
                    saveNonWorking('saturday', this.checked ? 1 : 0);
                });
                document.getElementById('sundayOff').addEventListener('change', function() {
                    saveNonWorking('sunday', this.checked ? 1 : 0);
                });
            });
        </script>

        <!-- app.js already loaded at the top of scripts -->
    @endpush
</x-app-layout>
@endcan
