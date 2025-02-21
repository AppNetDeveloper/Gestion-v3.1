<?php

namespace App\Services;

use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Config;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ImapService
{
    protected ?Client $client = null;
    protected ?string $error = null;

    /**
     * Intenta conectar al servidor IMAP usando las credenciales del usuario autenticado.
     * Si ocurre algún error o falta algún campo, se asigna null a $client y se guarda un mensaje de error.
     */
    public function connect(): void
    {
        $user = Auth::user();
        if (!$user) {
            Log::warning("IMAP: Usuario no autenticado.");
            $this->error = "Usuario no autenticado.";
            return;
        }

        // Verifica que existan los campos necesarios.
        $fields = ['imap_host', 'imap_port', 'imap_username', 'imap_password'];
        foreach ($fields as $field) {
            if (empty($user->$field)) {
                Log::error("IMAP: Campo '$field' faltante para el usuario ID {$user->id}");
                $this->error = "Falta configuración IMAP: $field.";
                $this->client = null;
                return;
            }
        }

        // Construye la configuración IMAP con los datos del usuario.
        $configArray = [
            'host'          => $user->imap_host,
            'port'          => (int)$user->imap_port,
            'encryption'    => in_array($user->imap_encryption, ['ssl', 'tls']) ? $user->imap_encryption : null,
            'validate_cert' => true,
            'username'      => $user->imap_username,
            'password'      => $user->imap_password,
            'protocol'      => 'imap',
            'delimiter'     => '/',
            'default_folder'=> 'INBOX',
            'fetch'         => [
                'fetch_body'  => true,
                'fetch_flags' => true,
            ],
        ];

        try {
            $config = new Config($configArray);
            // Forzamos el fallback: un arreglo vacío (no null)
            $defaultConfig = [];
            $this->client = new Client($config, $defaultConfig);
            $this->client->connect();

            if (!$this->client->isConnected()) {
                Log::error("IMAP: Conexión fallida para el usuario ID {$user->id} en host {$user->imap_host}");
                $this->error = "Conexión IMAP fallida. Verifica las credenciales.";
                $this->client = null;
            } else {
                $this->error = null;
            }
        } catch (\Throwable $e) {
            $this->client = null;
            $this->error = "Error conectando al servidor IMAP: " . $e->getMessage();
            Log::error("IMAP: " . $this->error, [
                'user_id' => $user->id,
                'host'    => $user->imap_host,
            ]);
        }
    }

    /**
     * Devuelve el mensaje de error (si existe) al intentar conectar.
     *
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Obtiene una colección de mensajes (encabezados) de la carpeta indicada.
     *
     * @param int    $limit      Número máximo de mensajes a obtener.
     * @param string $folderName Nombre de la carpeta, por defecto 'INBOX'.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getMessages(int $limit = 50, string $folderName = 'INBOX')
    {
        if (!$this->client) {
            $this->connect();
        }
        if (!$this->client || !$this->client->isConnected()) {
            return collect();
        }

        try {
            return $this->client->getFolder($folderName)
                ->messages()
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
     *
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
            return $this->client->getFolder($folderName)
                ->messages()
                ->find($uid);
        } catch (\Throwable $e) {
            Log::error("IMAP: Error obteniendo mensaje por UID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verifica si la conexión IMAP está activa.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->client?->isConnected() ?? false;
    }
}
