#!/bin/sh

# Obtener la arquitectura de la CPU
ARCH=$(uname -m)

if [ "$ARCH" = "x86_64" ]; then
  echo "**Añadiendo repositorios Debian nonfree para x86_64 (Debian 12)**"

  # Añadir la línea "deb http://deb.debian.org/debian bookworm main contrib non-free" al archivo /etc/apt/sources.list
  echo "deb http://deb.debian.org/debian bookworm main contrib non-free" | sudo tee -a /etc/apt/sources.list

  # Actualizar la lista de paquetes
  sudo apt update

elif [ "$ARCH" = "aarch64" ]; then
  echo "**Añadiendo repositorios Debian nonfree para aarch64 (Debian 12)**"

  # Añadir la línea "deb http://deb.debian.org/debian bookworm main contrib non-free"  al archivo /etc/apt/sources.list
  echo "deb http://deb.debian.org/debian bookworm main contrib non-free" | sudo tee -a /etc/apt/sources.list
echo "deb [arch=armhf] http://httpredir.debian.org/debian/ buster main contrib non-free" | sudo tee -a /etc/apt/sources.list
  # Actualizar la lista de paquetes
  sudo apt update

else
  echo "**Arquitectura de CPU no compatible: $ARCH**"
  echo "Este script solo funciona en sistemas x86_64 o aarch64."
  exit 1
fi

echo "**Repositorios Debian nonfree añadidos correctamente (Debian 12)**"
