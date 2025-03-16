#!/bin/bash
# install.sh

echo "Iniciando la instalación de dependencias..."

# Inicializa el package.json si no existe
if [ ! -f package.json ]; then
  npm init -y
fi

# Instala las dependencias necesarias
npm install @whiskeysockets/baileys express node-cache pino axios @hapi/boom dotenv qrcode
npm install swagger-ui-express swagger-jsdoc
npm install swagger-ui-express swagger-jsdoc --save
npm install multer
npm install https-agent
npm install express-fileupload
npm install @hapi/boom
npm install dotenv
npm install qrcode
npm install axios
npm install node-cache
npm install pino
npm install express
npm install node-cron



npm update
npm install

echo "Instalación completada."
