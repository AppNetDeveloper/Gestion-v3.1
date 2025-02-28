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
} = require('@whiskeysockets/baileys');
const Pino = require('pino');
const QRCode = require('qrcode');
const axios = require('axios'); // Para realizar la llamada HTTP externa
const https = require('https'); // Para configurar el agente HTTPS

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

            // Si no existe ese chat en la sesión, lo inicializamos
            if (!sessions[sessionId].chatsMap) {
                sessions[sessionId].chatsMap = {};
            }

            // Si no existe el chat en el mapa, lo inicializamos
            if (!sessions[sessionId].chatsMap[remoteJid]) {
                sessions[sessionId].chatsMap[remoteJid] = {
                    id: remoteJid,
                    name: remoteJid,  // Asignar un nombre o dejar el remoteJid por defecto
                    lastMessage: msg.message,
                    messageTimestamp,  // Establecer el timestamp aquí
                };
            } else {
                // Si el mensaje es más reciente, actualizamos el `lastMessage` y `messageTimestamp`
                if (messageTimestamp > sessions[sessionId].chatsMap[remoteJid].messageTimestamp) {
                    sessions[sessionId].chatsMap[remoteJid].lastMessage = msg.message;
                    sessions[sessionId].chatsMap[remoteJid].messageTimestamp = messageTimestamp;
                }
            }

            // Si el mensaje es enviado por nosotros, debemos actualizar también el lastMessage
            if (msg.key.fromMe) {
                sessions[sessionId].chatsMap[remoteJid].lastMessage = msg.message; // Actualiza el lastMessage con el mensaje enviado
            }
        });

        // Guardamos los chats con la nueva información
        saveChats(sessionId, Object.values(sessions[sessionId].chatsMap));

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
});



  // Procesa otros eventos del socket
  sock.ev.process(async (events) => {
    if (events['connection.update']) {
      const update = events['connection.update'];
      const { connection, lastDisconnect, qr } = update;

      if (qr) {
        console.log(`🆕 Código QR generado para ${sessionId}`);
        sessions[sessionId].qrCode = qr;
      }

      if (connection === 'close') {
        if (
          lastDisconnect?.error instanceof Boom &&
          lastDisconnect?.error?.output?.statusCode !== DisconnectReason.loggedOut
        ) {
          console.log(`🔄 Reconectando sesión para ${sessionId}...`);
          await startSession(sessionId);
        } else {
          console.log(`🚪 Sesión cerrada para ${sessionId}`);
          clearInterval(sessions[sessionId].storeInterval);
          if (fs.existsSync(authDir)) {
            fs.rmSync(authDir, { recursive: true, force: true });
            console.log(`🗑️ Archivos eliminados para ${sessionId}`);
          }
          delete sessions[sessionId];
        }
      }

      if (connection === 'open') {
        console.log(`✅ Conexión establecida para ${sessionId}`);
        sessions[sessionId].qrCode = null;
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
      // Obtener los chats desde el store de Baileys
      let chats = session.store.chats && session.store.chats.all && session.store.chats.all();

      // Si no existen chats sincronizados en el store, cargamos los chats desde el archivo
      if (!chats || chats.length === 0) {
        chats = session.chats || loadChats(sessionId);
      }

      // Si no existen chats aún, intentamos construir la lista a partir de los mensajes históricos
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
                messageTimestamp: msg.messageTimestamp || 0, // Timestamp del último mensaje
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

      // Usamos la función getMessages para asegurarnos de que los mensajes y timestamps se actualicen correctamente
      async function getMessages(sessionId, jid) {
        const session = sessions[sessionId];
        if (!session) return [];
        try {
          // Filtramos los mensajes del chat por remoteJid
          const messages = (session.messageHistory || []).filter((msg) => msg.key.remoteJid === jid);
          return messages;
        } catch (error) {
          console.error(`❌ Error al obtener los mensajes del chat ${jid}:`, error);
          return [];
        }
      }

      // Organiza los chats por el último mensaje (usando messageTimestamp para ordenar)
      const sortedChats = chats.sort((a, b) => {
        return b.messageTimestamp - a.messageTimestamp; // Ordena de más reciente a más antiguo
      });

      // Mapeo de chats para incluir el último mensaje y timestamp
      const mappedChats = await Promise.all(sortedChats.map(async (chat) => {
        // Obtiene los mensajes del chat usando getMessages
        const messages = await getMessages(sessionId, chat.id);
        const lastMessage = messages.length > 0 ? messages[messages.length - 1] : null;
        const messageTimestamp = lastMessage ? lastMessage.messageTimestamp : 0;

        // Filtramos los chats que no tienen un mensaje o timestamp válido
        if (messageTimestamp > 0 && lastMessage) {
          return {
            jid: chat.id,
            name: chat.name || chat.id,
            lastMessage: lastMessage.message ? lastMessage.message.conversation || "No Message" : "No Message",
            unreadCount: chat.unreadCount || 0,
            messageTimestamp: messageTimestamp, // Incluir el timestamp aquí
          };
        }
        return null;
      }));

      // Filtramos los chats nulos (aquellos sin mensaje o timestamp válido)
      const filteredChats = mappedChats.filter(chat => chat !== null);

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
  const session = sessions[sessionId];
  if (!session) {
    return res.status(404).json({ error: 'Sesión no encontrada' });
  }
  try {
    const messages = (session.messageHistory || []).filter((msg) => {
      return msg.key && msg.key.remoteJid === jid;
    });
    res.json({ messages });
  } catch (error) {
    console.error(`❌ Error al obtener mensajes del chat ${jid}:`, error);
    res.status(500).json({ error: 'Error al obtener los mensajes' });
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
 * /delete-message/{sessionId}:
 *   delete:
 *     summary: Elimina un mensaje concreto
 *     parameters:
 *       - in: path
 *         name: sessionId
 *         required: true
 *         schema:
 *           type: string
 *     requestBody:
 *       description: JSON con "remoteJid", "fromMe" (boolean) e "id"
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: object
 *             properties:
 *               remoteJid:
 *                 type: string
 *               fromMe:
 *                 type: boolean
 *               id:
 *                 type: string
 *     responses:
 *       200:
 *         description: Mensaje eliminado correctamente
 *       400:
 *         description: Datos inválidos
 *       404:
 *         description: Sesión no disponible
 *       500:
 *         description: Error interno
 */
app.delete('/delete-message/:sessionId', async (req, res) => {
  const { sessionId } = req.params;
  const { remoteJid, fromMe, id } = req.body;

  // Validaciones básicas
  if (!remoteJid || typeof fromMe === 'undefined' || !id) {
    return res.status(400).json({
      error: 'Se requieren los campos "remoteJid", "fromMe" y "id" en el cuerpo de la petición.'
    });
  }

  const session = sessions[sessionId];
  if (!session) {
    return res
      .status(404)
      .json({ error: `La sesión ${sessionId} no está disponible.` });
  }

  try {
    // 1. Elimina el mensaje del dispositivo (si se puede) usando Baileys
    // Nota: fromMe debe ser boolean, id es el "ID" del mensaje
    await session.sock.sendMessage(remoteJid, {
      delete: { remoteJid, fromMe, id }
    });

    // 2. Eliminamos el mensaje de la memoria local (messageHistory)
    session.messageHistory = session.messageHistory.filter((msg) => {
      const msgId = msg.key?.id;
      const msgJid = msg.key?.remoteJid;
      const msgFrom = msg.key?.fromMe;
      return !(msgId === id && msgJid === remoteJid && msgFrom === fromMe);
    });

    // 3. Guardamos en disco la nueva lista de mensajes
    saveMessageHistory(sessionId, session.messageHistory);

    return res.json({
      success: true,
      message: `Mensaje con id "${id}" eliminado de la sesión ${sessionId}.`
    });
  } catch (error) {
    console.error(`❌ Error al eliminar mensaje en la sesión ${sessionId}:`, error);
    return res.status(500).json({ error: 'Error interno al intentar eliminar el mensaje.' });
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
    return res
      .status(404)
      .json({ error: `La sesión ${sessionId} no está disponible.` });
  }

  try {
    // 1. Opcional: limpiar el chat en el dispositivo usando Baileys
    //    Esto deja el chat “vacío” en el cliente WhatsApp.
    //    Si prefieres "borrar" el chat por completo, se puede usar: { delete: true }.
    await session.sock.chatModify({ clear: { messages: true } }, jid);

    // 2. Eliminar del store local: session.store.chats y session.chats
    if (session.store.chats) {
      session.store.chats.delete(jid);
    }
    if (Array.isArray(session.chats)) {
      session.chats = session.chats.filter((chat) => chat.id !== jid);
      saveChats(sessionId, session.chats); // Guardar en disco
    }

    // 3. Eliminar todos los mensajes asociados a ese jid en el historial local
    session.messageHistory = session.messageHistory.filter(
      (msg) => msg.key?.remoteJid !== jid
    );
    saveMessageHistory(sessionId, session.messageHistory);

    return res.json({
      success: true,
      message: `Chat ${jid} eliminado o vaciado correctamente en la sesión ${sessionId}.`
    });
  } catch (error) {
    console.error(`❌ Error al eliminar/vaciar chat en sesión ${sessionId}:`, error);
    return res.status(500).json({ error: 'Error interno al intentar eliminar o vaciar el chat.' });
  }
});

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
  for (const sessionId of sessionDirs) {
    const authDir = path.join(STORE_FILE_PATH, sessionId);
    if (!fs.existsSync(path.join(authDir, 'creds.json'))) {
      console.log(`⚠️ Sesión ${sessionId} no tiene archivos de autenticación, omitiendo...`);
      continue;
    }
    try {
      console.log(`♻️ Restaurando sesión: ${sessionId}`);
      await startSession(sessionId);
    } catch (error) {
      console.error(`❌ Error restaurando sesión ${sessionId}:`, error);
    }
  }
}

// Iniciamos el servidor
app.listen(PORT, async () => {
  console.log(`🚀 Servidor corriendo en http://localhost:${PORT}`);
  await restoreSessions();
});
