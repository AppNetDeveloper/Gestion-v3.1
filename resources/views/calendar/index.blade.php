<x-app-layout>
    <div class="space-y-8">
        <!-- Calendario y filtros -->
        <div class="dashcode-calender">
            <h4 class="font-medium lg:text-2xl text-xl capitalize text-slate-900 inline-block ltr:pr-4 rtl:pl-4 mb-1 sm:mb-0 mb-6">
                {{ __("Calendar") }}
            </h4>
            <div class="grid grid-cols-12 gap-4">
                <!-- Sidebar de acciones y filtros -->
                <div class="col-span-12 lg:col-span-3 card p-6">
                    <button class="btn btn-dark block w-full add-event">
                        {{ __("Add Event") }}
                    </button>
                </div>
                <!-- Calendario -->
                <div class="col-span-12 lg:col-span-9 card p-6">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Incluir FullCalendar CSS y JS desde CDN -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <!-- Incluir SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Función auxiliar para formatear fechas a formato "YYYY-MM-DDTHH:MM"
        function formatDateTimeLocal(date) {
            if (!date) return '';
            const d = new Date(date);
            const year = d.getFullYear();
            const month = ('0' + (d.getMonth() + 1)).slice(-2);
            const day = ('0' + d.getDate()).slice(-2);
            const hours = ('0' + d.getHours()).slice(-2);
            const minutes = ('0' + d.getMinutes()).slice(-2);
            return `${year}-${month}-${day}T${hours}:${minutes}`;
        }

        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOMContentLoaded fired');

            // Forzar URLs seguras reemplazando http:// por https://
            var eventsFetchUrl = "{{ str_replace('http://', 'https://', route('events.fetch')) }}";
            var eventsStoreUrl = "{{ str_replace('http://', 'https://', route('events.store')) }}";
            var updateUrlTemplate = "{{ str_replace('http://', 'https://', route('events.update', ['id' => ':id'])) }}";
            var destroyUrlTemplate = "{{ str_replace('http://', 'https://', route('events.destroy', ['id' => ':id'])) }}";

            // Inicializar FullCalendar con eventClick para editar eventos
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: {
                    url: eventsFetchUrl,
                    method: 'GET'
                },
                eventClick: function(info) {
                    console.log("Evento clickeado: ", info.event);
                    var eventObj = info.event;
                    // Preparar valores para el formulario de edición
                    var title = eventObj.title;
                    var startDate = formatDateTimeLocal(eventObj.start);
                    var endDate = eventObj.end ? formatDateTimeLocal(eventObj.end) : '';
                    var category = eventObj.extendedProps.category || '';

                    Swal.fire({
                        title: '{{ __("Edit Event") }}',
                        width: 1200,
                        html:
                            '<input id="swal-event-title" class="swal2-input" placeholder="{{ __("Title") }}" value="' + title + '">' +
                            '<input id="swal-event-start-date" type="datetime-local" class="swal2-input" placeholder="{{ __("Start Date") }}" value="' + startDate + '">' +
                            '<input id="swal-event-end-date" type="datetime-local" class="swal2-input" placeholder="{{ __("End Date (optional)") }}" value="' + endDate + '">' +
                            '<select id="swal-event-category" class="swal2-input">' +
                                '<option value="">{{ __("Select a category") }}</option>' +
                                '<option value="business" ' + (category === 'business' ? 'selected' : '') + '>{{ __("Business") }}</option>' +
                                '<option value="personal" ' + (category === 'personal' ? 'selected' : '') + '>{{ __("Personal") }}</option>' +
                                '<option value="holiday" ' + (category === 'holiday' ? 'selected' : '') + '>{{ __("Holiday") }}</option>' +
                                '<option value="family" ' + (category === 'family' ? 'selected' : '') + '>{{ __("Family") }}</option>' +
                                '<option value="meeting" ' + (category === 'meeting' ? 'selected' : '') + '>{{ __("Meeting") }}</option>' +
                                '<option value="etc" ' + (category === 'etc' ? 'selected' : '') + '>{{ __("Others") }}</option>' +
                            '</select>',
                        focusConfirm: false,
                        showCancelButton: true,
                        confirmButtonText: '{{ __("Save Changes") }}',
                        showDenyButton: true,
                        denyButtonText: '{{ __("Delete Event") }}',
                        preConfirm: () => {
                            const newTitle = document.getElementById('swal-event-title').value;
                            const newStartDate = document.getElementById('swal-event-start-date').value;
                            const newEndDate = document.getElementById('swal-event-end-date').value;
                            const newCategory = document.getElementById('swal-event-category').value;
                            if (!newTitle || !newStartDate || !newCategory) {
                                Swal.showValidationMessage('{{ __("Please complete the required fields: Title, Start Date, and Category") }}');
                                return false;
                            }
                            return {
                                title: newTitle,
                                start_date: newStartDate,
                                end_date: newEndDate,
                                category: newCategory
                            };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Actualizar el evento
                            var updatedData = result.value;
                            console.log("Datos actualizados: ", updatedData);
                            var updateUrl = updateUrlTemplate.replace(':id', eventObj.id);
                            var formData = new FormData();
                            formData.append('event-title', updatedData.title);
                            formData.append('event-start-date', updatedData.start_date);
                            formData.append('event-end-date', updatedData.end_date);
                            formData.append('event-category', updatedData.category);

                            fetch(updateUrl, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                    'X-HTTP-Method-Override': 'PUT'
                                },
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                console.log("Respuesta del servidor (update): ", data);
                                if (data.success) {
                                    calendar.refetchEvents();
                                    Swal.fire('{{ __("Success") }}', '{{ __("The event has been updated successfully") }}', 'success');
                                } else {
                                    Swal.fire('{{ __("Error") }}', '{{ __("There was a problem updating the event") }}', 'error');
                                }
                            })
                            .catch(error => {
                                console.error("Error en la petición fetch (update): ", error);
                                Swal.fire('{{ __("Error") }}', '{{ __("Error in the request") }}', 'error');
                            });
                        } else if (result.isDenied) {
                            // Solicitar confirmación para eliminar el evento
                            Swal.fire({
                                title: '{{ __("Are you sure?") }}',
                                text: '{{ __("This action cannot be undone") }}',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: '{{ __("Yes, delete") }}',
                                cancelButtonText: '{{ __("Cancel") }}'
                            }).then((confirmResult) => {
                                if (confirmResult.isConfirmed) {
                                    var destroyUrl = destroyUrlTemplate.replace(':id', eventObj.id);
                                    fetch(destroyUrl, {
                                        method: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                            'X-HTTP-Method-Override': 'DELETE'
                                        }
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        console.log("Respuesta del servidor (destroy): ", data);
                                        if (data.success) {
                                            calendar.refetchEvents();
                                            Swal.fire('{{ __("Deleted") }}', '{{ __("The event has been deleted successfully") }}', 'success');
                                        } else {
                                            Swal.fire('{{ __("Error") }}', '{{ __("There was a problem deleting the event") }}', 'error');
                                        }
                                    })
                                    .catch(error => {
                                        console.error("Error en la petición fetch (destroy): ", error);
                                        Swal.fire('{{ __("Error") }}', '{{ __("Error in the request") }}', 'error');
                                    });
                                }
                            });
                        }
                    });
                }
            });
            calendar.render();

            // Funcionalidad para añadir un nuevo evento
            var addEventBtn = document.querySelector('.add-event');
            addEventBtn.addEventListener('click', function() {
                console.log("Botón '{{ __("Add Event") }}' clickeado");
                Swal.fire({
                    title: '{{ __("Add Event") }}',
                    width: 1200,
                    html:
                        '<input id="swal-event-title" class="swal2-input" placeholder="{{ __("Title") }}">' +
                        '<input id="swal-event-start-date" type="datetime-local" class="swal2-input" placeholder="{{ __("Start Date") }}">' +
                        '<input id="swal-event-end-date" type="datetime-local" class="swal2-input" placeholder="{{ __("End Date (optional)") }}">' +
                        '<select id="swal-event-category" class="swal2-input">' +
                            '<option value="">{{ __("Select a category") }}</option>' +
                            '<option value="business">{{ __("Business") }}</option>' +
                            '<option value="personal">{{ __("Personal") }}</option>' +
                            '<option value="holiday">{{ __("Holiday") }}</option>' +
                            '<option value="family">{{ __("Family") }}</option>' +
                            '<option value="meeting">{{ __("Meeting") }}</option>' +
                            '<option value="etc">{{ __("Others") }}</option>' +
                        '</select>',
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: '{{ __("Save") }}',
                    preConfirm: () => {
                        const title = document.getElementById('swal-event-title').value;
                        const startDate = document.getElementById('swal-event-start-date').value;
                        const endDate = document.getElementById('swal-event-end-date').value;
                        const category = document.getElementById('swal-event-category').value;
                        if (!title || !startDate || !category) {
                            Swal.showValidationMessage('{{ __("Please complete the required fields: Title, Start Date, and Category") }}');
                            return false;
                        }
                        return { title: title, start_date: startDate, end_date: endDate, category: category };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        var eventData = result.value;
                        console.log("Datos del evento:", eventData);
                        var formData = new FormData();
                        formData.append('event-title', eventData.title);
                        formData.append('event-start-date', eventData.start_date);
                        formData.append('event-end-date', eventData.end_date);
                        formData.append('event-category', eventData.category);

                        fetch(eventsStoreUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': "{{ csrf_token() }}"
                            },
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            console.log("Respuesta del servidor:", data);
                            if (data.success) {
                                calendar.refetchEvents();
                                Swal.fire('{{ __("Success") }}', '{{ __("The event has been added successfully") }}', 'success');
                            } else {
                                Swal.fire('{{ __("Error") }}', '{{ __("There was a problem saving the event") }}', 'error');
                            }
                        })
                        .catch(error => {
                            console.error("Error en la petición fetch:", error);
                            Swal.fire('{{ __("Error") }}', '{{ __("Error in the request") }}', 'error');
                        });
                    }
                });
            });
        });
    </script>
</x-app-layout>
