<x-app-layout>
    <div class="space-y-8">
        {{-- Encabezado general con título y botones de acción --}}
        <div class="flex flex-wrap justify-between items-center gap-4 mb-4">
            <div>
                <h4 class="font-medium lg:text-2xl text-xl capitalize text-slate-900 dark:text-white">
                    {{ __('Kanban - Turnos por Día') }}
                </h4>
            </div>
            <div class="flex gap-2">
                {{-- Botón Guardar Cambios --}}
                <button id="save-changes" title="{{ __('Guardar Cambios') }}" class="bg-green-500 hover:bg-green-600 text-white p-3 rounded shadow-md transition duration-150 ease-in-out" data-tippy-content="{{ __('Guardar Cambios') }}">
                    <iconify-icon icon="heroicons-outline:save" class="w-6 h-6"></iconify-icon>
                </button>
                {{-- Botón Restablecer Asignaciones --}}
                <button id="reset-assignments" title="{{ __('Restablecer Asignaciones') }}" class="bg-red-500 hover:bg-red-600 text-white p-3 rounded shadow-md transition duration-150 ease-in-out" data-tippy-content="{{ __('Restablecer Asignaciones') }}">
                    <iconify-icon icon="mdi:refresh" class="w-6 h-6"></iconify-icon>
                </button>
            </div>
        </div>

        {{-- Contenedor principal scrollable para los días --}}
        <div class="flex space-x-6 overflow-hidden overflow-x-auto pb-4 rtl:space-x-reverse">
            {{-- Iteración sobre los días de la semana --}}
            @foreach($daysOfWeek as $day)
                @php
                    // Obtener los turnos del día actual, o una colección vacía si no hay.
                    $shiftsOfDay = $shiftDaysGrouped->get($day) ?? collect([]);

                    // Obtener todos los IDs de usuarios ya asignados en *cualquier* turno de este día.
                    $assignedUserIdsThisDay = $shiftsOfDay->flatMap(function($shiftDay) {
                        return $shiftDay->users->pluck('id');
                    })->unique();

                    // Filtrar la lista general de usuarios para obtener los disponibles para este día.
                    // Un usuario está disponible si NO está en $assignedUserIdsThisDay
                    // y tiene el permiso 'timecontrolstatus index'.
                    $availableUsers = $users->reject(function($user) use ($assignedUserIdsThisDay) {
                        return $assignedUserIdsThisDay->contains($user->id);
                    })->filter(function($user) {
                        // Asegúrate de que la relación 'roles' y 'permissions' esté cargada si usas Spatie Permissions
                        // o ajusta según tu sistema de permisos.
                        return $user->can('timecontrolstatus index');
                    });
                @endphp

                {{-- Columna para cada día --}}
                <div id="{{ strtolower($day) }}" data-day-index="{{ $loop->index }}" class="w-[320px] flex-none min-h-screen h-full rounded transition-all duration-100 bg-slate-200 dark:bg-slate-700 shadow-sm">
                    {{-- Encabezado del día --}}
                    <div class="relative flex justify-between items-center bg-white dark:bg-slate-800 rounded-t shadow px-6 py-5">
                        {{-- Indicador visual --}}
                        <span class="absolute left-0 top-1/2 -translate-y-1/2 h-8 w-[2px] bg-primary-500 rounded-r-full"></span>
                        {{-- Nombre del día --}}
                        <h3 class="text-lg text-slate-900 dark:text-white font-medium capitalize">
                            {{ __($day) }}
                        </h3>
                        {{-- Botones de acción del día --}}
                        <div class="flex items-center space-x-2 rtl:space-x-reverse">
                            {{-- Botón Copiar Usuarios del día anterior (solo a partir del segundo día) --}}
                            @if(!$loop->first)
                                <button class="scale border border-slate-300 dark:border-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded h-6 w-6 flex items-center justify-center text-base text-slate-600 copy-btn transition duration-150 ease-in-out"
                                        data-day-index="{{ $loop->index }}"
                                        onclick="copyPreviousDayUsers(this)"
                                        data-tippy-content="{{ __('Copiar asignaciones del día anterior') }}"
                                        data-tippy-theme="dark">
                                    <iconify-icon icon="mdi:content-copy"></iconify-icon>
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Contenido del día (Pool de disponibles y Turnos) --}}
                    <div class="p-3 space-y-4">
                        {{-- Pool de empleados disponibles (solo si hay turnos en el día) --}}
                        @if($shiftsOfDay->isNotEmpty())
                            <div>
                                <strong class="block mb-2 text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Empleados Disponibles') }}</strong>
                                <div id="pool-{{ strtolower($day) }}" class="users-droppable flex flex-wrap gap-2 p-3 bg-slate-100 dark:bg-slate-600 rounded min-h-[60px] border border-slate-300 dark:border-slate-500">
                                    {{-- Iterar sobre usuarios disponibles filtrados --}}
                                    @forelse($availableUsers as $user)
                                        <div class="employee-card bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 px-2 py-1 rounded text-xs text-slate-800 dark:text-slate-200 cursor-move shadow-sm hover:shadow-md transition-shadow" data-employee-id="{{ $user->id }}">
                                            {{ $user->name }}
                                        </div>
                                    @empty
                                        <p class="text-xs text-slate-500 dark:text-slate-400 italic w-full text-center py-2">{{ __('No hay empleados disponibles') }}</p>
                                    @endforelse
                                </div>
                            </div>
                        @else
                             <div class="text-center py-4 text-sm text-slate-500 dark:text-slate-400 italic">
                                {{ __('No hay turnos definidos para este día.') }}
                            </div>
                        @endif

                        {{-- Contenedor de turnos del día --}}
                        {{-- Importante: El orden de estos contenedores debe ser consistente día a día para que la copia funcione --}}
                        <div id="shifts-{{ strtolower($day) }}" class="space-y-4 min-h-[calc(100vh-250px)]">
                            @foreach($shiftsOfDay as $shiftDay)
                                <div class="card rounded-md bg-white dark:bg-slate-800 shadow-base custom-class card-body p-4 border border-slate-200 dark:border-slate-700">
                                    {{-- Encabezado del turno --}}
                                    <header class="flex justify-between items-start mb-3">
                                        <div>
                                            <h4 class="font-medium text-base dark:text-slate-200 text-slate-900 mb-1">
                                                {{ $shiftDay->shift->name }}
                                            </h4>
                                            {{-- Información del horario --}}
                                            <p class="text-slate-600 dark:text-slate-400 text-xs">
                                                <iconify-icon icon="mdi:clock-outline" class="inline-block mr-1"></iconify-icon>
                                                @if($shiftDay->isSplitShift())
                                                    {{ optional($shiftDay->start_time)->format('H:i') }} - {{ optional($shiftDay->split_start_time)->format('H:i') }}
                                                    <span class="text-slate-400 dark:text-slate-500 mx-1">|</span>
                                                    {{ optional($shiftDay->split_end_time)->format('H:i') }} - {{ optional($shiftDay->end_time)->format('H:i') }}
                                                @else
                                                    {{ optional($shiftDay->start_time)->format('H:i') }} - {{ optional($shiftDay->end_time)->format('H:i') }}
                                                @endif
                                                <span class="text-slate-400 dark:text-slate-500 mx-1">|</span>
                                                {{ $shiftDay->effective_hours ?? __('N/A') }}h
                                            </p>
                                        </div>
                                    </header>

                                    {{-- Contenedor droppable para asignar empleados a este turno --}}
                                    {{-- El ID sigue siendo único para cada ShiftDay, pero la copia usará el orden --}}
                                    <div id="shift-{{ $shiftDay->id }}-users" class="users-droppable flex flex-wrap gap-2 p-3 bg-slate-50 dark:bg-slate-700/50 rounded min-h-[50px] border border-dashed border-slate-300 dark:border-slate-600">
                                        @forelse($shiftDay->users as $user)
                                            <div class="employee-card bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 px-2 py-1 rounded text-xs text-slate-800 dark:text-slate-200 cursor-move shadow-sm hover:shadow-md transition-shadow" data-employee-id="{{ $user->id }}">
                                                {{ $user->name }}
                                            </div>
                                        @empty
                                            <p class="text-xs text-slate-400 dark:text-slate-500 italic w-full text-center py-1">{{ __('Arrastra empleados aquí') }}</p>
                                        @endforelse
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
        {{-- Asegúrate de que las librerías estén cargadas --}}
        {{-- <script src=".../dragula.min.js"></script> --}}
        {{-- <script src=".../sweetalert2@11"></script> --}}
        {{-- <script src=".../tippy.js@6"></script> --}}

        <script type="module">
            // Inicializar Tippy
            if (typeof tippy === 'function') {
                tippy('[data-tippy-content]');
            } else {
                console.warn('Tippy.js no está cargado.');
            }

            // Configuración SweetAlert2
            const swalButtons = Swal.mixin({
                customClass: {
                    confirmButton: 'btn bg-primary-500 text-white hover:bg-primary-600 px-4 py-2 rounded mx-1',
                    cancelButton: 'btn bg-slate-400 text-white hover:bg-slate-500 px-4 py-2 rounded mx-1'
                },
                buttonsStyling: false
            });

            // Instanciar Dragula
            const drakeInstances = [];
            document.querySelectorAll('[data-day-index]').forEach(dayContainer => {
                const userContainers = Array.from(dayContainer.querySelectorAll('.users-droppable'));
                if (userContainers.length > 0) {
                    const drake = dragula(userContainers);
                    drakeInstances.push(drake);
                    drake.on('drag', (el) => el.classList.add('opacity-50', 'shadow-lg'));
                    drake.on('dragend', (el) => el.classList.remove('opacity-50', 'shadow-lg'));
                    drake.on('drop', (el, target, source, sibling) => {
                        updatePlaceholderVisibility(source);
                        updatePlaceholderVisibility(target);
                    });
                }
            });

            /**
             * Muestra u oculta el placeholder de un contenedor droppable.
             * @param {HTMLElement} container - El contenedor (pool o shift-users).
             */
            function updatePlaceholderVisibility(container) {
                if (!container) return;
                const isPool = container.id.startsWith('pool-');
                const placeholderText = isPool
                    ? "{{ __('No hay empleados disponibles') }}"
                    : "{{ __('Arrastra empleados aquí') }}";
                const placeholderClass = isPool ? 'py-2' : 'py-1';

                let placeholder = container.querySelector('p.italic');
                const hasCards = container.querySelector('.employee-card');

                if (hasCards && placeholder) {
                    placeholder.remove();
                } else if (!hasCards && !placeholder) {
                     placeholder = document.createElement('p');
                     placeholder.className = `text-xs text-slate-500 dark:text-slate-400 italic w-full text-center ${placeholderClass}`;
                     placeholder.textContent = placeholderText;
                     container.appendChild(placeholder);
                }
            }


            /**
             * Copia las asignaciones de usuarios del día anterior al día actual, basándose en el orden de los turnos.
             * @param {HTMLElement} button - El botón que disparó el evento.
             */
            window.copyPreviousDayUsers = function(button) {
                const currentIndex = parseInt(button.getAttribute('data-day-index'));
                console.log('[Copy] Iniciando copia para el día índice:', currentIndex);

                if (isNaN(currentIndex) || currentIndex === 0) {
                    console.warn('[Copy] Índice inválido o es el primer día.');
                    return;
                }

                const currentDayContainer = document.querySelector(`[data-day-index="${currentIndex}"]`);
                const previousDayContainer = document.querySelector(`[data-day-index="${currentIndex - 1}"]`);

                if (!currentDayContainer || !previousDayContainer) {
                    console.error('[Copy] Error: No se encontró el contenedor del día actual o anterior.');
                    return;
                }
                 console.log('[Copy] Contenedor día actual:', currentDayContainer.id);
                 console.log('[Copy] Contenedor día anterior:', previousDayContainer.id);

                // Selecciona TODOS los contenedores de usuarios de turnos en orden
                const previousShiftUserContainers = previousDayContainer.querySelectorAll('[id^="shifts-"] [id^="shift-"][id$="-users"]');
                const currentShiftUserContainers = currentDayContainer.querySelectorAll('[id^="shifts-"] [id^="shift-"][id$="-users"]');
                const currentPoolContainer = currentDayContainer.querySelector('[id^="pool-"]');

                if (!currentPoolContainer) {
                    console.warn('[Copy] No se encontró el pool de usuarios del día actual (posiblemente no hay turnos):', currentDayContainer.id);
                } else {
                    console.log('[Copy] Pool actual encontrado:', currentPoolContainer.id);
                }

                if (previousShiftUserContainers.length !== currentShiftUserContainers.length) {
                    console.warn(`[Copy] El número de turnos difiere entre el día anterior (${previousShiftUserContainers.length}) y el actual (${currentShiftUserContainers.length}). La copia podría ser incorrecta.`);
                } else {
                     console.log(`[Copy] Encontrados ${previousShiftUserContainers.length} turnos en día anterior y ${currentShiftUserContainers.length} en día actual.`);
                }

                // 1. Mover todas las tarjetas del día actual de vuelta al pool (si existe el pool)
                if (currentPoolContainer) {
                    console.log('[Copy] Paso 1: Moviendo empleados actuales de vuelta al pool...');
                    currentShiftUserContainers.forEach(container => {
                        container.querySelectorAll('.employee-card').forEach(card => {
                            if (!currentPoolContainer.contains(card)) {
                                currentPoolContainer.appendChild(card);
                            }
                        });
                        container.innerHTML = '';
                    });
                    console.log('[Copy] Paso 1 completado. Actualizando placeholders...');
                    currentShiftUserContainers.forEach(updatePlaceholderVisibility);
                    updatePlaceholderVisibility(currentPoolContainer);
                } else {
                     console.log('[Copy] Paso 1 omitido: No hay pool en el día actual.');
                }

                // 2. Iterar sobre los turnos POR ÍNDICE y copiar asignaciones
                console.log('[Copy] Paso 2: Copiando asignaciones del día anterior por posición...');
                previousShiftUserContainers.forEach((prevContainer, index) => {
                    const currentTargetContainer = currentShiftUserContainers[index];

                    if (currentTargetContainer) {
                         console.log(`[Copy] Procesando índice ${index}: Contenedor anterior ${prevContainer.id} -> Contenedor actual ${currentTargetContainer.id}`);
                        prevContainer.querySelectorAll('.employee-card').forEach(prevCard => {
                            const employeeId = prevCard.getAttribute('data-employee-id');
                             console.log(`[Copy]   Intentando copiar empleado ID: ${employeeId}`);

                            let cardToMove = null;
                            if (currentPoolContainer) {
                                cardToMove = currentPoolContainer.querySelector(`.employee-card[data-employee-id="${employeeId}"]`);
                            }

                            if (cardToMove) {
                                console.log(`[Copy]     Empleado ${employeeId} encontrado en el pool. Moviendo a ${currentTargetContainer.id}`);
                                currentTargetContainer.appendChild(cardToMove);
                            } else {
                                console.warn(`[Copy]     Empleado ${employeeId} (del turno índice ${index} anterior) NO encontrado en el pool actual. Quizás ya está asignado en otro turno de hoy o no está disponible.`);
                            }
                        });
                    } else {
                         console.warn(`[Copy] No se encontró contenedor destino para el índice ${index} en el día actual.`);
                    }
                });

                // 3. Actualizar visibilidad de placeholders en todos los contenedores afectados del día actual
                 console.log('[Copy] Paso 3: Actualizando visibilidad de placeholders final...');
                 currentShiftUserContainers.forEach(updatePlaceholderVisibility);
                 if (currentPoolContainer) {
                    updatePlaceholderVisibility(currentPoolContainer);
                 }

                console.log('[Copy] Proceso de copia finalizado.');
            }

            // --- Listener para Restablecer Asignaciones ---
            document.getElementById('reset-assignments').addEventListener('click', function() {
                swalButtons.fire({
                    title: @json(__('Confirmación')),
                    text: @json(__('¿Restablecer todas las asignaciones? Los empleados volverán al pool de "Disponibles" de su día.')),
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: @json(__('Sí, restablecer')),
                    cancelButtonText: @json(__('Cancelar'))
                }).then((result) => {
                    if (result.isConfirmed) {
                        console.log('[Reset] Iniciando reseteo...');
                        document.querySelectorAll('[data-day-index]').forEach(dayContainer => {
                            const poolContainer = dayContainer.querySelector('[id^="pool-"]');
                            const shiftContainers = dayContainer.querySelectorAll('[id^="shifts-"] [id^="shift-"][id$="-users"]');
                            console.log(`[Reset] Procesando día: ${dayContainer.id}`);

                            if (poolContainer) {
                                shiftContainers.forEach(shiftContainer => {
                                    shiftContainer.querySelectorAll('.employee-card').forEach(card => {
                                        poolContainer.appendChild(card);
                                    });
                                    shiftContainer.innerHTML = '';
                                });
                                shiftContainers.forEach(updatePlaceholderVisibility);
                                updatePlaceholderVisibility(poolContainer);
                                console.log(`[Reset] Placeholders actualizados para día: ${dayContainer.id}`);
                            } else {
                                 console.log(`[Reset] No se encontró pool para el día: ${dayContainer.id}. Reseteo omitido para este día.`);
                            }
                        });
                        Swal.fire({
                            title: @json(__('Completado')),
                            text: @json(__('Se han restablecido todas las asignaciones de los días con turnos definidos.')),
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        console.log('[Reset] Reseteo completado.');
                    } else {
                         console.log('[Reset] Reseteo cancelado.');
                    }
                });
            });

            // --- Listener para Guardar Cambios ---
            document.getElementById('save-changes').addEventListener('click', function() {
                 swalButtons.fire({
                    title: @json(__('Confirmación')),
                    text: @json(__('¿Guardar las asignaciones actuales de todos los días?')),
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: @json(__('Sí, guardar')),
                    cancelButtonText: @json(__('Cancelar'))
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: @json(__('Guardando...')),
                            text: @json(__('Por favor, espera.')),
                            allowOutsideClick: false,
                            didOpen: () => { Swal.showLoading(); }
                        });

                        const shiftUserContainers = document.querySelectorAll('[id^="shifts-"] [id^="shift-"][id$="-users"]');
                        const updatePromises = [];
                        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        console.log('[Save] Iniciando guardado...');

                        shiftUserContainers.forEach(container => {
                            const shiftDayIdMatch = container.id.match(/shift-(\d+)-users/);
                            if (!shiftDayIdMatch || !shiftDayIdMatch[1]) {
                                 console.warn(`[Save] Saltando contenedor con ID inválido: ${container.id}`);
                                 return;
                            }
                            const shiftDayId = shiftDayIdMatch[1];
                            const userCards = container.querySelectorAll('.employee-card');
                            const userIds = Array.from(userCards).map(card => card.getAttribute('data-employee-id'));

                            let url = '';
                            let method = '';
                            let body = null;
                            let headers = {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                                // 'Content-Type' se añadirá condicionalmente
                            };

                            if (userIds.length > 0) {
                                // --- Hay usuarios asignados: Actualizar usando POST a /update-users ---
                                url = `/shift-days/${shiftDayId}/update-users`; // URL para actualizar
                                method = 'POST';
                                body = JSON.stringify({ users: userIds });
                                headers['Content-Type'] = 'application/json'; // Necesario para POST con body JSON
                                console.log(`[Save] Preparando UPDATE para ShiftDay ID: ${shiftDayId}, Método: ${method}, URL: ${url}, Usuarios: [${userIds.join(', ')}]`);
                            } else {
                                // --- No hay usuarios asignados: Eliminar asignaciones usando DELETE a /users ---
                                url = `/shift-days/${shiftDayId}/users`; // URL para eliminar
                                method = 'DELETE';
                                // DELETE no necesita body ni Content-Type
                                console.log(`[Save] Preparando DELETE para ShiftDay ID: ${shiftDayId}, Método: ${method}, URL: ${url}`);
                            }

                            const promise = fetch(url, {
                                method: method,
                                headers: headers,
                                body: body // Será null para DELETE
                            })
                            .then(response => {
                                // Para DELETE exitoso, la respuesta puede no tener cuerpo (status 204 No Content)
                                if (!response.ok) {
                                     console.error(`[Save] Error en respuesta para ShiftDay ${shiftDayId} (${method} ${url}): ${response.status}`);
                                    // Intenta obtener el mensaje de error del JSON, si no, usa el statusText
                                    return response.json().catch(() => ({ // Proporciona un objeto vacío si .json() falla
                                            message: `Error ${response.status}: ${response.statusText}`
                                        }))
                                        .then(errData => {
                                            throw new Error(errData.message || `Error al procesar turno ${shiftDayId}`);
                                        });
                                }
                                // Si es DELETE y fue exitoso (204), no habrá JSON, devuelve algo indicando éxito
                                if (response.status === 204) {
                                    return { success: true, message: `ShiftDay ${shiftDayId} cleared.` };
                                }
                                // Para POST exitoso (u otros como 200 OK con cuerpo)
                                return response.json();
                            });
                            updatePromises.push(promise);
                        });

                        Promise.all(updatePromises)
                            .then(results => {
                                 console.log('[Save] Todas las promesas resueltas.');
                                Swal.fire({
                                    title: @json(__('Éxito')),
                                    text: @json(__('Las asignaciones de turnos se han guardado correctamente.')),
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            })
                            .catch(error => {
                                console.error('[Save] Error en Promise.all:', error);
                                Swal.fire({
                                    title: @json(__('Error')),
                                    text: error.message || @json(__('Ocurrió un error al intentar guardar las asignaciones.')),
                                    icon: 'error'
                                });
                            });
                    } else {
                        console.log('[Save] Guardado cancelado.');
                    }
                });
            });

             // Inicializar placeholders al cargar la página
             document.querySelectorAll('.users-droppable').forEach(updatePlaceholderVisibility);

        </script>
    @endpush
</x-app-layout>
