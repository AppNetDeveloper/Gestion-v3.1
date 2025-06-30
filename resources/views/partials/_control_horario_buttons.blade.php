{{-- resources/views/partials/_control_horario_buttons.blade.php --}}
@php
    // Log de depuración para verificar los datos recibidos
    \Log::info('_control_horario_buttons - Datos recibidos:', [
        'allowedButtons_isset' => isset($allowedButtons),
        'allowedAddButtons_isset' => isset($allowedAddButtons),
        'allowedButtons_count' => isset($allowedButtons) ? $allowedButtons->count() : 'N/A',
        'allowedButtons_data' => isset($allowedButtons) ? $allowedButtons->toArray() : 'N/A',
        'allowedAddButtons' => $allowedAddButtons ?? 'N/A'
    ]);
@endphp

<script>
    console.log('=== PARTIAL _control_horario_buttons CARGADO ===');
    console.log('Datos del partial:', {
        allowedButtons: @json($allowedButtons ?? []),
        allowedAddButtons: @json($allowedAddButtons ?? false)
    });
</script>

@if (!isset($allowedButtons) || !isset($allowedAddButtons) || $allowedButtons->isEmpty() || $allowedAddButtons == 0)
    <p class="text-slate-500 dark:text-slate-400 col-span-full">Control horario deshabilitado o sin permisos.</p>
@else
    @foreach ($allowedButtons as $buttonStatusId)
        @php
            $status = \App\Models\TimeControlStatus::find($buttonStatusId);
        @endphp
        @if ($status)
            @php
                \Log::info('Generando botón de control horario:', [
                    'buttonStatusId' => $buttonStatusId,
                    'status_id' => $status->id,
                    'status_name' => $status->table_name,
                    'status_icon' => $status->icon
                ]);
            @endphp
            <a href="#" class="attendance-button block" data-status-id="{{ $buttonStatusId }}" id="attendance-button-{{ $buttonStatusId }}" onclick="console.log('Click directo en botón {{ $buttonStatusId }}'); return false;">
                <button class="btn btn-primary w-full h-full flex items-center justify-center space-x-2 rtl:space-x-reverse">
                    <iconify-icon icon="{{ $status->icon ?? 'heroicons-outline:clock' }}"></iconify-icon>
                    <span>{{ $status->table_name ?? 'Estado Desconocido' }}</span>
                </button>
            </a>
            <script>
                console.log('Botón generado:', {
                    statusId: {{ $buttonStatusId }},
                    statusName: '{{ $status->table_name }}',
                    statusIcon: '{{ $status->icon ?? "heroicons-outline:clock" }}'
                });
            </script>
        @else
            @php
                \Log::warning('Status no encontrado para buttonStatusId:', ['buttonStatusId' => $buttonStatusId]);
            @endphp
            <script>
                console.warn('Status no encontrado para ID:', {{ $buttonStatusId }});
            </script>
        @endif
    @endforeach
@endif
