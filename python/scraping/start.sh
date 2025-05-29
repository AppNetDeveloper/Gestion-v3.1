#!/bin/bash
# Ruta al directorio del proyecto
PROJECT_DIR="/var/www/html/python/scraping"

# Activar el entorno virtual
export PATH="$PROJECT_DIR/venv/bin:$PATH"
source "$PROJECT_DIR/venv/bin/activate"

# Instalar dependencias
echo "Instalando/actualizando dependencias..."
"$PROJECT_DIR/venv/bin/pip" install --upgrade pip
"$PROJECT_DIR/venv/bin/pip" install aiohttp duckduckgo-search beautifulsoup4 requests fastapi uvicorn

# Verificar instalación
echo "Verificando instalación..."
"$PROJECT_DIR/venv/bin/python" -c "import aiohttp, duckduckgo_search, bs4, requests; print('Todas las dependencias están instaladas correctamente')" || {
    echo "Error: No se pudieron cargar todas las dependencias"
    exit 1
}

# Ejecutar la aplicación
echo "Iniciando la aplicación..."
exec "$PROJECT_DIR/venv/bin/uvicorn" scraping:app --host 0.0.0.0 --port 8000 --reload
