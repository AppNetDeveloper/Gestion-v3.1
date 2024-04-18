#!/bin/bash

# Obtener el nombre del dominio
dominio=$1

# Validar el nombre del dominio
if [ -z "$dominio" ]; then
  echo "**ERROR:** Debes especificar el nombre del dominio como argumento."
  echo "Ejemplo: ./script.sh midominio.es"
  exit 1
fi

# Obtener la IP pública del servidor
ip_publica=$(curl -s ifconfig.me)

# Actualizar el sistema
sudo apt update && sudo apt upgrade

# Instalar Postfix
sudo apt install postfix

# Configurar el nombre del dominio
echo "myhostname = $dominio" | sudo tee -a /etc/postfix/main.cf

# Configurar la dirección IP
echo "mynetworks = 127.0.0.1, $ip_publica" | sudo tee -a /etc/postfix/main.cf

# Crear un usuario de correo electrónico
sudo adduser noreplay

# Crear un buzón de correo
sudo maildirmake /home/noreplay/Maildir

# Configurar Postfix para Laravel
echo "driver = smtp" | sudo tee -a /etc/postfix/main.cf
echo "host = localhost" | sudo tee -a /etc/postfix/main.cf
echo "port = 25" | sudo tee -a /etc/postfix/main.cf
echo "username = noreplay" | sudo tee -a /etc/postfix/main.cf
echo "password = noreplaylaravel123" | sudo tee -a /etc/postfix/main.cf
echo "encryption = tls" | sudo tee -a /etc/postfix/main.cf

# Generar usuario y contraseña SMTP
echo "noreplay:noreplaylaravel123" | sudo tee -a /etc/postfix/sasl_passwd

# Reiniciar Postfix
sudo systemctl restart postfix

# Probar el envío de un correo electrónico
echo "Asunto: Prueba de correo electrónico" | mail -s noreplay@$dominio destino@correo.com

# Mostrar mensaje de éxito
echo "**Postfix ha sido instalado y configurado correctamente.**"
echo "**Puedes acceder a tu correo electrónico en https://webmail.$dominio**"
echo "**Usuario:** noreplay"
echo "**Contraseña:** noreplaylaravel123"

