<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Contactos",
 *     description="API para gestionar contactos de usuarios"
 * )
 */

class ContactApiController extends Controller
{
    /**
     * Obtener todos los contactos del usuario autenticado
     *
     * @return JsonResponse
     * 
     * @OA\Get(
     *     path="/api/contacts",
     *     summary="Obtener lista de contactos",
     *     description="Devuelve todos los contactos del usuario autenticado",
     *     operationId="contactsIndex",
     *     tags={"Contactos"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de contactos obtenida correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Nombre del contacto"),
     *                     @OA\Property(property="phone", type="string", example="+34600000000"),
     *                     @OA\Property(property="address", type="string", example="Dirección del contacto"),
     *                     @OA\Property(property="email", type="string", example="contacto@ejemplo.com"),
     *                     @OA\Property(property="web", type="string", example="https://ejemplo.com"),
     *                     @OA\Property(property="telegram", type="string", example="@usuario_telegram"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        // Solo se obtienen los contactos del usuario autenticado
        $contacts = Contact::where('user_id', auth()->id())->get();
        
        return response()->json([
            'success' => true,
            'data' => $contacts
        ]);
    }
    
    /**
     * Obtener un contacto específico
     *
     * @param int $id
     * @return JsonResponse
     * 
     * @OA\Get(
     *     path="/api/contacts/{id}",
     *     summary="Obtener un contacto específico",
     *     description="Devuelve un contacto específico del usuario autenticado",
     *     operationId="contactsShow",
     *     tags={"Contactos"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del contacto",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contacto obtenido correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Nombre del contacto"),
     *                 @OA\Property(property="phone", type="string", example="+34600000000"),
     *                 @OA\Property(property="address", type="string", example="Dirección del contacto"),
     *                 @OA\Property(property="email", type="string", example="contacto@ejemplo.com"),
     *                 @OA\Property(property="web", type="string", example="https://ejemplo.com"),
     *                 @OA\Property(property="telegram", type="string", example="@usuario_telegram"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Contacto no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Contacto no encontrado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado"
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        // Buscar el contacto solo entre los que pertenecen al usuario autenticado
        $contact = Contact::where('user_id', auth()->id())->find($id);
        
        if (!$contact) {
            return response()->json([
                'success' => false,
                'message' => 'Contacto no encontrado'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $contact
        ]);
    }
    
    /**
     * Crear un nuevo contacto
     *
     * @param Request $request
     * @return JsonResponse
     * 
     * @OA\Post(
     *     path="/api/contacts",
     *     summary="Crear un nuevo contacto",
     *     description="Crea un nuevo contacto para el usuario autenticado",
     *     operationId="contactsStore",
     *     tags={"Contactos"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos del nuevo contacto",
     *         @OA\JsonContent(
     *             required={"name", "phone"},
     *             @OA\Property(property="name", type="string", example="Nombre del contacto"),
     *             @OA\Property(property="phone", type="string", example="+34600000000"),
     *             @OA\Property(property="address", type="string", example="Dirección del contacto"),
     *             @OA\Property(property="email", type="string", format="email", example="contacto@ejemplo.com"),
     *             @OA\Property(property="web", type="string", format="url", example="https://ejemplo.com"),
     *             @OA\Property(property="telegram", type="string", example="@usuario_telegram")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Contacto creado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Nombre del contacto"),
     *                 @OA\Property(property="phone", type="string", example="+34600000000"),
     *                 @OA\Property(property="address", type="string", example="Dirección del contacto"),
     *                 @OA\Property(property="email", type="string", example="contacto@ejemplo.com"),
     *                 @OA\Property(property="web", type="string", example="https://ejemplo.com"),
     *                 @OA\Property(property="telegram", type="string", example="@usuario_telegram"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ),
     *             @OA\Property(property="message", type="string", example="Contacto creado correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'address' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'web' => 'nullable|url|max:255',
            'telegram' => 'nullable|string|max:255',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Asignar el ID del usuario autenticado
        $data = $validator->validated();
        $data['user_id'] = auth()->id();
        
        $contact = Contact::create($data);
        
        return response()->json([
            'success' => true,
            'data' => $contact,
            'message' => 'Contacto creado correctamente'
        ], 201);
    }
    
    /**
     * Actualizar un contacto existente
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     * 
     * @OA\Put(
     *     path="/api/contacts/{id}",
     *     summary="Actualizar un contacto existente",
     *     description="Actualiza un contacto existente del usuario autenticado",
     *     operationId="contactsUpdate",
     *     tags={"Contactos"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del contacto",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         description="Datos del contacto a actualizar",
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Nombre actualizado"),
     *             @OA\Property(property="phone", type="string", example="+34600000001"),
     *             @OA\Property(property="address", type="string", example="Dirección actualizada"),
     *             @OA\Property(property="email", type="string", format="email", example="nuevo@ejemplo.com"),
     *             @OA\Property(property="web", type="string", format="url", example="https://nuevo-ejemplo.com"),
     *             @OA\Property(property="telegram", type="string", example="@nuevo_usuario")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contacto actualizado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Nombre actualizado"),
     *                 @OA\Property(property="phone", type="string", example="+34600000001"),
     *                 @OA\Property(property="address", type="string", example="Dirección actualizada"),
     *                 @OA\Property(property="email", type="string", example="nuevo@ejemplo.com"),
     *                 @OA\Property(property="web", type="string", example="https://nuevo-ejemplo.com"),
     *                 @OA\Property(property="telegram", type="string", example="@nuevo_usuario"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ),
     *             @OA\Property(property="message", type="string", example="Contacto actualizado correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Contacto no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Contacto no encontrado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado"
     *     )
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Buscar el contacto solo entre los que pertenecen al usuario autenticado
        $contact = Contact::where('user_id', auth()->id())->find($id);
        
        if (!$contact) {
            return response()->json([
                'success' => false,
                'message' => 'Contacto no encontrado'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:50',
            'address' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'web' => 'nullable|url|max:255',
            'telegram' => 'nullable|string|max:255',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $contact->update($validator->validated());
        
        return response()->json([
            'success' => true,
            'data' => $contact,
            'message' => 'Contacto actualizado correctamente'
        ]);
    }
    
    /**
     * Eliminar un contacto
     *
     * @param int $id
     * @return JsonResponse
     * 
     * @OA\Delete(
     *     path="/api/contacts/{id}",
     *     summary="Eliminar un contacto",
     *     description="Elimina un contacto existente del usuario autenticado",
     *     operationId="contactsDestroy",
     *     tags={"Contactos"},
     *     security={{
     *         "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del contacto",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contacto eliminado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Contacto eliminado correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Contacto no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Contacto no encontrado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado"
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        // Buscar el contacto solo entre los que pertenecen al usuario autenticado
        $contact = Contact::where('user_id', auth()->id())->find($id);
        
        if (!$contact) {
            return response()->json([
                'success' => false,
                'message' => 'Contacto no encontrado'
            ], 404);
        }
        
        $contact->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Contacto eliminado correctamente'
        ]);
    }
}
