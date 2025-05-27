<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTelegramToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @OA\SecurityRequirement(
     *     {"telegramToken": {}}
     * )
     */
    public function handle(Request $request, Closure $next)
    {
        $apiToken = $request->bearerToken();
        $validToken = config('services.telegram.api_token');

        if (!$apiToken || $apiToken !== $validToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing Telegram API token',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
