// Importar módulos usando la sintaxis ES Modules
import mqtt from 'mqtt';
import mysql from 'mysql2/promise'; // O import { Pool } from 'pg'; si usas PostgreSQL
import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url'; // Necesario para simular __dirname
import { dirname } from 'path';     // Necesario para simular __dirname

// --- Configuración ---

// Simular __dirname y __filename en ES Modules
const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// Cargar variables de entorno desde ../.env
const envPath = path.resolve(__dirname, '../.env');
const loadEnvResult = dotenv.config({ path: envPath });

if (loadEnvResult.error) {
    console.error('FATAL ERROR: No se pudo cargar el archivo .env en', envPath, loadEnvResult.error);
    process.exit(1); // Salir si no se encuentra el .env
}

// Configuración MQTT
const MQTT_BROKER_URL = process.env.MQTT_BROKER_URL || 'mqtt://localhost:1883'; // Permite override desde .env
const MQTT_TOPIC = 'owntracks/user/appnetd'; // El tópico específico a escuchar
const MQTT_CLIENT_ID = `owntracks_listener_${Math.random().toString(16).substr(2, 8)}`; // ID de cliente único

// Configuración Base de Datos (desde .env de Laravel)
const DB_CONFIG = {
    host: process.env.DB_HOST || '127.0.0.1',
    port: process.env.DB_PORT || 3306, // Cambia a 5432 para PostgreSQL
    user: process.env.DB_USERNAME,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_DATABASE,
    waitForConnections: true,
    connectionLimit: 10, // Límite de conexiones en el pool
    queueLimit: 0, // Sin límite en la cola de espera
};

// Validar configuración mínima de DB
if (!DB_CONFIG.user || !DB_CONFIG.database) {
    console.error('FATAL ERROR: Faltan variables de entorno de base de datos (DB_USERNAME, DB_DATABASE) en .env');
    process.exit(1);
}

// --- Variables Globales ---
let dbPool;
let mqttClient;
const DB_RETRY_INTERVAL = 5000; // Reintentar conexión DB cada 5 segundos
const MAX_DB_RETRIES = 10; // Máximo número de reintentos para la conexión inicial

// --- Funciones ---

/**
 * Intenta establecer la conexión con la base de datos con reintentos.
 */
async function connectToDatabase(retries = MAX_DB_RETRIES) {
    try {
        // Para MySQL/MariaDB con mysql2
        dbPool = mysql.createPool(DB_CONFIG);
        // Probar la conexión obteniendo una temporalmente
        const connection = await dbPool.getConnection();
        console.log('Conexión a la base de datos establecida con éxito.');
        connection.release();
        return dbPool;

        /* ----- Descomenta esto y comenta lo anterior si usas PostgreSQL con pg -----
        // import { Pool } from 'pg'; // Asegúrate de tener esta importación arriba
        dbPool = new Pool(DB_CONFIG);
        // Probar la conexión
        const client = await dbPool.connect();
        console.log('Conexión a la base de datos (PostgreSQL) establecida con éxito.');
        client.release();
        return dbPool;
        */

    } catch (error) {
        console.error(`Error al conectar a la base de datos (intento ${MAX_DB_RETRIES - retries + 1}/${MAX_DB_RETRIES}):`, error.message);
        if (retries > 0) {
            console.log(`Reintentando conexión a la base de datos en ${DB_RETRY_INTERVAL / 1000} segundos...`);
            await new Promise(resolve => setTimeout(resolve, DB_RETRY_INTERVAL));
            return connectToDatabase(retries - 1);
        } else {
            console.error('FATAL ERROR: No se pudo establecer conexión con la base de datos después de varios intentos.');
            process.exit(1); // Salir si no se puede conectar inicialmente
        }
    }
}

/**
 * Valida los datos recibidos del JSON de OwnTracks.
 * Devuelve el objeto validado y parseado o null si no es válido.
 */
function validateAndParsePayload(payloadString) {
    let data;
    try {
        data = JSON.parse(payloadString);
    } catch (error) {
        console.error('Error: Mensaje MQTT recibido no es un JSON válido:', payloadString, error.message);
        return null;
    }

    // Validaciones esenciales
    if (typeof data !== 'object' || data === null) {
        console.error('Error: Payload no es un objeto JSON:', data);
        return null;
    }
    if (data._type !== 'location') {
        console.log('Info: Mensaje ignorado, no es de tipo "location":', data._type);
        return null;
    }
    if (typeof data.lat !== 'number' || typeof data.lon !== 'number') {
        console.error('Error: Faltan coordenadas (lat/lon) o no son números:', data);
        return null;
    }
    if (typeof data.tst !== 'number') {
        console.error('Error: Falta timestamp (tst) o no es un número:', data);
        return null;
    }
    if (!data.tid) { // tid puede ser string o número, pero debe existir
        console.error('Error: Falta Tracker ID (tid):', data);
        return null;
    }

    // Intentar convertir tid a entero (asumiendo que tid es el user_id)
    // ¡¡¡IMPORTANTE!!!: Ajusta esta lógica si 'tid' no es directamente el user_id numérico.
    const userId = parseInt(data.tid, 10);
    if (isNaN(userId)) {
        console.error(`Error: No se pudo convertir tid "${data.tid}" a un user_id numérico.`);
        // Aquí podrías tener lógica para buscar el usuario por 'tid' si fuera un string como 'tokay'
        // const user = await findUserByTid(data.tid); // Ejemplo
        // if (!user) return null;
        // userId = user.id;
        return null; // Por ahora, si no es numérico directo, lo descartamos
    }

    // Mapeo y preparación de datos para la DB (coincidiendo con la migración)
    // Se usan `?? null` para los campos opcionales
    const locationData = {
        user_id: userId,
        latitude: data.lat,
        longitude: data.lon,
        // Convierte Unix timestamp (segundos) a formato DATETIME de MySQL/ISO para PG
        recorded_at: new Date(data.tst * 1000).toISOString().slice(0, 19).replace('T', ' '),
        accuracy: data.acc ?? null,
        altitude: data.alt ?? null,
        velocity: data.vel ?? null,
        course: data.cog ?? null,
        vertical_accuracy: data.vac ?? null,
        battery_level: data.batt ?? null,
        battery_status: data.bs ?? null,
        connection_type: data.conn ?? null,
        ssid: data.SSID ?? null, // Nota: SSID en mayúsculas en el JSON original
        bssid: data.BSSID ?? null, // Nota: BSSID en mayúsculas en el JSON original
        trigger_type: data.t ?? null,
        type: data._type ?? 'location', // Campo renombrado en la DB
        owntracks_message_id: data._id ?? null, // Campo renombrado en la DB
        message_created_at: data.created_at ? new Date(data.created_at * 1000).toISOString().slice(0, 19).replace('T', ' ') : null,
        monitoring_mode: data.m ?? null
    };

    return locationData;
}

/**
 * Inserta los datos de ubicación en la base de datos.
 */
async function insertLocation(locationData) {
    const sql = `
        INSERT INTO locations (
            user_id, latitude, longitude, recorded_at, accuracy, altitude,
            velocity, course, vertical_accuracy, battery_level, battery_status,
            connection_type, ssid, bssid, trigger_type, type,
            owntracks_message_id, message_created_at, monitoring_mode,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    `;
    // Asegúrate de que el orden de los ? coincida con el array de valores
    const values = [
        locationData.user_id, locationData.latitude, locationData.longitude,
        locationData.recorded_at, locationData.accuracy, locationData.altitude,
        locationData.velocity, locationData.course, locationData.vertical_accuracy,
        locationData.battery_level, locationData.battery_status,
        locationData.connection_type, locationData.ssid, locationData.bssid,
        locationData.trigger_type, locationData.type,
        locationData.owntracks_message_id, locationData.message_created_at,
        locationData.monitoring_mode
    ];

    let connection;
    try {
        // Para MySQL/MariaDB con mysql2
        connection = await dbPool.getConnection();
        await connection.query(sql, values); // Usar query con placeholders para seguridad

        /* ----- Descomenta esto y comenta lo anterior si usas PostgreSQL con pg -----
           // PostgreSQL usa $1, $2, ... en lugar de ?
           const pgSql = sql.replace(/\?/g, (match, index) => `$${index + 1}`);
           await dbPool.query(pgSql, values);
        */

        console.log(`INFO: Ubicación guardada para user_id ${locationData.user_id} en ${locationData.recorded_at}`);

    } catch (error) {
        console.error('ERROR al insertar ubicación en la base de datos:', error.message);
        // Podrías añadir lógica aquí para reintentar o guardar en un log de fallos
    } finally {
        if (connection) {
            connection.release(); // ¡¡MUY IMPORTANTE: Liberar siempre la conexión!!
        }
    }
}

/**
 * Inicia la conexión MQTT y la suscripción.
 */
function connectToMqtt() {
    console.log(`Intentando conectar a MQTT Broker: ${MQTT_BROKER_URL}`);
    mqttClient = mqtt.connect(MQTT_BROKER_URL, {
        clientId: MQTT_CLIENT_ID,
        clean: true, // Empezar sesión limpia cada vez
        connectTimeout: 4000, // Tiempo de espera para conectar
        reconnectPeriod: 1000, // Intentar reconectar cada segundo si se pierde conexión
    });

    mqttClient.on('connect', () => {
        console.log('Conectado a MQTT Broker.');
        mqttClient.subscribe(MQTT_TOPIC, (err) => {
            if (!err) {
                console.log(`Suscrito al tópico: ${MQTT_TOPIC}`);
            } else {
                console.error(`ERROR al suscribirse al tópico ${MQTT_TOPIC}:`, err);
                // Considerar reintentar suscripción o salir si es crítico
            }
        });
    });

    mqttClient.on('message', async (topic, message) => {
        console.log(`Mensaje recibido en tópico ${topic}`);
        const payloadString = message.toString();

        // Procesamiento robusto: validar y luego insertar
        try {
            const locationData = validateAndParsePayload(payloadString);

            if (locationData) {
                // No esperar (await) aquí si quieres procesar mensajes concurrentemente,
                // pero puede sobrecargar la DB. Esperar es más seguro.
                await insertLocation(locationData);
            } else {
                console.log('Mensaje inválido o no procesable, descartado.');
            }
        } catch (processingError) {
            // Captura errores inesperados durante validación/inserción que no fueron capturados internamente
            console.error("ERROR INESPERADO durante el procesamiento del mensaje:", processingError);
        }
    });

    mqttClient.on('error', (error) => {
        console.error('Error de conexión MQTT:', error);
        // La librería intentará reconectar automáticamente (reconnectPeriod)
    });

    mqttClient.on('reconnect', () => {
        console.log('Reconectando a MQTT Broker...');
    });

    mqttClient.on('close', () => {
        console.log('Conexión MQTT cerrada.');
        // Supervisor debería reiniciar el script si se cierra inesperadamente
    });

    mqttClient.on('offline', () => {
        console.log('Cliente MQTT está offline.');
    });
}

/**
 * Maneja el cierre ordenado de la aplicación.
 */
async function gracefulShutdown() {
    console.log('\nRecibida señal de apagado. Cerrando conexiones...');
    if (mqttClient) {
        // Desuscribirse y cerrar conexión MQTT
        try {
            // Esperar un poco para que mensajes en vuelo se procesen
            await new Promise(resolve => setTimeout(resolve, 500));
            if (mqttClient.connected) {
                 mqttClient.unsubscribe(MQTT_TOPIC, (err) => {
                     if(err) console.error("Error al desuscribir:", err);
                     mqttClient.end(true, () => { // true fuerza el cierre incluso si hay mensajes offline
                         console.log('Cliente MQTT desconectado.');
                         closeDbPool();
                     });
                 });
            } else {
                 mqttClient.end(true);
                 console.log('Cliente MQTT ya estaba desconectado.');
                 closeDbPool();
            }

        } catch (error) {
            console.error('Error durante el cierre de MQTT:', error);
            closeDbPool(); // Intentar cerrar DB igualmente
        }
    } else {
       closeDbPool(); // Cerrar DB si MQTT no estaba inicializado
    }
}

function closeDbPool() {
     if (dbPool) {
        // Cerrar pool de conexiones de la base de datos
        dbPool.end(err => {
            if (err) {
                console.error('Error al cerrar el pool de la base de datos:', err);
            } else {
                console.log('Pool de la base de datos cerrado.');
            }
            process.exit(err ? 1 : 0); // Salir con código de error si falló el cierre
        });
    } else {
        process.exit(0); // Salir si no había pool
    }
}

// --- Inicio de la Aplicación ---

async function main() {
    console.log('Iniciando listener MQTT de OwnTracks (ESM)...');

    // Conectar primero a la base de datos
    await connectToDatabase();

    // Si la conexión a DB fue exitosa, conectar a MQTT
    connectToMqtt();

    // Capturar señales de terminación para cierre ordenado
    process.on('SIGINT', gracefulShutdown); // Ctrl+C
    process.on('SIGTERM', gracefulShutdown); // Señal de kill (usada por Supervisor)

    // Capturar errores no manejados para evitar que el script se caiga silenciosamente
    process.on('uncaughtException', (error, origin) => {
        console.error('ERROR NO CAPTURADO (uncaughtException):', error);
        console.error('Origen:', origin);
        // Considera si quieres salir aquí o intentar seguir. Salir permite a Supervisor reiniciar.
        process.exit(1);
    });

    process.on('unhandledRejection', (reason, promise) => {
        console.error('ERROR NO CAPTURADO (unhandledRejection):', reason);
        // console.error('Promesa:', promise); // Puede ser muy verboso
        process.exit(1);
    });

    console.log('Listener iniciado. Esperando mensajes MQTT...');
}

// Ejecutar la función principal
main();
