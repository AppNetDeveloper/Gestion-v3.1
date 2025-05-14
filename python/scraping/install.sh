#!/usr/bin/env bash
# -----------------------------------------------------------------------------
# setup_env.sh – Prepara un entorno Python con FastAPI, Playwright, etc.
# Requiere: Debian/Ubuntu 22.04 o superior. Ejecuta con:
#   ./setup_env.sh
# -----------------------------------------------------------------------------
python3 -m pip install --break-system-packages \
       --ignore-installed typing_extensions==4.13.2

python3 -m pip install --break-system-packages -r requirements.txt

# ------------- Parámetros ----------------------------------------------------
VENV_DIR="venv-email"                # Nombre/directorio del entorno virtual
REQ_FILE="requirements.txt"          # Fichero de dependencias (si existe)
PYTHON_BIN="/usr/bin/python3"        # Ruta a Python
PLAYWRIGHT_CACHE="/var/www/.cache/ms-playwright"

# ------------- Funciones auxiliares -----------------------------------------
info()  { printf "\e[34m[INFO]\e[0m  %s\n" "$*"; }
warn()  { printf "\e[33m[WARN]\e[0m  %s\n" "$*"; }
error() { printf "\e[31m[ERROR]\e[0m %s\n" "$*" >&2; exit 1; }

as_root() {
  if [[ $EUID -ne 0 ]]; then sudo "$@"; else "$@"; fi
}

# ------------- 1. Repositorios APT ------------------------------------------
info "Añadiendo repositorios main, universe, restricted, multiverse…"
as_root add-apt-repository -y main
as_root add-apt-repository -y universe
as_root add-apt-repository -y restricted
as_root add-apt-repository -y multiverse
as_root apt-get update -qq

# ------------- 2. Paquetes de sistema ---------------------------------------
info "Instalando python3-venv, python3-pip y dependencias de Playwright…"
as_root apt-get install -y python3-venv python3-pip

# Dependencias nativas requeridas por Playwright/Chromium
as_root apt-get install -y \
  libgtk-4-1 libgraphene-1.0-0 libgstreamer-gl1.0-0 \
  gstreamer1.0-plugins-bad gstreamer1.0-plugins-base \
  libenchant-2-2 libsecret-1-0 libmanette-0.2-0

# ------------- 3. Entorno virtual -------------------------------------------
if [[ ! -d "$VENV_DIR" ]]; then
  info "Creando entorno virtual “${VENV_DIR}”…"
  "$PYTHON_BIN" -m venv "$VENV_DIR"
else
  warn "Entorno virtual ya existe; se reutilizará."
fi

# Activamos el entorno
# shellcheck disable=SC1090
source "${VENV_DIR}/bin/activate"
info "Entorno virtual activado."

# ------------- 4. Instalación de requisitos ---------------------------------
PIP_FLAGS="--break-system-packages --upgrade pip"
info "Actualizando pip…"
pip install $PIP_FLAGS

# (4a) requirements.txt opcional
if [[ -f "$REQ_FILE" ]]; then
  info "Instalando dependencias de ${REQ_FILE}…"
  pip install --break-system-packages -r "$REQ_FILE"
else
  warn "No se encontró ${REQ_FILE}; se omite."
fi

# (4b) Paquetes adicionales explícitos
info "Instalando paquetes Python solicitados…"
pip install --break-system-packages \
  fastapi uvicorn googlesearch-python beautifulsoup4 requests \
  duckduckgo-search playwright pathlib httpx

# Garantizamos la última versión de duckduckgo-search
pip install --break-system-packages --upgrade duckduckgo-search

# ------------- 5. Playwright -------------------------------------------------
info "Instalando navegadores de Playwright…"
export PLAYWRIGHT_BROWSERS_PATH="$PLAYWRIGHT_CACHE"
python -m playwright install chromium

# En algunos casos se necesitan dependencias extra:
as_root playwright install-deps
as_root playwright install --with-deps

# ------------- 6. Fin --------------------------------------------------------
deactivate
info "¡Entorno listo! Actívalo cuando quieras con:"
printf "    source %s/bin/activate\n" "$VENV_DIR"
