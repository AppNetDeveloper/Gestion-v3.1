require('dotenv').config();
const express = require('express');
const fs = require('fs');
const path = require('path'); // Módulo para manejar rutas de archivos
const NodeCache = require('node-cache');
const { Boom } = require('@hapi/boom');
const makeWASocket = require('@whiskeysockets/baileys').default;
const {
  fetchLatestBaileysVersion,
  makeCacheableSignalKeyStore,
  useMultiFileAuthState,
  DisconnectReason,
  makeInMemoryStore, // Se utiliza para mantener un store en memoria con métodos de ayuda.
} = require('@whiskeysockets/baileys');
const Pino = require('pino');
const QRCode = require('qrcode');

const PORT = process.env.PORT || 3005;
const STORE_FILE_PATH = './whatsapp_sessions';

const app = express();
app.use(express.json());

const logger = Pino(
  { timestamp: () => `,"time":"${new Date().toJSON()}"` },
  Pino.destination('./wa-logs.txt')
);
logger.level = 'trace';

// Objeto para almacenar las sesiones activas de forma independiente
const sessions = {};

/**
 * Función para guardar los chats en un archivo JSON persistente.
 * Se guarda en la carpeta de la sesión (chats.json).
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
 * Función para cargar los chats desde el archivo persistente.
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
 * Función para guardar el historial de mensajes en un archivo JSON persistente.
 * Se guarda en la carpeta de la sesión (messages.json).
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
 * Función para cargar el historial de mensajes desde el archivo persistente.
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
  const authDir = `${STORE_FILE_PATH}/${sessionId}`;
  if (!fs.existsSync(authDir)) fs.mkdirSync(authDir, { recursive: true });

  const { state, saveCreds } = await useMultiFileAuthState(authDir);
  const { version } = await fetchLatestBaileysVersion();

  console.log(`📲 Iniciando sesión para ${sessionId} con WhatsApp v${version.join('.')}`);

  // Se crea un store en memoria para manejar chats y mensajes
  const store = makeInMemoryStore({ logger });
  store.readFromFile(`${authDir}/store.json`);

  // Intervalo para persistir el store cada 10 segundos
  const storeInterval = setInterval(() => {
    try {
      store.writeToFile(`${authDir}/store.json`);
    } catch (error) {
      console.error(`❌ Error escribiendo el store para ${sessionId}:`, error);
    }
  }, 10_000);

  // Se crea el socket de conexión
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

  // Vincula el store a los eventos del socket
  store.bind(sock.ev);

  // Inicializamos la sesión con historial y chats persistidos
  sessions[sessionId] = {
    sock,
    state,
    store,
    qrCode: null,
    messageHistory: loadMessageHistory(sessionId),
    chats: loadChats(sessionId),
    storeInterval
  };

  // Captura de mensajes entrantes y actualización del historial
  sock.ev.on('messages.upsert', (m) => {
    console.log('Nuevo mensaje/upsert recibido:', m);
    if (m.type === 'notify' || m.type === 'append') {
      sessions[sessionId].messageHistory.push(...m.messages);
      saveMessageHistory(sessionId, sessions[sessionId].messageHistory);
    }
  });

  // Procesa los eventos del socket
  sock.ev.process(async (events) => {
    if (events['connection.update']) {
      const update = events['connection.update'];
      const { connection, lastDisconnect, qr } = update;

      if (qr) {
        console.log(`🆕 Código QR generado para ${sessionId}`);
        sessions[sessionId].qrCode = qr;
      }

      if (connection === 'close') {
        if ((lastDisconnect?.error instanceof Boom) &&
            lastDisconnect?.error?.output?.statusCode !== DisconnectReason.loggedOut) {
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

/**
 * RUTAS DE LA API
 */

// Inicia una nueva sesión de WhatsApp para un usuario
app.post('/start-session/:sessionId', async (req, res) => {
  const { sessionId } = req.params;
  if (sessions[sessionId]) {
    return res.json({ message: `La sesión ${sessionId} ya está en ejecución.` });
  }
  try {
    await startSession(sessionId);
    res.json({ message: `Sesión iniciada para ${sessionId}. Escanea el QR en /get-qr/${sessionId}` });
  } catch (error) {
    console.error(`Error al iniciar sesión para ${sessionId}:`, error);
    res.status(500).json({ error: `Error al iniciar sesión para ${sessionId}` });
  }
});

// Obtiene el código QR en Base64 para conectarse
app.get('/get-qr/:sessionId', async (req, res) => {
  const { sessionId } = req.params;
  const session = sessions[sessionId];
  if (!session) {
    return res.status(404).json({ error: 'Sesión no encontrada' });
  }
  if (!session.qrCode) {
    // En lugar de devolver error, informamos que aún no se ha generado el QR.
    return res.status(200).json({ message: 'El código QR aún no se ha generado. Por favor, espere.' });
  }
  try {
    const qrBase64 = await QRCode.toDataURL(session.qrCode);
    res.json({ success: true, qr: qrBase64 });
  } catch (error) {
    console.error(`Error generando el QR para ${sessionId}:`, error);
    res.status(500).json({ error: 'Error generando el código QR' });
  }
});

// Obtiene el código QR en formato imagen (PNG)
app.get('/get-qr-jpg/:sessionId', async (req, res) => {
  const { sessionId } = req.params;
  const session = sessions[sessionId];
  if (!session) {
    return res.status(404).json({ error: 'Sesión no encontrada' });
  }
  if (!session.qrCode) {
    return res.status(200).json({ message: 'El código QR aún no se ha generado. Por favor, espere.' });
  }
  QRCode.toDataURL(session.qrCode, { type: 'image/png' }, (err, url) => {
    if (err) {
      console.error(`Error generando la imagen QR para ${sessionId}:`, err);
      return res.status(500).json({ error: 'Error generando la imagen del código QR' });
    }
    const base64Data = url.split(',')[1];
    const imgBuffer = Buffer.from(base64Data, 'base64');
    res.writeHead(200, { 'Content-Type': 'image/png', 'Content-Length': imgBuffer.length });
    res.end(imgBuffer);
  });
});

// Obtiene la lista de chats sincronizados para la sesión
app.get('/get-chats/:sessionId', async (req, res) => {
  const { sessionId } = req.params;
  const session = sessions[sessionId];
  if (!session) {
    return res.status(404).json({ error: 'Sesión no encontrada' });
  }
  try {
    let chats = session.store.chats && session.store.chats.all && session.store.chats.all();
    if (!chats || chats.length === 0) {
      chats = session.chats || loadChats(sessionId);
    }
    const mappedChats = chats.map(chat => ({
      jid: chat.id,
      name: chat.name || chat.id,
      lastMessage: chat.lastMessage?.message?.conversation || null,
      unreadCount: chat.unreadCount || 0,
    }));
    console.log(`Chats de ${sessionId}:`, mappedChats);
    res.json({ chats: mappedChats });
  } catch (error) {
    console.error(`❌ Error al obtener los chats de ${sessionId}:`, error);
    res.status(500).json({ error: 'Error al obtener los chats' });
  }
});

// Obtiene el historial de mensajes persistido para el chat indicado (por JID)
app.get('/get-messages/:sessionId/:jid', async (req, res) => {
  const { sessionId, jid } = req.params;
  const session = sessions[sessionId];
  if (!session) {
    return res.status(404).json({ error: 'Sesión no encontrada' });
  }
  try {
    const messages = (session.messageHistory || []).filter(msg => {
      return msg.key && msg.key.remoteJid === jid;
    });
    res.json({ messages });
  } catch (error) {
    console.error(`❌ Error al obtener mensajes del chat ${jid}:`, error);
    res.status(500).json({ error: 'Error al obtener los mensajes' });
  }
});

// Envía un mensaje de texto al JID indicado
app.post('/send-message/:sessionId', async (req, res) => {
  const { sessionId } = req.params;
  const { jid, message } = req.body;
  if (!jid || !message) {
    return res.status(400).json({ error: 'Los parámetros "jid" y "message" son requeridos' });
  }
  const session = sessions[sessionId];
  if (!session) {
    return res.status(400).json({ error: `La sesión ${sessionId} no está conectada.` });
  }
  try {
    await session.sock.sendMessage(jid, { text: message });
    res.json({ message: 'Mensaje enviado correctamente' });
  } catch (error) {
    console.error(`❌ Error al enviar mensaje desde ${sessionId} a ${jid}:`, error);
    res.status(500).json({ error: 'Error al enviar el mensaje' });
  }
});

// Cierra la sesión y elimina los archivos de autenticación
app.post('/logout/:sessionId', async (req, res) => {
  const { sessionId } = req.params;
  const session = sessions[sessionId];
  if (!session) {
    return res.status(404).json({ error: 'Sesión no encontrada' });
  }
  try {
    await session.sock.logout();
    clearInterval(session.storeInterval); // Cancela el intervalo
    delete sessions[sessionId];
    const authDir = `${STORE_FILE_PATH}/${sessionId}`;
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

// Retorna la lista de sesiones activas
app.get('/sessions', (req, res) => {
  const activeSessions = Object.keys(sessions);
  res.json({ activeSessions });
});

// Al iniciar el servidor, se intenta restaurar sesiones previamente guardadas
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
    const authDir = `${STORE_FILE_PATH}/${sessionId}`;
    if (!fs.existsSync(`${authDir}/creds.json`)) {
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

app.listen(PORT, async () => {
  console.log(`🚀 Servidor corriendo en http://localhost:${PORT}`);
  await restoreSessions(); // Restaura sesiones automáticamente al iniciar el servidor
});
