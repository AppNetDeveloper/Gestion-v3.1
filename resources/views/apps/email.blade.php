<x-app-layout>
    <div class="space-y-8">
        <!-- Calendario y filtros -->
        <div class="dashcode-calender">
            <h4 class="font-medium lg:text-2xl text-xl capitalize text-slate-900 inline-block ltr:pr-4 rtl:pl-4 mb-1 sm:mb-0 mb-6">
                Calendario
            </h4>
            <div class="grid grid-cols-12 gap-4">
                <!-- Sidebar de acciones y filtros -->
                <div class="col-span-12 lg:col-span-3 card p-6">
                    <button class="btn btn-dark block w-full add-event">
                        Añadir Evento
                    </button>
                    <!-- Puedes agregar aquí los filtros de categorías -->
                </div>
                <!-- Calendario -->
                <div class="col-span-12 lg:col-span-9 card p-6">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>

        <!-- Modal para agregar evento -->
        <div class="addmodal-wrapper" id="addeventModal" style="display:none;">
            <div class="modal-overlay"></div>
            <div class="modal-content">
                <div class="flex min-h-full justify-center text-center p-6 items-start">
                    <div class="w-full transform overflow-hidden rounded-md bg-white dark:bg-slate-800 text-left align-middle shadow-xl transition-all max-w-xl">
                        <div class="relative overflow-hidden py-4 px-5 text-white flex justify-between bg-slate-900 dark:bg-slate-800">
                            <h2 class="capitalize leading-6 tracking-wider font-medium text-base text-white">Evento</h2>
                            <button class="text-[22px] close-event-modal">
                                <iconify-icon icon="heroicons:x-mark"></iconify-icon>
                            </button>
                        </div>
                        <div class="px-6 py-8">
                            <form id="add-event-form" class="space-y-5">
                                <div class="fromGroup">
                                    <label for="event-title" class="form-label">Título:</label>
                                    <input type="text" id="event-title" name="event-title" placeholder="Añadir Título" class="form-control" required>
                                </div>
                                <div class="fromGroup">
                                    <label for="event-start-date" class="form-label">Fecha Inicio</label>
                                    <input class="form-control py-2" id="event-start-date" name="event-start-date" type="datetime-local" required>
                                </div>
                                <div class="fromGroup">
                                    <label for="event-end-date" class="form-label">Fecha Fin</label>
                                    <input class="form-control py-2" id="event-end-date" name="event-end-date" type="datetime-local">
                                </div>
                                <div class="fromGroup">
                                    <label for="event-category" class="form-label">Categoría:</label>
                                    <select id="event-category" name="event-category" required class="form-control">
                                        <option value="">Selecciona una categoría</option>
                                        <option value="business">Negocios</option>
                                        <option value="personal">Personal</option>
                                        <option value="holiday">Vacaciones</option>
                                        <option value="family">Familia</option>
                                        <option value="meeting">Reunión</option>
                                        <option value="etc">Otros</option>
                                    </select>
                                </div>
                                <div class="text-right">
                                    <button type="submit" id="submit-button" class="btn btn-dark">Añadir Evento</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Incluir FullCalendar CSS y JS desde CDN -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicialización de FullCalendar
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: {
                    url: "{{ route('events.fetch') }}",
                    method: 'GET'
                }
            });
            calendar.render();

            // Mostrar el modal para agregar evento
            document.querySelector('.add-event').addEventListener('click', function() {
                document.getElementById('addeventModal').style.display = 'block';
            });
            // Cerrar el modal
            document.querySelector('.close-event-modal').addEventListener('click', function() {
                document.getElementById('addeventModal').style.display = 'none';
            });

            // Envío del formulario para agregar evento vía AJAX
            document.getElementById('add-event-form').addEventListener('submit', function(e) {
                e.preventDefault();

                var formData = new FormData(this);

                fetch("{{ route('events.store') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        // Refrescar eventos en el calendario
                        calendar.refetchEvents();
                        // Ocultar modal y resetear formulario
                        document.getElementById('addeventModal').style.display = 'none';
                        this.reset();
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        });
    </script>
</x-app-layout>
