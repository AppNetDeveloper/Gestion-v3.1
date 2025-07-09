<?php
/**
 * Script para procesar embeddings directamente con SQL
 * Evita cargar el framework Laravel completo para reducir el uso de memoria
 */

// Configuración de la base de datos desde el archivo .env
$envFile = __DIR__ . '/.env';
$dbConfig = [];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $dbConfig[trim($key)] = trim($value);
        }
    }
}

// Configuración de la conexión a la base de datos
$host = $dbConfig['DB_HOST'] ?? 'localhost';
$port = $dbConfig['DB_PORT'] ?? '5432';
$dbname = $dbConfig['DB_DATABASE'] ?? 'postgres';
$user = $dbConfig['DB_USERNAME'] ?? 'postgres';
$password = $dbConfig['DB_PASSWORD'] ?? '';

// Conectar a la base de datos
try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "Conexión a la base de datos establecida correctamente.\n";
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage() . "\n");
}

// Función para procesar un solo embedding
function processSingleEmbedding($pdo) {
    // Obtener un chunk pendiente
    $stmt = $pdo->query("
        SELECT kb.id, kb.ollama_tasker_id 
        FROM knowledge_base kb
        WHERE kb.embedding_status = 'pending' 
        AND kb.ollama_tasker_id IS NOT NULL
        LIMIT 1
    ");
    
    $chunk = $stmt->fetch();
    
    if (!$chunk) {
        echo "No hay chunks pendientes para procesar.\n";
        return false;
    }
    
    echo "Procesando chunk ID: {$chunk['id']}\n";
    
    // Obtener la tarea Ollama
    $stmt = $pdo->prepare("
        SELECT response, error 
        FROM ollama_taskers
        WHERE id = :taskerId
    ");
    $stmt->execute(['taskerId' => $chunk['ollama_tasker_id']]);
    $task = $stmt->fetch();
    
    if (!$task) {
        // Actualizar estado a error
        $stmt = $pdo->prepare("
            UPDATE knowledge_base 
            SET embedding_status = 'error' 
            WHERE id = :chunkId
        ");
        $stmt->execute(['chunkId' => $chunk['id']]);
        
        echo "No se encontró la tarea Ollama ID {$chunk['ollama_tasker_id']}\n";
        return true;
    }
    
    if ($task['response']) {
        try {
            $data = json_decode($task['response'], true);
            $embedding = null;
            
            if (isset($data['embedding'])) {
                $embedding = $data['embedding'];
            } elseif (is_array($data)) {
                $embedding = $data;
            }
            
            if ($embedding && is_array($embedding)) {
                // Verificar dimensiones
                if (count($embedding) != 768) {
                    echo "Error: El embedding tiene " . count($embedding) . " dimensiones, pero se esperaban 768\n";
                    
                    $stmt = $pdo->prepare("
                        UPDATE knowledge_base 
                        SET embedding_status = 'error' 
                        WHERE id = :chunkId
                    ");
                    $stmt->execute(['chunkId' => $chunk['id']]);
                    
                    return true;
                }
                
                // Actualizar el embedding
                $stmt = $pdo->prepare("
                    UPDATE knowledge_base 
                    SET embedding = :embedding::vector, 
                        embedding_status = 'done', 
                        updated_at = NOW() 
                    WHERE id = :chunkId
                ");
                
                $stmt->execute([
                    'embedding' => json_encode($embedding),
                    'chunkId' => $chunk['id']
                ]);
                
                echo "Embedding guardado para chunk ID {$chunk['id']}\n";
                return true;
            } else {
                throw new Exception('Formato de embedding inválido');
            }
        } catch (Exception $e) {
            $stmt = $pdo->prepare("
                UPDATE knowledge_base 
                SET embedding_status = 'error' 
                WHERE id = :chunkId
            ");
            $stmt->execute(['chunkId' => $chunk['id']]);
            
            echo "Error procesando embedding: " . $e->getMessage() . "\n";
            return true;
        }
    } elseif ($task['error']) {
        $stmt = $pdo->prepare("
            UPDATE knowledge_base 
            SET embedding_status = 'error' 
            WHERE id = :chunkId
        ");
        $stmt->execute(['chunkId' => $chunk['id']]);
        
        echo "Error en tarea Ollama: {$task['error']}\n";
        return true;
    } else {
        echo "Tarea Ollama aún pendiente para chunk ID {$chunk['id']}\n";
        return true;
    }
}

// Función para contar chunks pendientes
function countPendingChunks($pdo) {
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM knowledge_base 
        WHERE embedding_status = 'pending' 
        AND ollama_tasker_id IS NOT NULL
    ");
    $result = $stmt->fetch();
    return $result['count'];
}

// Procesar todos los embeddings pendientes
$count = 0;
$pendingCount = countPendingChunks($pdo);

echo "Iniciando procesamiento de embeddings pendientes. Total: $pendingCount\n";
echo "-----------------------------------------------------\n";

while (true) {
    $result = processSingleEmbedding($pdo);
    
    if ($result) {
        $count++;
        $pendingCount = countPendingChunks($pdo);
        
        echo "Procesado $count embeddings. Quedan $pendingCount pendientes.\n";
        echo "-----------------------------------------------------\n";
        
        if ($pendingCount <= 0) {
            echo "No hay más embeddings pendientes.\n";
            break;
        }
        
        // Pequeña pausa para evitar sobrecarga
        sleep(1);
    } else {
        echo "No hay más embeddings para procesar.\n";
        break;
    }
}

echo "Procesamiento completado. Se procesaron $count embeddings.\n";
