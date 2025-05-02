# -*- coding: utf-8 -*-

# --- Constantes Base URL ---
BASE_EMPRESITE = "https://empresite.eleconomista.es"
BASE_PAGINAS_AMARILLAS = "https://www.paginasamarillas.es"

# --- User Agents ---
# Lista de User-Agents para simular diferentes navegadores/dispositivos
UA_LIST = [
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:125.0) Gecko/20100101 Firefox/125.0",
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36",
    "Mozilla/5.0 (iPhone; CPU iPhone OS 17_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4.1 Mobile/15E148 Safari/604.1"
]

# --- Timeouts (en segundos) ---
# Timeout para peticiones HTTP generales (requests, httpx)
HTTP_TIMEOUT = 20
# Timeout para llamadas a la API de DuckDuckGo
DDG_TIMEOUT = 20
# Timeout para la conexión y carga inicial de Playwright
PLAYWRIGHT_GOTO_TIMEOUT = 90000 # 90 segundos
# Timeout para esperar selectores principales en Playwright (Página 1)
PLAYWRIGHT_SELECTOR_TIMEOUT_P1 = 60000 # 60 segundos
# Timeout para esperar selectores principales en Playwright (Páginas > 1)
PLAYWRIGHT_SELECTOR_TIMEOUT_PN = 75000 # 75 segundos
# Timeout corto para elementos como banners de cookies
PLAYWRIGHT_SHORT_TIMEOUT = 7000 # 7 segundos
# Timeout para enviar el callback
CALLBACK_TIMEOUT = 30.0

# --- Otros ---
# Directorio para guardar screenshots de depuración de Páginas Amarillas
SCREENSHOT_PA_DIR_NAME = "screenshots_pa"

# Número máximo de URLs a procesar concurrentemente con asyncio.gather
# Ajustar según los recursos del servidor. Un valor muy alto puede causar problemas.
MAX_CONCURRENT_URL_FETCHES = 10

