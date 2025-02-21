<?php
if (! function_exists('formatMessageText')) {
    /**
     * Detecta si un texto es JSON y lo formatea de forma legible.
     *
     * @param string $text
     * @return string
     */
    function formatMessageText($text)
    {
        // Intenta decodificar el JSON
        $decoded = json_decode($text);
        if (json_last_error() === JSON_ERROR_NONE) {
            // Si es JSON válido, lo formatea con JSON_PRETTY_PRINT
            return '<pre class="formatted-json">' . 
                   htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) .
                   '</pre>';
        }
        // Si no es JSON, se aplica el convertidor de URLs
        return convertUrlsToLinks($text);
    }
}
