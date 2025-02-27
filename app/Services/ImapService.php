<?php

namespace App\Services;

use Webklex\PHPIMAP\Config;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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
    public function getMessages(int $limit = 10, string $folderName = 'INBOX')
    {
        if (!$this->client) {
            $this->connect();
        }
        if (!$this->client || !$this->client->isConnected()) {
            return collect();
        }

        try {
            return $this->client->getFolder($folderName)
                ->query()
                ->all()
                ->limit($limit)
                ->get();
        } catch (\Throwable $e) {
            Log::error("IMAP: Error obteniendo mensajes: " . $e->getMessage());
            return collect();
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

}
