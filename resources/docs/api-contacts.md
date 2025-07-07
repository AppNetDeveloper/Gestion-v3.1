# API de Contactos - Documentación

Esta API te permite gestionar tus contactos a través de peticiones HTTP utilizando tu token API personal.

## Autenticación

Todas las solicitudes a la API deben incluir tu token API personal en el encabezado de la solicitud:

```
Authorization: Bearer TU_TOKEN_API
```

Puedes obtener o regenerar tu token API desde tu perfil de usuario.

## Endpoints disponibles

### Listar todos los contactos

```
GET /api/contacts
```

**Respuesta**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "name": "Nombre del contacto",
      "phone": "+34600000000",
      "address": "Dirección del contacto",
      "email": "contacto@ejemplo.com",
      "web": "https://ejemplo.com",
      "telegram": "@usuario_telegram",
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-01T00:00:00.000000Z"
    }
  ]
}
```

### Obtener un contacto específico

```
GET /api/contacts/{id}
```

**Respuesta**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "name": "Nombre del contacto",
    "phone": "+34600000000",
    "address": "Dirección del contacto",
    "email": "contacto@ejemplo.com",
    "web": "https://ejemplo.com",
    "telegram": "@usuario_telegram",
    "created_at": "2023-01-01T00:00:00.000000Z",
    "updated_at": "2023-01-01T00:00:00.000000Z"
  }
}
```

### Crear un nuevo contacto

```
POST /api/contacts
```

**Parámetros**:
```json
{
  "name": "Nombre del contacto",
  "phone": "+34600000000",
  "address": "Dirección del contacto",
  "email": "contacto@ejemplo.com",
  "web": "https://ejemplo.com",
  "telegram": "@usuario_telegram"
}
```

**Respuesta**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "name": "Nombre del contacto",
    "phone": "+34600000000",
    "address": "Dirección del contacto",
    "email": "contacto@ejemplo.com",
    "web": "https://ejemplo.com",
    "telegram": "@usuario_telegram",
    "created_at": "2023-01-01T00:00:00.000000Z",
    "updated_at": "2023-01-01T00:00:00.000000Z"
  },
  "message": "Contacto creado correctamente"
}
```

### Actualizar un contacto existente

```
PUT /api/contacts/{id}
```

**Parámetros**:
```json
{
  "name": "Nuevo nombre",
  "phone": "+34600000001",
  "address": "Nueva dirección",
  "email": "nuevo@ejemplo.com",
  "web": "https://nuevo-ejemplo.com",
  "telegram": "@nuevo_usuario"
}
```

**Respuesta**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "name": "Nuevo nombre",
    "phone": "+34600000001",
    "address": "Nueva dirección",
    "email": "nuevo@ejemplo.com",
    "web": "https://nuevo-ejemplo.com",
    "telegram": "@nuevo_usuario",
    "created_at": "2023-01-01T00:00:00.000000Z",
    "updated_at": "2023-01-01T00:00:00.000000Z"
  },
  "message": "Contacto actualizado correctamente"
}
```

### Eliminar un contacto

```
DELETE /api/contacts/{id}
```

**Respuesta**:
```json
{
  "success": true,
  "message": "Contacto eliminado correctamente"
}
```

## Códigos de estado HTTP

- `200 OK`: La solicitud se ha completado correctamente
- `201 Created`: El recurso se ha creado correctamente
- `404 Not Found`: El recurso solicitado no existe
- `422 Unprocessable Entity`: Los datos enviados no son válidos
- `500 Internal Server Error`: Error del servidor

## Ejemplos de uso con cURL

### Listar todos los contactos
```bash
curl -X GET \
  'https://tu-dominio.com/api/contacts' \
  -H 'Authorization: Bearer TU_TOKEN_API'
```

### Crear un nuevo contacto
```bash
curl -X POST \
  'https://tu-dominio.com/api/contacts' \
  -H 'Authorization: Bearer TU_TOKEN_API' \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Nombre del contacto",
    "phone": "+34600000000",
    "address": "Dirección del contacto",
    "email": "contacto@ejemplo.com",
    "web": "https://ejemplo.com",
    "telegram": "@usuario_telegram"
}'
```
