#!/usr/bin/env bash
# -----------------------------------------------------------------------------
# setup_env.sh – Prepara un entorno Python con FastAPI, Playwright, etc.
# Requiere: Debian/Ubuntu 22.04 o superior.
# Uso: sudo ./install.sh
# -----------------------------------------------------------------------------

set -e  # Detener el script si hay algún error

# ------------- Configuración -------------------------------------------------
VENV_DIR="venv"                      # Directorio del entorno virtual
REQ_FILE="requirements.txt"          # Fichero de dependencias
PYTHON_BIN="/usr/bin/python3"        # Ruta a Python
PLAYWRIGHT_CACHE="${HOME}/.cache/ms-playwright"

# ------------- Funciones auxiliares -----------------------------------------
info()  { printf "\e[34m[INFO]\e[0m  %s\n" "$*"; }
warn()  { printf "\e[33m[WARN]\e[0m  %s\n" "$*"; }
error() { printf "\e[31m[ERROR]\e[0m %s\n" "$*" >&2; exit 1; }

# Verificar si el comando existe
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Ejecutar como root si es necesario
as_root() {
    if [[ $EUID -ne 0 ]]; then
        sudo "$@"
    else
        "$@"
    fi
}

# Instalar paquete usando pipx
pipx_install() {
    if ! pipx list | grep -q "$1"; then
        info "Instalando $1 con pipx..."
        pipx install "$1" || warn "No se pudo instalar $1 con pipx"
    else
        info "$1 ya está instalado con pipx"
    fi
}

# Verificar e instalar pipx
install_pipx() {
    if ! command -v pipx >/dev/null 2>&1; then
        info "Instalando pipx..."
        python3 -m pip install --user pipx
        python3 -m pipx ensurepath
        export PATH="$PATH:$HOME/.local/bin"
        
        # Recargar el perfil para asegurar que pipx esté en el PATH
        if [ -f ~/.bashrc ]; then
            source ~/.bashrc
        fi
    fi
    
    # Verificar que pipx esté disponible
    if ! command -v pipx >/dev/null 2>&1; then
        error "No se pudo instalar pipx. Por favor, instálalo manualmente:"
        echo "  python3 -m pip install --user pipx"
        echo "  python3 -m pipx ensurepath"
        exit 1
    fi
}

# Crear o actualizar entorno virtual
setup_venv() {
    local venv_dir="$1"
    
    if [[ ! -d "$venv_dir" ]]; then
        info "Creando entorno virtual en ${venv_dir}..."
        "$PYTHON_BIN" -m venv "$venv_dir"
    else
        warn "El entorno virtual ${venv_dir} ya existe, actualizando..."
    fi
    
    # Activar el entorno virtual
    # shellcheck disable=SC1090
    source "${venv_dir}/bin/activate"
    
    # Actualizar pip y herramientas básicas
    python -m pip install --upgrade pip setuptools wheel
    
    # Instalar dependencias si existen
    if [[ -f "$REQ_FILE" ]]; then
        info "Instalando dependencias desde ${REQ_FILE}..."
        python -m pip install -r "$REQ_FILE"
    else
        warn "No se encontró ${REQ_FILE}, instalando dependencias por defecto..."
        python -m pip install \
            fastapi \
            uvicorn \
            python-multipart \
            pydantic \
            python-dotenv \
            requests \
            beautifulsoup4 \
            duckduckgo-search \
            playwright \
            httpx \
            aiohttp \
            googlesearch-python \
            phonenumbers 
    fi
    
    # Instalar Playwright
    info "Configurando Playwright..."
    export PLAYWRIGHT_BROWSERS_PATH="$PLAYWRIGHT_CACHE"
    python -m playwright install chromium
    python -m playwright install-deps
    
    # Instalar herramientas útiles con pipx
    for pkg in "black" "flake8" "isort" "mypy"; do
        pipx_install "$pkg"
    done
    
    deactivate
}

# ------------- 1. Verificar sistema operativo --------------------------------
if ! command -v apt-get >/dev/null 2>&1; then
    error "Este script solo es compatible con distribuciones basadas en Debian/Ubuntu"
fi

# ------------- 2. Instalar dependencias del sistema -------------------------
info "Actualizando lista de paquetes..."
as_root apt-get update -qq

info "Instalando dependencias del sistema..."
sudo apt-get install -y python3-phonenumbers
as_root apt-get install -y --no-install-recommends \
    python3-venv \
    python3-pip \
    python3-dev \
    libgtk-4-1 \
    libgraphene-1.0-0 \
    libgstreamer-gl1.0-0 \
    gstreamer1.0-plugins-bad \
    gstreamer1.0-plugins-base \
    libenchant-2-2 \
    libsecret-1-0 \
    libmanette-0.2-0 \
    wget \
    curl \
    git \
    pipx

# ------------- 3. Configurar pipx ------------------------------------------
install_pipx

# ------------- 4. Configurar entorno virtual -------------------------------
setup_venv "$VENV_DIR"

# ------------- 5. Verificación final ---------------------------------------
info "Verificando instalación..."
# shellcheck disable=SC1090
source "${VENV_DIR}/bin/activate"
python -c "import fastapi, uvicorn, playwright; print('✓ Todas las dependencias están instaladas correctamente')"
deactivate

# ------------- 6. Mensaje final --------------------------------------------
echo -e "\n\e[32m✅ Instalación completada\e[32m\e[0m"
echo "Para activar el entorno virtual, ejecuta:"
echo "    source ${VENV_DIR}/bin/activate"
echo ""
echo "Para ejecutar el servidor:"
echo "    uvicorn main:app --reload"
echo ""

# Verificar que todo funciona
info "Verificando que todo funciona correctamente..."
if [ -f "test_install.py" ]; then
    source "${VENV_DIR}/bin/activate"
    if python test_install.py; then
        info "✓ Todas las pruebas pasaron correctamente"
    else
        warn "Algunas pruebas fallaron"
    fi
    deactivate
fi

exit 0