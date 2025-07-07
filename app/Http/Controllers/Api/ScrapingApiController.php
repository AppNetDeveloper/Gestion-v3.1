<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Scraping;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Scraping",
 *     description="API para el servicio de scraping"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="scrapingToken",
 *     type="apiKey",
 *     name="token",
 *     in="query",
 *     description="Token de autenticación para el servicio de scraping"
 * )
 */
class ScrapingApiController extends Controller
{
    /**
     * Obtiene una tarea de scraping pendiente.
     * 
     * @OA\Get(
     *     path="/api/scraping",
     *     summary="Obtiene una tarea de scraping pendiente",
     *     description="Devuelve la tarea de scraping pendiente más antigua para ser procesada por el scraper",
     *     operationId="getScrapingTask",
     *     tags={"Scraping"},
     *     security={{
     *         "scrapingToken": {}
     *     }},
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         description="Token de autenticación del usuario",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tarea de scraping pendiente",
     *         @OA\JsonContent(
     *             @OA\Property(property="taskerId", type="string", example="SCRAPER_TASK_XYZ_789"),
     *             @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
     *             @OA\Property(property="keywords", type="string", example="desarrollo web barcelona,agencia marketing madrid"),
     *             @OA\Property(property="linkedinUsername", type="string", example="usuario@ejemplo.com"),
     *             @OA\Property(property="linkedinPassword", type="string", example="contraseña123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No hay tareas pendientes"
     *     )
     * )
     */
    public function getTask(Request $request)
    {
        try {
            // Validar que el token viene en la URL
            if (!$request->has('token')) {
                return response()->json(['error' => 'Token no proporcionado'], 401);
            }
            
            $tokenValue = $request->query('token');
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($tokenValue);
            if (!$token) {
                return response()->json(['error' => 'Token inválido'], 401);
            }

            $user = $token->tokenable;
            if (!$user) {
                return response()->json(['error' => 'Usuario no encontrado'], 401);
            }

            // Buscar la tarea pendiente más antigua para este usuario
            $scraping = Scraping::where('user_id', $user->id)
                ->where('status', Scraping::STATUS_PENDING)
                ->orderBy('created_at', 'asc')
                ->first();

            if (!$scraping) {
                return response()->json(['message' => 'No hay tareas pendientes'], 404);
            }

            // Preparar respuesta
            $response = [
                'taskerId' => $scraping->tasker_id,
                'token' => $request->query('token'), // Devolvemos el mismo token que recibimos
                'keywords' => $scraping->keywords
            ];

            // Añadir credenciales de LinkedIn solo si están presentes
            if (!empty($scraping->linkedin_username)) {
                $response['linkedinUsername'] = $scraping->linkedin_username;
            }

            if (!empty($scraping->linkedin_password)) {
                $response['linkedinPassword'] = $scraping->linkedin_password;
            }

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Error al obtener tarea de scraping: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Recibe un contacto del scraper y lo asocia a una tarea de scraping.
     * 
     * @OA\Post(
     *     path="/api/scraping",
     *     summary="Recibe un contacto del scraper",
     *     description="Recibe un contacto del scraper y lo asocia a una tarea de scraping",
     *     operationId="receiveScrapingContact",
     *     tags={"Scraping"},
     *     security={{
     *         "scrapingToken": {}
     *     }},
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         description="Token de autenticación del usuario",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "value", "tasker", "name", "token"},
     *             @OA\Property(property="type", type="string", example="email", description="Tipo de contacto: email o phone"),
     *             @OA\Property(property="value", type="string", example="contacto@ejemplo.com", description="Valor del contacto"),
     *             @OA\Property(property="tasker", type="string", example="TAREA_WEB_001", description="ID de la tarea de scraping"),
     *             @OA\Property(property="name", type="string", example="scraping", description="Nombre del scraper"),
     *             @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...", description="Token de autenticación")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contacto recibido correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Contacto recibido correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en los datos enviados"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tarea de scraping no encontrada"
     *     )
     * )
     */
    public function receiveContact(Request $request)
    {
        try {
            // Validar que el token viene en la URL
            if (!$request->has('token')) {
                return response()->json(['error' => 'Token no proporcionado en URL'], 401);
            }
            
            $urlToken = $request->query('token');
            
            // Validar los datos recibidos en el JSON
            $validator = Validator::make($request->all(), [
                'type' => 'required|string|in:email,phone',
                'value' => 'required|string|max:255',
                'tasker' => 'required|string|exists:scrapings,tasker_id',
                'name' => 'required|string',
                'token' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }

            // Verificar que el token en la URL coincide con el token en el JSON
            if ($urlToken !== $request->token) {
                return response()->json(['error' => 'Token en URL no coincide con el token en el cuerpo'], 401);
            }

            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($urlToken);
            if (!$token) {
                return response()->json(['error' => 'Token inválido'], 401);
            }
            $user = $token->tokenable;
            if (!$user) {
                return response()->json(['error' => 'Usuario no encontrado'], 401);
            }

            // Buscar la tarea de scraping
            $scraping = Scraping::where('tasker_id', $request->tasker)
                ->where('user_id', $user->id)
                ->first();

            if (!$scraping) {
                return response()->json(['error' => 'Tarea de scraping no encontrada'], 404);
            }

            // Normalizar el valor según el tipo
            $value = $request->value;
            $field = $request->type === 'email' ? 'email' : 'phone';

            // Normalizar teléfono si es necesario
            if ($field === 'phone') {
                $value = preg_replace('/[^0-9+]/', '', $value);
            }

            // Buscar si ya existe un contacto con ese valor
            $contact = Contact::where($field, $value)->first();

            // Si no existe, crear uno nuevo
            if (!$contact) {
                $contact = new Contact();
                $contact->user_id = $user->id;
                $contact->name = 'Scraping Contact';
                $contact->$field = $value;
                $contact->save();
            }

            // Asociar el contacto a la tarea de scraping si no está ya asociado
            if (!$scraping->contacts()->where('contacts.id', $contact->id)->exists()) {
                $scraping->contacts()->attach($contact->id);
            }

            // Actualizar el estado de la tarea a completada (status = 1)
            $scraping->status = 1;
            $scraping->save();

            return response()->json([
                'success' => true,
                'message' => 'Contacto recibido y procesado correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error al recibir contacto de scraping: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    // El método updateStatus ha sido eliminado ya que ahora el estado se actualiza automáticamente en el método receiveContact
}
