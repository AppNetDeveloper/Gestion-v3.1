<!-- email/partials/message-detail.blade.php -->
<div class="message-detail">
    <h2 class="text-2xl font-bold mb-4">{{ decodeMimeHeader($message->getSubject()) }}</h2>
    <p class="text-sm text-gray-600 mb-2">
        <strong>{{ __("De:") }}</strong> {{ isset($message->getFrom()[0]) ? $message->getFrom()[0]->mail : 'N/D' }}
    </p>
    <p class="text-xs text-gray-500 mb-4">
        {{ \Carbon\Carbon::parse($message->getDate())->format('d/m/Y H:i') }}
    </p>
    <div class="prose max-w-none mb-4 dark:prose-dark">
        {!! $message->getHTMLBody() ?: $message->getTextBody() !!}
    </div>

    <!-- Adjuntos (si existen) -->
    @if(count($message->getAttachments()) > 0)
        <div class="mb-4">
            <h3 class="font-semibold">{{ __("Archivos adjuntos:") }}</h3>
            <ul class="list-disc list-inside">
                @foreach($message->getAttachments() as $index => $attachment)
                    <li>
                        <a href="{{ route('emails.attachment.download', ['messageUid' => $message->getUid(), 'attachmentIndex' => $index]) }}"
                           class="text-blue-500 hover:underline" target="_blank"
                           title="{{ __('Descargar adjunto') }}">
                            <iconify-icon icon="heroicons-outline:paper-clip" class="text-lg mr-1"></iconify-icon>
                            {{ $attachment->getName() }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Formulario para responder -->
    <div class="mt-6">
        <form action="{{ route('emails.reply', $message->getUid()) }}" method="POST">
            @csrf
            <input type="hidden" name="to" value="{{ isset($message->getFrom()[0]) ? $message->getFrom()[0]->mail : '' }}">
            <input type="hidden" name="subject" value="Re: {{ decodeMimeHeader($message->getSubject()) }}">
            <label for="reply" class="font-semibold mb-2 block">{{ __("Responder (HTML permitido):") }}</label>
            <textarea id="reply" name="content" class="w-full border rounded p-2" rows="6" placeholder="{{ __('Escribe tu respuesta...') }}"></textarea>
            <button type="submit"
                    class="mt-2 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded flex items-center"
                    title="{{ __('Enviar respuesta') }}">
                <iconify-icon icon="heroicons-outline:paper-airplane" class="text-xl mr-2"></iconify-icon>
                {{ __("Enviar Respuesta") }}
            </button>
        </form>
    </div>
</div>
