<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWhatsAppToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @OA\SecurityRequirement(
     *     {"whatsappToken": {}}
     * )
     */
    public function handle(Request $request, Closure $next)
    {
        $apiToken = $request->bearerToken();
        $validToken = config('services.whatsapp.api_token');

        if (!$apiToken || $apiToken !== $validToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing WhatsApp API token',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
