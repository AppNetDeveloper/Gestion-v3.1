#!/bin/bash
# ==============================================================================
# Script de Instalación Final para Dify (v9 - Configuración Manual Guiada)
# ==============================================================================
#
# Este script realiza la instalación hasta el punto de la configuración y luego
# se detiene, proporcionando instrucciones claras para que el usuario edite
# manualmente el archivo de configuración. Esto evita cualquier error con
# los comandos de edición automática.
#
# ==============================================================================

# --- Configuración ---
INSTALL_PATH="/var/www/html/dify_app"
DIFY_REPO="https://github.com/langgenius/dify.git"
API_PORT="3000"

# --- Colores ---
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# --- Función para manejar errores ---
handle_error() {
    echo -e "\n${RED}### ERROR ###\n$1\n#############${NC}"
    read -p "Presiona Enter para salir..."
    exit 1
}

# --- Inicio del Script ---
echo -e "${YELLOW}--- Iniciando el script de instalación de Dify (v9 - Configuración Manual) ---${NC}"
echo "Dify se instalará en: ${YELLOW}${INSTALL_PATH}${NC}"

# 1. Limpieza previa
echo -e "\n--- Paso 1: Limpieza del entorno ---"
if [ -d "$INSTALL_PATH" ]; then
    read -p "Se ha encontrado un directorio de instalación existente. ¿Quieres borrarlo para una instalación 100% limpia? (s/N): " choice
    if [[ "$choice" =~ ^[Ss]$ ]]; then
        echo "Deteniendo cualquier contenedor asociado..."
        (cd "$INSTALL_PATH/docker" && sudo docker compose -f docker-compose.middleware.yaml down -v 2>/dev/null) || true
        echo "Borrando el directorio antiguo: ${INSTALL_PATH}"
        sudo rm -rf "$INSTALL_PATH" || handle_error "No se pudo borrar el directorio '$INSTALL_PATH'."
    fi
fi

# 2. Clonar el repositorio
if [ ! -d "$INSTALL_PATH" ]; then
    echo -e "\n--- Paso 2: Clonando el repositorio ---"
    sudo git clone "$DIFY_REPO" "$INSTALL_PATH" || handle_error "Falló la clonación del repositorio."
    echo -e "${GREEN}Repositorio clonado con éxito.${NC}"
fi

# 3. Navegar al directorio de Docker
DOCKER_PATH="${INSTALL_PATH}/docker"
echo -e "\nCambiando al directorio de Docker: ${YELLOW}${DOCKER_PATH}${NC}"
cd "$DOCKER_PATH" || handle_error "No se pudo entrar en el directorio '${DOCKER_PATH}'."

# 4. Copiar el fichero de configuración
echo -e "\n--- Paso 3: Preparando el archivo de configuración ---"
sudo cp middleware.env.example middleware.env || handle_error "No se pudo copiar 'middleware.env.example'."
echo -e "${GREEN}Archivo 'middleware.env' creado correctamente.${NC}"

# 5. INSTRUCCIONES PARA LA CONFIGURACIÓN MANUAL
echo -e "\n${YELLOW}========================= ACCIÓN MANUAL REQUERIDA ========================${NC}"
echo -e "El script se detendrá ahora para que modifiques el archivo de configuración."
echo -e "Por favor, ejecuta el siguiente comando para abrir el editor:"
echo ""
echo -e "${GREEN}sudo nano ${PWD}/middleware.env${NC}"
echo ""
echo -e "Una vez dentro, busca la línea que dice ${YELLOW}PORT=5001${NC} y cámbiala por ${YELLOW}PORT=${API_PORT}${NC}."
echo -e "Después, guarda los cambios y cierra el editor (Ctrl+X, luego Y, luego Enter)."
echo -e "${YELLOW}===========================================================================${NC}"
read -p "Presiona Enter cuando hayas terminado de editar el archivo para continuar con la instalación..."

# 6. Verificación post-edición
if ! grep -q "PORT=${API_PORT}" middleware.env; then
    handle_error "La verificación ha fallado. La línea 'PORT=${API_PORT}' no se encontró en 'middleware.env'. Por favor, edita el archivo de nuevo."
fi
echo -e "${GREEN}¡Configuración del puerto verificada correctamente!${NC}"

# 7. Iniciar los servicios de Dify
echo -e "\n--- Paso 4: Iniciando los servicios de Dify (esto puede tardar varios minutos) ---"
sudo docker compose -f docker-compose.middleware.yaml up -d || handle_error "Falló el comando 'docker compose up -d'. Revisa los logs de Docker para más detalles."

# 8. Verificación Final
echo -e "\n--- Paso 5: Verificando la instalación ---"
sleep 20
echo -e "${YELLOW}Estado final de los contenedores:${NC}"
sudo docker compose -f docker-compose.middleware.yaml ps
echo -e "\n${GREEN}¡DIFY INSTALADO CORRECTAMENTE EN MODO MIDDLEWARE!${NC}"
read -p "Presiona Enter para salir..."
exit 0
