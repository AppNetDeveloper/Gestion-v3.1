<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DistanceHelper
{
    public static function carretera(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $url = 'https://api.openrouteservice.org/v2/directions/driving-car';

        $response = Http::withHeaders([
            'Authorization' => config('services.openroute.key'),
            'Content-Type' => 'application/json',
        ])->post($url, [
            'coordinates' => [
                [$lng1, $lat1],
                [$lng2, $lat2],
            ],
        ]);

        if ($response->successful()) {
            $data = $response->json();

            if (isset($data['routes'][0]['summary']['distance'])) {
                // Corregido a la ruta real de la respuesta:
                return (int) round($data['routes'][0]['summary']['distance'] / 1000);
            }

            // LOG: estructura inesperada
            Log::error('OpenRouteService: estructura inesperada', [
                'respuesta' => $data,
            ]);
        } else {
            // LOG: fallo petición
            Log::error('OpenRouteService: error petición', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return 0;
    }
}
