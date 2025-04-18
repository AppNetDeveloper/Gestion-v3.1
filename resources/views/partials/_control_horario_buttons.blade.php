{{-- resources/views/partials/_control_horario_buttons.blade.php --}}
@if (!isset($allowedButtons) || !isset($allowedAddButtons) || $allowedButtons->isEmpty() || $allowedAddButtons == 0)
    <p class="text-slate-500 dark:text-slate-400 col-span-full">Control horario deshabilitado o sin permisos.</p>
@else
    @foreach ($allowedButtons as $buttonStatusId)
        @php
            $status = \App\Models\TimeControlStatus::find($buttonStatusId);
        @endphp
        @if ($status)
            <a href="#" class="attendance-button block" data-status-id="{{ $buttonStatusId }}">
                <button class="btn btn-primary w-full h-full flex items-center justify-center space-x-2 rtl:space-x-reverse">
                    <iconify-icon icon="{{ $status->icon ?? 'heroicons-outline:clock' }}"></iconify-icon>
                    <span>{{ $status->table_name ?? 'Estado Desconocido' }}</span>
                </button>
            </a>
        @endif
    @endforeach
@endif
