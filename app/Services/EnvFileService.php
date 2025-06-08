<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Dotenv\Dotenv;

class EnvFileService
{

    /**
     * @return Collection
     */
    public function getAllEnv() : Collection
    {
        $dotenv = Dotenv::createImmutable(base_path());
        $dotenv->load();
        $envDetails = new Collection($_ENV);
        return $envDetails->map(function ($value, $key) {
            return [
                'key' => $key,
                'value' => $value,
            ];
        })->groupBy(function ($item, $key) {
            $key = explode('_', $key);
            return $key[0];
        });
    }

    /**
     * Get the specified env data.
     * @param  array  $env
     * @return Collection
     */
    public function getEnv(array $env) : Collection
    {
        $dotenv = Dotenv::createImmutable(base_path());
        $dotenv->load();
        $filteredEnv = array_intersect_key($_ENV, array_flip($env));
        $filteredEnv = new Collection($filteredEnv);
        return $filteredEnv->map(function ($value, $key) {
            return [
                'key' => $key,
                'value' => $value,
            ];
        })->groupBy(function ($item, $key) {
            $key = explode('_', $key);
            return $key[0];
        });
    }

    /**
     * @param  Request  $request
     * @return Collection
     */
    public function updateEnv(Request $request): Collection
    {
        $envFile = base_path('.env');
        
        // Leer el contenido actual del archivo .env
        $envContent = file_get_contents($envFile);
        $lines = explode("\n", $envContent);
        $updatedLines = [];
        $foundKeys = [];
        
        // Procesar cada línea existente
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Si la línea está vacía o es un comentario, la mantenemos igual
            if (empty($line) || str_starts_with($line, '#')) {
                $updatedLines[] = $line;
                continue;
            }
            
            // Extraer la clave de la línea actual
            $parts = explode('=', $line, 2);
            $currentKey = trim($parts[0]);
            
            // Si la clave está en la solicitud, la actualizamos
            if (isset($request[$currentKey])) {
                $value = $request[$currentKey];
                // Escapar comillas dobles y agregar comillas si el valor contiene espacios o caracteres especiales
                $escapedValue = str_replace('"', '\\"', $value);
                if (preg_match('/[\s\"\'\\=#]/', $escapedValue)) {
                    $escapedValue = '"' . $escapedValue . '"';
                }
                $updatedLines[] = $currentKey . '=' . $escapedValue;
                $foundKeys[] = $currentKey;
            } else {
                $updatedLines[] = $line;
            }
        }
        
        // Agregar claves que no existían
        foreach ($request->except('_token', '_method') as $key => $value) {
            if (!in_array($key, $foundKeys)) {
                $escapedValue = str_replace('"', '\\"', $value);
                if (preg_match('/[\s\"\'\\=#]/', $escapedValue)) {
                    $escapedValue = '"' . $escapedValue . '"';
                }
                $updatedLines[] = $key . '=' . $escapedValue;
            }
        }
        
        // Escribir el contenido actualizado de vuelta al archivo .env
        file_put_contents($envFile, implode("\n", $updatedLines));
        
        // Limpiar la caché de configuración
        \Artisan::call('config:clear');
        \Artisan::call('cache:clear');
        
        // Cargar las variables de entorno actualizadas
        $dotenv = \Dotenv\Dotenv::createMutable(base_path());
        $dotenv->load();
        
        // Devolver las variables de entorno actualizadas
        return $this->getEnv(array_keys($request->except('_token', '_method')));
}




}

