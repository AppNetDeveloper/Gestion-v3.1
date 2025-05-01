<?php

// app/helpers/MessageFormatter.php

if (! function_exists('formatMessageText')) {
    /**
     * Formatea el texto de un mensaje para mostrarlo en HTML.
     * Convierte URLs en enlaces clicables y saltos de línea en <br>.
     *
     * @param mixed $message Puede ser un string o un array/objeto con la estructura del mensaje.
     * @return string HTML formateado para mostrar el mensaje.
     */
    function formatMessageText($message)
    {
        $textContent = '';

        // 1. Extraer el texto principal del mensaje
        if (is_array($message) || is_object($message)) {
            $message = (array) $message; // Convertir objeto a array si es necesario
            $textContent = $message['message']['conversation']
                ?? $message['message']['extendedTextMessage']['text']
                ?? $message['message']['imageMessage']['caption']
                ?? $message['message']['videoMessage']['caption']
                ?? ''; // Añadir más tipos si es necesario

            // Manejar casos especiales como mensajes revocados
            if (isset($message['message']['protocolMessage']['type']) && $message['message']['protocolMessage']['type'] === 'REVOKE') {
                return '<span class="italic text-slate-500 dark:text-slate-400">' . e(__('Message deleted')) . '</span>';
            }

        } elseif (is_string($message)) {
             // Si $message no es un array/objeto, intentar decodificar JSON (como en tu código original)
             $decoded = json_decode($message, true);
             if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                 // Si es JSON válido y se decodifica a array, intentar extraer texto como antes
                 $textContent = $decoded['message']['conversation']
                     ?? $decoded['message']['extendedTextMessage']['text']
                     ?? $decoded['message']['imageMessage']['caption']
                     ?? $decoded['message']['videoMessage']['caption']
                     ?? '';

                 if (isset($decoded['message']['protocolMessage']['type']) && $decoded['message']['protocolMessage']['type'] === 'REVOKE') {
                     return '<span class="italic text-slate-500 dark:text-slate-400">' . e(__('Message deleted')) . '</span>';
                 }

             } else {
                 // Si no es JSON válido o no es array/objeto, tratarlo como texto plano
                 $textContent = $message;
             }
        }

        // Si no se encontró contenido de texto, devolver string vacío
        if (empty($textContent)) {
            return '';
        }

        // 2. Escapar HTML del texto para seguridad
        $escapedText = e($textContent);

        // 3. Convertir URLs a enlaces clicables
        $pattern = '/(https?:\/\/[^\s<>"\'`]+)|(www\.[^\s<>"\'`]+)/i';
        $linkedText = preg_replace_callback($pattern, function ($matches) {
            $url = $matches[0];
            $href = $url;
            // Añadir https:// si empieza con www.
            if (stripos($href, 'www.') === 0) {
                $href = 'https://' . $href;
            }
            // Acortar URL mostrada si es muy larga (opcional)
            $displayUrl = strlen($url) > 50 ? substr($url, 0, 47) . '...' : $url;

            // Crear el enlace HTML
            return '<a href="' . e($href) . '" target="_blank" rel="noopener noreferrer" class="message-link">' . e($displayUrl) . '</a>';
        }, $escapedText);

        // 4. Convertir saltos de línea a <br>
        return nl2br($linkedText, false); // Usar false para <br> en lugar de <br />
    }
}

// Asegúrate de que otras funciones necesarias como convertCsvImage estén definidas
// o incluidas si se usan en otros helpers o en el Blade.
if (! function_exists('convertCsvImage')) {
    function convertCsvImage($imageString) {
        $parts = explode(',', $imageString, 2);
        if (!isset($parts[1])) { return $imageString; }
        $dataAfterComma = trim($parts[1]);
        if (ctype_digit(str_replace([',', ' '], '', $dataAfterComma))) {
            $numbers = explode(',', $dataAfterComma);
            $binaryData = '';
            foreach ($numbers as $num) { $binaryData .= chr((int)$num); }
            return $parts[0] . ',' . base64_encode($binaryData);
        }
        return $imageString;
    }
}

