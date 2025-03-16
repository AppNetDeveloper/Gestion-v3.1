<?php
if (! function_exists('formatMessageText')) {
    /**
     * Formatea el contenido de un mensaje según su tipo.
     *
     * Soporta:
     * - Mensajes de texto extendido (extendedTextMessage) o conversación simple (conversation).
     * - Mensajes de audio: muestra un botón para descargar.
     * - Mensajes de video: muestra un botón para desencriptar/descargar o reproducir.
     * - Mensajes de imagen: muestra la miniatura y, al hacer clic, abre la imagen en zoom.
     * - Protocol messages de revocación: muestra "Message Removed".
     *
     * @param mixed $message Puede ser un string o un array con la estructura del mensaje.
     * @return string HTML formateado para mostrar el mensaje.
     */
    function formatMessageText($message)
    {
        // Si $message no es un array, intentamos decodificarlo como JSON.
        if (!is_array($message)) {
            $decoded = json_decode($message, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $message = $decoded;
            } else {
                return convertUrlsToLinks($message);
            }
        }

        // Si es un protocolMessage de tipo REVOKE, mostramos un mensaje fijo.
        if (
            isset($message['protocolMessage']['type']) &&
            $message['protocolMessage']['type'] === 'REVOKE'
        ) {
            return '<em>Message Removed</em>';
        }

        // Mensaje extendido: mostramos el 'text'.
        if (isset($message['extendedTextMessage']['text'])) {
            return convertUrlsToLinks($message['extendedTextMessage']['text']);
        }

        // Mensaje de conversación simple.
        if (isset($message['conversation'])) {
            return convertUrlsToLinks($message['conversation']);
        }

        // Mensaje de audio: mostramos un botón para descargar el audio.
        if (isset($message['audioMessage']['url'])) {
            $audioUrl = $message['audioMessage']['url'];
            return '<a href="' . e($audioUrl) . '" download class="btn btn-sm btn-primary">Audio</a>';
        }

        // Mensaje de video: mostramos un botón para desencriptar/descargar o reproducir.
        if (isset($message['videoMessage']['url'])) {
            $videoUrl = $message['videoMessage']['url'];
            if (str_ends_with(parse_url($videoUrl, PHP_URL_PATH), '.enc')) {
                // Codifica la URL en Base64 para evitar problemas con caracteres especiales.
                $encryptedUrlBase64 = base64_encode($videoUrl);
                // Obtén la mediaKey del mensaje.
                $mediaKey = $message['videoMessage']['mediaKey'] ?? null;
                if ($mediaKey) {
                    // Si se desea, se puede pasar la mediaKey directamente (ya está en base64) o aplicar urlencode.
                    $mediaKeyEncoded = urlencode($mediaKey);
                    // Construye la URL hacia la ruta 'decryptMedia' incluyendo mediaKey.
                    $decryptUrl = route('decryptMedia', [
                        'url' => $encryptedUrlBase64,
                        'mediaKey' => $mediaKeyEncoded
                    ]);
                } else {
                    $decryptUrl = route('decryptMedia', ['url' => $encryptedUrlBase64]);
                }
                return '<a href="' . e($decryptUrl) . '" class="btn btn-sm btn-primary">Desencriptar Video</a>';
            } else {
                return '<a href="' . e($videoUrl) . '" download class="btn btn-sm btn-primary">Video</a>';
            }
        }



        // Mensaje de imagen: mostramos la miniatura y agregamos evento para zoom.
        if (isset($message['imageMessage'])) {
            $imgData   = $message['imageMessage'];
            $imageUrl  = $imgData['url'] ?? '';
            $thumbnail = $imgData['jpegThumbnail'] ?? '';
            if ($thumbnail && !str_starts_with($thumbnail, 'data:image')) {
                $thumbnail = 'data:image/png;base64,' . $thumbnail;
            }
            if ($imageUrl) {
                return '<img src="' . e($thumbnail ?: $imageUrl) . '" alt="Image" style="max-width:100px;cursor:pointer" onclick="showZoomModal(\'' . e($imageUrl) . '\')" />';
            }
        }

        // Si no se cumple ningún caso, intentamos formatearlo como JSON legible.
        $decoded = json_decode(json_encode($message), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return '<pre>' . htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>';
        }

        return convertUrlsToLinks($message);
    }
}
