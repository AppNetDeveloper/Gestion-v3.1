<div id="email-list-container">
    <ul class="divide-y">
        @forelse($messages as $mail)
            <li class="py-2">
                <a href="{{ route('emails.show', $mail->getUid()) }}?folder={{ $folder }}" class="block hover:bg-gray-100 p-2 rounded">
                    <p class="{{ $mail->getFlags()->contains('\Seen') ? 'font-normal' : 'font-bold' }}">
                        {{ decodeMimeHeader($mail->getSubject()) }}
                    </p>
                    <p class="text-sm text-gray-600">
                        De: {{ isset($mail->getFrom()[0]) ? $mail->getFrom()[0]->mail : 'N/D' }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ \Carbon\Carbon::parse((string)$mail->getDate())->format('d/m/Y H:i') }}
                    </p>
                </a>
            </li>
        @empty
            <li class="py-2 text-gray-500">{{ __("No se encontraron correos.") }}</li>
        @endforelse
    </ul>
    <div class="mt-4">
        {{ $messages->appends(request()->query())->links() }}
    </div>
</div>
