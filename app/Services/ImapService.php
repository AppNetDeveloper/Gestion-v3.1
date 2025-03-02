<?php

namespace App\Services;

use Webklex\PHPIMAP\Config;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Contact;

class ImapService
{
    // Nota: el tipo de $client sigue siendo Webklex\PHPIMAP\Client
    protected ?\Webklex\PHPIMAP\Client $client = null;
    protected ?string $error = null;

    /**
     * Obtiene la configuración IMAP para el usuario autenticado.
     *
     * @return array
     * @throws \Exception Si falta algún campo requerido.
     */
    protected function getUserImapConfig(): array
    {
        $user = Auth::user();

        if (!$user) {
            throw new \Exception("Usuario no autenticado.");
        }

        // Validamos que existan los campos requeridos.
        $requiredFields = ['imap_host', 'imap_port', 'imap_username', 'imap_password'];
        foreach ($requiredFields as $field) {
            if (empty($user->$field)) {
                throw new \Exception("Falta configuración IMAP: $field.");
            }
        }

        // Asignamos 'ssl' por defecto si el valor de encriptación no es 'ssl' ni 'tls'.
        $encryption = in_array($user->imap_encryption, ['ssl', 'tls']) ? $user->imap_encryption : 'ssl';

        return [
            'host'           => $user->imap_host,
            'port'           => (int)$user->imap_port,
            'encryption'     => $encryption,
            'validate_cert'  => true,  // Puedes hacerlo dinámico si es necesario.
            'username'       => $user->imap_username,
            'password'       => $user->imap_password,
            'protocol'       => 'imap',  // Puedes extenderlo para que sea configurable.
            'delimiter'      => '/',
            'default_folder' => 'INBOX',
            'fetch'          => [
                'fetch_body'  => true,
                'fetch_flags' => true,
            ],
        ];
    }

    /**
     * Intenta conectar al servidor IMAP utilizando la configuración extraída del usuario.
     */
    public function connect(): void
    {
        try {
            $configArray = $this->getUserImapConfig();
        } catch (\Exception $e) {
            Log::error("IMAP: " . $e->getMessage());
            $this->error = $e->getMessage();
            return;
        }

        try {
            // Utilizamos la fachada para crear el cliente, inyectando la configuración del usuario
            $this->client = \Webklex\IMAP\Facades\Client::make($configArray);
            $this->client->connect();

            if (!$this->client->isConnected()) {
                Log::error("IMAP: Conexión fallida para el usuario ID " . Auth::id() . " en host " . $configArray['host']);
                $this->error = "Conexión IMAP fallida. Verifica las credenciales.";
                $this->client = null;
            } else {
                $this->error = null;
            }
        } catch (\Throwable $e) {
            $this->client = null;
            $this->error = "Error conectando al servidor IMAP: " . $e->getMessage();
            Log::error("IMAP: " . $this->error, [
                'user_id' => Auth::id(),
                'host'    => $configArray['host'] ?? 'desconocido',
            ]);
        }
    }


    /**
     * Devuelve el mensaje de error, si existe.
     *
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Obtiene una colección de mensajes (encabezados) de la carpeta especificada.
     *
     * @param int    $limit      Número máximo de mensajes a obtener.
     * @param string $folderName Nombre de la carpeta, por defecto 'INBOX'.
     * @return \Illuminate\Support\Collection
     */
    public function getMessages(int $limit = 20, string $folderName = 'INBOX')
    {
        if (!$this->client) {
            $this->connect();
        }

        if (!$this->client || !$this->client->isConnected()) {
            return collect();
        }

        try {
            // Obtener los mensajes del servidor
            $messages = $this->client->getFolder($folderName)
                ->query()
                ->all()
                ->limit($limit)
                ->get();

            // Guardar los mensajes en un archivo si los necesitas
           // $this->saveMessagesToFile($messages, $folderName);

            return $messages;
        } catch (\Throwable $e) {
            Log::error("IMAP: Error obteniendo mensajes: " . $e->getMessage());
            return collect();
        }
    }
    protected function getUserImapConfig2(): array
    {
        $user = Auth::user();

        if (!$user) {
            throw new \Exception("Usuario no autenticado.");
        }

        // Validamos que existan los campos requeridos.
        $requiredFields = ['imap_host', 'imap_port', 'imap_username', 'imap_password'];
        foreach ($requiredFields as $field) {
            if (empty($user->$field)) {
                throw new \Exception("Falta configuración IMAP: $field.");
            }
        }

        // Asignamos 'ssl' por defecto si el valor de encriptación no es 'ssl' ni 'tls'.
        $encryption = in_array($user->imap_encryption, ['ssl', 'tls']) ? $user->imap_encryption : 'ssl';

        return [
            'host'           => $user->imap_host,
            'port'           => (int)$user->imap_port,
            'encryption'     => $encryption,
            'validate_cert'  => true,  // Puedes hacerlo dinámico si es necesario.
            'username'       => $user->imap_username,
            'password'       => $user->imap_password,
            'protocol'       => 'imap',  // Puedes extenderlo para que sea configurable.
            'delimiter'      => '/',
            'default_folder' => 'INBOX',
            'fetch'          => [
                'fetch_body'  => false,
                'fetch_flags' => true,
            ],
        ];
    }

    /**
     * Intenta conectar al servidor IMAP utilizando la configuración extraída del usuario.
     */
    public function connect2(): void
    {
        try {
            $configArray = $this->getUserImapConfig2();
        } catch (\Exception $e) {
            Log::error("IMAP: " . $e->getMessage());
            $this->error = $e->getMessage();
            return;
        }

        try {
            // Utilizamos la fachada para crear el cliente, inyectando la configuración del usuario
            $this->client = \Webklex\IMAP\Facades\Client::make($configArray);
            $this->client->connect();

            if (!$this->client->isConnected()) {
                Log::error("IMAP: Conexión fallida para el usuario ID " . Auth::id() . " en host " . $configArray['host']);
                $this->error = "Conexión IMAP fallida. Verifica las credenciales.";
                $this->client = null;
            } else {
                $this->error = null;
            }
        } catch (\Throwable $e) {
            $this->client = null;
            $this->error = "Error conectando al servidor IMAP: " . $e->getMessage();
            Log::error("IMAP: " . $this->error, [
                'user_id' => Auth::id(),
                'host'    => $configArray['host'] ?? 'desconocido',
            ]);
        }
    }
    public function getOnlyMessages(string $folderName = 'INBOX')
    {
        if (!$this->client) {
            $this->connect2();
        }

        if (!$this->client || !$this->client->isConnected()) {
            return collect();
        }

        try {
            // Obtener todos los mensajes del servidor (sin límite)
            $messages = $this->client->getFolder($folderName)
                ->query()
                ->all()  // Obtener todos los mensajes
                ->get();

            // Puedes guardar los mensajes en un archivo si lo deseas
            // $this->saveMessagesToFile($messages, $folderName);

            return $messages;
        } catch (\Throwable $e) {
            Log::error("IMAP: Error obteniendo mensajes: " . $e->getMessage());
            return collect();
        }
    }

    /**
     * Guarda los mensajes en un archivo en el disco local.
     *
     * @param \Webklex\PHPIMAP\Support\MessageCollection $messages
     * @param string $folderName
     */
    protected function saveMessagesToFile($messages, $folderName)
    {
        try {
            // Ruta del archivo en el directorio de almacenamiento
            $filePath = storage_path("app/imap/imap_messages_" . Auth::id() . "_{$folderName}.json");

            // Verificar si la carpeta existe, si no, crearla
            $folderPath = storage_path('app/imap');
            if (!is_dir($folderPath)) {
                mkdir($folderPath, 0777, true);
            }

            // Guardar los mensajes en el archivo como JSON
            file_put_contents($filePath, json_encode($messages));

            Log::info("IMAP: Mensajes guardados en el archivo {$filePath}");
        } catch (\Throwable $e) {
            Log::error("IMAP: Error al guardar los mensajes en archivo: " . $e->getMessage());
        }
    }



    /**
     * Obtiene un mensaje específico por UID de la carpeta indicada.
     *
     * @param int    $uid        UID del mensaje.
     * @param string $folderName Nombre de la carpeta, por defecto 'INBOX'.
     * @return mixed|null
     */
    public function getMessageByUid(int $uid, string $folderName = 'INBOX')
    {
        if (!$this->client) {
            $this->connect();
        }
        if (!$this->client || !$this->client->isConnected()) {
            return null;
        }

        try {
            $messages = $this->client->getFolder($folderName)
                ->query()
                ->where('uid', $uid)
                ->get();

            return $messages->first();
        } catch (\Throwable $e) {
            Log::error("IMAP: Error obteniendo mensaje por UID: " . $e->getMessage());
            return null;
        }
    }


    /**
     * Indica si la conexión IMAP está activa.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->client?->isConnected() ?? false;
    }

    public function getFolders()
    {
        if (!$this->client) {
            $this->connect();
        }
        if (!$this->client || !$this->client->isConnected()) {
            return collect();
        }

        try {
            return $this->client->getFolders();
        } catch (\Throwable $e) {
            Log::error("IMAP: Error obteniendo carpetas: " . $e->getMessage());
            return collect();
        }
    }

    /**
     * Borra un mensaje por UID de la carpeta indicada.
     *
     * @param int    $uid        UID del mensaje a borrar.
     * @param string $folderName Nombre de la carpeta, por defecto 'INBOX'.
     * @return bool  True si se borró correctamente, false en caso contrario.
     */
    public function deleteMessageByUid(int $uid, string $folderName = 'INBOX'): bool
    {
        if (!$this->client) {
            $this->connect();
        }
        if (!$this->client || !$this->client->isConnected()) {
            return false;
        }

        try {
            $messages = $this->client->getFolder($folderName)
                ->query()
                ->where('uid', $uid)
                ->get();

            $mail = $messages->first();
            if ($mail) {
                // Borrar el mensaje del servidor IMAP.
                $mail->delete();

                // Limpia la caché para este mensaje y para la lista.
                Cache::forget('imap_message_' . Auth::id() . '_' . $folderName . '_' . $uid);
                Cache::forget('imap_messages_' . Auth::id() . '_' . $folderName);
                return true;
            }
            return false;
        } catch (\Throwable $e) {
            Log::error("IMAP: Error borrando mensaje por UID: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Sincroniza los contactos para el usuario actual (imap_type = 1).
     * Recorre todas las carpetas, obtiene los nuevos correos y extrae el remitente.
     * Si el remitente no existe en la tabla de contactos, crea un nuevo registro.
     *
     * @param string $folderName Opcional: si se desea limitar a una carpeta.
     * @return void
     */
    public function syncContactsForUser(string $folderName = null): void
    {
        // Nos aseguramos de conectar usando la configuración que no descarga el cuerpo
        if (!$this->client) {
            $this->connect2();
        }

        if (!$this->client || !$this->client->isConnected()) {
            Log::error("IMAP: No se pudo conectar para sincronizar contactos.");
            return;
        }

        // Si se especifica una carpeta, la usaremos; de lo contrario, recorremos todas las carpetas
        $folders = $folderName
            ? collect([(object)['name' => $folderName]])
            : $this->getFolders();

        foreach ($folders as $folder) {
            try {
                // Obtenemos todos los mensajes de la carpeta (solo encabezados gracias a 'fetch_body' => false)
                $messages = $this->client->getFolder($folder->name)
                    ->query()
                    ->unseen()
                    ->get();

                foreach ($messages as $email) {
                    $from = $email->getFrom();
                    if (!empty($from) && isset($from[0])) {
                        $senderEmail = $from[0]->mail;
                        // Verificamos si el contacto ya existe para el usuario actual
                        $userId = Auth::id();
                        $contact = Contact::where('user_id', $userId)
                            ->where('email', $senderEmail)
                            ->first();

                        if (!$contact) {
                            // Crear nuevo contacto
                            Contact::create([
                                'user_id' => $userId,
                                'email'   => $senderEmail,
                                // Aquí puedes agregar otros campos si es necesario
                            ]);
                            Log::info("IMAP: Nuevo contacto agregado para el usuario {$userId}: {$senderEmail}");
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::error("IMAP: Error sincronizando contactos en la carpeta {$folder->name}: " . $e->getMessage());
                continue; // Continuamos con la siguiente carpeta
            }
        }
    }

}
