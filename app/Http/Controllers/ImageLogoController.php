<?php

namespace App\Http\Controllers;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImageLogoController extends Controller
{
    public function show($id)
    {
        $media = Media::find($id); // Suponiendo que tienes un modelo Media

        if (!$media) {
            abort(404); // Imagen no encontrada
        }
        // Verificar si el disco es 'public'
        if ($media->disk === 'public') {
                return redirect($media->getUrl()); // Redirige a la URL pública para el disco 'public'
            } elseif ($media->disk === 'local') {
                $filePath = $media->getPath();

                if (!file_exists($filePath)) {
                    abort(404);
                }
                return response()->file($filePath);
            } else { // Para otros discos
            $disk = Storage::disk($media->disk);
            $filePath = $media->getPath();

            if (!$disk->exists($filePath)) {
                abort(404);
            }
            $mimeType = $disk->mimeType($filePath);
            $stream = $disk->readStream($filePath);

            return new StreamedResponse(function () use ($stream) {
                fpassthru($stream);
            }, 200, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"'
            ]);
        }

    }
}
