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
     */public function updateEnv(Request $request) : Collection
{
    $envFile = base_path('.env');

    // Leer el contenido actual del archivo .env
    $envContent = file_get_contents($envFile);

    // Iterar sobre las claves y valores proporcionados en la solicitud
    foreach ($request->except('_token', '_method') as $key => $value) {
        // Crear el patrón de búsqueda para la variable de entorno
        $pattern = '/^' . preg_quote($key) . '\s*=\s*(.*)$/m';

        // Reemplazar el valor existente o agregar una nueva variable de entorno
        $replacement = $key . '=' . $value;
        $envContent = preg_replace($pattern, $replacement, $envContent, 1);
    }

    // Escribir el contenido actualizado de vuelta al archivo .env
    file_put_contents($envFile, $envContent);

    // Cargar las variables de entorno actualizadas
    $dotenv = Dotenv::createMutable(base_path());
    $dotenv->load();

    // Devolver las variables de entorno actualizadas
    return $this->getEnv(array_keys($request->except('_token', '_method')));
}




}

