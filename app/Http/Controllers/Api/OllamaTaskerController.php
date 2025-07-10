<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\OllamaTasker;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Info(
 *     title="Ollama Tasker API",
 *     version="1.0.0",
 *     description="API para gestionar tareas asíncronas con Ollama",
 *     @OA\Contact(
 *         email="info@appnet.dev"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="https://app.appnet.dev",
 *     description="API Server"
 * )
 * 
 * @OA\Tag(
 *     name="Ollama Tasks",
 *     description="Operaciones para gestionar tareas de Ollama"
 * )
 * 
 * @OA\SecurityScheme(
 *     type="apiKey",
 *     in="header",
 *     securityScheme="api_key",
 *     name="Authorization"
 * )
 * 
 * @OA\Security(
 *     {
 *         "api_key": {}
 *     }
 * )
 */

/**
 * @OA\Schema(
 *     schema="TaskInput",
 *     required={"prompt"},
 *     @OA\Property(property="prompt", type="string", example="Dime un chiste"),
 *     @OA\Property(property="model", type="string", example="gemma3:4b-it-qat"),
 *     @OA\Property(property="callback_url", type="string", format="url", example="https://tudominio.com/webhook/ollama-callback")
 * )
 *
 * @OA\Schema(
 *     schema="TaskResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="task_id", type="integer", example=1),
 *     @OA\Property(property="status", type="string", example="Task created and queued for processing")
 * )
 *
 * @OA\Schema(
 *     schema="TaskResult",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(
 *         property="task",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="model", type="string", example="gemma3:4b-it-qat"),
 *         @OA\Property(property="prompt", type="string", example="Dime un chiste"),
 *         @OA\Property(property="response", type="string", nullable=true, example="¿Por qué los programadores prefieren el modo oscuro? ¡Porque la luz atrae los bugs!"),
 *         @OA\Property(property="error", type="string", nullable=true, example=null),
 *         @OA\Property(property="created_at", type="string", format="date-time"),
 *         @OA\Property(property="updated_at", type="string", format="date-time")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Error de validación"),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         @OA\AdditionalProperties(
 *             @OA\Property(type="array", @OA\Items(type="string"))
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="NotFoundResponse",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="error", type="string", example="Task not found")
 * )
 */

class OllamaTaskerController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/ollama-tasks",
     *     tags={"Ollama Tasks"},
     *     summary="Crear una nueva tarea de Ollama",
     *     description="Crea una nueva tarea asíncrona para procesar un prompt con Ollama. Requiere autenticación por token de API.",
     *     operationId="createTask",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos de la tarea a crear",
     *         @OA\JsonContent(ref="#/components/schemas/TaskInput")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tarea creada exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/TaskResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     *
     * Create a new task
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createTask(Request $request)
    {
        Log::info('OllamaTaskerController@createTask: Request received.');
        // Get allowed models from .env
        $defaultModel = env('OLLAMA_MODEL_DEFAULT', 'gemma3:4b-it-qat');
        $miniModel = env('OLLAMA_MODEL_MINI', 'gemma3:4b-it-qat');
        
        // Validate the request
        $validator = Validator::make($request->all(), [
            'prompt' => 'required|string',
            'model' => 'sometimes|string|in:' . $defaultModel . ',' . $miniModel,
            'callback_url' => 'sometimes|url|nullable',
        ], [
            'model.in' => 'El modelo debe ser uno de los permitidos: ' . $defaultModel . ' o ' . $miniModel,
        ]);

        if ($validator->fails()) {
            Log::error('OllamaTaskerController@createTask: Validation failed.', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Determine which model to use
        $model = $request->input('model');
        if (!$model || !in_array($model, [$defaultModel, $miniModel])) {
            $model = $miniModel; // Default to mini model if not specified or invalid
        }

        // Create a new task
        $task = OllamaTasker::create([
            'model' => $model,
            'prompt' => $request->input('prompt'),
            'response' => null,
            'error' => null,
            'callback_url' => $request->input('callback_url'),
        ]);

        // Dispatch the task to be processed in the background
        dispatch(function () use ($task) {
            $this->processTask($task);
        })->afterResponse();

        return response()->json([
            'success' => true,
            'task_id' => $task->id,
            'status' => 'Task created and queued for processing'
        ]);
    }


    /**
     * Get the status and result of a task
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * @OA\Get(
     *     path="/api/ollama-tasks/{id}",
     *     tags={"Ollama Tasks"},
     *     summary="Obtener el resultado de una tarea",
     *     description="Obtiene el estado y resultado de una tarea de Ollama por su ID. Requiere autenticación por token de API.",
     *     operationId="getTaskResult",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la tarea",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles de la tarea",
     *         @OA\JsonContent(ref="#/components/schemas/TaskResult")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tarea no encontrada",
     *         @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")
     *     )
     * )
     */
    public function getTaskResult($id)
    {
        $task = OllamaTasker::find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'error' => 'Task not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'task' => [
                'id' => $task->id,
                'model' => $task->model,
                'prompt' => $task->prompt,
                'response' => $task->response,
                'error' => $task->error,
                'created_at' => $task->created_at,
                'updated_at' => $task->updated_at,
            ]
        ]);
    }

    /**
     * Process the task by calling the Ollama API
     *
     * @param  \App\Models\OllamaTasker  $task
     * @return void
     */
    protected function processTask(OllamaTasker $task)
    {
        try {
            $task->touch(); // Update the updated_at timestamp
            
            $ollamaUrl = rtrim(env('OLLAMA_URL', 'http://localhost:11434'), '/') . '/api/chat';
            
            Log::info('Processing Ollama task', [
                'task_id' => $task->id,
                'model' => $task->model,
                'ollama_url' => $ollamaUrl
            ]);

            $response = Http::timeout(300) // 5 minutes timeout
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . env('OLLAMA_TASKER_API_TOKEN')
                ])
                ->post($ollamaUrl, [
                    'model' => $task->model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $task->prompt
                        ]
                    ],
                    'stream' => false
                ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['message']['content'])) {
                    $task->response = $responseData['message']['content'];
                    $task->save();
                    
                    Log::info('Successfully processed Ollama task', [
                        'task_id' => $task->id,
                        'response_length' => strlen($task->response)
                    ]);
                    
                    // If there's a callback URL, send the result there
                    if ($task->callback_url) {
                        $this->sendCallback($task);
                    }
                    
                    return;
                }
                
                throw new \Exception('Invalid response format from Ollama API');
            }
            
            throw new \Exception('Failed to get a successful response from Ollama API');
            
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            Log::error('Error processing Ollama task', [
                'task_id' => $task->id,
                'error' => $errorMessage,
                'trace' => $e->getTraceAsString()
            ]);
            
            $task->error = $errorMessage;
            $task->save();
            
            // If there's a callback URL, send the error there
            if ($task->callback_url) {
                $this->sendErrorCallback($task, $errorMessage);
            }
        }
    }
    
    /**
     * Send the task result to a callback URL
     * 
     * @param  \App\Models\OllamaTasker  $task
     * @return void
     */
    protected function sendCallback(OllamaTasker $task)
    {
        try {
            Http::post($task->callback_url, [
                'status' => 'completed',
                'task_id' => $task->id,
                'response' => $task->response,
                'completed_at' => now()->toDateTimeString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send callback', [
                'task_id' => $task->id,
                'callback_url' => $task->callback_url,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Send an error to the callback URL
     * 
     * @param  \App\Models\OllamaTasker  $task
     * @param  string  $errorMessage
     * @return void
     */
    protected function sendErrorCallback(OllamaTasker $task, string $errorMessage)
    {
        try {
            Http::post($task->callback_url, [
                'status' => 'failed',
                'task_id' => $task->id,
                'error' => $errorMessage,
                'failed_at' => now()->toDateTimeString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send error callback', [
                'task_id' => $task->id,
                'callback_url' => $task->callback_url,
                'error' => $e->getMessage()
            ]);
        }
    }
}
