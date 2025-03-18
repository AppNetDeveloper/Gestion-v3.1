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
      const searchCriteria = user.process_unread_only ? ['UNSEEN'] : ['ALL']; // Solo no leídos si es true
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

                    // Intentamos obtener el cuerpo en formato texto o html
                    plainBody = parsed.text || parsed.html || parsed.textAsHtml || parsed.textPlain || 'Cuerpo del mensaje no disponible.';
                } catch (error) {
                    console.error(`Error al parsear el correo ${uid}:`, error.message);
                    const textPart = mensaje.parts.find((p) => p.which && p.which.includes('TEXT'));
                    plainBody = textPart ? textPart.body : 'Cuerpo del mensaje no disponible.';
                }

                // Si parsed existe, extraer los adjuntos
                if (parsed) {
                    attachments = parsed.attachments || [];
                }
            } catch (error) {
                console.error(`Error al procesar el correo ${uid}:`, error.message);

                const textPart = mensaje.parts.find((p) => p.which && p.which.includes('TEXT'));
                plainBody = textPart ? textPart.body : '';
            }
        } else {
            const textPart = mensaje.parts.find((p) => p.which && p.which.includes('TEXT'));
            plainBody = textPart ? textPart.body : '';
        }

        const metadata = {
          uid,
          subject: headerPart && headerPart.body.subject ? headerPart.body.subject[0] : 'Sin asunto',
          from: headerPart && headerPart.body.from ? headerPart.body.from[0] : '',
          to: headerPart && headerPart.body.to ? headerPart.body.to[0] : '',
          date: headerPart && headerPart.body.date ? headerPart.body.date[0] : '',
          read: esLeido,
        };

        await fs.writeJson(metadataFile, metadata, { spaces: 2 });
        console.log(`Metadata guardada en ${subfolder}: ${metadataFile}`);

        const bodyFile = path.join(subfolderDir, `${uid}_body.txt`);
        await fs.writeFile(bodyFile, plainBody);
        console.log(`Cuerpo guardado en ${subfolder}: ${bodyFile}`);

        if (attachments.length > 0) {
          const attachmentsDir = path.join(folderDir, 'attachments', String(uid));
          await fs.ensureDir(attachmentsDir);
          console.log(`Se encontraron ${attachments.length} adjunto(s) para el correo ${uid} en ${carpeta}. Carpeta de adjuntos: ${attachmentsDir}`);
          for (const attachment of attachments) {
            const attachmentFile = path.join(attachmentsDir, attachment.filename);
            await fs.writeFile(attachmentFile, attachment.content);
            console.log(`Adjunto guardado: ${attachmentFile}`);
          }
        }

        // Si API_ACTIVE es true, enviar los datos del correo a la API externa
        if (process.env.API_ACTIVE === 'true') {
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

        // Acción sobre el correo después de procesarlo
        if (user.mark_as_read_or_delete === 'read') {
          try {
            await connection.addFlags(uid, ['\\Seen']);
            console.log(`Correo ${uid} marcado como leído.`);
          } catch (error) {
            console.error(`Error al marcar el correo ${uid} como leído:`, error.message);
          }
        } else if (user.mark_as_read_or_delete === 'delete') {
          try {
            await connection.addFlags(uid, ['\\Deleted']);
            await connection.expunge();
            console.log(`Correo ${uid} eliminado del servidor IMAP.`);
          } catch (error) {
            console.error(`Error al eliminar el correo ${uid} del servidor IMAP:`, error.message);
          }
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
 *                 process_unread_only:
 *                   type: boolean
 *                   example: true
 *                   description: "Indica si se deben procesar solo los correos no leídos."
 *                 mark_as_read_or_delete:
 *                   type: string
 *                   example: "read"
 *                   enum: ["read", "delete", "none"]
 *                   description: "Acción a tomar sobre el correo: 'read' para marcarlo como leído, 'delete' para eliminarlo."
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

// Iniciar el servidor
app.listen(PORT, () => {
  console.log(`Servidor Node.js corriendo en puerto ${PORT} 🚀`);
  console.log(`Documentación Swagger disponible en http://localhost:${PORT}/api-docs`);
});
