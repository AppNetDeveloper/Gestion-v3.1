<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;
use PDOException;
use Exception;

class ProcessEmbeddingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'embeddings:process {--limit=0 : Limite de embeddings a procesar antes de terminar (0 para ilimitado)} {--delay=1 : Retraso en segundos entre cada procesamiento de chunk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesa los embeddings pendientes en bucle infinito para uso con Supervisor.';

    /**
     * The database connection instance.
     *
     * @var PDO
     */
    protected $pdo;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Iniciando comando de procesamiento de embeddings...");
        // AÑADIDO: Log para indicar el inicio del intento de conexión
        $this->info("Intento de conexión a la base de datos...");

        // Intentar conectar a la base de datos
        if (!$this->connectToDatabase()) {
            // AÑADIDO: Mensaje de error más explícito si la conexión falla definitivamente
            $this->error("Fallo definitivo al conectar a la base de datos. Terminando.");
            return Command::FAILURE; // Fallo al conectar
        }

        $limit = (int) $this->option('limit');
        $delay = (int) $this->option('delay');
        $processedCount = 0;

        $this->info("Conexión a la base de datos establecida correctamente.");
        $this->line("-----------------------------------------------------");

        while (true) {
            // Verificar si se ha alcanzado el límite (si no es 0)
            if ($limit > 0 && $processedCount >= $limit) {
                $this->info("Límite de $limit embeddings procesados alcanzado. Terminando...");
                break;
            }

            try {
                $this->pdo->beginTransaction(); // Iniciar transacción para Atomicidad
                $result = $this->processSingleEmbedding();
                $this->pdo->commit(); // Confirmar transacción

                if ($result === true) {
                    $processedCount++;
                    $pendingCount = $this->countPendingChunks();
                    $this->info("Procesado {$processedCount} embeddings. Quedan {$pendingCount} pendientes.");
                    $this->line("-----------------------------------------------------");

                    if ($pendingCount <= 0) {
                        $this->info("No hay más embeddings pendientes.");
                        // Opcional: Si quieres que termine cuando no haya más
                        // break;
                        // Si quieres que continúe esperando nuevos embeddings, no rompas el bucle
                    }
                } elseif ($result === false) {
                    $this->info("No hay más embeddings para procesar por ahora. Esperando nuevos...");
                    $this->line("-----------------------------------------------------");
                    // No hay chunks pendientes, podemos pausar más tiempo si es necesario
                    sleep(max(1, $delay * 5)); // Pausa más larga si no hay trabajo
                }
            } catch (PDOException $e) {
                // Error de base de datos
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack(); // Revertir transacción en caso de error
                }
                $this->error("Error de base de datos: " . $e->getMessage());
                // AÑADIDO: Log del código de error PDO
                $this->error("Código de error PDO: " . $e->getCode());
                $this->error("Reintentando conexión en 5 segundos...");
                sleep(5); // Esperar antes de intentar reconectar
                $this->connectToDatabase(); // Intentar reconectar
            } catch (Exception $e) {
                // Otros errores generales
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                $this->error("Error inesperado: " . $e->getMessage());
                $this->line("-----------------------------------------------------");
                sleep($delay); // Esperar antes de la siguiente iteración
            }

            // Pequeña pausa para evitar sobrecarga del CPU y la DB
            if ($result === true) { // Solo si se procesó algo, se aplica el delay normal
                sleep($delay);
            }
        }

        $this->info("Procesamiento completado. Se procesaron {$processedCount} embeddings.");
        return Command::SUCCESS;
    }

    /**
     * Conecta a la base de datos usando las variables de entorno de Laravel.
     *
     * @return bool
     */
    protected function connectToDatabase()
    {
        $host = env('DB_HOST', 'localhost');
        $port = env('DB_PORT', '5432');
        $dbname = env('DB_DATABASE', 'forge'); // 'forge' es el valor por defecto de Laravel
        $user = env('DB_USERNAME', 'forge');
        $password = env('DB_PASSWORD', '');

        // AÑADIDO: Logs de depuración para la configuración de la base de datos
        $this->info("DB_HOST: {$host}");
        $this->info("DB_PORT: {$port}");
        $this->info("DB_DATABASE: {$dbname}");
        $this->info("DB_USERNAME: {$user}");
        // $this->info("DB_PASSWORD: " . (empty($password) ? '[EMPTY]' : '[SET]')); // Descomentar con precaución, no loguear contraseñas en producción

        try {
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
            $this->pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // AÑADIDO: Timeout de conexión para evitar que se quede colgado indefinidamente
                PDO::ATTR_TIMEOUT => 5 // 5 segundos de timeout para la conexión
            ]);
            // AÑADIDO: Log para confirmar la conexión PDO exitosa
            $this->info("Conexión PDO exitosa en connectToDatabase().");
            return true;
        } catch (PDOException $e) {
            $this->error("Error de conexión a la base de datos: " . $e->getMessage());
            // AÑADIDO: Log del código de error PDO en la conexión
            $this->error("Código de error PDO: " . $e->getCode());
            return false;
        } catch (Exception $e) {
            // AÑADIDO: Captura de otros errores inesperados durante la conexión
            $this->error("Error inesperado en connectToDatabase(): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Procesa un solo embedding pendiente.
     *
     * @return bool|null True si se procesó un chunk, false si no hay, null si hubo un error irrecuperable.
     */
    protected function processSingleEmbedding()
    {
        // Usar SELECT FOR UPDATE para bloquear el chunk y evitar que otros procesos lo tomen
        $stmt = $this->pdo->query("
            SELECT kb.id, kb.ollama_tasker_id
            FROM knowledge_base kb
            WHERE kb.embedding_status = 'pending'
            AND kb.ollama_tasker_id IS NOT NULL
            LIMIT 1
            FOR UPDATE SKIP LOCKED
        ");

        $chunk = $stmt->fetch();

        if (!$chunk) {
            return false; // No hay chunks pendientes para procesar
        }

        $this->info("Procesando chunk ID: {$chunk['id']}");

        // Obtener la tarea Ollama
        $stmt = $this->pdo->prepare("
            SELECT response, error
            FROM ollama_taskers
            WHERE id = :taskerId
        ");
        $stmt->execute(['taskerId' => $chunk['ollama_tasker_id']]);
        $task = $stmt->fetch();

        if (!$task) {
            // Si no se encuentra la tarea Ollama, marca el chunk como error
            $this->updateEmbeddingStatus($chunk['id'], 'error', "No se encontró la tarea Ollama ID {$chunk['ollama_tasker_id']}");
            return true; // Se ha gestionado el chunk, aunque sea con error
        }

        if ($task['response']) {
            // AÑADIDO: Logs para inspeccionar la respuesta de Ollama y uso de memoria
            $this->info("Tamaño de la respuesta de Ollama (en bytes): " . strlen($task['response']));
            $this->info("Primeros 200 caracteres de la respuesta: " . substr($task['response'], 0, 200));
            $this->info("Uso de memoria antes de json_decode: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB");

            try {
                $data = json_decode($task['response'], true);
                $this->info("Uso de memoria después de json_decode: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB");

                $embedding = null;

                if (isset($data['embedding'])) {
                    $embedding = $data['embedding'];
                } elseif (is_array($data)) {
                    // Si el JSON es directamente un array de embedding
                    $embedding = $data;
                }

                if ($embedding && is_array($embedding)) {
                    // AÑADIDO: Log de dimensiones del embedding
                    $this->info("Dimensiones del embedding: " . count($embedding));
                    // Opcional: Descomentar para ver los primeros elementos del embedding (puede ser mucha salida)
                    // $this->info("Primeros 10 elementos del embedding: " . implode(', ', array_slice($embedding, 0, 10)));
                    $this->info("Uso de memoria antes de json_encode para DB: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB");

                    // Verificar dimensiones
                    if (count($embedding) != 768) {
                        $this->updateEmbeddingStatus($chunk['id'], 'error', "Error: El embedding tiene " . count($embedding) . " dimensiones, pero se esperaban 768");
                        return true;
                    }

                    // Actualizar el embedding
                    $json_embedding = json_encode($embedding); // Aquí puede ocurrir el pico de memoria si el array es masivo
                    $this->info("Tamaño del JSON del embedding (en bytes): " . strlen($json_embedding));
                    $this->info("Uso de memoria después de json_encode para DB: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB");

                    $stmt = $this->pdo->prepare("
                        UPDATE knowledge_base
                        SET embedding = :embedding::vector,
                            embedding_status = 'done',
                            updated_at = NOW()
                        WHERE id = :chunkId
                    ");

                    $stmt->execute([
                        'embedding' => $json_embedding, // Usar la variable ya codificada
                        'chunkId' => $chunk['id']
                    ]);

                    $this->info("Embedding guardado para chunk ID {$chunk['id']}");
                    // AÑADIDO: Log de uso de memoria al finalizar el procesamiento de un chunk
                    $this->info("Uso de memoria al finalizar chunk: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB (Pico: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB)");
                    return true;
                } else {
                    throw new Exception('Formato de embedding inválido en la respuesta de Ollama.');
                }
            } catch (Exception $e) {
                $this->updateEmbeddingStatus($chunk['id'], 'error', "Error procesando embedding: " . $e->getMessage());
                // AÑADIDO: Log de uso de memoria en caso de error
                $this->error("Uso de memoria en error: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB (Pico: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB)");
                return true;
            }
        } elseif ($task['error']) {
            $this->updateEmbeddingStatus($chunk['id'], 'error', "Error en tarea Ollama: {$task['error']}");
            return true;
        } else {
            // Si la tarea Ollama aún no tiene respuesta ni error, significa que está pendiente
            $this->warn("Tarea Ollama aún pendiente para chunk ID {$chunk['id']}. Saltando por ahora.");
            return true; // Se considera procesado en el sentido de que no se reintentará inmediatamente
        }
    }

    /**
     * Actualiza el estado de un embedding en la base de datos.
     *
     * @param int $chunkId
     * @param string $status
     * @param string $message
     * @return void
     */
    protected function updateEmbeddingStatus(int $chunkId, string $status, string $message = '')
    {
        $stmt = $this->pdo->prepare("
            UPDATE knowledge_base
            SET embedding_status = :status,
                updated_at = NOW(),
                error_message = :message
            WHERE id = :chunkId
        ");
        $stmt->execute([
            'status' => $status,
            'message' => $message,
            'chunkId' => $chunkId
        ]);
        $this->error("Chunk ID {$chunkId} - Estado: {$status}. Mensaje: {$message}");
    }

    /**
     * Cuenta los chunks pendientes.
     *
     * @return int
     */
    protected function countPendingChunks()
    {
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as count
            FROM knowledge_base
            WHERE embedding_status = 'pending'
            AND ollama_tasker_id IS NOT NULL
        ");
        $result = $stmt->fetch();
        return (int) $result['count'];
    }
}