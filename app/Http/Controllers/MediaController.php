<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MediaController extends Controller
{
    public function decrypt(Request $request)
    {
        $encodedUrl = $request->query('url');
        $mediaKey = $request->query('mediaKey');

        if (!$encodedUrl || !$mediaKey) {
            abort(400, 'No se proporcionó la URL encriptada o la mediaKey.');
        }

        // Decodificar la URL desde Base64.
        $encryptedUrl = base64_decode($encodedUrl);

        $decryptedFilePath = \decryptMedia($encryptedUrl, $mediaKey);
        Log::info('Ruta del archivo desencriptado: ' . $decryptedFilePath);

        if (!$decryptedFilePath || !file_exists($decryptedFilePath)) {
            abort(404, 'Archivo desencriptado no encontrado.');
        }

        return response()->download($decryptedFilePath, 'video.mp4')
                         ->deleteFileAfterSend(true);
    }
}


