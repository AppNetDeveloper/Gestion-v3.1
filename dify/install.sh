#!/bin/bash
# ==============================================================================
# Script Definitivo para la Instalación Completa de Dify en Ubuntu (CORREGIDO)
# ==============================================================================
#
# Este script realiza las siguientes acciones:
# 1. Verifica si Git y Docker están instalados. Si no, instala Docker.
# 2. Gestiona instalaciones previas de Dify, permitiendo una instalación limpia.
# 3. Clona el repositorio oficial de Dify.
# 4. Configura Dify para ejecutarse en el puerto 3000 usando el método
#    de override, que es el correcto para el problema del puerto 80.
# 5. Inicia Dify usando un comando explícito a prueba de fallos.
#
# ==============================================================================

# --- Configuración ---
DIFY_REPO="https://github.com/langgenius/dify.git"
DIFY_DIR="dify"
WEB_PORT="3000"

# --- Colores para una salida más clara ---
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # Sin Color

# --- Función para manejar errores ---
handle_error() {
    echo -e "\n${RED}----------------------------------------------------"
    echo -e "ERROR: $1"
    echo -e "----------------------------------------------------${NC}"
    read -p "Presiona Enter para salir..."
    exit 1
}

# --- Función para mostrar información de debug ---
debug_paths() {
    echo -e "${YELLOW}--- DEBUG: Verificando estructura de directorios ---${NC}"
    echo "Directorio actual: $(pwd)"
    echo "Contenido del directorio actual:"
    ls -la
    if [ -d "$DIFY_DIR" ]; then
        echo "Contenido de $DIFY_DIR:"
        ls -la "$DIFY_DIR"
        if [ -d "$DIFY_DIR/docker" ]; then
            echo "Contenido de $DIFY_DIR/docker:"
            ls -la "$DIFY_DIR/docker"
        else
            echo "No existe $DIFY_DIR/docker"
        fi
    fi
}

# ==============================================================================
# FASE 1: VERIFICAR E INSTALAR DEPENDENCIAS
# ==============================================================================
echo -e "${YELLOW}--- FASE 1: Verificando dependencias del sistema ---${NC}"

# Verificar Git
if ! command -v git &> /dev/null; then
    handle_error "'git' no está instalado. Por favor, ejecuta 'sudo apt update && sudo apt install git' e inténtalo de nuevo."
fi

# Verificar e instalar Docker si es necesario
if ! command -v docker &> /dev/null; then
    echo "Docker no está instalado. Iniciando el proceso de instalación oficial..."
    
    # Instalación oficial de Docker
    sudo apt-get update
    sudo apt-get install -y ca-certificates curl gnupg
    sudo install -m 0755 -d /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    sudo chmod a+r /etc/apt/keyrings/docker.gpg
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
    sudo apt-get update
    sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

    # Añadir usuario al grupo docker
    sudo usermod -aG docker $USER
    
    echo -e "\n${GREEN}¡Docker se ha instalado correctamente!${NC}"
    echo -e "${YELLOW}----------------------------------------------------------------------------------"
    echo -e "¡ACCIÓN REQUERIDA!"
    echo -e "Para que los permisos de Docker se apliquen, debes CERRAR SESIÓN y VOLVER A ENTRAR."
    echo -e "Después de volver a iniciar sesión, ejecuta este mismo script otra vez para continuar con la instalación de Dify."
    echo -e "----------------------------------------------------------------------------------${NC}"
    exit 0
fi

# Verificar si el demonio de Docker está en ejecución
if ! docker info > /dev/null 2>&1; then
    handle_error "El servicio de Docker no está en ejecución. Por favor, inícialo con 'sudo systemctl start docker' e inténtalo de nuevo."
fi

echo -e "${GREEN}Dependencias de Git y Docker correctas.${NC}"

# ==============================================================================
# FASE 2: INSTALACIÓN Y CONFIGURACIÓN DE DIFY
# ==============================================================================
echo -e "\n${YELLOW}--- FASE 2: Iniciando instalación de Dify ---${NC}"

# Gestionar el directorio Dify existente
if [ -d "$DIFY_DIR" ]; then
    echo -e "${YELLOW}Se ha encontrado un directorio 'dify' existente.${NC}"
    read -p "¿Quieres borrarlo para realizar una instalación limpia? (s/N): " choice
    case "$choice" in 
      s|S ) 
        echo "Borrando el directorio antiguo..."
        rm -rf "$DIFY_DIR" || handle_error "No se pudo borrar el directorio '$DIFY_DIR'."
        ;;
      * ) 
        echo -e "Continuando con el directorio existente. Esto puede causar problemas si la instalación anterior falló."
        ;;
    esac
fi

# Clonar el repositorio si no existe
if [ ! -d "$DIFY_DIR" ]; then
    echo "Clonando el repositorio de Dify..."
    git clone "$DIFY_REPO" || handle_error "Falló la clonación del repositorio."
fi

# Mostrar información de debug
debug_paths

# Determinar la ruta correcta del directorio docker
DOCKER_DIR=""
if [ -d "$DIFY_DIR/docker" ]; then
    DOCKER_DIR="$DIFY_DIR/docker"
    echo -e "${GREEN}Encontrado directorio docker en: $DOCKER_DIR${NC}"
elif [ -d "$DIFY_DIR/dify/docker" ]; then
    DOCKER_DIR="$DIFY_DIR/dify/docker"
    echo -e "${GREEN}Encontrado directorio docker en: $DOCKER_DIR${NC}"
else
    handle_error "No se pudo encontrar el directorio 'docker' en ninguna de las ubicaciones esperadas."
fi

# Navegar a la carpeta de Docker correcta
cd "$DOCKER_DIR" || handle_error "No se pudo entrar en el directorio '$DOCKER_DIR'."

# Verificar que los archivos docker-compose existen
COMPOSE_FILE=""
if [ -f "docker-compose.yml" ]; then
    COMPOSE_FILE="docker-compose.yml"
elif [ -f "docker-compose.yaml" ]; then
    COMPOSE_FILE="docker-compose.yaml"
else
    handle_error "No se encontró el archivo 'docker-compose.yml' ni 'docker-compose.yaml' en '$DOCKER_DIR'."
fi

echo -e "${GREEN}Archivo de Docker Compose encontrado: $COMPOSE_FILE${NC}"

echo -e "${GREEN}Archivos de Docker Compose encontrados correctamente.${NC}"

# Parar cualquier contenedor existente primero
echo -e "\nParando contenedores existentes de Dify (si los hay)..."
docker compose down 2>/dev/null || true

# Crear el fichero de override para configurar el puerto en NGINX
echo -e "\nConfigurando el puerto ${WEB_PORT} para el servicio NGINX..."
cat > docker-compose.override.yml <<'EOL'
version: '3'
services:
  nginx:
    ports:
      - "3000:80"
EOL
echo -e "${GREEN}Archivo 'docker-compose.override.yml' creado/actualizado.${NC}"

# Verificar el contenido del archivo override
echo -e "${YELLOW}Contenido del archivo override:${NC}"
cat docker-compose.override.yml

# Verificar que se creó correctamente
if [ ! -f "docker-compose.override.yml" ]; then
    handle_error "No se pudo crear el archivo 'docker-compose.override.yml'."
fi

# Mostrar los archivos disponibles
echo -e "${YELLOW}Archivos en el directorio docker:${NC}"
ls -la

# Iniciar los servicios de Dify con el comando explícito
echo -e "\nIniciando los servicios de Dify (esto puede tardar varios minutos)..."

# Probar primero si docker compose funciona
if ! docker compose version &> /dev/null; then
    handle_error "Docker Compose no está disponible. Asegúrate de que Docker está correctamente instalado."
fi

# Parar servicios web comunes que puedan estar usando el puerto 80
echo -e "\nVerificando y parando servicios web que puedan interferir..."
sudo systemctl stop apache2 2>/dev/null && echo "Apache2 detenido" || true
sudo systemctl stop nginx 2>/dev/null && echo "Nginx del sistema detenido" || true

# Ejecutar docker compose
echo -e "\nIniciando los servicios de Dify con configuración corregida..."
docker compose -f "$COMPOSE_FILE" -f docker-compose.override.yml up -d || handle_error "Falló el comando 'docker compose up'. Revisa los logs de Docker para más detalles."

# ==============================================================================
# FASE 3: VERIFICACIÓN POST-INSTALACIÓN
# ==============================================================================
echo -e "\n${YELLOW}--- FASE 3: Verificando la instalación ---${NC}"

# Esperar un momento para que los contenedores se inicien
sleep 5

# Verificar que los contenedores están ejecutándose
echo "Verificando el estado de los contenedores..."
if docker compose ps | grep -q "Up"; then
    echo -e "${GREEN}Los contenedores de Dify se están ejecutando correctamente.${NC}"
else
    echo -e "${YELLOW}Advertencia: Algunos contenedores podrían no estar ejecutándose. Verificando...${NC}"
    docker compose ps
fi

# ==============================================================================
# FASE 4: COMPLETADO
# ==============================================================================
echo -e "\n${GREEN}¡Instalación de Dify completada con éxito!${NC}"
echo -e "----------------------------------------------------"
echo -e "Puedes acceder a la interfaz web de Dify en tu navegador en:"
echo -e "URL: ${YELLOW}http://localhost:${WEB_PORT}${NC}  (o  http://<IP-de-tu-servidor>:${WEB_PORT})"
echo ""
echo -e "La primera vez, tendrás que crear una cuenta de administrador."
echo -e "Para conectar Dify con Ollama, usa la dirección: ${YELLOW}http://host.docker.internal:11434${NC}"
echo ""
echo -e "${YELLOW}Comandos útiles:${NC}"
echo -e "• Ver logs: ${YELLOW}docker compose logs -f${NC}"
echo -e "• Parar servicios: ${YELLOW}docker compose down${NC}"
echo -e "• Reiniciar servicios: ${YELLOW}docker compose restart${NC}"
echo -e "• Ver estado: ${YELLOW}docker compose ps${NC}"
echo -e "----------------------------------------------------"

read -p "Presiona Enter para salir..."
exit 0