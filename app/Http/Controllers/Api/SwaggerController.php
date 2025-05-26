<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/**
 * @OA\Info(
 *     title="API Documentation",
 *     version="1.0.0",
 *     description="Documentación completa de la API del sistema",
 *     @OA\Contact(
 *         email="info@appnetdeveloper.com"
 *     )
 * )
 * 
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 * 
 * @OA\Tag(
 *     name="WhatsApp",
 *     description="Endpoints para manejar mensajes de WhatsApp"
 * )
 * 
 * @OA\Tag(
 *     name="Telegram",
 *     description="Endpoints para manejar mensajes de Telegram"
 * )
 * 
 * @OA\Tag(
 *     name="Scraping",
 *     description="Endpoints para manejar el servicio de scraping"
 * )
 * 
 * @OA\Tag(
 *     name="Server Monitor",
 *     description="Endpoints para monitoreo de servidores"
 * )
 * 
 * @OA\SecurityScheme(
 *     type="http",
 *     scheme="bearer",
 *     securityScheme="bearerAuth",
 *     bearerFormat="JWT"
 * )
 * 
 * @OA\SecurityScheme(
 *     type="apiKey",
 *     in="header",
 *     name="token",
 *     securityScheme="apiToken"
 * )
 */
class SwaggerController extends Controller
{
    // This controller doesn't need any methods
    // It's just for Swagger annotations
}
