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
