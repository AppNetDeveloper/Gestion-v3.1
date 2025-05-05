global.crypto = require('crypto');
require('dotenv').config();

const express = require('express');
const fs = require('fs');
const path = require('path'); // Módulo para manejar rutas de archivos
const NodeCache = require('node-cache');
const { Boom } = require('@hapi/boom');
const { default: makeWASocket } = require('@whiskeysockets/baileys');
const {
    fetchLatestBaileysVersion,
    makeCacheableSignalKeyStore,
    useMultiFileAuthState,
    DisconnectReason,
    makeInMemoryStore,
    // --- ASEGÚRATE DE QUE ESTA LÍNEA ESTÉ PRESENTE ---
    downloadContentFromMessage
  } = require('@whiskeysockets/baileys');
const mime = require('mime-types'); // Asegúrate de tener esta línea y haber hecho npm install mime-types
const Pino = require('pino');
const QRCode = require('qrcode');
const axios = require('axios'); // Para realizar la llamada HTTP externa
const https = require('https'); // Para configurar el agente HTTPS

const multer = require('multer');
const upload = multer({ dest: 'uploads/' });

const retryCounters = {};

const cron = require('node-cron');

// Objeto para almacenar las reglas de autoresponder por sesión:
const autoresponderRules = {};

// Swagger
const swaggerUi = require('swagger-ui-express');
const swaggerJsdoc = require('swagger-jsdoc');

// Agente HTTPS que ignora errores de certificado (por ejemplo, certificados autofirmados en desarrollo)
const httpsAgent = new https.Agent({ rejectUnauthorized: false });

const PORT = process.env.PORT || 3005;
const STORE_FILE_PATH = './whatsapp_sessions';

const app = express();
app.use(express.json());

// Creamos un logger con Pino
const logger = Pino(
  { timestamp: () => `,"time":"${new Date().toJSON()}"` },
  Pino.destination('./wa-logs.txt')
);
logger.level = 'trace';

// Almacena las sesiones activas
const sessions = {};

// -------------------- Swagger Config --------------------------------

/**
 * Definimos la configuración principal de Swagger (OpenAPI):
 * - openapi: versión del spec
 * - info: info básica de la API
 * - servers: lista de servidores/base URLs
 */
const swaggerDefinition = {
  openapi: '3.0.0',
  info: {
    title: 'WhatsApp API - Documentación',
    version: '1.0.0',
    description: 'API para gestionar sesiones de WhatsApp, enviar mensajes, etc.'
  },
  servers: [
    {
      url: `http://localhost:${PORT}`,
      description: 'Servidor local'
    }
  ]
};

/**
 * Opciones de swagger-jsdoc:
 * - swaggerDefinition: la configuración de arriba
 * - apis: arreglo de archivos donde buscar anotaciones JSDoc
 *   Usamos __filename para que lea ESTE archivo.
 */
const swaggerOptions = {
  swaggerDefinition,
  apis: [__filename], // Buscamos las anotaciones en este archivo
};

const swaggerSpec = swaggerJsdoc(swaggerOptions);

// Montamos la documentación en /api-docs
app.use('/api-docs', swaggerUi.serve, swaggerUi.setup(swaggerSpec));

// ----------------------------------------------------------------------

/**
 * Guarda los chats en un archivo JSON persistente (chats.json) en la carpeta de la sesión.
 */
function saveChats(sessionId, chats) {
  try {
    const filePath = path.join(STORE_FILE_PATH, sessionId, 'chats.json');
    fs.writeFileSync(filePath, JSON.stringify(chats, null, 2));
    console.log(`💾 Chats guardados para ${sessionId} en ${filePath}`);
  } catch (error) {
    console.error(`❌ Error al guardar chats para ${sessionId}:`, error);
  }
}

/**
 * Carga los chats desde el archivo persistente.
 */
function loadChats(sessionId) {
  try {
    const filePath = path.join(STORE_FILE_PATH, sessionId, 'chats.json');
    if (fs.existsSync(filePath)) {
      const data = fs.readFileSync(filePath);
      const chats = JSON.parse(data);
      console.log(`🔍 Chats cargados para ${sessionId}:`, chats);
      return chats;
    }
  } catch (error) {
    console.error(`❌ Error al cargar chats para ${sessionId}:`, error);
  }
  return [];
}

/**
 * Guarda el historial de mensajes en un archivo JSON persistente (messages.json) en la carpeta de la sesión.
 */
function saveMessageHistory(sessionId, messages) {
  try {
    const filePath = path.join(STORE_FILE_PATH, sessionId, 'messages.json');
    fs.writeFileSync(filePath, JSON.stringify(messages, null, 2));
    console.log(`💾 Historial de mensajes guardado para ${sessionId} en ${filePath}`);
  } catch (error) {
    console.error(`❌ Error al guardar mensajes para ${sessionId}:`, error);
  }
}

/**
 * Carga el historial de mensajes desde el archivo persistente.
 */
function loadMessageHistory(sessionId) {
  try {
    const filePath = path.join(STORE_FILE_PATH, sessionId, 'messages.json');
    if (fs.existsSync(filePath)) {
      const data = fs.readFileSync(filePath);
      const messages = JSON.parse(data);
      console.log(`🔍 Mensajes cargados para ${sessionId}:`, messages);
      return messages;
    }
  } catch (error) {
    console.error(`❌ Error al cargar mensajes para ${sessionId}:`, error);
  }
  return [];
}

/**
 * Inicia una nueva sesión de WhatsApp para el usuario indicado.
 * Cada sesión utiliza su propio directorio de autenticación y store.
 */
async function startSession(sessionId) {
  const authDir = path.join(STORE_FILE_PATH, sessionId);
  if (!fs.existsSync(authDir)) fs.mkdirSync(authDir, { recursive: true });

  const { state, saveCreds } = await useMultiFileAuthState(authDir);
  const { version } = await fetchLatestBaileysVersion();
  console.log(`📲 Iniciando sesión para ${sessionId} con WhatsApp v${version.join('.')}`);

  const store = makeInMemoryStore({ logger });
  store.readFromFile(path.join(authDir, 'store.json'));

  const storeInterval = setInterval(() => {
    try {
      store.writeToFile(path.join(authDir, 'store.json'));
    } catch (error) {
      console.error(`❌ Error escribiendo el store para ${sessionId}:`, error);
    }
  }, 10_000);

  const sock = makeWASocket({
    version,
    logger,
    printQRInTerminal: false,
    auth: {
      creds: state.creds,
      keys: makeCacheableSignalKeyStore(state.keys, logger),
    },
    msgRetryCounterCache: new NodeCache(),
  });

  store.bind(sock.ev);

  // Forzamos la sincronización de contactos antes de guardar la sesión
 // try {
    //await sock.fetchContacts();
   // console.log(`✅ Contactos sincronizados para ${sessionId}`);
 // } catch (error) {
   // console.error(`❌ Error sincronizando contactos para ${sessionId}:`, error);
 // }

  sessions[sessionId] = {
    sock,
    state,
    store,
    qrCode: null,
    messageHistory: loadMessageHistory(sessionId),
    chats: loadChats(sessionId),
    storeInterval,
  };

  // Evento para capturar mensajes (recibidos y enviados)
    sock.ev.on('messages.upsert', async (m) => {
        console.log('Nuevo mensaje/upsert recibido:', m);

        if (m.type === 'notify' || m.type === 'append') {
            // Añadir los mensajes al historial de la sesión
            sessions[sessionId].messageHistory.push(...m.messages);
            saveMessageHistory(sessionId, sessions[sessionId].messageHistory);

            // Actualizamos la información de los chats con el nuevo mensaje
            m.messages.forEach((msg) => {
                const remoteJid = msg.key.remoteJid;
                const messageTimestamp = msg.messageTimestamp || Date.now();  // Aseguramos que el timestamp es válido

                // Inicializamos chatsMap si no existe
                if (!sessions[sessionId].chatsMap) {
                    sessions[sessionId].chatsMap = {};
                }

                // Determinar el nombre del contacto usando ambos métodos:
                let contactName = remoteJid; // Valor por defecto

                // Versión 2: Intentamos obtener el nombre del contacto desde el store de contactos
                if (
                    sessions[sessionId].store &&
                    sessions[sessionId].store.contacts &&
                    sessions[sessionId].store.contacts[remoteJid]
                ) {
                    const contact = sessions[sessionId].store.contacts[remoteJid];
                    if (contact && contact.notify && contact.notify.trim() !== '') {
                        contactName = contact.notify;
                    }
                }

                // Versión 1: Si el mensaje tiene pushName, lo usamos (siempre que tenga valor)
                if (msg.pushName && msg.pushName.trim() !== '') {
                    contactName = msg.pushName;
                }

                // Si no existe el chat en el mapa, lo inicializamos
                if (!sessions[sessionId].chatsMap[remoteJid]) {
                    sessions[sessionId].chatsMap[remoteJid] = {
                        id: remoteJid,
                        name: contactName,  // Usamos el nombre obtenido
                        lastMessage: msg.message,
                        messageTimestamp,  // Establecemos el timestamp
                    };
                } else {
                    // Si el mensaje es más reciente, actualizamos el lastMessage, timestamp y nombre
                    if (messageTimestamp > sessions[sessionId].chatsMap[remoteJid].messageTimestamp) {
                        sessions[sessionId].chatsMap[remoteJid].lastMessage = msg.message;
                        sessions[sessionId].chatsMap[remoteJid].messageTimestamp = messageTimestamp;
                        sessions[sessionId].chatsMap[remoteJid].name = contactName;
                    }
                }

                // Si el mensaje fue enviado por nosotros, actualizamos también el lastMessage
                if (msg.key.fromMe) {
                    sessions[sessionId].chatsMap[remoteJid].lastMessage = msg.message;
                }
            });

            // Guardamos los chats con la nueva información
            saveChats(sessionId, Object.values(sessions[sessionId].chatsMap));

            // Procesamos los medios de los mensajes:
            // --- Procesamiento de Medios (Usando Baileys) ---
            for (const msg of m.messages) {
                let mediaObject = null;
                let mediaType = '';

                // Determinar el tipo de medio y obtener el objeto correcto
                if (msg.message?.imageMessage) {
                    mediaObject = msg.message.imageMessage;
                    mediaType = 'image';
                    // Log para depurar el objeto imageMessage
                    console.log('🔎 Detalle del imageMessage recibido:', JSON.stringify(mediaObject, null, 2));
                } else if (msg.message?.videoMessage) {
                    mediaObject = msg.message.videoMessage;
                    mediaType = 'video';
                    // Log para depurar el objeto videoMessage (opcional)
                    // console.log('🔎 Detalle del videoMessage recibido:', JSON.stringify(mediaObject, null, 2));
                } else if (msg.message?.audioMessage) {
                    mediaObject = msg.message.audioMessage;
                    mediaType = 'audio';
                } else if (msg.message?.documentMessage) {
                    mediaObject = msg.message.documentMessage;
                    mediaType = 'document';
                } else if (msg.message?.stickerMessage) {
                    mediaObject = msg.message.stickerMessage;
                    mediaType = 'sticker';
                }

                // Si encontramos un objeto multimedia, lo procesamos
                if (mediaObject && mediaType) {
                    console.log(`✨ [${sessionId}] Procesando ${mediaType} de ${msg.key.remoteJid} (MsgID: ${msg.key.id})...`);

                    // --- LLAMADA CORRECTA A LA NUEVA FUNCIÓN ---
                    // Pasamos: sessionId, el objeto protobuf (mediaObject), el tipo (mediaType), y el ID del mensaje
                    const publicBaseUrl = process.env.PUBLIC_BASE_URL || `http://localhost:${PORT}`; // <--- ESTA LÍNEA DEBE EXISTIR
                    const publicUrl = await processMediaMessage(sessionId, mediaObject, mediaType, msg.key.id);
                    // --- FIN LLAMADA CORRECTA ---

                    if (publicUrl) {
                        // Añadir la URL pública al objeto del mensaje (opcional, para referencia futura)
                        // Usamos una clave diferente para no sobreescribir la original 'url' si existe
                        if (msg.message.imageMessage) msg.message.imageMessage.url_publica = publicUrl;
                        if (msg.message.videoMessage) msg.message.videoMessage.url_publica = publicUrl;
                        if (msg.message.audioMessage) msg.message.audioMessage.url_publica = publicUrl;
                        if (msg.message.documentMessage) msg.message.documentMessage.url_publica = publicUrl;
                        if (msg.message.stickerMessage) msg.message.stickerMessage.url_publica = publicUrl;

                        console.log(`✅ [${sessionId}] URL pública para ${mediaType} (MsgID: ${msg.key.id}): ${publicUrl}`);
                        // Guardar historial de nuevo si se modificó el mensaje
                        saveMessageHistory(sessionId, sessions[sessionId].messageHistory);
                    } else {
                        // El error ya se loguea dentro de processMediaMessage si falla la descarga o falta la clave
                        console.error(`❌ [${sessionId}] Falló el procesamiento de ${mediaType} para ${msg.key.remoteJid} (MsgID: ${msg.key.id})`);
                    }
                }
            } // Fin del bucle for (const msg of m.messages)

            // Llamada a la API externa si está habilitada
            if (process.env.EXTERNAL_API_ENABLED === 'true' && process.env.EXTERNAL_API_URL) {
                for (const msg of m.messages) {
                    try {
                        let mensajeTexto = "";
                        let imageData = null;

                        if (msg.message && msg.message.conversation) {
                            mensajeTexto = msg.message.conversation;
                        } else if (msg.message && msg.message.extendedTextMessage && msg.message.extendedTextMessage.text) {
                            mensajeTexto = msg.message.extendedTextMessage.text;
                        } else if (msg.message && msg.message.imageMessage) {
                            // Si es imagen, usamos el caption o "Only Image" si no hay caption.
                            if (
                                msg.message.imageMessage.caption &&
                                typeof msg.message.imageMessage.caption === "string" &&
                                msg.message.imageMessage.caption.trim() !== ""
                            ) {
                                mensajeTexto = msg.message.imageMessage.caption;
                            } else {
                                mensajeTexto = "Only Image";
                            }
                            if (msg.message.imageMessage.jpegThumbnail) {
                                imageData = msg.message.imageMessage.jpegThumbnail;
                                if (typeof imageData !== "string") {
                                    imageData = imageData.toString("base64");
                                }
                                imageData = "data:image/png;base64," + imageData;
                            }
                        } else {
                            mensajeTexto = JSON.stringify(msg.message);
                        }

                        const phone = msg.key.remoteJid.split('@')[0];
                        const status = msg.key.fromMe ? 'send' : 'received';

                        // Construimos el objeto a enviar con la estructura requerida.
                        const payload = {
                            token: process.env.EXTERNAL_API_TOKEN,
                            user_id: sessionId,
                            phone: phone,
                            message: mensajeTexto,
                            status: status,
                            image: imageData || null,
                        };

                        await axios.post(process.env.EXTERNAL_API_URL, payload, { httpsAgent });
                        console.log(`✅ Mensaje (${status}) enviado a API externa para ${msg.key.remoteJid}`);
                    } catch (err) {
                        console.error(`❌ Error al enviar mensaje a API externa:`, err);
                    }
                }
            }
        }
        //si falla lo de capturar mesajes es por culpa de este
        //logica de autoresponder
        m.messages.forEach(async (msg) => {
            const text = (msg.message?.conversation || msg.message?.extendedTextMessage?.text || "").toLowerCase();
            // Si hay reglas configuradas para la sesión, se revisan:
            if (autoresponderRules[sessionId]) {
            autoresponderRules[sessionId].forEach(async rule => {
                if (text.includes(rule.keyword)) {
                // Envía respuesta automática
                await sessions[sessionId].sock.sendMessage(msg.key.remoteJid, { text: rule.response });
                console.log(`Respuesta automática enviada para palabra clave: ${rule.keyword}`);
                }
            });
            }
        });
    });




  // Procesa otros eventos del socket
  sock.ev.process(async (events) => {
    if (events['connection.update']) {
      const update = events['connection.update'];
      const { connection, lastDisconnect, qr } = update;
      const authDir = path.join(STORE_FILE_PATH, sessionId); // Definir authDir aquí

      if (qr) {
        console.log(`🆕 Código QR generado para ${sessionId}`);
        sessions[sessionId].qrCode = qr;
      }

      if (connection === 'open') {
        console.log(`✅ Conexión establecida para ${sessionId}`);
        sessions[sessionId].qrCode = null;
      } else if (connection === 'close') {
        console.log(`🚪 Conexión cerrada para ${sessionId}. Error:`, lastDisconnect?.error);

        // Si el error no indica logout, esperamos 5 segundos y reconectamos
        if (
          lastDisconnect?.error &&
          lastDisconnect?.error?.output &&
          lastDisconnect?.error?.output?.statusCode !== DisconnectReason.loggedOut
        ) {
          console.log(`🔄 Esperando 5 segundos para reconectar sesión ${sessionId}...`);
          setTimeout(async () => {
            try {
              await startSession(sessionId);
            } catch (error) {
              console.error(`❌ Error al reconectar sesión ${sessionId}:`, error);
            }
          }, 5000);
        } else {
          console.log(`🚪 Sesión ${sessionId} cerrada (logged out)`);
          clearInterval(sessions[sessionId].storeInterval);
          if (fs.existsSync(authDir)) {
            fs.rmSync(authDir, { recursive: true, force: true });
            console.log(`🗑️ Archivos eliminados para ${sessionId}`);
          }
          delete sessions[sessionId];
        }
      }
    }

    if (events['creds.update']) {
      await saveCreds();
    }

    if (events['chats.set']) {
      console.log(`📥 Chats sincronizados para ${sessionId}:`, events['chats.set']);
      const { chats } = events['chats.set'];
      if (chats && Array.isArray(chats)) {
        chats.forEach(chat => {
          sessions[sessionId].store.chats.set(chat.id, chat);
        });
        sessions[sessionId].chats = chats;
        saveChats(sessionId, chats);
      }
    }
  });

  return sock;

}
/**
 * @openapi
 * /create-group/{sessionId}:
 *   post:
 *     summary: Crea un nuevo grupo en WhatsApp
 *     parameters:
 *       - in: path
 *         name: sessionId
 *         required: true
 *         description: ID de la sesión de WhatsApp
 *         schema:
 *           type: string
 *     requestBody:
 *       description: Datos del grupo a crear
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             properties:
 *               subject:
 *                 type: string
 *               participants:
 *                 type: array
 *                 items:
 *                   type: string
 *             example:
 *               subject: "Nuevo Grupo"
 *               participants: ["123456789@s.whatsapp.net", "987654321@s.whatsapp.net"]
 *     responses:
 *       200:
 *         description: Grupo creado correctamente
 *       404:
 *         description: Sesión no encontrada
 *       500:
 *         description: Error al crear el grupo
 */
app.post('/create-group/:sessionId', async (req, res) => {
    const { sessionId } = req.params;
    const session = sessions[sessionId];
    if (!session) return res.status(404).json({ error: 'Sesión no encontrada' });

    const { subject, participants } = req.body;
    if (!subject || !Array.isArray(participants) || participants.length === 0) {
      return res.status(400).json({ error: 'Se requieren un subject y al menos un participante' });
    }
    try {
      const result = await session.sock.groupCreate(subject, participants);
      res.json({ message: 'Grupo creado correctamente', group: result });
    } catch (error) {
      console.error(`❌ Error al crear el grupo para ${sessionId}:`, error);
      res.status(500).json({ error: 'Error al crear el grupo' });
    }
  });
/**
 * @openapi
 * /forward-message/{sessionId}:
 *   post:
 *     summary: Reenvía un mensaje a otro destinatario
 *     parameters:
 *       - in: path
 *         name: sessionId
 *         required: true
 *         description: ID de la sesión de WhatsApp
 *         schema:
 *           type: string
 *     requestBody:
 *       description: Datos del mensaje a reenviar
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             properties:
 *               messageId:
 *                 type: string
 *               forwardJid:
 *                 type: string
 *             example:
 *               messageId: "ABCD1234"
 *               forwardJid: "123456789@s.whatsapp.net"
 *     responses:
 *       200:
 *         description: Mensaje reenviado correctamente
 *       404:
 *         description: Sesión o mensaje no encontrado
 *       500:
 *         description: Error al reenviar el mensaje
 */
app.post('/forward-message/:sessionId', async (req, res) => {
    const { sessionId } = req.params;
    const session = sessions[sessionId];
    if (!session) return res.status(404).json({ error: 'Sesión no encontrada' });

    const { messageId, forwardJid } = req.body;
    if (!messageId || !forwardJid) {
      return res.status(400).json({ error: 'Se requieren messageId y forwardJid' });
    }
    try {
      // Buscar el mensaje en el historial de la sesión
      const message = session.messageHistory.find(msg => msg.key.id === messageId);
      if (!message) return res.status(404).json({ error: 'Mensaje no encontrado' });

      const result = await session.sock.sendMessage(forwardJid, { forward: message });
      res.json({ message: 'Mensaje reenviado correctamente', result });
    } catch (error) {
      console.error(`❌ Error al reenviar el mensaje para ${sessionId}:`, error);
      res.status(500).json({ error: 'Error al reenviar el mensaje' });
    }
  });

/**
 * @openapi
 * /edit-contact/{sessionId}:
 *   post:
 *     summary: Edita la información de un contacto en el store local
 *     parameters:
 *       - in: path
 *         name: sessionId
 *         required: true
 *         description: ID de la sesión de WhatsApp
 *         schema:
 *           type: string
 *     requestBody:
 *       description: Datos del contacto a editar
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             properties:
 *               jid:
 *                 type: string
 *               name:
 *                 type: string
 *             example:
 *               jid: "34690937275@s.whatsapp.net"
 *               name: "Nuevo Nombre"
 *     responses:
 *       200:
 *         description: Contacto actualizado correctamente
 *       404:
 *         description: Sesión o contacto no encontrado
 *       500:
 *         description: Error al actualizar el contacto
 */
app.post('/edit-contact/:sessionId', async (req, res) => {
    const { sessionId } = req.params;
    const { jid, name } = req.body;
    const session = sessions[sessionId];
    if (!session || !session.store || !session.store.contacts) {
      return res.status(404).json({ error: 'Sesión o contactos no encontrados' });
    }
    if (!jid || !name) {
      return res.status(400).json({ error: 'Se requieren "jid" y "name".' });
    }
    try {
      // Actualizamos la información en el store local
      if (session.store.contacts[jid]) {
        session.store.contacts[jid].notify = name;
      } else {
        return res.status(404).json({ error: 'Contacto no encontrado en el store.' });
      }
      // Persistimos los cambios en el archivo store.json
      const authDir = path.join(STORE_FILE_PATH, sessionId);
      session.store.writeToFile(path.join(authDir, 'store.json'));
      res.json({ message: 'Contacto actualizado correctamente' });
    } catch (error) {
      console.error(`❌ Error al actualizar el contacto para ${sessionId}:`, error);
      res.status(500).json({ error: 'Error al actualizar el contacto' });
    }
  });

  /**
   * @openapi
   * /create-contact/{sessionId}:
   *   post:
   *     summary: Agrega un contacto al store local (simulación, no se crea en WhatsApp)
   *     parameters:
   *       - in: path
   *         name: sessionId
   *         required: true
   *         description: ID de la sesión de WhatsApp
   *         schema:
   *           type: string
   *     requestBody:
   *       description: Datos del contacto a crear
   *       required: true
   *       content:
   *         application/json:
   *           schema:
   *             type: object
   *             properties:
   *               jid:
   *                 type: string
   *               name:
   *                 type: string
   *             example:
   *               jid: "34600000000@s.whatsapp.net"
   *               name: "Contacto Nuevo"
   *     responses:
   *       200:
   *         description: Contacto creado correctamente (en store local)
   *       404:
   *         description: Sesión no encontrada
   *       500:
   *         description: Error al crear el contacto
   */
  app.post('/create-contact/:sessionId', async (req, res) => {
    const { sessionId } = req.params;
    const { jid, name } = req.body;
    const session = sessions[sessionId];
    if (!session || !session.store) {
      return res.status(404).json({ error: 'Sesión no encontrada' });
    }
    if (!jid || !name) {
      return res.status(400).json({ error: 'Se requieren "jid" y "name".' });
    }
    try {
      // Agrega el contacto al store local
      session.store.contacts[jid] = {
        id: jid,
        notify: name,
        // Puedes agregar otros campos según sea necesario
      };
      // Persistimos en el archivo store.json
      const authDir = path.join(STORE_FILE_PATH, sessionId);
      session.store.writeToFile(path.join(authDir, 'store.json'));
      res.json({ message: 'Contacto creado correctamente (en store local)' });
    } catch (error) {
      console.error(`❌ Error al crear el contacto para ${sessionId}:`, error);
      res.status(500).json({ error: 'Error al crear el contacto' });
    }
  });

  /**
   * @openapi
   * /search-contacts/{sessionId}:
   *   get:
   *     summary: Busca contactos en el store local por nombre o jid
   *     parameters:
   *       - in: path
   *         name: sessionId
   *         required: true
   *         description: ID de la sesión de WhatsApp
   *         schema:
   *           type: string
   *       - in: query
   *         name: query
   *         required: true
   *         description: Texto a buscar en el nombre o jid
   *         schema:
   *           type: string
   *     responses:
   *       200:
   *         description: Lista de contactos que coinciden con la búsqueda
   *       404:
   *         description: Sesión o contactos no encontrados
   *       500:
   *         description: Error al buscar contactos
   */
  app.get('/search-contacts/:sessionId', async (req, res) => {
    const { sessionId } = req.params;
    const { query } = req.query;
    const session = sessions[sessionId];
    if (!session || !session.store || !session.store.contacts) {
      return res.status(404).json({ error: 'Sesión o contactos no encontrados' });
    }
    if (!query) {
      return res.status(400).json({ error: 'Se requiere el parámetro "query" en la búsqueda.' });
    }
    try {
      const lowerQuery = query.toLowerCase();
      const contacts = Object.keys(session.store.contacts).map(jid => {
        const contact = session.store.contacts[jid];
        return {
          jid,
          name: (contact.notify && contact.notify.trim() !== '') ? contact.notify : jid,
          ...contact,
        };
      });
      const filtered = contacts.filter(contact =>
        contact.name.toLowerCase().includes(lowerQuery) || contact.jid.toLowerCase().includes(lowerQuery)
      );
      res.json({ contacts: filtered });
    } catch (error) {
      console.error(`❌ Error al buscar contactos para ${sessionId}:`, error);
      res.status(500).json({ error: 'Error al buscar contactos' });
    }
  });

  /**
   * @openapi
   * /statistics/{sessionId}:
   *   get:
   *     summary: Obtiene estadísticas de la sesión (contactos, chats, mensajes)
   *     parameters:
   *       - in: path
   *         name: sessionId
   *         required: true
   *         description: ID de la sesión de WhatsApp
   *         schema:
   *           type: string
   *     responses:
   *       200:
   *         description: Estadísticas de la sesión
   *         content:
   *           application/json:
   *             schema:
   *               type: object
   *               properties:
   *                 totalContacts:
   *                   type: integer
   *                 totalChats:
   *                   type: integer
   *                 totalMessages:
   *                   type: integer
   *       404:
   *         description: Sesión no encontrada
   *       500:
   *         description: Error al obtener estadísticas
   */
  app.get('/statistics/:sessionId', async (req, res) => {
    const { sessionId } = req.params;
    const session = sessions[sessionId];
    if (!session) {
      return res.status(404).json({ error: 'Sesión no encontrada' });
    }
    try {
      const totalContacts = session.store && session.store.contacts ? Object.keys(session.store.contacts).length : 0;
      const totalChats = session.chats ? session.chats.length : 0;
      const totalMessages = session.messageHistory ? session.messageHistory.length : 0;

      res.json({
        totalContacts,
        totalChats,
        totalMessages
      });
    } catch (error) {
      console.error(`❌ Error al obtener estadísticas para ${sessionId}:`, error);
      res.status(500).json({ error: 'Error al obtener estadísticas' });
    }
  });

  // ============================================
// 1. Obtener Detalles de un Contacto
// ============================================
/**
 * @openapi
 * /contact-details/{sessionId}/{jid}:
 *   get:
 *     summary: Obtiene detalles de un contacto específico del store local
 *     parameters:
 *       - in: path
 *         name: sessionId
 *         required: true
 *         description: ID de la sesión de WhatsApp
 *         schema:
 *           type: string
 *       - in: path
 *         name: jid
 *         required: true
 *         description: JID del contacto
 *         schema:
 *           type: string
 *     responses:
 *       200:
 *         description: Detalles del contacto
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 contact:
 *                   type: object
 *       404:
 *         description: Sesión o contacto no encontrado
 *       500:
 *         description: Error al obtener detalles
 */
app.get('/contact-details/:sessionId/:jid', async (req, res) => {
    const { sessionId, jid } = req.params;
    const session = sessions[sessionId];
    if (!session || !session.store || !session.store.contacts) {
      return res.status(404).json({ error: 'Sesión o contactos no encontrados' });
    }
    const contact = session.store.contacts[jid];
    if (!contact) {
      return res.status(404).json({ error: 'Contacto no encontrado' });
    }
    res.json({ contact });
  });

  // ============================================
  // 2. Exportación de Contactos
  // ============================================
  /**
   * @openapi
   * /export-contacts/{sessionId}:
   *   get:
   *     summary: Exporta los contactos del store local en formato CSV
   *     parameters:
   *       - in: path
   *         name: sessionId
   *         required: true
   *         description: ID de la sesión de WhatsApp
   *         schema:
   *           type: string
   *     responses:
   *       200:
   *         description: Archivo CSV con contactos
   *         content:
   *           text/csv:
   *             schema:
   *               type: string
   *       404:
   *         description: Sesión o contactos no encontrados
   *       500:
   *         description: Error al exportar contactos
   */
  app.get('/export-contacts/:sessionId', async (req, res) => {
    const { sessionId } = req.params;
    const session = sessions[sessionId];
    if (!session || !session.store || !session.store.contacts) {
      return res.status(404).json({ error: 'Sesión o contactos no encontrados' });
    }
    try {
      const contacts = Object.keys(session.store.contacts).map(jid => {
        const contact = session.store.contacts[jid];
        return {
          jid,
          name: (contact.notify && contact.notify.trim() !== '') ? contact.notify : jid,
        };
      });
      // Convertir a CSV (ejemplo simple)
      let csv = "jid,name\n";
      contacts.forEach(c => {
        csv += `"${c.jid}","${c.name}"\n`;
      });
      res.header('Content-Type', 'text/csv');
      res.attachment('contacts.csv');
      res.send(csv);
    } catch (error) {
      console.error(`Error al exportar contactos para ${sessionId}:`, error);
      res.status(500).json({ error: 'Error al exportar contactos' });
    }
  });

  // ============================================
  // 3. Importación de Contactos (JSON)
  // ============================================
  /**
   * @openapi
   * /import-contacts/{sessionId}:
   *   post:
   *     summary: Importa contactos al store local desde un JSON (array de contactos)
   *     parameters:
   *       - in: path
   *         name: sessionId
   *         required: true
   *         description: ID de la sesión de WhatsApp
   *         schema:
   *           type: string
   *     requestBody:
   *       description: Array de contactos a importar. Cada contacto debe tener "jid" y "name".
   *       required: true
   *       content:
   *         application/json:
   *           schema:
   *             type: array
   *             items:
   *               type: object
   *               properties:
   *                 jid:
   *                   type: string
   *                 name:
   *                   type: string
   *             example:
   *               - jid: "34600000000@s.whatsapp.net"
   *                 name: "Contacto Importado 1"
   *               - jid: "34600000001@s.whatsapp.net"
   *                 name: "Contacto Importado 2"
   *     responses:
   *       200:
   *         description: Contactos importados correctamente
   *       404:
   *         description: Sesión no encontrada
   *       500:
   *         description: Error al importar contactos
   */
  app.post('/import-contacts/:sessionId', async (req, res) => {
    const { sessionId } = req.params;
    const contactsToImport = req.body;
    const session = sessions[sessionId];
    if (!session || !session.store) {
      return res.status(404).json({ error: 'Sesión no encontrada' });
    }
    if (!Array.isArray(contactsToImport)) {
      return res.status(400).json({ error: 'Se requiere un array de contactos' });
    }
    try {
      contactsToImport.forEach(contact => {
        if (contact.jid && contact.name) {
          session.store.contacts[contact.jid] = {
            id: contact.jid,
            notify: contact.name
          };
          // Registrar en el log de auditoría
          if (!session.auditLog) session.auditLog = [];
          session.auditLog.push({
            type: 'import-contact',
            jid: contact.jid,
            name: contact.name,
            timestamp: new Date().toISOString()
          });
        }
      });
      // Persistir en store.json
      const authDir = path.join(STORE_FILE_PATH, sessionId);
      session.store.writeToFile(path.join(authDir, 'store.json'));
      res.json({ message: 'Contactos importados correctamente' });
    } catch (error) {
      console.error(`Error al importar contactos para ${sessionId}:`, error);
      res.status(500).json({ error: 'Error al importar contactos' });
    }
  });

  // ============================================
  // 4. Enviar Mensajes Multimedia
  // ============================================
  /**
   * @openapi
   * /send-multimedia/{sessionId}:
   *   post:
   *     summary: Envía un mensaje multimedia (imagen, video o documento) a un destinatario
   *     parameters:
   *       - in: path
   *         name: sessionId
   *         required: true
   *         description: ID de la sesión de WhatsApp
   *         schema:
   *           type: string
   *     requestBody:
   *       description: Datos del mensaje multimedia
   *       required: true
   *       content:
   *         application/json:
   *           schema:
   *             type: object
   *             properties:
   *               jid:
   *                 type: string
   *               mediaType:
   *                 type: string
   *                 enum: [image, video, document]
   *               base64Data:
   *                 type: string
   *               caption:
   *                 type: string
   *             example:
   *               jid: "34690937275@s.whatsapp.net"
   *               mediaType: "image"
   *               base64Data: "iVBORw0KGgoAAAANSUhEUgAA..."
   *               caption: "Una imagen de prueba"
   *     responses:
   *       200:
   *         description: Mensaje multimedia enviado correctamente
   *       404:
   *         description: Sesión no encontrada
   *       500:
   *         description: Error al enviar el mensaje multimedia
   */
  app.post('/send-multimedia/:sessionId', async (req, res) => {
    const { sessionId } = req.params;
    const { jid, mediaType, base64Data, caption } = req.body;
    const session = sessions[sessionId];
    if (!session) {
      return res.status(404).json({ error: 'Sesión no encontrada' });
    }
    if (!jid || !mediaType || !base64Data) {
      return res.status(400).json({ error: 'Se requieren jid, mediaType y base64Data' });
    }
    try {
      let messagePayload = {};
      // Configuramos el payload según el tipo de medio
      if (mediaType === 'image') {
        messagePayload = {
          image: { url: `data:image/png;base64,${base64Data}` },
          caption: caption || ''
        };
      } else if (mediaType === 'video') {
        messagePayload = {
          video: { url: `data:video/mp4;base64,${base64Data}` },
          caption: caption || ''
        };
      } else if (mediaType === 'document') {
        messagePayload = {
          document: { url: `data:application/octet-stream;base64,${base64Data}` },
          caption: caption || ''
        };
      } else {
        return res.status(400).json({ error: 'Tipo de medio no soportado' });
      }
      const result = await session.sock.sendMessage(jid, messagePayload);
      res.json({ message: 'Mensaje multimedia enviado correctamente', result });
    } catch (error) {
      console.error(`Error al enviar mensaje multimedia para ${sessionId}:`, error);
      res.status(500).json({ error: 'Error al enviar mensaje multimedia' });
    }
  });

  // ============================================
  // 5. Historial de Ediciones y Auditoría
  // ============================================

  /**
   * @openapi
   * /audit-log/{sessionId}:
   *   get:
   *     summary: Obtiene el historial de auditoría de la sesión
   *     parameters:
   *       - in: path
   *         name: sessionId
   *         required: true
   *         description: ID de la sesión de WhatsApp
   *         schema:
   *           type: string
   *     responses:
   *       200:
   *         description: Historial de auditoría obtenido
   *       404:
   *         description: Sesión no encontrada
   *       500:
   *         description: Error al obtener el historial de auditoría
   */
  app.get('/audit-log/:sessionId', async (req, res) => {
    const { sessionId } = req.params;
    const session = sessions[sessionId];
    if (!session) return res.status(404).json({ error: 'Sesión no encontrada' });
    res.json({ auditLog: session.auditLog || [] });
  });

  // (Modifica las rutas de edición y creación de contactos para registrar auditoría)
  // En /edit-contact
  app.post('/edit-contact/:sessionId', async (req, res) => {
    const { sessionId } = req.params;
    const { jid, name } = req.body;
    const session = sessions[sessionId];
    if (!session || !session.store || !session.store.contacts) {
      return res.status(404).json({ error: 'Sesión o contactos no encontrados' });
    }
    if (!jid || !name) {
      return res.status(400).json({ error: 'Se requieren "jid" y "name".' });
    }
    try {
      if (session.store.contacts[jid]) {
        session.store.contacts[jid].notify = name;
        if (!session.auditLog) session.auditLog = [];
        session.auditLog.push({
          type: 'edit-contact',
          jid,
          newName: name,
          timestamp: new Date().toISOString()
        });
      } else {
        return res.status(404).json({ error: 'Contacto no encontrado en el store.' });
      }
      const authDir = path.join(STORE_FILE_PATH, sessionId);
      session.store.writeToFile(path.join(authDir, 'store.json'));
      res.json({ message: 'Contacto actualizado correctamente' });
    } catch (error) {
      console.error(`Error al actualizar el contacto para ${sessionId}:`, error);
      res.status(500).json({ error: 'Error al actualizar el contacto' });
    }
  });

  // En /create-contact
  app.post('/create-contact/:sessionId', async (req, res) => {
    const { sessionId } = req.params;
    const { jid, name } = req.body;
    const session = sessions[sessionId];
    if (!session || !session.store) {
      return res.status(404).json({ error: 'Sesión no encontrada' });
    }
    if (!jid || !name) {
      return res.status(400).json({ error: 'Se requieren "jid" y "name".' });
    }
    try {
      session.store.contacts[jid] = {
        id: jid,
        notify: name,
      };
      if (!session.auditLog) session.auditLog = [];
      session.auditLog.push({
        type: 'create-contact',
        jid,
        name,
        timestamp: new Date().toISOString()
      });
      const authDir = path.join(STORE_FILE_PATH, sessionId);
      session.store.writeToFile(path.join(authDir, 'store.json'));
      res.json({ message: 'Contacto creado correctamente (en store local)' });
    } catch (error) {
      console.error(`Error al crear el contacto para ${sessionId}:`, error);
      res.status(500).json({ error: 'Error al crear el contacto' });
    }
  });

  // ============================================
  // 6. Búsqueda Avanzada en Mensajes
  // ============================================
  /**
   * @openapi
   * /search-messages/{sessionId}:
   *   get:
   *     summary: Busca mensajes en el historial de la sesión según criterios
   *     parameters:
   *       - in: path
   *         name: sessionId
   *         required: true
   *         description: ID de la sesión de WhatsApp
   *         schema:
   *           type: string
   *       - in: query
   *         name: query
   *         required: false
   *         description: Texto a buscar en los mensajes
   *         schema:
   *           type: string
   *       - in: query
   *         name: fromDate
   *         required: false
   *         description: Fecha de inicio en formato ISO (por ejemplo, 2025-03-01T00:00:00Z)
   *         schema:
   *           type: string
   *       - in: query
   *         name: toDate
   *         required: false
   *         description: Fecha de fin en formato ISO
   *         schema:
   *           type: string
   *     responses:
   *       200:
   *         description: Lista de mensajes que cumplen los criterios
   *       404:
   *         description: Sesión no encontrada
   *       500:
   *         description: Error al buscar mensajes
   */
  app.get('/search-messages/:sessionId', async (req, res) => {
    const { sessionId } = req.params;
    const { query, fromDate, toDate } = req.query;
    const session = sessions[sessionId];
    if (!session) {
      return res.status(404).json({ error: 'Sesión no encontrada' });
    }
    try {
      let messages = session.messageHistory || [];
      if (query) {
        const lowerQuery = query.toLowerCase();
        messages = messages.filter(msg => {
          let text = '';
          if (msg.message && msg.message.conversation) {
            text = msg.message.conversation;
          } else if (msg.message && msg.message.extendedTextMessage && msg.message.extendedTextMessage.text) {
            text = msg.message.extendedTextMessage.text;
          }
          return text.toLowerCase().includes(lowerQuery);
        });
      }
      if (fromDate) {
        const fromTimestamp = new Date(fromDate).getTime();
        messages = messages.filter(msg => (msg.messageTimestamp * 1000) >= fromTimestamp);
      }
      if (toDate) {
        const toTimestamp = new Date(toDate).getTime();
        messages = messages.filter(msg => (msg.messageTimestamp * 1000) <= toTimestamp);
      }
      res.json({ messages });
    } catch (error) {
      console.error(`Error al buscar mensajes para ${sessionId}:`, error);
      res.status(500).json({ error: 'Error al buscar mensajes' });
    }
  });

/**
 * @openapi
 * /get-contacts/{sessionId}:
 *   get:
 *     summary: Obtiene la lista de contactos sincronizados para la sesión
 *     parameters:
 *       - in: path
 *         name: sessionId
 *         required: true
 *         description: ID de la sesión de WhatsApp
 *         schema:
 *           type: string
 *     responses:
 *       200:
 *         description: Lista de contactos sincronizados
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 contacts:
 *                   type: array
 *                   items:
 *                     type: object
 *                     properties:
 *                       jid:
 *                         type: string
 *                       name:
 *                         type: string
 *       404:
 *         description: Sesión o contactos no encontrados
 *       500:
 *         description: Error al obtener los contactos
 */
app.get('/get-contacts/:sessionId', async (req, res) => {
    const { sessionId } = req.params;
    const session = sessions[sessionId];

    // Verificamos que exista la sesión y el store de contactos
    if (!session || !session.store || !session.store.contacts) {
      return res.status(404).json({ error: 'Sesión o contactos no encontrados' });
    }

    try {
      // Extraemos los contactos: el store es un objeto donde cada llave es el jid del contacto
      const contacts = Object.keys(session.store.contacts).map(jid => {
        const contact = session.store.contacts[jid];
        return {
          jid,
          // Se usa la propiedad 'notify' para el nombre, o el jid si no hay valor
          name: (contact.notify && contact.notify.trim() !== '') ? contact.notify : jid,
          // Puedes incluir otros campos adicionales si lo requieres
          ...contact,
        };
      });
      res.json({ contacts });
    } catch (error) {
      console.error(`❌ Error al obtener los contactos para ${sessionId}:`, error);
      res.status(500).json({ error: 'Error al obtener los contactos' });
    }
  });



/**
 * @openapi
 * /upload-media/{sessionId}:
 *   post:
 *     summary: Sube un archivo multimedia y lo asocia a la sesión
 *     parameters:
 *       - in: path
 *         name: sessionId
 *         required: true
 *         description: ID de la sesión de WhatsApp
 *         schema:
 *           type: string
 *     requestBody:
 *       description: Archivo multimedia a subir (form-data)
 *       required: true
 *       content:
 *         multipart/form-data:
 *           schema:
 *             type: object
 *             properties:
 *               media:
 *                 type: string
 *                 format: binary
 *     responses:
 *       200:
 *         description: Archivo subido correctamente
 *       400:
 *         description: Error en la subida del archivo
 */
app.post('/upload-media/:sessionId', upload.single('media'), (req, res) => {
  const { sessionId } = req.params;
  if (!req.file) {
    return res.status(400).json({ error: 'No se ha subido ningún archivo' });
  }
  // Aquí podrías guardar la ruta o información del archivo en una base de datos o en memoria asociada a la sesión.
  res.json({ message: 'Archivo subido correctamente', file: req.file });
});

/**
 * @openapi
 * /list-media/{sessionId}:
 *   get:
 *     summary: Lista los archivos multimedia subidos para la sesión
 *     parameters:
 *       - in: path
 *         name: sessionId
 *         required: true
 *         description: ID de la sesión de WhatsApp
 *         schema:
 *           type: string
 *     responses:
 *       200:
 *         description: Lista de archivos multimedia
 */
app.get('/list-media/:sessionId', (req, res) => {
  const { sessionId } = req.params;
  // Aquí se debe implementar la lógica para listar los archivos asociados a la sesión.
  // Por ejemplo, leyendo de una base de datos o directorio específico.
  res.json({ media: [/* lista de archivos */] });
});



// Ruta para configurar o actualizar una regla de autoresponder:
/**
 * @openapi
 * /set-autoresponder/{sessionId}:
 *   post:
 *     summary: Configura o actualiza una regla de autoresponder para la sesión de WhatsApp
 *     parameters:
 *       - in: path
 *         name: sessionId
 *         required: true
 *         description: ID de la sesión de WhatsApp
 *         schema:
 *           type: string
 *     requestBody:
 *       description: Objeto que contiene la palabra clave y la respuesta para configurar la regla de autoresponder
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             properties:
 *               keyword:
 *                 type: string
 *               response:
 *                 type: string
 *             example:
 *               keyword: "hola"
 *               response: "Hola, gracias por tu mensaje."
 *     responses:
 *       200:
 *         description: Regla de autoresponder configurada exitosamente
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 message:
 *                   type: string
 *                 rules:
 *                   type: array
 *                   items:
 *                     type: object
 *                     properties:
 *                       keyword:
 *                         type: string
 *                       response:
 *                         type: string
 *       400:
 *         description: Se requieren "keyword" y "response"
 *       500:
 *         description: Error interno al configurar la regla
 */
app.post('/set-autoresponder/:sessionId', (req, res) => {
    const { sessionId } = req.params;
    const { keyword, response } = req.body;
    if (!keyword || !response) {
      return res.status(400).json({ error: 'Se requieren "keyword" y "response".' });
    }
    if (!autoresponderRules[sessionId]) {
      autoresponderRules[sessionId] = [];
    }
    autoresponderRules[sessionId].push({ keyword: keyword.toLowerCase(), response });
    res.json({ message: 'Regla de autoresponder configurada', rules: autoresponderRules[sessionId] });
  });

/**
 * @swagger
 * /schedule-message/{sessionId}:
 *   post:
 *     summary: Programa un mensaje para enviarlo en una fecha y hora futuras
 *     parameters:
 *       - in: path
 *         name: sessionId
 *         required: true
 *         description: ID de la sesión de WhatsApp
 *         schema:
 *           type: string
 *     requestBody:
 *       description: Datos del mensaje a programar
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             properties:
 *               jid:
 *                 type: string
 *               message:
 *                 type: string
 *               scheduledTime:
 *                 type: string
 *                 description: "Fecha y hora en formato ISO (ejemplo: 2025-03-03T15:00:00Z)"
 *             example:
 *               jid: "34690937275@s.whatsapp.net"
 *               message: "Mensaje programado de prueba"
 *               scheduledTime: "2025-03-03T15:00:00Z"
 *     responses:
 *       200:
 *         description: Mensaje programado correctamente
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 message:
 *                   type: string
 *               example:
 *                 message: "Mensaje programado correctamente"
 *       400:
 *         description: Error de validación
 *       404:
 *         description: Sesión no encontrada
 *       500:
 *         description: Error al programar el mensaje
 */


app.post('/schedule-message/:sessionId', async (req, res) => {
    const { sessionId } = req.params;
    const { jid, message, scheduledTime } = req.body;
    const session = sessions[sessionId];
    if (!session) {
      return res.status(404).json({ error: 'Sesión no encontrada' });
    }
    if (!jid || !message || !scheduledTime) {
      return res.status(400).json({ error: 'Se requieren "jid", "message" y "scheduledTime".' });
    }

    try {
      const scheduleDate = new Date(scheduledTime);
      const now = new Date();
      const delay = scheduleDate.getTime() - now.getTime();
      if (delay <= 0) {
        return res.status(400).json({ error: 'La fecha programada debe ser futura' });
      }
      // Programar el mensaje usando setTimeout (para un ejemplo simple)
      setTimeout(async () => {
        try {
          await session.sock.sendMessage(jid, { text: message });
          console.log(`Mensaje programado enviado a ${jid}`);
        } catch (err) {
          console.error('Error al enviar mensaje programado:', err);
        }
      }, delay);
      res.json({ message: 'Mensaje programado correctamente' });
    } catch (error) {
      console.error(`Error al programar mensaje para ${sessionId}:`, error);
      res.status(500).json({ error: 'Error al programar el mensaje' });
    }
  });

// ------------------------------------------------------
// --------------- RUTAS DE LA API ----------------------
// ------------------------------------------------------

/**
 * @openapi
 * /start-session/{sessionId}:
 *   post:
 *     summary: Inicia una nueva sesión de WhatsApp para un usuario
 *     parameters:
 *       - in: path
 *         name: sessionId
 *         required: true
 *         description: ID de la sesión
 *         schema:
 *           type: string
 *     responses:
 *       200:
 *         description: Sesión iniciada correctamente
 *       500:
 *         description: Error al iniciar la sesión
 */
app.post('/start-session/:sessionId', async (req, res) => {
  const { sessionId } = req.params;
  if (sessions[sessionId]) {
    return res.json({ message: `La sesión ${sessionId} ya está en ejecución.` });
  }
  try {
    await startSession(sessionId);
    res.json({
      message: `Sesión iniciada para ${sessionId}. Escanea el QR en /get-qr/${sessionId}`,
    });
  } catch (error) {
    console.error(`Error al iniciar sesión para ${sessionId}:`, error);
    res.status(500).json({ error: `Error al iniciar sesión para ${sessionId}` });
  }
});

/**
 * @openapi
 * /get-qr/{sessionId}:
 *   get:
 *     summary: Obtiene el código QR en Base64 para conectarse
 *     parameters:
 *       - in: path
 *         name: sessionId
 *         required: true
 *         schema:
 *           type: string
 *     responses:
 *       200:
 *         description: Código QR o mensaje de espera
 *       404:
 *         description: Sesión no encontrada
 *       500:
 *         description: Error generando el código QR
 */
app.get('/get-qr/:sessionId', async (req, res) => {
  const { sessionId } = req.params;
  const session = sessions[sessionId];
  if (!session) {
    return res.status(404).json({ error: 'Sesión no encontrada' });
  }
  if (!session.qrCode) {
    return res.status(200).json({
      message: 'El código QR aún no se ha generado. Por favor, espere.',
    });
  }
  try {
    const qrBase64 = await QRCode.toDataURL(session.qrCode);
    res.json({ success: true, qr: qrBase64 });
  } catch (error) {
    console.error(`Error generando el QR para ${sessionId}:`, error);
    res.status(500).json({ error: 'Error generando el código QR' });
  }
});

/**
 * @openapi
 * /get-qr-jpg/{sessionId}:
 *   get:
 *     summary: Obtiene el código QR en formato imagen PNG
 *     parameters:
 *       - in: path
 *         name: sessionId
 *         required: true
 *         schema:
 *           type: string
 *     responses:
 *       200:
 *         description: Imagen PNG con el QR
 *       404:
 *         description: Sesión no encontrada
 */
app.get('/get-qr-jpg/:sessionId', async (req, res) => {
  const { sessionId } = req.params;
  const session = sessions[sessionId];
  if (!session) {
    return res.status(404).json({ error: 'Sesión no encontrada' });
  }
  if (!session.qrCode) {
    return res.status(200).json({
      message: 'El código QR aún no se ha generado. Por favor, espere.',
    });
  }
  QRCode.toDataURL(session.qrCode, { type: 'image/png' }, (err, url) => {
    if (err) {
      console.error(`Error generando la imagen QR para ${sessionId}:`, err);
      return res.status(500).json({ error: 'Error generando la imagen del código QR' });
    }
    const base64Data = url.split(',')[1];
    const imgBuffer = Buffer.from(base64Data, 'base64');
    res.writeHead(200, {
      'Content-Type': 'image/png',
      'Content-Length': imgBuffer.length,
    });
    res.end(imgBuffer);
  });
});

/**
 * @openapi
 * /get-chats/{sessionId}:
 *   get:
 *     summary: Obtiene la lista de chats sincronizados para la sesión
 *     parameters:
 *       - in: path
 *         name: sessionId
 *         required: true
 *         schema:
 *           type: string
 *     responses:
 *       200:
 *         description: Retorna la lista de chats
 *       404:
 *         description: Sesión no encontrada
 *       500:
 *         description: Error al obtener los chats
 */
app.get('/get-chats/:sessionId', async (req, res) => {
    const { sessionId } = req.params;
    const session = sessions[sessionId];

    if (!session) {
      return res.status(404).json({ error: 'Sesión no encontrada' });
    }

    try {
      // Intentamos obtener los chats desde el store de Baileys
      let chats = session.store.chats && session.store.chats.all && session.store.chats.all();

      // Si no existen chats en el store, cargamos desde el archivo
      if (!chats || chats.length === 0) {
        chats = session.chats || loadChats(sessionId);
      }

      // Si aun no hay chats, construimos la lista a partir del historial de mensajes
      if (!chats || chats.length === 0) {
        const chatMap = {};
        (session.messageHistory || []).forEach((msg) => {
          const remoteJid = msg.key && msg.key.remoteJid;
          if (remoteJid) {
            if (!chatMap[remoteJid]) {
              chatMap[remoteJid] = {
                id: remoteJid,
                name: remoteJid,
                lastMessage: msg.message,
                unreadCount: 0,
                messageTimestamp: msg.messageTimestamp || 0,
              };
            } else {
              if (msg.messageTimestamp > (chatMap[remoteJid].messageTimestamp || 0)) {
                chatMap[remoteJid].lastMessage = msg.message;
                chatMap[remoteJid].messageTimestamp = msg.messageTimestamp;
              }
            }
          }
        });
        chats = Object.values(chatMap);
      }

      // Actualizamos el nombre de cada chat consultando el store de contactos
      const updatedChats = chats.map((chat) => {
        let updatedName = chat.name; // Valor por defecto
        if (
          session.store &&
          session.store.contacts &&
          session.store.contacts[chat.id]
        ) {
          const contact = session.store.contacts[chat.id];
          // Prioridad: notify > pushname > name > jid
          updatedName =
            (contact.notify && contact.notify.trim() !== ""
              ? contact.notify
              : (contact.pushname && contact.pushname.trim() !== ""
                ? contact.pushname
                : (contact.name && contact.name.trim() !== ""
                  ? contact.name
                  : chat.id)));
        }
        chat.name = updatedName;
        return chat;
      });

      // Función auxiliar para obtener los mensajes de un chat
      async function getMessages(sessionId, jid) {
        const session = sessions[sessionId];
        if (!session) return [];
        try {
          const messages = (session.messageHistory || []).filter(
            (msg) => msg.key.remoteJid === jid
          );
          return messages;
        } catch (error) {
          console.error(`❌ Error al obtener los mensajes del chat ${jid}:`, error);
          return [];
        }
      }

      // Ordenamos los chats de más reciente a más antiguo
      const sortedChats = updatedChats.sort(
        (a, b) => b.messageTimestamp - a.messageTimestamp
      );

      // Mapeamos cada chat para incluir el último mensaje y su timestamp
      const mappedChats = await Promise.all(
        sortedChats.map(async (chat) => {
          const messages = await getMessages(sessionId, chat.id);
          const lastMessage = messages.length > 0 ? messages[messages.length - 1] : null;
          const messageTimestamp = lastMessage ? lastMessage.messageTimestamp : 0;

          if (messageTimestamp > 0 && lastMessage) {
            let lastMsgText = "No Message";
            if (lastMessage.message) {
              if (lastMessage.message.conversation) {
                lastMsgText = lastMessage.message.conversation;
              } else if (
                lastMessage.message.extendedTextMessage &&
                lastMessage.message.extendedTextMessage.text
              ) {
                lastMsgText = lastMessage.message.extendedTextMessage.text;
              }
            }
            return {
              jid: chat.id,
              name: chat.name || chat.id,
              lastMessage: lastMsgText,
              unreadCount: chat.unreadCount || 0,
              messageTimestamp: messageTimestamp,
            };
          }
          return null;
        })
      );

      // Filtramos los chats nulos (aquellos sin mensaje válido)
      const filteredChats = mappedChats.filter((chat) => chat !== null);

      console.log(`Chats de ${sessionId}:`, filteredChats);
      res.json({ chats: filteredChats });
    } catch (error) {
      console.error(`❌ Error al obtener los chats de ${sessionId}:`, error);
      res.status(500).json({ error: 'Error al obtener los chats' });
    }
  });




/**
 * @openapi
 * /get-messages/{sessionId}/{jid}:
 *   get:
 *     summary: Obtiene el historial de mensajes de un chat específico
 *     parameters:
 *       - in: path
 *         name: sessionId
 *         required: true
 *         schema:
 *           type: string
 *       - in: path
 *         name: jid
 *         required: true
 *         schema:
 *           type: string
 *     responses:
 *       200:
 *         description: Lista de mensajes para el JID indicado
 *       404:
 *         description: Sesión no encontrada
 *       500:
 *         description: Error al obtener los mensajes
 */
app.get('/get-messages/:sessionId/:jid', async (req, res) => {
    const { sessionId, jid } = req.params;
    const limit = parseInt(req.query.limit) || null;
    const session = sessions[sessionId];

    if (!session || !session.sock?.user) {
      return res.status(404).json({ error: 'Sesión no encontrada o no conectada.' });
    }

    try {
      // Filtrar mensajes del historial en memoria
      let messagesFromHistory = (session.messageHistory || []).filter((msg) => {
        return msg.key && msg.key.remoteJid === jid;
      });

      // Ordenar por timestamp (más antiguo primero)
      messagesFromHistory.sort((a,b) => (a.messageTimestamp || 0) - (b.messageTimestamp || 0));

      // Aplicar límite si se especificó
      if (limit && messagesFromHistory.length > limit) {
          messagesFromHistory = messagesFromHistory.slice(-limit);
      }

      // --- EXTRAER URL PÚBLICA ---
      const formattedMessages = messagesFromHistory.map(msg => {
          let publicUrl = null;
          if (msg.message?.imageMessage?.url_publica) {
              publicUrl = msg.message.imageMessage.url_publica;
          } else if (msg.message?.videoMessage?.url_publica) {
              publicUrl = msg.message.videoMessage.url_publica;
          } else if (msg.message?.audioMessage?.url_publica) {
              publicUrl = msg.message.audioMessage.url_publica;
          } else if (msg.message?.documentMessage?.url_publica) {
              publicUrl = msg.message.documentMessage.url_publica;
          } else if (msg.message?.stickerMessage?.url_publica) {
               publicUrl = msg.message.stickerMessage.url_publica;
          }

          return {
              messageData: msg, // Devolvemos el objeto mensaje original completo
              publicMediaUrl: publicUrl // Y añadimos la URL pública si existe
          };
      });
      // --- FIN EXTRACCIÓN ---


      res.json({ messages: formattedMessages }); // Devolver los mensajes formateados

    } catch (error) {
      console.error(`❌ Error al obtener mensajes del chat ${jid} para ${sessionId}:`, error);
      res.status(500).json({ error: 'Error interno al obtener los mensajes' });
    }
  });

/**
 * @openapi
 * /send-message/{sessionId}:
 *   post:
 *     summary: Envía un mensaje de texto al JID indicado
 *     parameters:
 *       - in: path
 *         name: sessionId
 *         required: true
 *         schema:
 *           type: string
 *     requestBody:
 *       description: JSON con "jid" y "message"
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             properties:
 *               jid:
 *                 type: string
 *               message:
 *                 type: string
 *     responses:
 *       200:
 *         description: Mensaje enviado correctamente
 *       400:
 *         description: Falta el parámetro "jid" o "message"
 *       500:
 *         description: Error al enviar el mensaje
 */
app.post('/send-message/:sessionId', async (req, res) => {
    const { sessionId } = req.params;
    const { jid, message } = req.body;
    if (!jid || !message) {
      return res
        .status(400)
        .json({ error: 'Los parámetros "jid" y "message" son requeridos' });
    }
    const session = sessions[sessionId];
    if (!session) {
      return res
        .status(400)
        .json({ error: `La sesión ${sessionId} no está conectada.` });
    }
    try {
      // Enviamos el mensaje
      const sentMessage = await session.sock.sendMessage(jid, { text: message });

      // Actualizamos el chat con el nuevo mensaje y timestamp
      const remoteJid = sentMessage.key.remoteJid;
      const messageTimestamp = sentMessage.messageTimestamp;

      // Si no existe ese chat en la sesión, lo inicializamos
      if (!sessions[sessionId].chatsMap) {
        sessions[sessionId].chatsMap = {};
      }

      if (!sessions[sessionId].chatsMap[remoteJid]) {
        sessions[sessionId].chatsMap[remoteJid] = {
          id: remoteJid,
          name: remoteJid, // Asignar un nombre o dejar el remoteJid por defecto
          lastMessage: sentMessage.message,
          messageTimestamp,  // Establecer el timestamp aquí
        };
      } else {
        // Si el mensaje es más reciente, actualizamos el `lastMessage` y `messageTimestamp`
        if (messageTimestamp > sessions[sessionId].chatsMap[remoteJid].messageTimestamp) {
          sessions[sessionId].chatsMap[remoteJid].lastMessage = sentMessage.message;
          sessions[sessionId].chatsMap[remoteJid].messageTimestamp = messageTimestamp;
        }
      }

      // Guardamos los chats con la nueva información
      saveChats(sessionId, Object.values(sessions[sessionId].chatsMap));

      res.json({ message: 'Mensaje enviado correctamente' });
    } catch (error) {
      console.error(`❌ Error al enviar mensaje desde ${sessionId} a ${jid}:`, error);
      res.status(500).json({ error: 'Error al enviar el mensaje' });
    }
  });


/**
 * @openapi
 * components:
 *   schemas:
 *     DeleteMessageRequest:
 *       type: object
 *       required:
 *         - remoteJid
 *         - fromMe
 *         - id
 *       properties:
 *         remoteJid:
 *           type: string
 *           description: Identificador del chat (por ejemplo “1234@s.whatsapp.net”)
 *         fromMe:
 *           type: boolean
 *           description: Indica si el mensaje fue enviado por el propio usuario
 *         id:
 *           type: string
 *           description: ID interno del mensaje en WhatsApp
 *         participant:
 *           type: string
 *           description: ID del participante (solo para grupos, cuando fromMe es false)
 *
 *     SuccessResponse:
 *       type: object
 *       properties:
 *         success:
 *           type: boolean
 *           example: true
 *         message:
 *           type: string
 *           example: Solicitud para eliminar mensaje con id "ABC123" enviada.
 *
 *     ErrorResponse:
 *       type: object
 *       properties:
 *         error:
 *           type: string
 *           example: Se requieren los campos "remoteJid" (string), "fromMe" (boolean) y "id" (string).
 *
 * /delete-message/{sessionId}:
 *   delete:
 *     tags:
 *       - Messages
 *     summary: Elimina un mensaje concreto
 *     description: |
 *       Borra un mensaje de WhatsApp tanto en el dispositivo remoto
 *       como de la memoria local de la sesión.
 *     parameters:
 *       - name: sessionId
 *         in: path
 *         description: ID de la sesión activa de Baileys
 *         required: true
 *         schema:
 *           type: string
 *     requestBody:
 *       required: true
 *       description: Datos necesarios para identificar el mensaje a eliminar
 *       content:
 *         application/json:
 *           schema:
 *             $ref: '#/components/schemas/DeleteMessageRequest'
 *     responses:
 *       '200':
 *         description: Mensaje eliminado correctamente
 *         content:
 *           application/json:
 *             schema:
 *               $ref: '#/components/schemas/SuccessResponse'
 *       '400':
 *         description: Datos inválidos o sesión no conectada
 *         content:
 *           application/json:
 *             schema:
 *               $ref: '#/components/schemas/ErrorResponse'
 *       '404':
 *         description: Sesión no disponible
 *         content:
 *           application/json:
 *             schema:
 *               $ref: '#/components/schemas/ErrorResponse'
 *       '500':
 *         description: Error interno del servidor
 *         content:
 *           application/json:
 *             schema:
 *               $ref: '#/components/schemas/ErrorResponse'
 */

app.delete('/delete-message/:sessionId', async (req, res) => {
    const { sessionId } = req.params;
    // --- OBTENER CAMPOS SUELTOS DEL CUERPO ---
    const { remoteJid, fromMe, id, participant } = req.body; // Obtener participant también si se envía

    // --- VALIDACIONES BÁSICAS DE LOS CAMPOS SUELTOS ---
    // typeof fromMe === 'undefined' es más seguro que !fromMe si el valor puede ser false
    if (!remoteJid || typeof fromMe !== 'boolean' || !id) {
      return res.status(400).json({
        error: 'Se requieren los campos "remoteJid" (string), "fromMe" (boolean) y "id" (string) en el cuerpo de la petición.'
      });
    }
    // --- FIN VALIDACIONES ---

    const session = sessions[sessionId];
    if (!session) {
      return res.status(404).json({ error: `La sesión ${sessionId} no está disponible.` });
    }
     if (!session.sock?.user) { // Verificar si está conectado
       return res.status(400).json({ error: `La sesión ${sessionId} no está conectada.` });
    }

    try {
      console.log(`[${sessionId}] 🗑️ Solicitando eliminar mensaje ${id} en chat ${remoteJid}`);

      // --- CONSTRUIR EL OBJETO messageKey PARA BAILEYS ---
      // Baileys SÍ espera un objeto anidado para la clave del mensaje
      const messageKey = {
          remoteJid,
          fromMe,
          id,
          // Añadir participant solo si existe y fromMe es false (mensajes de grupo recibidos)
          ...(participant && !fromMe && { participant })
      };
      // --- FIN CONSTRUCCIÓN ---

      // 1. Elimina el mensaje del dispositivo usando Baileys y el objeto messageKey construido
      await session.sock.sendMessage(remoteJid, {
          delete: messageKey // Pasar el objeto messageKey construido
      });

      console.log(`[${sessionId}] ✅ Solicitud de eliminación enviada para mensaje ${id}`);

      // 2. Eliminamos el mensaje de la memoria local (messageHistory)
      session.messageHistory = session.messageHistory?.filter((msg) => {
        // Comparar las propiedades de la clave
        return !(msg.key?.id === id && msg.key?.remoteJid === remoteJid && msg.key?.fromMe === fromMe);
      }) || [];

      // 3. Guardamos en disco la nueva lista de mensajes
      saveMessageHistory(sessionId, session.messageHistory);

      return res.json({
        success: true,
        message: `Solicitud para eliminar mensaje con id "${id}" enviada.`
      });
    } catch (error) {
      console.error(`[${sessionId}] ❌ Error al eliminar mensaje ${id} en sesión:`, error);
      // Devolver un mensaje de error más específico si es posible
      const errorMessage = error.message || 'Error interno al intentar eliminar el mensaje.';
      return res.status(500).json({ error: errorMessage });
    }
  });

/**
 * @openapi
 * /delete-chat/{sessionId}:
 *   delete:
 *     summary: Elimina (o vacía) un chat completo
 *     parameters:
 *       - in: path
 *         name: sessionId
 *         required: true
 *         schema:
 *           type: string
 *     requestBody:
 *       description: JSON con "jid"
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             properties:
 *               jid:
 *                 type: string
 *     responses:
 *       200:
 *         description: Chat eliminado/vaciado correctamente
 *       400:
 *         description: Parámetros inválidos
 *       404:
 *         description: Sesión no disponible
 *       500:
 *         description: Error interno
 */
app.delete('/delete-chat/:sessionId', async (req, res) => {
    const { sessionId } = req.params;
    const { jid } = req.body;

    if (!jid) {
      return res.status(400).json({
        error: 'El campo "jid" es requerido en el cuerpo de la petición.'
      });
    }

    const session = sessions[sessionId];
    if (!session) {
      return res.status(404).json({ error: `La sesión ${sessionId} no está disponible.` });
    }

    try {
      const finalJid = await resolveToString(jid);
      console.log("Final jid:", finalJid, "Tipo:", typeof finalJid);

      // Intentamos llamar a chatModify; si falla, lo capturamos y continuamos
      try {
        const patch = JSON.parse(JSON.stringify({ clear: { messages: true } }));

        //he comentado esto porque falla es para borrar desde server directamente en telefono
        //await session.sock.chatModify(patch, finalJid);
        console.log("chatModify ejecutado correctamente");
      } catch (err) {
        console.error("Error en chatModify, se procederá a eliminar localmente:", err);
        // Aquí podemos optar por continuar sin modificar el chat en WhatsApp
      }

      // Eliminamos el chat del historial local (JSON)
      session.messageHistory = session.messageHistory.filter((msg) => {
        const msgJid = msg.key?.remoteJid;
        return msgJid !== finalJid;
      });
      saveMessageHistory(sessionId, session.messageHistory);

      // Actualizamos la lista de chats en memoria y en el archivo
      if (sessions[sessionId].chatsMap) {
        delete sessions[sessionId].chatsMap[finalJid];
        saveChats(sessionId, Object.values(sessions[sessionId].chatsMap));
      }

      return res.json({
        success: true,
        message: `Chat ${finalJid} eliminado localmente en la sesión ${sessionId}.`
      });
    } catch (error) {
      console.error(`❌ Error al eliminar/vaciar chat en sesión ${sessionId}:`, error);
      return res.status(500).json({ error: 'Error interno al intentar eliminar o vaciar el chat.' });
    }
  });



  async function resolveToString(value) {
    const resolved = await Promise.resolve(value);
    return String(resolved);
  }


/**
 * @openapi
 * /logout/{sessionId}:
 *   post:
 *     summary: Cierra la sesión y elimina los archivos de autenticación
 *     parameters:
 *       - in: path
 *         name: sessionId
 *         required: true
 *         schema:
 *           type: string
 *     responses:
 *       200:
 *         description: Sesión cerrada y eliminada
 *       404:
 *         description: Sesión no encontrada
 *       500:
 *         description: Error al cerrar sesión
 */
app.post('/logout/:sessionId', async (req, res) => {
    const { sessionId } = req.params;
    const session = sessions[sessionId];
    if (!session) {
      return res.status(404).json({ error: 'Sesión no encontrada' });
    }
    try {
      await session.sock.logout();
      clearInterval(session.storeInterval);
      delete sessions[sessionId];
      const authDir = path.join(STORE_FILE_PATH, sessionId);
      if (fs.existsSync(authDir)) {
        fs.rmSync(authDir, { recursive: true, force: true });
        console.log(`🗑️ Archivos eliminados para ${sessionId}`);
      }
      // Creamos un archivo flag para indicar que la sesión fue cerrada
      const flagFile = path.join(STORE_FILE_PATH, `${sessionId}_loggedOut.flag`);
      fs.writeFileSync(flagFile, 'true');
      res.json({ message: `Sesión ${sessionId} cerrada y eliminada` });
    } catch (error) {
      console.error(`❌ Error al cerrar sesión ${sessionId}:`, error);
      res.status(500).json({ error: 'Error al cerrar sesión' });
    }
  });


/**
 * @openapi
 * /sessions:
 *   get:
 *     summary: Retorna la lista de sesiones activas
 *     responses:
 *       200:
 *         description: Lista de sesiones activas
 */
app.get('/sessions', (req, res) => {
  const activeSessions = Object.keys(sessions);
  res.json({ activeSessions });
});

/**
 * @openapi
 * /media/{sessionId}/{fileName}:
 *   get:
 *     summary: Obtiene un archivo multimedia
 *     description: Devuelve el archivo multimedia almacenado en la ruta de la sesión especificada.
 *     tags:
 *       - Media
 *     parameters:
 *       - in: path
 *         name: sessionId
 *         required: true
 *         description: ID de la sesión
 *         schema:
 *           type: string
 *       - in: path
 *         name: fileName
 *         required: true
 *         description: Nombre del archivo multimedia
 *         schema:
 *           type: string
 *     responses:
 *       200:
 *         description: Archivo encontrado y enviado
 *         content:
 *           application/octet-stream:
 *             schema:
 *               type: string
 *               format: binary
 *       404:
 *         description: Archivo no encontrado
 *         content:
 *           application/json:
 *             schema:
 *               type: object
 *               properties:
 *                 error:
 *                   type: string
 *                   example: Archivo no encontrado
 */
app.get('/media/:sessionId/:fileName', (req, res) => {
    const { fileName } = req.params; // Solo necesitamos fileName de los params
    const filePath = path.join(__dirname, 'media', fileName); // Busca en la carpeta 'media' raíz
    const absoluteFilePath = path.resolve(filePath);

    console.log(`🔍 Solicitando archivo multimedia: ${absoluteFilePath}`);

    if (fs.existsSync(absoluteFilePath)) {
      console.log(`✔️ Enviando archivo: ${absoluteFilePath}`);
      res.sendFile(absoluteFilePath, (err) => { // Usar sendFile para que maneje Content-Type
          if (err) {
              console.error(`❌ Error al enviar archivo ${absoluteFilePath}:`, err);
              if (!res.headersSent) {
                  res.status(500).json({ error: 'Error interno al enviar el archivo.' });
              }
          }
      });
    } else {
      console.log(`❌ Archivo no encontrado: ${absoluteFilePath}`);
      res.status(404).json({ error: 'Archivo no encontrado' });
    }
  });

/**
 * Procesa el mensaje multimedia: usa downloadContentFromMessage para obtener el buffer desencriptado,
 * lo guarda en la carpeta /media raíz y retorna la URL pública.
 * @param {string} sessionId ID de la sesión actual.
 * @param {object} messageProto El objeto protobuf del mensaje multimedia (ej. imageMessage, videoMessage).
 * @param {string} mediaType El tipo de medio ('image', 'video', 'audio', 'document', 'sticker').
 * @param {string} messageId ID del mensaje (para nombre de archivo único).
 * @returns {Promise<string|null>} La URL pública del archivo guardado o null si hay error.
 */
async function processMediaMessage(sessionId, messageProto, mediaType, messageId) {
    if (!messageProto) {
        console.error(`❌ [${sessionId}] No se proporcionó el protobuf del mensaje multimedia (MsgID: ${messageId}).`);
        return null;
    }

    // --- VERIFICACIÓN MÁS ROBUSTA ---
    if (!messageProto.mediaKey || messageProto.mediaKey.length === 0) {
        console.warn(`⚠️ [${sessionId}] MediaKey faltante o vacía para ${mediaType} (MsgID: ${messageId}). No se puede desencriptar. Mensaje:`, JSON.stringify(messageProto));
        return null; // No podemos continuar sin la clave
    }
    // --- FIN VERIFICACIÓN ---

    try {
      console.log(`⬇️ [${sessionId}] Descargando y desencriptando ${mediaType} (MsgID: ${messageId})...`);

      // Usamos la función de Baileys para obtener el stream o buffer
      const stream = await downloadContentFromMessage(messageProto, mediaType);

      // Convertimos el stream a un buffer
      let buffer = Buffer.from([]);
      for await(const chunk of stream) {
          buffer = Buffer.concat([buffer, chunk]);
      }
      console.log(`✅ [${sessionId}] ${mediaType} descargado y desencriptado (Tamaño: ${buffer.length} bytes)`);

      // --- Lógica para guardar el buffer ---
      const mediaDir = path.join(__dirname, 'media'); // Carpeta 'media' en el root
      const absoluteMediaDir = path.resolve(mediaDir);

      // Crear carpeta si no existe
      if (!fs.existsSync(absoluteMediaDir)) {
        console.log(`📁 [${sessionId}] Creando carpeta media en: ${absoluteMediaDir}`);
        fs.mkdirSync(absoluteMediaDir, { recursive: true });
      }

      // Determinar extensión
      let extension = mediaType; // Extensión por defecto
      const mimeType = messageProto.mimetype;

      if (mimeType) {
          const derivedExtension = mime.extension(mimeType);
          if (derivedExtension) {
              extension = derivedExtension;
              console.log(`   [${sessionId}] Mimetype: ${mimeType} -> Extensión: .${extension}`);
          } else {
               console.warn(`   [${sessionId}] ⚠️ No se pudo derivar extensión del mimetype: ${mimeType}. Usando .${extension}`);
          }
      } else if (mediaType === 'audio') {
          extension = 'ogg'; // Baileys suele devolver ogg para audio PTT
      } else if (mediaType === 'sticker') {
          extension = 'webp';
      } else {
           console.warn(`   [${sessionId}] ⚠️ Mimetype no disponible para ${mediaType}. Usando extensión por defecto: .${extension}`);
      }


      // Genera el nombre del archivo único
      const fileHash = messageProto.fileSha256 ? messageProto.fileSha256.toString('hex').substring(0, 10) : Date.now(); // Usar hash o timestamp
      const fileName = `${sessionId}-${messageId}-${fileHash}.${extension}`;
      const destPath = path.join(absoluteMediaDir, fileName);

      console.log(`💾 [${sessionId}] Guardando archivo desencriptado en: ${destPath}`);
      fs.writeFileSync(destPath, buffer);
      console.log(`✔️ [${sessionId}] Archivo guardado exitosamente: ${fileName}`);

      // --- ¡AQUÍ ESTÁ LA CORRECCIÓN! ---
      // Construye la URL pública (Asegúrate que la ruta API /media/:sessionId/:fileName exista y funcione)
      // Asegúrate que PUBLIC_BASE_URL está definido en tu .env o usa un valor por defecto
      const publicBaseUrl = process.env.PUBLIC_BASE_URL || `http://localhost:${PORT}`; // Define publicBaseUrl
      const publicUrl = `${publicBaseUrl}/media/${sessionId}/${fileName}`; // Usa publicBaseUrl
      // --- FIN CORRECCIÓN ---
      console.log(`🔗 [${sessionId}] URL pública generada: ${publicUrl}`);

      return publicUrl; // Retorna la URL para acceder al archivo

    } catch (err) {
      // Manejo específico del error "Cannot derive from empty media key"
      if (err.message?.includes('Cannot derive from empty media key')) {
           console.error(`❌ [${sessionId}] Error al procesar ${mediaType} (MsgID: ${messageId}): La MediaKey está vacía o es inválida.`, err.message);
      } else {
          console.error(`❌ [${sessionId}] Error general al procesar ${mediaType} (MsgID: ${messageId}):`, err);
      }
      if (err.cause) console.error("   -> Causa del error:", err.cause);
      // Loguear el objeto que causó el problema puede ser útil para depurar
      // console.error("   -> Objeto del mensaje problemático:", JSON.stringify(messageProto));
      return null;
    }
  }

// Configurar la tarea programada para eliminar archivos a medianoche
cron.schedule('0 0 * * *', () => {
    const mediaDir = path.join(__dirname, 'media');

    // Verificamos si la carpeta existe
    if (fs.existsSync(mediaDir)) {
      fs.readdirSync(mediaDir).forEach((file) => {
        const filePath = path.join(mediaDir, file);
        try {
          if (fs.lstatSync(filePath).isFile()) {
            fs.unlinkSync(filePath); // Eliminar archivo
            console.log(`Archivo eliminado: ${filePath}`);
          }
        } catch (err) {
          console.error(`Error al eliminar el archivo ${filePath}:`, err);
        }
      });
      console.log('🗑️ Todos los archivos de la carpeta media han sido eliminados.');
    } else {
      console.log('❌ La carpeta media no existe.');
    }
  });

// ---------------------------------
// Restaura sesiones previamente guardadas
async function restoreSessions() {
    if (!fs.existsSync(STORE_FILE_PATH)) {
      fs.mkdirSync(STORE_FILE_PATH, { recursive: true });
    }
    const sessionDirs = fs.readdirSync(STORE_FILE_PATH);
    if (sessionDirs.length === 0) {
      console.log("🔹 No hay sesiones previas para restaurar.");
      return;
    }
    console.log(`🔄 Restaurando ${sessionDirs.length} sesiones activas...`);
    for (const item of sessionDirs) {
      // Si el item es un flag y además NO existe el archivo creds.json, lo omitimos
      const authDir = path.join(STORE_FILE_PATH, item);
      if (item.endsWith('_loggedOut.flag') && !fs.existsSync(path.join(authDir, 'creds.json'))) {
        console.log(`⚠️ Sesión ${item} marcada como cerrada, omitiendo restauración.`);
        continue;
      }
      if (!fs.existsSync(path.join(authDir, 'creds.json'))) {
        console.log(`⚠️ Sesión ${item} no tiene archivos de autenticación, omitiendo...`);
        continue;
      }
      try {
        console.log(`♻️ Restaurando sesión: ${item}`);
        await startSession(item);
      } catch (error) {
        console.error(`❌ Error restaurando sesión ${item}:`, error);
      }
    }
  }



// Iniciamos el servidor
app.listen(PORT, async () => {
  console.log(`🚀 Servidor corriendo en http://localhost:${PORT}`);
  await restoreSessions();
});
