<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class StorageHelper
{
    /**
     * Verifica de manera segura si un archivo existe en el almacenamiento.
     *
     * @param string|null $path Ruta del archivo a verificar
     * @return bool
     */
    public static function exists($path): bool
    {
        if (empty($path)) {
            Log::warning('StorageHelper::exists llamado con una ruta vacía o nula');
            return false;
        }
        
        return Storage::exists($path);
    }
    
    /**
     * Descarga de manera segura un archivo del almacenamiento.
     *
     * @param string|null $path Ruta del archivo a descargar
     * @param string|null $name Nombre que se mostrará al descargar
     * @return mixed
     */
    public static function download($path, $name = null)
    {
        if (empty($path)) {
            Log::warning('StorageHelper::download llamado con una ruta vacía o nula');
            abort(404, 'Archivo no encontrado en el almacenamiento.');
        }
        
        if (!Storage::exists($path)) {
            Log::warning("StorageHelper::download - El archivo no existe: {$path}");
            abort(404, 'Archivo no encontrado en el almacenamiento.');
        }
        
        return Storage::download($path, $name);
    }
    
    /**
     * Elimina de manera segura un archivo del almacenamiento.
     *
     * @param string|null $path Ruta del archivo a eliminar
     * @return bool
     */
    public static function delete($path): bool
    {
        if (empty($path)) {
            Log::warning('StorageHelper::delete llamado con una ruta vacía o nula');
            return false;
        }
        
        if (!Storage::exists($path)) {
            Log::warning("StorageHelper::delete - El archivo no existe: {$path}");
            return false;
        }
        
        return Storage::delete($path);
    }
}
