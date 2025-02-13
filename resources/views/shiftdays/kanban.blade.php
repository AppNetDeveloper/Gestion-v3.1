<x-app-layout>
    <div class="space-y-8">
        <!-- Encabezado general -->
        <div class="flex flex-wrap justify-between items-center mb-4">
            <div>
                <h4 class="font-medium lg:text-2xl text-xl capitalize text-slate-900 inline-block mb-1 sm:mb-0">
                    Kanban - Turnos por Día
                </h4>
            </div>
            <div class="flex gap-2">
                <button id="save-changes" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded" data-tippy-content="Guardar Cambios">
                    Guardar Cambios
                </button>
                <button id="import-last-turn" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded" data-tippy-content="Importar Último Turno">
                    Importar Último Turno
                </button>
            </div>
        </div>

        <!-- Contenedor principal de días -->
        <div class="flex space-x-6 overflow-hidden overflow-x-auto pb-4 rtl:space-x-reverse">
            <!-- Iteramos los días de la semana -->
            @foreach($daysOfWeek as $day)
                @php
                    // Obtenemos los turnos para el día actual (o colección vacía)
                    $shiftsOfDay = $shiftDaysGrouped->get($day) ?? collect([]);
                    // Recolectamos los IDs de los usuarios asignados en los turnos de este día
                    $assignedUserIds = collect();
                    foreach ($shiftsOfDay as $shiftDay) {
                        $assignedUserIds = $assignedUserIds->merge($shiftDay->users->pluck('id'));
                    }
                    // Los usuarios disponibles son aquellos que no están asignados en ningún turno de este día
                    $availableUsers = $users->reject(function($user) use ($assignedUserIds) {
                        return $assignedUserIds->contains($user->id);
                    });
                @endphp

                <div id="{{ strtolower($day) }}" data-day-index="{{ $loop->index }}" class="w-[320px] flex-none min-h-screen h-full rounded transition-all duration-100 shadow-none bg-slate-200 dark:bg-slate-700">
                    <!-- Encabezado del día -->
                    <div class="relative flex justify-between items-center bg-white dark:bg-slate-800 rounded shadow-base px-6 py-5">
                        <span class="absolute left-0 top-1/2 -translate-y-1/2 h-8 w-[2px] bg-primary-500"></span>
                        <h3 class="text-lg text-slate-900 dark:text-white font-medium capitalize">
                            {{ $day }}
                        </h3>
                        <div class="flex items-center space-x-2">
                            <!-- Botón para agregar turno -->
                            <button class="scale border border-slate-200 dark:border-slate-700 dark:text-slate-400 rounded h-6 w-6 flex items-center justify-center text-base text-slate-600" data-tippy-content="Add Card" data-tippy-theme="dark">
                                <iconify-icon icon="ph:plus-bold"></iconify-icon>
                            </button>
                            @if(!$loop->first)
                                <!-- A partir del segundo día, botón de copiar -->
                                <button class="scale border border-slate-200 dark:border-slate-700 dark:text-slate-400 rounded h-6 w-6 flex items-center justify-center text-base text-slate-600 copy-btn" data-day-index="{{ $loop->index }}" onclick="copyPreviousDayUsers(this)" data-tippy-content="Copy Users" data-tippy-theme="dark">
                                    <iconify-icon icon="mdi:content-copy"></iconify-icon>
                                </button>
                            @else
                                <!-- En el primer día se muestra el botón de eliminar -->
                                <button class="scale border border-slate-200 dark:border-slate-700 dark:text-slate-400 rounded h-6 w-6 flex items-center justify-center text-base text-slate-600 delete-btn" data-tippy-content="Delete" data-tippy-theme="danger">
                                    <iconify-icon icon="fluent:delete-20-regular"></iconify-icon>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Área interna del día -->
                    <div class="p-2">
                        @if($shiftsOfDay->count())
                            <!-- Pool de empleados disponibles (usuarios reales) -->
                            <div class="mb-4">
                                <strong class="block mb-1 text-sm text-slate-700 dark:text-slate-300">Empleados Disponibles:</strong>
                                <div id="pool-{{ strtolower($day) }}" class="users-droppable flex flex-wrap gap-2 p-2 bg-slate-100 dark:bg-slate-600 rounded">
                                    @foreach($availableUsers as $user)
                                        <div class="employee-card bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 px-2 py-1 rounded text-xs text-slate-800 dark:text-slate-200 cursor-move" data-employee-id="{{ $user->id }}">
                                            {{ $user->name }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Contenedor de turnos del día (fijos, no arrastrables) -->
                        <div id="shifts-{{ strtolower($day) }}" class="h-full min-h-[calc(100vh-100px)]">
                            @foreach($shiftsOfDay as $shiftDay)
                                <div class="mb-4">
                                    <!-- Tarjeta de turno (fija) -->
                                    <div class="card rounded-md bg-white dark:bg-slate-800 shadow-base custom-class card-body p-6">
                                        <header class="flex justify-between items-end mb-2">
                                            <h4 class="font-medium text-base dark:text-slate-200 text-slate-900">
                                                {{ $shiftDay->shift->name }}
                                            </h4>
                                            <!-- Menú dropdown -->
                                            <div class="dropstart relative">
                                                <button class="inline-flex justify-center items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <iconify-icon class="text-xl ltr:ml-2 rtl:mr-2" icon="heroicons-outline:dots-vertical"></iconify-icon>
                                                </button>
                                                <ul class="dropdown-menu min-w-max absolute text-sm text-slate-700 dark:text-white hidden bg-white dark:bg-slate-700 shadow z-[2] float-left overflow-hidden list-none text-left rounded-lg mt-1 m-0 bg-clip-padding border-none">
                                                    <li>
                                                        <a href="#" class="hover:bg-slate-900 dark:hover:bg-slate-600 dark:hover:bg-opacity-70 hover:text-white w-full border-b border-b-gray-500 border-opacity-10 px-4 py-2 text-sm dark:text-slate-300 cursor-pointer flex space-x-2 items-center capitalize rtl:space-x-reverse">
                                                            <iconify-icon icon="clarity:note-edit-line"></iconify-icon>
                                                            <span>Edit</span>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="#" class="hover:bg-slate-900 dark:hover:bg-slate-600 dark:hover:bg-opacity-70 hover:text-white w-full px-4 py-2 text-sm dark:text-slate-300 cursor-pointer flex space-x-2 items-center capitalize rtl:space-x-reverse">
                                                            <iconify-icon icon="fluent:delete-28-regular"></iconify-icon>
                                                            <span>Delete</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </header>

                                        <!-- Información del turno -->
                                        <p class="text-slate-600 dark:text-slate-400 text-sm mb-4">
                                            <strong>Horario:</strong>
                                            @if($shiftDay->isSplitShift())
                                                {{ optional($shiftDay->start_time)->format('H:i') }} -
                                                {{ optional($shiftDay->split_start_time)->format('H:i') }}
                                                <span class="text-xs text-slate-400">(descanso)</span>
                                                {{ optional($shiftDay->split_end_time)->format('H:i') }} -
                                                {{ optional($shiftDay->end_time)->format('H:i') }}
                                            @else
                                                {{ optional($shiftDay->start_time)->format('H:i') }} -
                                                {{ optional($shiftDay->end_time)->format('H:i') }}
                                            @endif
                                            <br>
                                            <strong>Horas efectivas:</strong>
                                            {{ $shiftDay->effective_hours ?? 'N/A' }}
                                        </p>

                                        <!-- Contenedor para asignar empleados a este turno (droppable para empleados) -->
                                        <div id="shift-{{ $shiftDay->id }}-users" class="users-droppable flex flex-wrap gap-2 p-2 bg-slate-50 dark:bg-slate-700 rounded min-h-[50px]">
                                            @foreach($shiftDay->users as $user)
                                                <div class="employee-card bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 px-2 py-1 rounded text-xs text-slate-800 dark:text-slate-200 cursor-move" data-employee-id="{{ $user->id }}">
                                                    {{ $user->name }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @push('scripts')
        <script type="module">
            // NOTA: No se instancia Dragula para mover turnos, ya que éstos son fijos.
            // Instancia Dragula para mover empleados (pool y asignados) dentro de cada día
            @foreach($daysOfWeek as $day)
                (function(){
                    const dayContainer = document.getElementById("{{ strtolower($day) }}");
                    if(dayContainer) {
                        const userContainers = dayContainer.querySelectorAll('.users-droppable');
                        dragula(Array.from(userContainers));
                    }
                })();
            @endforeach

            /**
             * Función que copia la asignación de usuarios (innerHTML de cada contenedor de turnos)
             * del día anterior al día actual y, luego, elimina del pool del día actual los usuarios copiados.
             * Se empareja cada contenedor por su posición.
             */
            window.copyPreviousDayUsers = function(button) {
                const currentIndex = parseInt(button.getAttribute('data-day-index'));
                const currentDay = document.querySelector('[data-day-index="'+ currentIndex +'"]');
                const previousDay = document.querySelector('[data-day-index="'+ (currentIndex - 1) +'"]');
                if (!previousDay || !currentDay) return;

                const previousUserContainers = previousDay.querySelectorAll('[id^="shift-"][id$="-users"]');
                const currentUserContainers = currentDay.querySelectorAll('[id^="shift-"][id$="-users"]');

                previousUserContainers.forEach((prevContainer, index) => {
                    if (currentUserContainers[index]) {
                        currentUserContainers[index].innerHTML = prevContainer.innerHTML;
                    }
                });

                // Elimina del pool del día actual los empleados que se copiaron
                const currentDayId = currentDay.id;
                const poolContainer = document.getElementById('pool-' + currentDayId);
                if (poolContainer) {
                    let copiedEmployeeIds = new Set();
                    currentUserContainers.forEach(container => {
                        container.querySelectorAll('.employee-card').forEach(card => {
                            const empId = card.getAttribute('data-employee-id');
                            if (empId) {
                                copiedEmployeeIds.add(empId);
                            }
                        });
                    });
                    poolContainer.querySelectorAll('.employee-card').forEach(card => {
                        const empId = card.getAttribute('data-employee-id');
                        if (copiedEmployeeIds.has(empId)) {
                            card.remove();
                        }
                    });
                }
            }

            // Función para guardar los cambios de asignación de empleados en cada turno
            document.getElementById('save-changes').addEventListener('click', function() {
                // Selecciona todos los contenedores de turno con id que empiece por "shift-" y termine en "-users"
                const shiftContainers = document.querySelectorAll('[id^="shift-"][id$="-users"]');
                let promises = [];
                shiftContainers.forEach(container => {
                    // Se asume que el id es del tipo "shift-<shiftDayId>-users"
                    let parts = container.id.split('-');
                    let shiftDayId = parts[1];
                    // Recolecta los IDs de los empleados que están en el contenedor
                    let userCards = container.querySelectorAll('.employee-card');
                    let userIds = Array.from(userCards).map(card => card.getAttribute('data-employee-id'));

                    // Envía la petición AJAX al endpoint correspondiente
                    let promise = fetch(`/shift-days/${shiftDayId}/update-users`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ users: userIds })
                    }).then(response => response.json());
                    promises.push(promise);
                });
                // Espera a que todas las peticiones se completen
                Promise.all(promises).then(results => {
                    console.log('Todos los turnos se han actualizado correctamente.');
                    // Aquí podrías mostrar una notificación o mensaje de éxito
                }).catch(error => {
                    console.error('Error al actualizar los turnos:', error);
                });
            });

            // (Opcional) Función para importar el último turno (a implementar según tu lógica)
            document.getElementById('import-last-turn').addEventListener('click', function() {
                // Aquí puedes implementar la lógica para importar la configuración del último turno.
                console.log('Importar Último Turno: función pendiente de implementar.');
            });
        </script>
    @endpush
</x-app-layout>
