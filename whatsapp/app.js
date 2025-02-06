// app.js
require('dotenv').config();

const express = require('express');
const fs = require('fs');
const axios = require('axios');
const NodeCache = require('node-cache');
const { Boom } = require('@hapi/boom');
const makeWASocket = require('@whiskeysockets/baileys').default;
const {
  delay,
  DisconnectReason,
  fetchLatestBaileysVersion,
  makeCacheableSignalKeyStore,
  makeInMemoryStore,
  useMultiFileAuthState,
} = require('@whiskeysockets/baileys');
const Pino = require('pino');
const QRCode = require('qrcode'); // Para generar el QR como imagen

// Configuración a través de variables de entorno
const PORT = process.env.PORT || 3005;
const AUTH_DIR = process.env.AUTH_DIR || './baileys_auth_info';
const STORE_FILE = process.env.STORE_FILE || './baileys_store_multi.json';
// Asegúrate de que la URL incluya el puerto si corresponde, por ejemplo: http://localhost:3005/api/whatsapp-credentials
const API_CREDENTIALS_URL = process.env.API_CREDENTIALS_URL || 'http://localhost/api/whatsapp-credentials';

// Inicialización de Express y Pino para logs
const app = express();
app.use(express.json());

const logger = Pino(
  { timestamp: () => `,"time":"${new Date().toJSON()}"` },
  Pino.destination('./wa-logs.txt')
);
logger.level = 'trace';

// Configuración de almacenamiento
const msgRetryCounterCache = new NodeCache();
const useStore = true;
const store = useStore ? makeInMemoryStore({ logger }) : undefined;

if (store) {
  store.readFromFile(STORE_FILE);
  setInterval(() => store.writeToFile(STORE_FILE), 10_000);
}

// Variables globales para el socket, el código QR y almacenamiento de mensajes
let sock = null;
let qrCode = null;
const chatMessagesStore = {};

/**
 * Función para iniciar y configurar el socket de WhatsApp.
 */
async function startSock() {
  // Estado de autenticación multiarchivo
  const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR);
  const { version, isLatest } = await fetchLatestBaileysVersion();
  console.log(`Usando la versión de WhatsApp v${version.join('.')}, ¿Es la última?: ${isLatest}`);

  sock = makeWASocket({
    version,
    logger,
    printQRInTerminal: true, // Siempre imprime el QR en la terminal
    autoRead: false, // Evita que se envíen recibos de lectura automáticamente
    auth: {
      creds: state.creds,
      keys: makeCacheableSignalKeyStore(state.keys, logger),
    },
    msgRetryCounterCache,
    getMessage: async (key) => {
      if (store) {
        const msg = await store.loadMessage(key.remoteJid, key.id);
        return msg?.message || undefined;
      }
      return {};
    },
  });

  // Vincula el store a los eventos del socket (si está habilitado)
  store?.bind(sock.ev);

  // Procesa los eventos de conexión, mensajes y actualización de credenciales
  sock.ev.process(async (events) => {
    // Manejo de actualización de conexión
    if (events['connection.update']) {
      const update = events['connection.update'];
      const { connection, lastDisconnect, qr } = update;

      // Si se genera un código QR, se almacena para ser consumido por los endpoints
      if (qr) {
        qrCode = qr;
        console.log('Nuevo código QR generado:', qr);
      }

      // Si la conexión se cierra, intenta reconectar (a menos que sea un logout)
      if (connection === 'close') {
        if ((lastDisconnect?.error instanceof Boom) && lastDisconnect?.error?.output?.statusCode !== DisconnectReason.loggedOut) {
          console.log('Intentando reconectar...');
          await startSock();
        } else {
          console.log('Conexión cerrada. Has sido desconectado.');
        }
      }

      // Cuando la conexión se abre, limpia el QR y envía las credenciales a la API
      if (connection === 'open') {
        console.log('Conexión exitosa.');
        qrCode = null;

        try {
          // Extrae los datos esenciales de la sesión
          const { registrationId, advSecretKey, me } = sock.authState.creds;
          const filteredCreds = { registrationId, advSecretKey, me };
          const filteredKeys = {}; // Puedes agregar más datos si es necesario

          // Envía la información a la URL configurada
          const response = await axios.post(API_CREDENTIALS_URL, {
            creds: JSON.stringify(filteredCreds),
            keys: JSON.stringify(filteredKeys)
          });
          console.log('Datos enviados a la API. Respuesta:', response.data);
        } catch (error) {
          console.error('Error al enviar los datos a la API:', error.response?.data || error.message);
        }
      }
    }

    // Actualización de credenciales (persistencia)
    if (events['creds.update']) {
      await saveCreds();
    }

    // Manejo de nuevos mensajes
    if (events['messages.upsert']) {
      const upsert = events['messages.upsert'];
      console.log('Evento "messages.upsert" capturado:', JSON.stringify(upsert, null, 2));

      if (upsert.type === 'notify') {
        for (const msg of upsert.messages) {
          const jid = msg.key.remoteJid;
          if (!chatMessagesStore[jid]) chatMessagesStore[jid] = [];
          chatMessagesStore[jid].push({
            id: msg.key.id,
            from: msg.key.fromMe ? 'me' : 'other',
            text: msg.message?.conversation || msg.message?.extendedTextMessage?.text || null,
            timestamp: msg.messageTimestamp,
          });
          console.log(`Mensaje almacenado para ${jid}:`, msg);
        }
      }
    }
  });
}

/* ============================
   ENDPOINTS DE LA API
   ============================ */

// Endpoint para recibir las credenciales (puedes adaptar este código según tus necesidades)
app.post('/api/whatsapp-credentials', (req, res) => {
  console.log('Datos recibidos en /api/whatsapp-credentials:', req.body);
  // Aquí podrías guardar los datos en una base de datos o realizar otra acción.
  res.json({ message: 'Credenciales recibidas correctamente' });
});

// Inicia la conexión a WhatsApp
app.post('/start-whatsapp', async (req, res) => {
  try {
    await startSock();
    res.json({ message: 'Conexión a WhatsApp iniciada' });
  } catch (error) {
    console.error('Error al iniciar WhatsApp:', error);
    res.status(500).json({ error: 'Error al iniciar la conexión a WhatsApp' });
  }
});

// Obtiene el código QR en formato JSON (texto)
app.get('/get-qr', (req, res) => {
  if (qrCode) {
    res.json({ success: true, qr: qrCode, message: 'Código QR disponible' });
  } else {
    res.status(404).json({
      success: false,
      message: 'No hay código QR disponible. Verifica que el dispositivo no esté ya conectado o que aún no se haya generado el código.'
    });
  }
});

// Obtiene el código QR como imagen JPEG
app.get('/get-qr-jpg', (req, res) => {
  if (!qrCode) {
    return res.status(404).json({ error: 'No hay código QR disponible' });
  }

  // Genera el QR en formato Data URL de imagen JPEG
  QRCode.toDataURL(qrCode, { type: 'image/jpeg' }, (err, url) => {
    if (err) {
      console.error('Error generando imagen QR:', err);
      return res.status(500).json({ error: 'Error generando la imagen del código QR' });
    }
    // Extrae la parte base64 del Data URL y la convierte en Buffer
    const base64Data = url.split(',')[1];
    const imgBuffer = Buffer.from(base64Data, 'base64');
    res.writeHead(200, {
      'Content-Type': 'image/jpeg',
      'Content-Length': imgBuffer.length,
    });
    res.end(imgBuffer);
  });
});

// Cierra la sesión y elimina la autenticación
app.post('/logout', async (req, res) => {
  try {
    if (sock) {
      if (fs.existsSync(AUTH_DIR)) {
        fs.rmSync(AUTH_DIR, { recursive: true, force: true });
      }
      await sock.logout();
      sock = null;
      qrCode = null;
      console.log('Sesión cerrada y autenticación eliminada');
      res.json({ message: 'Sesión cerrada y autenticación eliminada' });
    } else {
      res.status(400).json({ error: 'No hay una sesión activa' });
    }
  } catch (error) {
    console.error('Error al cerrar sesión:', error);
    res.status(500).json({ error: 'Error al cerrar sesión' });
  }
});

// Envía un mensaje a un chat específico
app.post('/send-message', async (req, res) => {
  const { jid, message } = req.body;
  if (!jid || !message) {
    return res.status(400).json({ error: 'Los parámetros "jid" y "message" son requeridos' });
  }
  try {
    if (!sock) return res.status(400).json({ error: 'Conexión a WhatsApp no establecida' });
    await sock.sendMessage(jid, { text: message });
    res.json({ message: 'Mensaje enviado correctamente' });
  } catch (error) {
    console.error('Error al enviar mensaje:', error);
    res.status(500).json({ error: 'Error al enviar el mensaje' });
  }
});

// Retorna la lista de chats disponibles
app.get('/get-chats', async (req, res) => {
  try {
    let chats = [];
    // Intenta obtener los chats desde el store de Baileys
    if (store && store.chats) {
      chats = store.chats.all().map(chat => ({
        jid: chat.id,
        name: chat.name || chat.id,
        lastMessage: chat.lastMessage?.message?.conversation || null,
        unreadCount: chat.unreadCount || 0,
      }));
    } else {
      // Fallback: usa la estructura local de mensajes
      chats = Object.keys(chatMessagesStore).map(jid => ({
        jid,
        name: jid,
        lastMessage: chatMessagesStore[jid].length
          ? chatMessagesStore[jid][chatMessagesStore[jid].length - 1].text
          : null,
        unreadCount: 0,
      }));
    }
    res.json({ chats });
  } catch (error) {
    console.error('Error al obtener conversaciones:', error);
    res.status(500).json({ error: 'Error al obtener las conversaciones' });
  }
});

// Retorna los mensajes de un chat específico
app.get('/get-messages', async (req, res) => {
  const { jid } = req.query;
  if (!jid) return res.status(400).json({ error: 'El parámetro "jid" es requerido' });
  try {
    const messages = chatMessagesStore[jid] || [];
    res.json({ messages });
  } catch (error) {
    console.error('Error al obtener mensajes:', error);
    res.status(500).json({ error: 'Error al obtener los mensajes de la conversación' });
  }
});

/* ============================
   INICIO DEL SERVIDOR
   ============================ */
app.listen(PORT, () => {
  console.log(`Servidor de API de WhatsApp escuchando en http://localhost:${PORT}`);
});

// Inicia la conexión a WhatsApp de forma automática
startSock().catch(error => console.error('Error al iniciar la conexión a WhatsApp:', error));
