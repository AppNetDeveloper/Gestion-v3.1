#!/bin/sh

# Obtener la arquitectura de la CPU
ARCH=$(uname -m)

if [ "$ARCH" = "x86_64" ]; then
  echo "**Instalando TensorFlow para x86_64**"

  # Instalar Python 3 y pip
  sudo apt update
  sudo apt install -y python3 python3-pip python3-venv

  # Instalar TensorFlow con soporte para GPU
  python3 -m pip install tensorflow[and-cuda]

  # Verificar la instalación de TensorFlow
  python3 -c "import tensorflow as tf; print(tf.config.list_physical_devices('GPU'))"
  pip install tensorflow --break-system-packages
  

elif [ "$ARCH" = "aarch64" ]; then
  echo "**Instalando TensorFlow para aarch64**"

  # Instalar Python 3 y pip
  sudo apt update
  sudo apt install -y python3 python3-pip python3-venv

  # Crear un entorno virtual y activarlo
  python3 -m venv tf_env
  source tf_env/bin/activate

  # Instalar TensorFlow optimizado para ARM
  pip install tensorflow-cpu-aws

  # Verificar la instalación de TensorFlow
  python3 -c "import tensorflow as tf; print(tf.config.list_physical_devices('CPU'))"

else
  echo "**Arquitectura de CPU no compatible: $ARCH**"
  echo "Este script solo funciona en sistemas x86_64 o aarch64."
  exit 1
fi

echo "**TensorFlow instalado correctamente para $ARCH**"

# Salir del entorno virtual (si se creó para aarch64)
if [ "$ARCH" = "aarch64" ]; then
  deactivate
fi
