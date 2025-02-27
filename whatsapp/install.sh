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


npm update
npm install

echo "Instalación completada."
