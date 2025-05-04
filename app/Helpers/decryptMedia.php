<?php

// Función HKDF (si no la tienes ya)
if (! function_exists('hkdf')) {
    /**
     * Deriva una clave utilizando HKDF.
     *
     * @param string $salt La sal.
     * @param string $ikm Input key material.
     * @param string $info Información de contexto.
     * @param int $length Longitud de salida deseada.
     * @param string $hash Algoritmo hash (por defecto 'sha256').
     * @return string Clave derivada.
     */
    function hkdf($salt, $ikm, $info, $length, $hash = 'sha256')
    {
        $prk = hash_hmac($hash, $ikm, $salt, true);
        $t = '';
        $okm = '';
        $i = 0;
        while (strlen($okm) < $length) {
            $i++;
            $t = hash_hmac($hash, $t . $info . chr($i), $prk, true);
            $okm .= $t;
        }
        return substr($okm, 0, $length);
    }
}

if (! function_exists('decryptMedia')) {
    /**
     * Descarga, desencripta y convierte un archivo remoto a MP4.
     *
     * Se utiliza la mediaKey (en base64) para derivar la clave y el IV con HKDF.
     * Se espera que el proceso de encriptación de WhatsApp haya utilizado:
     * - AES-256-CBC
     * - Un info string de "WhatsApp Video Keys"
     * - Una sal de 32 bytes de ceros.
     *
     * @param string $encryptedUrl La URL encriptada del archivo.
     * @param string $mediaKey La mediaKey del mensaje (del JSON), en base64.
     * @return string|null Ruta local del archivo MP4 o null en caso de error.
     */
    function decryptMedia($encryptedUrl, $mediaKey)
    {
        // Definir rutas temporales.
        $downloadedPath = storage_path('app/decrypted/original_video.enc');
        $decryptedPath  = storage_path('app/decrypted/original_video.dec');
        $convertedPath  = storage_path('app/decrypted/video.mp4');

        $directory = dirname($downloadedPath);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                \Log::error("No se pudo crear el directorio: {$directory}");
                return null;
            }
        }

        // 1. Descargar el archivo remoto.
        $encryptedData = @file_get_contents($encryptedUrl);
        if ($encryptedData === false) {
            \Log::error("No se pudo descargar el archivo desde: {$encryptedUrl}");
            return null;
        }
        if (file_put_contents($downloadedPath, $encryptedData) === false) {
            \Log::error("No se pudo guardar el archivo descargado en: {$downloadedPath}");
            return null;
        }

        // 2. Derivar la clave y el IV usando HKDF.
        $mediaKeyBin = base64_decode($mediaKey);
        $salt = str_repeat("\0", 32);
        $info = "WhatsApp Video Keys";
        // Derivar 112 bytes en total.
        $expandedKey = hkdf($salt, $mediaKeyBin, $info, 112);
        // Extraer la clave (primeros 32 bytes) y el IV (siguientes 16 bytes).
        $key = substr($expandedKey, 0, 32);
        $iv = substr($expandedKey, 32, 16);

        \Log::info("Longitud de key: " . strlen($key));
        \Log::info("Longitud de iv: " . strlen($iv));

        // 3. Preparar el ciphertext.
        // (Opcional) Eliminar encabezado: prueba con distintos valores.
        $decryptedData = false;
        $valid = false;
        $offsetFound = null;
        for ($headerOffset = 0; $headerOffset < 20; $headerOffset++) {
            $encryptedDataForDecryption = substr($encryptedData, $headerOffset);
            \Log::info("Probando offset {$headerOffset} - Ciphertext length: " . strlen($encryptedDataForDecryption));

            $decryptedCandidate = openssl_decrypt($encryptedDataForDecryption, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

            if ($decryptedCandidate !== false && strlen($decryptedCandidate) > 0) {
                // Puedes agregar aquí validaciones adicionales, por ejemplo:
                // Si es un archivo MP4, normalmente empieza con "ftyp"
                if (strpos($decryptedCandidate, "ftyp") !== false) {
                    $decryptedData = $decryptedCandidate;
                    $offsetFound = $headerOffset;
                    \Log::info("Offset correcto encontrado: {$headerOffset}");
                    $valid = true;
                    break;
                }
            }
        }

        if (!$valid) {
            \Log::error("No se encontró un offset válido para la desencriptación.");
            return null;
        }

        \Log::info("Decrypted data length: " . strlen($decryptedData));

        if ($decryptedData === false) {
            $opensslErrors = [];
            while ($err = openssl_error_string()) {
                $opensslErrors[] = $err;
            }
            \Log::error("La desencriptación falló para: {$encryptedUrl}. Errores: " . implode(" | ", $opensslErrors));
            return null;
        }else{
            \Log::info("La desencriptación fue exitosa");
        }
        if (file_put_contents($decryptedPath, $decryptedData) === false) {
            \Log::error("No se pudo guardar el archivo desencriptado en: {$decryptedPath}");
            return null;
        }else{
            \Log::info("El archivo desencriptado se guardó correctamente en: {$decryptedPath}");
        }

        // 5. Convertir el archivo desencriptado a MP4 usando FFmpeg.
        $ffmpegPath = '/usr/bin/ffmpeg'; // Ajusta según tu instalación.
        $ffmpegCmd = $ffmpegPath . " -y -i " . escapeshellarg($decryptedPath) .
                     " -c:v libx264 -c:a aac " . escapeshellarg($convertedPath) . " 2>&1";
        \Log::info("Ejecutando FFmpeg: {$ffmpegCmd}");
        exec($ffmpegCmd, $output, $returnVar);
        \Log::info("Salida de FFmpeg: " . implode("\n", $output));
        if ($returnVar !== 0 || !file_exists($convertedPath)) {
            \Log::error("Error en la conversión con FFmpeg. Código: {$returnVar}");
            return null;
        }

        // (Opcional) Borrar archivos temporales.
        @unlink($downloadedPath);
        @unlink($decryptedPath);

        return $convertedPath;
    }
}
