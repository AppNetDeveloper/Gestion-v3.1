<x-app-layout>
    <div class="space-y-8">
        <!-- Encabezado general -->
        <div class="flex flex-wrap justify-between items-center mb-4">
            <div>
                <h4 class="font-medium lg:text-2xl text-xl capitalize text-slate-900 inline-block mb-1 sm:mb-0">
                    {{ __('Kanban - Turnos por Día') }}
                </h4>
            </div>
            <div class="flex gap-2">
                <!-- Botón de guardar cambios con icono -->
                <button id="save-changes" title="{{ __('Guardar Cambios') }}" class="bg-green-500 hover:bg-green-600 text-white p-3 rounded" data-tippy-content="{{ __('Guardar Cambios') }}">
                    <iconify-icon icon="heroicons-outline:save" width="24" height="24"></iconify-icon>
                </button>
                <!-- Botón de restablecer asignaciones con icono y color danger -->
                <button id="reset-assignments" title="{{ __('Restablecer Asignaciones') }}" class="bg-red-500 hover:bg-red-600 text-white p-3 rounded" data-tippy-content="{{ __('Restablecer Asignaciones') }}">
                    <iconify-icon icon="mdi:refresh" width="24" height="24"></iconify-icon>
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
                    // Filtramos para mostrar solo los usuarios que tengan el permiso 'timecontrolstatus index'
                    $filteredAvailableUsers = $availableUsers->filter(function($user) {
                        return $user->can('timecontrolstatus index');
                    });
                @endphp

                <div id="{{ strtolower($day) }}" data-day-index="{{ $loop->index }}" class="w-[320px] flex-none min-h-screen h-full rounded transition-all duration-100 shadow-none bg-slate-200 dark:bg-slate-700">
                    <!-- Encabezado del día -->
                    <div class="relative flex justify-between items-center bg-white dark:bg-slate-800 rounded shadow-base px-6 py-5">
                        <span class="absolute left-0 top-1/2 -translate-y-1/2 h-8 w-[2px] bg-primary-500"></span>
                        <h3 class="text-lg text-slate-900 dark:text-white font-medium capitalize">
                            {{ __($day) }}
                        </h3>
                        <div class="flex items-center space-x-2">
                            <!-- Botón para agregar turno -->
                            <button class="scale border border-slate-200 dark:border-slate-700 dark:text-slate-400 rounded h-6 w-6 flex items-center justify-center text-base text-slate-600" data-tippy-content="{{ __('Agregar Turno') }}" data-tippy-theme="dark">
                                <iconify-icon icon="ph:plus-bold"></iconify-icon>
                            </button>
                            @if(!$loop->first)
                                <!-- Botón de copiar (a partir del segundo día) -->
                                <button class="scale border border-slate-200 dark:border-slate-700 dark:text-slate-400 rounded h-6 w-6 flex items-center justify-center text-base text-slate-600 copy-btn" data-day-index="{{ $loop->index }}" onclick="copyPreviousDayUsers(this)" data-tippy-content="{{ __('Copiar Usuarios') }}" data-tippy-theme="dark">
                                    <iconify-icon icon="mdi:content-copy"></iconify-icon>
                                </button>
                            @else
                                <!-- Botón de eliminar en el primer día -->
                                <button class="scale border border-slate-200 dark:border-slate-700 dark:text-slate-400 rounded h-6 w-6 flex items-center justify-center text-base text-slate-600 delete-btn" data-tippy-content="{{ __('Eliminar') }}" data-tippy-theme="danger">
                                    <iconify-icon icon="fluent:delete-20-regular"></iconify-icon>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Área interna del día -->
                    <div class="p-2">
                        @if($shiftsOfDay->count())
                            <!-- Pool de empleados disponibles -->
                            <div class="mb-4">
                                <strong class="block mb-1 text-sm text-slate-700 dark:text-slate-300">{{ __('Empleados Disponibles:') }}</strong>
                                <div id="pool-{{ strtolower($day) }}" class="users-droppable flex flex-wrap gap-2 p-2 bg-slate-100 dark:bg-slate-600 rounded">
                                    @foreach($filteredAvailableUsers as $user)
                                        <div class="employee-card bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 px-2 py-1 rounded text-xs text-slate-800 dark:text-slate-200 cursor-move" data-employee-id="{{ $user->id }}">
                                            {{ $user->name }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Contenedor de turnos del día -->
                        <div id="shifts-{{ strtolower($day) }}" class="h-full min-h-[calc(100vh-100px)]">
                            @foreach($shiftsOfDay as $shiftDay)
                                <div class="mb-4">
                                    <!-- Tarjeta de turno -->
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
                                                            <span>{{ __('Editar') }}</span>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="#" class="hover:bg-slate-900 dark:hover:bg-slate-600 dark:hover:bg-opacity-70 hover:text-white w-full px-4 py-2 text-sm dark:text-slate-300 cursor-pointer flex space-x-2 items-center capitalize rtl:space-x-reverse">
                                                            <iconify-icon icon="fluent:delete-28-regular"></iconify-icon>
                                                            <span>{{ __('Eliminar') }}</span>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </header>

                                        <!-- Información del turno -->
                                        <p class="text-slate-600 dark:text-slate-400 text-sm mb-4">
                                            <strong>{{ __('Horario:') }}</strong>
                                            @if($shiftDay->isSplitShift())
                                                {{ optional($shiftDay->start_time)->format('H:i') }} -
                                                {{ optional($shiftDay->split_start_time)->format('H:i') }}
                                                <span class="text-xs text-slate-400">({{ __('descanso') }})</span>
                                                {{ optional($shiftDay->split_end_time)->format('H:i') }} -
                                                {{ optional($shiftDay->end_time)->format('H:i') }}
                                            @else
                                                {{ optional($shiftDay->start_time)->format('H:i') }} -
                                                {{ optional($shiftDay->end_time)->format('H:i') }}
                                            @endif
                                            <br>
                                            <strong>{{ __('Horas efectivas:') }}</strong>
                                            {{ $shiftDay->effective_hours ?? __('N/A') }}
                                        </p>

                                        <!-- Contenedor para asignar empleados a este turno -->
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
             * Copia la asignación de usuarios del día anterior y elimina los duplicados del pool.
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

            // Restablece las asignaciones moviendo todas las tarjetas de empleado de vuelta al pool.
            document.getElementById('reset-assignments').addEventListener('click', function() {
                console.log('Botón Restablecer Asignaciones clickeado');
                Swal.fire({
                    title: @json(__('Confirmación')),
                    text: @json(__('Estás a punto de restablecer todas las asignaciones. ¿Estás seguro?')),
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: @json(__('Sí, restablecer')),
                    cancelButtonText: @json(__('Cancelar'))
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.querySelectorAll('[data-day-index]').forEach(dayContainer => {
                            const poolContainer = dayContainer.querySelector('[id^="pool-"]');
                            const shiftContainers = dayContainer.querySelectorAll('[id^="shift-"][id$="-users"]');
                            shiftContainers.forEach(shiftContainer => {
                                shiftContainer.querySelectorAll('.employee-card').forEach(card => {
                                    poolContainer.appendChild(card);
                                });
                            });
                        });
                        Swal.fire({
                            title: @json(__('Completado')),
                            text: @json(__('Se han restablecido todas las asignaciones.')),
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                });
            });

            // Guarda los cambios: actualiza o elimina las asignaciones en la base de datos.
            document.getElementById('save-changes').addEventListener('click', function() {
                console.log('Botón Guardar Cambios clickeado');
                Swal.fire({
                    title: @json(__('Confirmación')),
                    text: @json(__('Estás a punto de guardar los cambios. ¿Estás seguro?')),
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: @json(__('Sí, guardar')),
                    cancelButtonText: @json(__('Cancelar'))
                }).then((result) => {
                    if (result.isConfirmed) {
                        const shiftContainers = document.querySelectorAll('[id^="shift-"][id$="-users"]');
                        let promises = [];
                        shiftContainers.forEach(container => {
                            let parts = container.id.split('-');
                            let shiftDayId = parts[1];
                            let userCards = container.querySelectorAll('.employee-card');
                            let userIds = Array.from(userCards).map(card => card.getAttribute('data-employee-id'));
                            if (userIds.length === 0) {
                                let promise = fetch(`/shift-days/${shiftDayId}/users`, {
                                    method: 'DELETE',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                    }
                                }).then(response => response.json());
                                promises.push(promise);
                            } else {
                                let promise = fetch(`/shift-days/${shiftDayId}/update-users`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                    },
                                    body: JSON.stringify({ users: userIds })
                                }).then(response => response.json());
                                promises.push(promise);
                            }
                        });
                        Promise.all(promises).then(results => {
                            console.log('Todos los turnos se han actualizado correctamente.');
                            Swal.fire({
                                title: @json(__('Éxito')),
                                text: @json(__('Todos los turnos se han actualizado correctamente.')),
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        }).catch(error => {
                            console.error('Error al actualizar los turnos:', error);
                            Swal.fire({
                                title: @json(__('Error')),
                                text: @json(__('Hubo un error al actualizar los turnos.')),
                                icon: 'error'
                            });
                        });
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
