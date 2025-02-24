<x-app-layout>
    <head>
        <!-- Incluir CSS de Select2 desde CDN -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <!-- Incluir jQuery y Select2 desde CDN con defer -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" defer></script>
        <!-- Cargar el bundle compilado con Vite -->
        @vite(['resources/js/app.js'])
    </head>

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

    <!-- Opciones para contactos (se asume que $contacts se pasa desde el controlador) -->
    <script>
        const contactOptions = `
            <option value="">{{ __("Select a contact") }}</option>
            @foreach($contacts as $contact)
                <option value="{{ $contact->id }}">{{ $contact->name }}</option>
            @endforeach
        `;
    </script>

    <script>
        // Función auxiliar para formatear fechas a "YYYY-MM-DDTHH:MM"
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

        // Función para generar el HTML del formulario (crear/editar evento)
        function generateEventForm(data = {}) {
            return `
                <input id="swal-event-title" class="swal2-input" placeholder="{{ __("Title") }}" value="${data.title || ''}">
                <input id="swal-event-start-date" type="datetime-local" class="swal2-input" placeholder="{{ __("Start Date") }}" value="${data.start_date || ''}">
                <input id="swal-event-end-date" type="datetime-local" class="swal2-input" placeholder="{{ __("End Date (optional)") }}" value="${data.end_date || ''}">
                <input id="swal-event-video_conferencia" class="swal2-input" placeholder="{{ __("Video Conferencia (Optional)") }}" value="${data.video_conferencia || ''}">
                <select id="swal-event-contact" class="swal2-input">
                    ${contactOptions}
                </select>
                <select id="swal-event-category" class="swal2-input">
                    <option value="">{{ __("Select a category") }}</option>
                    <option value="business" ${(data.category === 'business') ? 'selected' : ''}>{{ __("Business") }}</option>
                    <option value="personal" ${(data.category === 'personal') ? 'selected' : ''}>{{ __("Personal") }}</option>
                    <option value="holiday" ${(data.category === 'holiday') ? 'selected' : ''}>{{ __("Holiday") }}</option>
                    <option value="family" ${(data.category === 'family') ? 'selected' : ''}>{{ __("Family") }}</option>
                    <option value="meeting" ${(data.category === 'meeting') ? 'selected' : ''}>{{ __("Meeting") }}</option>
                    <option value="etc" ${(data.category === 'etc') ? 'selected' : ''}>{{ __("Others") }}</option>
                </select>
            `;
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Rutas seguras para eventos
            var eventsFetchUrl = "{{ str_replace('http://', 'https://', route('events.fetch')) }}";
            var eventsStoreUrl = "{{ str_replace('http://', 'https://', route('events.store')) }}";
            var updateUrlTemplate = "{{ str_replace('http://', 'https://', route('events.update', ['id' => ':id'])) }}";
            var destroyUrlTemplate = "{{ str_replace('http://', 'https://', route('events.destroy', ['id' => ':id'])) }}";

            // Inicializar FullCalendar (usando FullCalendar importado vía bundle)
            var calendarEl = document.getElementById('calendar');
            var calendar = new Calendar(calendarEl, {
                plugins: [ dayGridPlugin, timeGridPlugin, listPlugin ],
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
                    var eventObj = info.event;
                    const eventData = {
                        title: eventObj.title,
                        start_date: formatDateTimeLocal(eventObj.start),
                        end_date: eventObj.end ? formatDateTimeLocal(eventObj.end) : '',
                        video_conferencia: eventObj.extendedProps.video_conferencia || '',
                        contact_id: eventObj.extendedProps.contact_id || '',
                        category: eventObj.extendedProps.category || ''
                    };

                    Swal.fire({
                        title: '{{ __("Edit Event") }}',
                        width: 1200,
                        html: generateEventForm(eventData),
                        didOpen: () => {
                            // Inicializar Select2 (usando CDN)
                            $('#swal-event-contact').select2({
                                dropdownParent: Swal.getPopup()
                            });
                            if(eventData.contact_id) {
                                $('#swal-event-contact').val(eventData.contact_id).trigger('change');
                            }
                        },
                        focusConfirm: false,
                        showCancelButton: true,
                        confirmButtonText: '{{ __("Save Changes") }}',
                        showDenyButton: true,
                        denyButtonText: '{{ __("Delete Event") }}',
                        preConfirm: () => {
                            const newTitle = document.getElementById('swal-event-title').value;
                            const newStartDate = document.getElementById('swal-event-start-date').value;
                            const newEndDate = document.getElementById('swal-event-end-date').value;
                            const newVideoConferencia = document.getElementById('swal-event-video_conferencia').value;
                            const newContact = document.getElementById('swal-event-contact').value;
                            const newCategory = document.getElementById('swal-event-category').value;

                            if (!newTitle || !newStartDate || !newCategory) {
                                Swal.showValidationMessage('{{ __("Please complete the required fields: Title, Start Date, and Category") }}');
                                return false;
                            }
                            return {
                                title: newTitle,
                                start_date: newStartDate,
                                end_date: newEndDate,
                                video_conferencia: newVideoConferencia,
                                contact_id: newContact,
                                category: newCategory
                            };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            var updatedData = result.value;
                            var updateUrl = updateUrlTemplate.replace(':id', eventObj.id);
                            var formData = new FormData();
                            formData.append('event-title', updatedData.title);
                            formData.append('event-start-date', updatedData.start_date);
                            formData.append('event-end-date', updatedData.end_date);
                            formData.append('event-video_conferencia', updatedData.video_conferencia);
                            formData.append('event-contact_id', updatedData.contact_id);
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
                                if (data.success) {
                                    calendar.refetchEvents();
                                    Swal.fire('{{ __("Success") }}', '{{ __("The event has been updated successfully") }}', 'success');
                                } else {
                                    Swal.fire('{{ __("Error") }}', '{{ __("There was a problem updating the event") }}', 'error');
                                }
                            })
                            .catch(error => {
                                Swal.fire('{{ __("Error") }}', '{{ __("Error in the request") }}', 'error');
                            });
                        } else if (result.isDenied) {
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
                                        if (data.success) {
                                            calendar.refetchEvents();
                                            Swal.fire('{{ __("Deleted") }}', '{{ __("The event has been deleted successfully") }}', 'success');
                                        } else {
                                            Swal.fire('{{ __("Error") }}', '{{ __("There was a problem deleting the event") }}', 'error');
                                        }
                                    })
                                    .catch(error => {
                                        Swal.fire('{{ __("Error") }}', '{{ __("Error in the request") }}', 'error');
                                    });
                                }
                            });
                        }
                    });
                }
            });

            calendar.render();

            // Funcionalidad para agregar un nuevo evento
            document.querySelector('.add-event').addEventListener('click', function() {
                Swal.fire({
                    title: '{{ __("Add Event") }}',
                    width: 1200,
                    html: generateEventForm(),
                    didOpen: () => {
                        $('#swal-event-contact').select2({
                            dropdownParent: Swal.getPopup()
                        });
                    },
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: '{{ __("Save") }}',
                    preConfirm: () => {
                        const title = document.getElementById('swal-event-title').value;
                        const startDate = document.getElementById('swal-event-start-date').value;
                        const endDate = document.getElementById('swal-event-end-date').value;
                        const videoConferencia = document.getElementById('swal-event-video_conferencia').value;
                        const contact = document.getElementById('swal-event-contact').value;
                        const category = document.getElementById('swal-event-category').value;

                        if (!title || !startDate || !category) {
                            Swal.showValidationMessage('{{ __("Please complete the required fields: Title, Start Date, and Category") }}');
                            return false;
                        }
                        return {
                            title: title,
                            start_date: startDate,
                            end_date: endDate,
                            video_conferencia: videoConferencia,
                            contact_id: contact,
                            category: category
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const eventData = result.value;
                        const formData = new FormData();
                        formData.append('event-title', eventData.title);
                        formData.append('event-start-date', eventData.start_date);
                        formData.append('event-end-date', eventData.end_date);
                        formData.append('event-video_conferencia', eventData.video_conferencia);
                        formData.append('event-contact_id', eventData.contact_id);
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
                            if (data.success) {
                                calendar.refetchEvents();
                                Swal.fire('{{ __("Success") }}', '{{ __("The event has been added successfully") }}', 'success');
                            } else {
                                Swal.fire('{{ __("Error") }}', '{{ __("There was a problem saving the event") }}', 'error');
                            }
                        })
                        .catch(error => {
                            Swal.fire('{{ __("Error") }}', '{{ __("Error in the request") }}', 'error');
                        });
                    }
                });
            });
        });
    </script>
</x-app-layout>
