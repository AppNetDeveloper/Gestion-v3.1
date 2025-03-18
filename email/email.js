import express from 'express';
import Imap from 'imap-simple';
import fs from 'fs-extra';
import path from 'path';
import swaggerJsdoc from 'swagger-jsdoc';
import swaggerUi from 'swagger-ui-express';
import dotenv from 'dotenv';
import axios from 'axios';
import { fileURLToPath } from 'url';
import { simpleParser } from 'mailparser';

// Cargar variables de entorno
dotenv.config();

// Definir __filename y __dirname en ES Modules
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Crear la aplicación Express
const app = express();
app.use(express.json());

// Configuración general
const PORT = process.env.PORT || 3000;
const SYNC_INTERVAL = parseInt(process.env.SYNC_INTERVAL, 10) || 300000; // 5 minutos por defecto

// ---------------------------------------------------
// Configuración de Swagger
// ---------------------------------------------------
const swaggerOptions = {
  definition: {
    openapi: '3.0.0',
    info: {
      title: 'IMAP API',
      version: '1.0.0',
      description:
        'API para gestionar correos electrónicos con Node.js usando configuración IMAP (multi usuario por id). Se separan los mensajes en Leidos y No_Leidos y se ordenan por fecha de recepción. Los adjuntos se guardan en una subcarpeta "attachments/<uid>" dentro de cada buzón, de modo que al eliminar el correo se borre también la carpeta de adjuntos.',
    },
    servers: [
      {
        url: `http://localhost:${PORT}`,
      },
    ],
  },
  apis: ['**/*.js'],
};

const swaggerDocs = swaggerJsdoc(swaggerOptions);
app.use('/api-docs', swaggerUi.serve, swaggerUi.setup(swaggerDocs));

// ---------------------------------------------------
// Funciones auxiliares
// ---------------------------------------------------

/**
 * Obtiene la configuración de usuarios desde el archivo imap_config.json.
 * Devuelve un arreglo vacío si el archivo no existe.
 */
async function obtenerUsuarios() {
  const configPath = path.join(__dirname, 'imap_config.json');
  if (!fs.existsSync(configPath)) {
    console.log('No se encontró el archivo imap_config.json, devolviendo arreglo vacío.');
    return [];
  }
  try {
    const usuarios = await fs.readJson(configPath);
    console.log(`Cargados ${usuarios.length} usuario(s) desde imap_config.json.`);
    return usuarios;
  } catch (error) {
    console.error('Error al leer imap_config.json:', error.message);
    return [];
  }
}

/**
 * Mapea la configuración recibida a la estructura esperada para imap-simple.
 * Usa "encryption" para determinar si TLS se activa.
 */
function mapearConfigIMAP(user) {
  return {
    imap: {
      user: user.username,
      password: user.password,
      host: user.host,
      port: user.port,
      tls: user.encryption && user.encryption.toLowerCase() === 'ssl',
      authTimeout: 3000,
      tlsOptions: {
        rejectUnauthorized: user.rejectUnauthorized !== undefined ? user.rejectUnauthorized : false,
      },
    },
  };
}

/**
 * Verifica la conexión IMAP para un usuario dado.
 */
async function verificarConexion(user) {
  try {
    console.log(`Verificando conexión para el usuario: ${user.id}`);
    const config = mapearConfigIMAP(user);
    const connection = await Imap.connect(config);
    connection.end();
    console.log(`Conexión exitosa para ${user.id}`);
    return { status: 'OK', message: 'Conexión exitosa' };
  } catch (error) {
    console.error(`Error en la conexión para ${user.id}:`, error.message);
    return { status: 'ERROR', message: error.message };
  }
}

/**
 * Envía los datos del correo a la API externa, en caso de que esté activada.
 */
async function sendEmailToExternalApi(userId, folder, emailData) {
  if (process.env.API_ACTIVE !== 'true') return;
  try {
    const response = await axios.post(process.env.API_URL, emailData, {
      headers: {
        'Authorization': `Bearer ${process.env.API_KEY}`,
        'Content-Type': 'application/json',
      },
    });
    console.log(
      `Correo ${emailData.metadata.uid} enviado a API externa para usuario ${userId} en carpeta ${folder}, status: ${response.status}`
    );
  } catch (error) {
    console.error(
      `Error enviando correo ${emailData.metadata.uid} a API externa:`,
      error.message
    );
  }
}

/**
 * Sincroniza los correos para el usuario.
 * Se conecta al IMAP, recorre cada carpeta y guarda localmente:
 * - La metadata (encabezado) en un archivo JSON.
 * - El cuerpo del mensaje (texto plano) en un archivo de texto.
 * Los mensajes se guardan en subcarpetas "Leidos" y "No_Leidos" según la bandera \Seen.
 * Además, se parsea el mensaje completo (raw) con simpleParser para extraer los adjuntos,
 * los cuales se guardan en: files/<user.id>/<carpeta>/attachments/<uid>/
 * Si la variable API_ACTIVE es true, se envía el correo a la API externa.
 */
async function sincronizarCorreos(user) {
  try {
    const userDir = path.join(__dirname, 'files', user.id);
    await fs.ensureDir(userDir);

    console.log(`Iniciando sincronización de correos para ${user.id}`);
    const config = mapearConfigIMAP(user);
    const connection = await Imap.connect(config);
    console.log(`Conexión IMAP establecida para ${user.id}`);

    const boxes = await connection.getBoxes();
    const carpetas = Object.keys(boxes);
    console.log(`Carpetas encontradas para ${user.id}:`, carpetas);

    // Recorrer cada buzón (carpeta)
    for (const carpeta of carpetas) {
      const folderDir = path.join(userDir, carpeta);
      await fs.ensureDir(folderDir);
      console.log(`Procesando carpeta: ${carpeta}`);

      // Abrir la carpeta
      await connection.openBox(carpeta);

      // Usamos fetchOptions para obtener el mensaje raw y los encabezados
      const searchCriteria = (user.import_model && user.import_model.toUpperCase() === 'UNSEEN') ? ['UNSEEN'] : ['ALL'];
      const fetchOptions = {
        bodies: ['', 'HEADER.FIELDS (FROM TO SUBJECT DATE)'],
        struct: true,
      };
      const mensajes = await connection.search(searchCriteria, fetchOptions);
      console.log(`Se encontraron ${mensajes.length} correos en la carpeta ${carpeta}`);

      for (const mensaje of mensajes) {
        const uid = mensaje.attributes.uid;
        const esLeido = mensaje.attributes.flags && mensaje.attributes.flags.includes('\\Seen');
        const subfolder = esLeido ? 'Leidos' : 'No_Leidos';
        const subfolderDir = path.join(folderDir, subfolder);
        await fs.ensureDir(subfolderDir);

        const metadataFile = path.join(subfolderDir, `${uid}.json`);
        // Si ya existe, se omite este correo
        if (await fs.pathExists(metadataFile)) {
          console.log(`El correo ${uid} ya existe en ${subfolder} de ${carpeta}, omitiendo.`);
          continue;
        }

        // Extraer el encabezado (HEADER)
        const headerPart = mensaje.parts.find((p) => p.which && p.which.includes('HEADER'));
        // Extraer el mensaje raw (la parte cuyo "which" es la cadena vacía)
        const rawPart = mensaje.parts.find((p) => p.which === '');
        let plainBody = '';
        let attachments = [];

        if (rawPart) {
            try {
                // Asegúrate de que el cuerpo raw sea un Buffer antes de pasarlo a simpleParser
                const rawBody = Buffer.isBuffer(rawPart.body) ? rawPart.body : Buffer.from(rawPart.body);

                // Definir parsed para que sea accesible
                let parsed = null;

                try {
                    parsed = await simpleParser(rawBody);
                    //console.log('Parsed:', parsed); // Verifica la estructura completa del objeto parsed

                    // Intentamos obtener el cuerpo en formato texto o html
                    plainBody = parsed.text || parsed.html || parsed.textAsHtml || parsed.textPlain || 'Cuerpo del mensaje no disponible.';
                } catch (error) {
                    console.error(`Error al parsear el correo ${uid}:`, error.message);
                    // Si no se puede parsear, intentamos recuperar el texto plano
                    const textPart = mensaje.parts.find((p) => p.which && p.which.includes('TEXT'));
                    plainBody = textPart ? textPart.body : 'Cuerpo del mensaje no disponible.';
                }

                // Si parsed existe, extraer los adjuntos
                if (parsed) {
                    attachments = parsed.attachments || [];
                }
            } catch (error) {
                console.error(`Error al procesar el cuerpo del correo ${uid}:`, error.message);

                // Fallback: si no se obtiene la parte raw, usamos la parte TEXT
                const textPart = mensaje.parts.find((p) => p.which && p.which.includes('TEXT'));
                plainBody = textPart ? textPart.body : '';
            }
        } else {
            // Fallback: si no se obtiene la parte raw, usamos la parte TEXT
            const textPart = mensaje.parts.find((p) => p.which && p.which.includes('TEXT'));
            plainBody = textPart ? textPart.body : '';
        }



        // Crear metadata
        const metadata = {
          uid,
          subject: headerPart && headerPart.body.subject ? headerPart.body.subject[0] : 'Sin asunto',
          from: headerPart && headerPart.body.from ? headerPart.body.from[0] : '',
          to: headerPart && headerPart.body.to ? headerPart.body.to[0] : '',
          date: headerPart && headerPart.body.date ? headerPart.body.date[0] : '',
          read: esLeido,
        };

        // Guardar metadata y cuerpo
        await fs.writeJson(metadataFile, metadata, { spaces: 2 });
        console.log(`Metadata guardada en ${subfolder}: ${metadataFile}`);

        const bodyFile = path.join(subfolderDir, `${uid}_body.txt`);
        await fs.writeFile(bodyFile, plainBody);
        console.log(`Cuerpo guardado en ${subfolder}: ${bodyFile}`);

        // Procesar y guardar adjuntos (si existen)
        if (attachments.length > 0) {
          // Se guardan en: files/<user.id>/<carpeta>/attachments/<uid>/
          const attachmentsDir = path.join(folderDir, 'attachments', String(uid));
          await fs.ensureDir(attachmentsDir);
          console.log(
            `Se encontraron ${attachments.length} adjunto(s) para el correo ${uid} en ${carpeta}. Carpeta de adjuntos: ${attachmentsDir}`
          );
          for (const attachment of attachments) {
            const attachmentFile = path.join(attachmentsDir, attachment.filename);
            await fs.writeFile(attachmentFile, attachment.content);
            console.log(`Adjunto guardado: ${attachmentFile}`);
          }
        } else {
          console.log(`No se encontraron adjuntos en el correo ${uid}.`);
        }

        // Si API_ACTIVE es true, enviar los datos del correo a la API externa
        if (process.env.API_ACTIVE === 'true') {
          // Preparamos los adjuntos para enviarlos en base64
          const externalAttachments = attachments.map((att) => ({
            filename: att.filename,
            content: att.content.toString('base64'),
          }));
          const emailData = {
            metadata,
            body: plainBody,
            attachments: externalAttachments,
          };
          await sendEmailToExternalApi(user.id, carpeta, emailData);
        }

        if (user.after_email_import === 'read') {
            await connection.addFlags(uid, '\\Seen');
            console.log(`Correo ${uid} marcado como leído en IMAP.`);
          } else if (user.after_email_import === 'delete') {
            await connection.addFlags(uid, '\\Deleted');
            await connection.expunge();
            console.log(`Correo ${uid} marcado para eliminar en IMAP.`);
          }

      }
    }
    connection.end();
    console.log(`Sincronización completada para ${user.id}`);
  } catch (error) {
    console.error(`Error al sincronizar correos para ${user.id}:`, error.message);
  }
}

// ---------------------------------------------------
// Endpoints de la API
// ---------------------------------------------------

/**
 * @openapi
 * /sync:
 *   post:
 *     summary: Recibe la configuración IMAP y la almacena localmente (actualiza si el id existe)
 *     tags:
 *       - IMAP
 *     requestBody:
 *       required: true
 *       content:
 *         application/json:
 *           schema:
 *             type: array
 *             description: Lista de configuraciones IMAP para cada usuario
 *             items:
 *               type: object
 *               properties:
 *                 id:
 *                   type: string
 *                   example: user1
 *                 host:
 *                   type: string
 *                   example: imappro.zoho.eu
 *                 port:
 *                   type: number
 *                   example: 993
 *                 encryption:
 *                   type: string
 *                   description: "ssl o tls"
 *                   example: ssl
 *                 username:
 *                   type: string
 *                   example: liviudiaconu@appnet.dev
 *                 password:
 *                   type: string
 *                   example: "•••••••••••••••"
 *                 rejectUnauthorized:
 *                   type: boolean
 *                   example: false
 *                 after_email_import:
 *                   type: string
 *                   description: Acción a tomar después de importar el correo ('read' para marcar como leído, 'delete' para eliminar)
 *                   enum: ['read', 'delete', 'none']
 *                   example: none
 *                 import_model:
 *                   type: string
 *                   description: Modelo de importación ('ALL' o 'UNSEEN')
 *                   enum: ['ALL', 'UNSEEN']
 *                   example: ALL
 *
 *     responses:
 *       200:
 *         description: Configuración almacenada con éxito
 *       500:
 *         description: Error en el servidor
 */
app.post('/sync', async (req, res) => {
    try {
      const configPath = path.join(__dirname, 'imap_config.json');
      let existingConfigs = [];
      if (fs.existsSync(configPath)) {
        existingConfigs = await fs.readJson(configPath);
      }
      for (const newConfig of req.body) {
        if (!newConfig.id) {
          return res.status(400).json({ success: false, message: 'El campo "id" es requerido en cada configuración.' });
        }
        const index = existingConfigs.findIndex((config) => config.id === newConfig.id);
        if (index !== -1) {
          existingConfigs[index] = newConfig;
          console.log(`Actualizada configuración para id: ${newConfig.id}`);
        } else {
          existingConfigs.push(newConfig);
          console.log(`Agregada nueva configuración para id: ${newConfig.id}`);
        }
      }
      await fs.writeJson(configPath, existingConfigs, { spaces: 2 });
      console.log('Configuración IMAP actualizada:', existingConfigs);
      res.json({ success: true, message: 'Configuración IMAP actualizada', data: existingConfigs });
      // Sincronización inicial después de actualizar la configuración
      (async () => {
        console.log('Iniciando sincronización inicial de correos...');
        const users = await obtenerUsuarios();
        for (const user of users) {
          await sincronizarCorreos(user);
        }
        console.log('Sincronización inicial completada.');
      })();
    } catch (error) {
      console.error('Error al actualizar la configuración IMAP:', error.message);
      res.status(500).json({ success: false, message: 'Error al actualizar la configuración', error: error.message });
    }
  });


/**
 * @openapi
 * /status:
 *   get:
 *     summary: Devuelve el estado de las conexiones IMAP (por id)
 *     tags:
 *       - IMAP
 *     responses:
 *       200:
 *         description: Lista de estados de conexiones IMAP
 */
app.get('/status', async (req, res) => {
  const users = await obtenerUsuarios();
  let statuses = [];
  for (const user of users) {
    const status = await verificarConexion(user);
    statuses.push({ id: user.id, status: status.status, message: status.message });
  }
  res.json({ success: true, statuses });
});

/**
 * @openapi
 * /folders/{id}:
 *   get:
 *     summary: Lista todas las carpetas de correo del usuario (usando id) a partir de los datos almacenados localmente
 *     tags:
 *       - Emails
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *     responses:
 *       200:
 *         description: Lista de carpetas
 */
app.get('/folders/:id', async (req, res) => {
  const userDir = path.join(__dirname, 'files', req.params.id);
  if (!fs.existsSync(userDir)) {
    console.log(`No se encontró el directorio para el usuario: ${req.params.id}`);
    return res.json([]);
  }
  const folders = await fs.readdir(userDir);
  console.log(`Carpetas encontradas para ${req.params.id}:`, folders);
  res.json({ success: true, folders });
});

/**
 * @openapi
 * /emails/{id}/{folder}:
 *   get:
 *     summary: Lista correos por carpeta (solo metadatos) para el usuario identificado por id, incluyendo Leidos y No_Leidos, ordenados por fecha
 *     tags:
 *       - Emails
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *       - in: path
 *         name: folder
 *         required: true
 *         schema:
 *           type: string
 *     responses:
 *       200:
 *         description: Lista de correos
 */
app.get('/emails/:id/:folder', async (req, res) => {
  const baseFolder = path.join(__dirname, 'files', req.params.id, req.params.folder);
  if (!fs.existsSync(baseFolder)) {
    console.log(`Directorio base no encontrado: ${baseFolder}`);
    return res.json([]);
  }
  const subfolders = ['Leidos', 'No_Leidos'];
  let emails = [];
  for (const subfolder of subfolders) {
    const subfolderPath = path.join(baseFolder, subfolder);
    if (fs.existsSync(subfolderPath)) {
      const files = await fs.readdir(subfolderPath);
      for (const file of files) {
        if (file.endsWith('.json')) {
          const metadata = await fs.readJson(path.join(subfolderPath, file));
          emails.push({
            uid: file.replace('.json', ''),
            subject: metadata.subject,
            from: metadata.from,
            date: metadata.date,
            read: metadata.read,
          });
        }
      }
    }
  }
  emails.sort((a, b) => new Date(a.date) - new Date(b.date));
  res.json({ success: true, emails });
});

/**
 * @openapi
 * /email/{id}/{folder}/{uid}:
 *   get:
 *     summary: Obtiene el cuerpo completo de un correo específico para el usuario (por id), e incluye adjuntos con links de descarga
 *     tags:
 *       - Emails
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *       - in: path
 *         name: folder
 *         required: true
 *         schema:
 *           type: string
 *       - in: path
 *         name: uid
 *         required: true
 *         schema:
 *           type: string
 *     responses:
 *       200:
 *         description: Cuerpo del correo, metadata y adjuntos con links de descarga
 *       404:
 *         description: Correo no encontrado
 */
app.get('/email/:id/:folder/:uid', async (req, res) => {
  const baseFolder = path.join(__dirname, 'files', req.params.id, req.params.folder);
  const subfolders = ['Leidos', 'No_Leidos'];
  let metadataFile, bodyFile;
  for (const subfolder of subfolders) {
    const subfolderPath = path.join(baseFolder, subfolder);
    const possibleMetadata = path.join(subfolderPath, `${req.params.uid}.json`);
    const possibleBody = path.join(subfolderPath, `${req.params.uid}_body.txt`);
    if (await fs.pathExists(possibleMetadata) && await fs.pathExists(possibleBody)) {
      metadataFile = possibleMetadata;
      bodyFile = possibleBody;
      break;
    }
  }
  if (!metadataFile || !bodyFile) {
    console.log(`Correo no encontrado para UID ${req.params.uid}`);
    return res.status(404).json({ success: false, error: 'Correo no encontrado' });
  }
  const metadata = await fs.readJson(metadataFile);
  const body = await fs.readFile(bodyFile, 'utf8');

  // Adjuntos se encuentran en: files/<user.id>/<folder>/attachments/<uid>/
  const attachmentsDir = path.join(baseFolder, 'attachments', req.params.uid);
  let attachments = [];
  if (await fs.pathExists(attachmentsDir)) {
    const storedFiles = await fs.readdir(attachmentsDir);
    attachments = storedFiles.map((filename) => ({
      filename,
      url: `${req.protocol}://${req.get('host')}/download/${req.params.id}/${req.params.folder}/${req.params.uid}/${encodeURIComponent(filename)}`,
    }));
  }
  res.json({ success: true, email: { metadata, body, attachments } });
});

/**
 * @openapi
 * /download/{id}/{folder}/{uid}/{filename}:
 *   get:
 *     summary: Descarga un archivo adjunto de un correo específico
 *     tags:
 *       - Emails
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *       - in: path
 *         name: folder
 *         required: true
 *         schema:
 *           type: string
 *       - in: path
 *         name: uid
 *         required: true
 *         schema:
 *           type: string
 *       - in: path
 *         name: filename
 *         required: true
 *         schema:
 *           type: string
 *     responses:
 *       200:
 *         description: Archivo adjunto
 *       404:
 *         description: Archivo no encontrado
 */
app.get('/download/:id/:folder/:uid/:filename', async (req, res) => {
  const baseFolder = path.join(__dirname, 'files', req.params.id, req.params.folder);
  const filePath = path.join(baseFolder, 'attachments', req.params.uid, req.params.filename);
  if (!(await fs.pathExists(filePath))) {
    return res.status(404).json({ success: false, error: 'Archivo no encontrado' });
  }
  res.sendFile(filePath);
});

/**
 * @openapi
 * /email/{id}/{folder}/{uid}:
 *   delete:
 *     summary: Elimina un correo y sus adjuntos para el usuario (por id)
 *     tags:
 *       - Emails
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *       - in: path
 *         name: folder
 *         required: true
 *         schema:
 *           type: string
 *       - in: path
 *         name: uid
 *         required: true
 *         schema:
 *           type: string
 *     responses:
 *       200:
 *         description: Correo eliminado
 *       500:
 *         description: No se pudo eliminar el correo
 */
app.delete('/email/:id/:folder/:uid', async (req, res) => {
  const baseFolder = path.join(__dirname, 'files', req.params.id, req.params.folder);
  const subfolders = ['Leidos', 'No_Leidos'];
  let found = false;
  for (const subfolder of subfolders) {
    const subfolderPath = path.join(baseFolder, subfolder);
    const metadataFile = path.join(subfolderPath, `${req.params.uid}.json`);
    const bodyFile = path.join(subfolderPath, `${req.params.uid}_body.txt`);
    if (await fs.pathExists(metadataFile) || await fs.pathExists(bodyFile)) {
      if (await fs.pathExists(metadataFile)) {
        await fs.remove(metadataFile);
        console.log(`Metadata eliminada: ${metadataFile}`);
      }
      if (await fs.pathExists(bodyFile)) {
        await fs.remove(bodyFile);
        console.log(`Cuerpo eliminado: ${bodyFile}`);
      }
      found = true;
    }
  }
  // Eliminar la carpeta de adjuntos: files/<user.id>/<folder>/attachments/<uid>/
  const attachmentsDir = path.join(baseFolder, 'attachments', req.params.uid);
  if (await fs.pathExists(attachmentsDir)) {
    await fs.remove(attachmentsDir);
    console.log(`Carpeta de adjuntos eliminada: ${attachmentsDir}`);
  }
  if (!found) {
    return res.status(404).json({ success: false, error: 'Correo no encontrado' });
  }
  res.json({ success: true, message: 'Correo eliminado' });
});

/**
 * @openapi
 * /logout/{id}:
 *   delete:
 *     summary: Realiza el logout eliminando toda la información almacenada para el usuario
 *     tags:
 *       - IMAP
 *     parameters:
 *       - in: path
 *         name: id
 *         required: true
 *         schema:
 *           type: string
 *     responses:
 *       200:
 *         description: Información del usuario eliminada correctamente
 *       404:
 *         description: Usuario no encontrado
 */
app.delete('/logout/:id', async (req, res) => {
  try {
    const userDir = path.join(__dirname, 'files', req.params.id);
    if (!(await fs.pathExists(userDir))) {
      return res.status(404).json({ success: false, error: 'Usuario no encontrado' });
    }
    await fs.remove(userDir);
    console.log(`Directorio del usuario ${req.params.id} eliminado.`);
    res.json({ success: true, message: `Usuario ${req.params.id} desconectado y datos eliminados` });
  } catch (error) {
    console.error('Error al realizar logout:', error.message);
    res.status(500).json({ success: false, error: error.message });
  }
});

// ---------------------------------------------------
// Tarea programada para sincronizar correos
// ---------------------------------------------------
setInterval(async () => {
  console.log('Iniciando tarea de sincronización de correos...');
  const users = await obtenerUsuarios();
  for (const user of users) {
    await sincronizarCorreos(user);
  }
  console.log('Tarea de sincronización completada.');
}, SYNC_INTERVAL);

// ---------------------------------------------------
// Sincronización inicial al arrancar el servidor
// ---------------------------------------------------
app.listen(PORT, () => {
  console.log(`Servidor Node.js corriendo en puerto ${PORT} 🚀`);
  console.log(`Documentación Swagger disponible en http://localhost:${PORT}/api-docs`);
  (async () => {
    console.log('Iniciando sincronización inicial de correos...');
    const users = await obtenerUsuarios();
    for (const user of users) {
      await sincronizarCorreos(user);
    }
    console.log('Sincronización inicial completada.');
  })();
});
