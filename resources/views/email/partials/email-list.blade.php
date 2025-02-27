<div id="email-list-container">
    <ul class="divide-y">
        @forelse($messages as $mail)
            @php
                $isRead = $mail->getFlags()->contains('\Seen');
            @endphp
            <li class="py-2 flex justify-between items-center">
                <a href="{{ route('emails.show', $mail->getUid()) }}?folder={{ $folder }}" class="block hover:bg-gray-100 dark:hover:bg-gray-800 p-2 rounded flex-1">
                    <p class="{{ $isRead ? 'font-normal' : 'font-bold' }}">
                        {{ decodeMimeHeader($mail->getSubject()) }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        {{ isset($mail->getFrom()[0]) ? $mail->getFrom()[0]->mail : 'N/D' }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ \Carbon\Carbon::parse((string)$mail->getDate())->format('d/m/Y H:i') }}
                    </p>
                </a>
                {{-- Botón para borrar el correo --}}
                <form action="{{ route('emails.delete', $mail->getUid()) }}" method="POST" onsubmit="return confirm('{{ __('¿Está seguro de borrar este correo?') }}');">
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
    <div class="mt-4">
        {{ $messages->appends(request()->query())->links() }}
    </div>
</div>
