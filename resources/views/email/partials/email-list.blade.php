<div id="email-list-container" class="bg-white dark:bg-gray-900 border rounded p-2">
    <ul class="divide-y dark:divide-gray-700">
        @forelse($messages as $mail)
            <li class="py-2 flex justify-between items-center">
                <a href="javascript:void(0);" onclick="loadMessageDetail({{ $mail->getUid() }}, '{{ $folder }}')"
                   class="block hover:bg-gray-100 dark:hover:bg-gray-800 p-2 rounded flex-1">
                    <p class="{{ $mail->getFlags()->contains('\Seen') ? 'font-normal' : 'font-bold' }}">
                        {{ $mail->getSubject() }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        {{ isset($mail->getFrom()[0]) ? $mail->getFrom()[0]->mail : 'N/D' }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ \Carbon\Carbon::parse($mail->getDate())->format('d/m/Y H:i') }}
                    </p>
                </a>
                <!-- Botón para borrar el correo -->
                <form action="{{ route('emails.delete', $mail->getUid()) }}" method="POST"
                      onsubmit="return confirm('{{ __('¿Está seguro de borrar este correo?') }}');"
                      class="ml-2">
                    @csrf
                    <input type="hidden" name="folder" value="{{ $folder }}">
                    <button type="submit"
                            class="p-2 rounded-full text-red-500 hover:text-red-700"
                            title="{{ __('Borrar') }}">
                        <iconify-icon icon="heroicons-outline:trash" class="text-xl"></iconify-icon>
                    </button>
                </form>
            </li>
        @empty
            <li class="py-2 text-gray-500">{{ __("No se encontraron correos.") }}</li>
        @endforelse
    </ul>

    <!-- Paginación -->
    <div class="mt-4">
        {{ $messages->appends(request()->query())->links() }}
    </div>
</div>


<script>
    // Función para cargar el detalle del correo
    function loadMessageDetail(uid, folder) {
        const url = "{{ route('emails.show', '') }}/" + uid + "?folder=" + folder;

        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('email-detail-container').innerHTML = data.html;
        })
        .catch(error => console.error('Error al cargar el correo:', error));
    }

    // Función para cambiar de carpeta y actualizar la lista de correos
    function changeFolder(folder) {
        const url = "{{ route('emails.index') }}?folder=" + folder;

        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.text())
        .then(data => {
            document.getElementById('email-list-container').innerHTML = data;
        })
        .catch(error => console.error('Error al cargar la carpeta:', error));
    }
</script>
