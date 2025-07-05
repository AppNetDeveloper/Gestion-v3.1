# -*- coding: utf-8 -*-
# Standard library imports
import asyncio
import hashlib
import json
import logging
import os
import random
import re
import shutil
import time
import uuid
import warnings
from collections import deque, defaultdict
from datetime import datetime, timedelta
from pathlib import Path
from typing import Any, Deque, Dict, List, Optional, Set, Tuple, Union
from urllib.parse import parse_qs, quote_plus, urlencode, urlparse
import urllib.error

# Third-party imports
import aiohttp
import httpx
import requests
from bs4 import BeautifulSoup
from duckduckgo_search import DDGS
from fastapi import BackgroundTasks, Depends, FastAPI, HTTPException, Request
from fastapi.concurrency import run_in_threadpool
from fastapi.middleware.cors import CORSMiddleware
from googlesearch import search as google_search
from playwright.sync_api import TimeoutError as PlaywrightTimeoutError
from playwright.sync_api import sync_playwright
from pydantic import BaseModel
from requests.packages.urllib3.exceptions import InsecureRequestWarning

# Local application imports
from search_engines import search_multiple_engines

# ================== BÚSQUEDA HTML EN DUCKDUCKGO ==================
import aiohttp
from bs4 import BeautifulSoup

async def search_duckduckgo_html(query, num_results=20):
    """
    Realiza una búsqueda en DuckDuckGo usando la web pública y parsea el HTML para extraer URLs.
    """
    search_url = f"https://duckduckgo.com/?t=h_&q={query.replace(' ', '+')}"
    headers = random_headers()
    results = []
    try:
        async with aiohttp.ClientSession(headers=headers) as session:
            async with session.get(search_url, timeout=15) as resp:
                html = await resp.text()
                soup = BeautifulSoup(html, 'html.parser')
                for a in soup.select('a.result__a'):
                    href = a.get('href')
                    if href and href.startswith('http'):
                        results.append(href)
                        if len(results) >= num_results:
                            break
    except Exception as e:
        logger.warning(f"[DuckDuckGo HTML] Error en búsqueda HTML: {e}")
    return results

# Suppress InsecureRequestWarning
warnings.filterwarnings("ignore", category=InsecureRequestWarning)

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.StreamHandler(),
        logging.FileHandler('/var/www/html/storage/logs/scraping.log')
    ]
)

logger = logging.getLogger(__name__)

# Initialize FastAPI app
app = FastAPI(title="Scraping Service", version="1.0.0")

# Add CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Health check endpoint
@app.get("/health")
async def health_check():
    return {"status": "ok", "service": "scraping", "version": "1.0.0"}

from dataclasses import dataclass
import asyncio
from typing import List, Dict, Any, Optional, Tuple, Deque, Set
from collections import deque

@dataclass
class SearchConfig:
    # Configuración general (optimizada para mayor velocidad)
    min_delay: float = 5.0  # Reducido de 30.0 a 5.0 segundos
    max_delay: float = 30.0  # Reducido de 120.0 a 30.0 segundos
    max_retries: int = 3      # Reducido de 5 a 3 reintentos
    backoff_factor: float = 1.5  # Reducido de 2.5 a 1.5 para incrementos más pequeños
    request_timeout: int = 60    # Reducido de 120 a 60 segundos
    max_concurrent_searches: int = 5  # Aumentado de 2 a 5 búsquedas concurrentes
    search_queue_delay: float = 2.0  # Reducido de 10.0 a 2.0 segundos
    
    # Habilitación de motores de búsqueda
    google_enabled: bool = True
    duckduckgo_enabled: bool = True
    gigablast_enabled: bool = True
    bing_enabled: bool = True
    brave_enabled: bool = True
    
    # Número de resultados por motor
    results_per_engine: int = 10
    
    # Tiempos de espera específicos por motor (en segundos)
    google_timeout: int = 90
    duckduckgo_timeout: int = 90
    gigablast_timeout: int = 60
    bing_timeout: int = 60
    brave_timeout: int = 60
    
    def __init__(self):
        # Intentar cargar configuración desde variables de entorno
        import os
        
        # Configuración de habilitación de motores
        self.google_enabled = os.getenv('GOOGLE_ENABLED', str(self.google_enabled)).lower() == 'true'
        self.duckduckgo_enabled = os.getenv('DUCKDUCKGO_ENABLED', str(self.duckduckgo_enabled)).lower() == 'true'
        self.gigablast_enabled = os.getenv('GIGABLAST_ENABLED', str(self.gigablast_enabled)).lower() == 'true'
        self.bing_enabled = os.getenv('BING_ENABLED', str(self.bing_enabled)).lower() == 'true'
        self.brave_enabled = os.getenv('BRAVE_ENABLED', str(self.brave_enabled)).lower() == 'true'
        
        # Configuración de timeouts
        self.google_timeout = int(os.getenv('GOOGLE_TIMEOUT', self.google_timeout))
        self.duckduckgo_timeout = int(os.getenv('DUCKDUCKGO_TIMEOUT', self.duckduckgo_timeout))
        self.gigablast_timeout = int(os.getenv('GIGABLAST_TIMEOUT', self.gigablast_timeout))
        self.bing_timeout = int(os.getenv('BING_TIMEOUT', self.bing_timeout))
        self.brave_timeout = int(os.getenv('BRAVE_TIMEOUT', self.brave_timeout))
        
        # Configuración de resultados
        self.results_per_engine = int(os.getenv('RESULTS_PER_ENGINE', self.results_per_engine))

        # Configuración de proxies
        self.proxies: Optional[List[str]] = os.getenv('PROXIES', None)
        if self.proxies:
            self.proxies = [p.strip() for p in self.proxies.split(',')]

# Importar configuracin
try:
    import config
except ImportError:
    logger.error("No se pudo importar el archivo 'config.py'. Asegrate de que existe en el mismo directorio.")
    # Definir valores por defecto si falla la importacin para evitar ms errores
    class ConfigFallback:
        BASE_EMPRESITE = "https://empresite.eleconomista.es"
        BASE_PAGINAS_AMARILLAS = "https://www.paginasamarillas.es"
        UA_LIST = ["Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36"]
        HTTP_TIMEOUT = 20
        DDG_TIMEOUT = 20
        PLAYWRIGHT_GOTO_TIMEOUT = 90000
        PLAYWRIGHT_SELECTOR_TIMEOUT_P1 = 60000
        PLAYWRIGHT_SELECTOR_TIMEOUT_PN = 75000
        PLAYWRIGHT_SHORT_TIMEOUT = 7000
        CALLBACK_TIMEOUT = 30.0
        SCREENSHOT_PA_DIR_NAME = "screenshots_pa"
        MAX_CONCURRENT_URL_FETCHES = 10
    config = ConfigFallback()

# Suprimir advertencias de InsecureRequestWarning al usar verify=False
from requests.packages.urllib3.exceptions import InsecureRequestWarning
warnings.filterwarnings("ignore", category=InsecureRequestWarning)
warnings.filterwarnings("ignore", category=UserWarning, module='googlesearch')

# Configurar el nivel de logging de urllib3 a WARNING para reducir ruido
logging.getLogger("urllib3").setLevel(logging.WARNING)
logging.getLogger("httpx").setLevel(logging.WARNING)

# Inicializar variables globales para el sistema de cola de búsquedas
search_queue = asyncio.Queue()
active_searches: Set[str] = set()  # Para evitar búsquedas duplicadas
search_lock = asyncio.Lock()  # Bloqueo para operaciones en active_searches
search_config = SearchConfig()  # Configuración de búsqueda
current_tasks = set()  # Track currently running tasks

app = FastAPI(
    title="Buscador Multi-Fuente Asncrono v3.4 (Callbacks, Concurrencia, Config)",
    description="Extrae datos de Google, DDG, Empresite, P. Amarillas. Soporta callbacks y procesamiento concurrente.",
    version="3.4.2",
    docs_url="/docs",  # Habilita la interfaz Swagger UI en /docs
    redoc_url="/redoc",  # Habilita la documentacin ReDoc en /redoc
    openapi_url="/openapi.json"  # Especifica la ruta del esquema OpenAPI
)

@app.get("/health")
async def health_check():
    """Endpoint de verificación de estado del servicio."""
    return {"status": "ok", "service": "scraping-service", "version": "3.4.2"}

# --- Modelos de Datos ---
class KeywordRequest(BaseModel):
    keyword: str
    results: int = 10
    callback_url: Optional[str] = None

class DirectoryRequest(BaseModel):
    actividad: str
    provincia: str
    paginas: int = 1
    callback_url: Optional[str] = None

def random_headers():
    """Genera cabeceras HTTP aleatorias usando UA_LIST de config."""
    user_agent = random.choice(config.UA_LIST)
    return {
        'User-Agent': user_agent,
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language': 'en-US,en;q=0.5',
        'DNT': '1',
        'Connection': 'keep-alive',
        'Upgrade-Insecure-Requests': '1'
    }

# ================== CONFIGURACIÓN DE CRAWLING PROFUNDO ==================
MAX_CRAWL_DEPTH = 2           # Profundidad máxima de crawling
MAX_CRAWL_PAGES = 20          # Máximo de páginas por dominio
CRAWL_TIMEOUT_PER_PAGE = 15   # Timeout por página (segundos)
MAX_CRAWL_CONCURRENCY = 5     # Máximas peticiones simultáneas

# ================== EXTRACTOR MEJORADO DE EMAILS ==================
def extract_emails_from_html(html_content: str, soup: BeautifulSoup) -> List[str]:
    """
    Extrae direcciones de correo electrónico de forma robusta desde el HTML y texto visible.
    Incluye mailto, texto visible, atributos data-email y comentarios, y detecta ofuscaciones comunes.
    """
    # Limpiar scripts y estilos
    for script_or_style in soup(["script", "style"]):
        script_or_style.decompose()

    all_emails = set()

    # 1. mailto:
    for a in soup.select('a[href^="mailto:"]'):
        try:
            mailto_href = a.get('href', '')
            email = mailto_href.split('mailto:')[-1].split('?')[0].strip()
            if email:
                all_emails.add(email.lower())
        except Exception:
            pass

    # 2. data-email y atributos
    for tag in soup.find_all(attrs={"data-email": True}):
        email = tag.get("data-email", "").strip()
        if email:
            all_emails.add(email.lower())

    # 3. Comentarios HTML
    comments = soup.find_all(string=lambda text: isinstance(text, type(soup.comment)))
    for comment in comments:
        found = re.findall(r'[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}', comment)
        for email in found:
            all_emails.add(email.lower())

    # 4. Texto visible (con ofuscaciones)
    try:
        visible_text = soup.body.get_text(separator=' ', strip=True)
        visible_text = visible_text.replace('[at]', '@').replace('(at)', '@')
        visible_text = visible_text.replace('[dot]', '.').replace('(dot)', '.')
        visible_text = visible_text.replace(' arroba ', '@').replace(' punto ', '.')
        email_pattern = r'\b[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}\b'
        found_emails = re.findall(email_pattern, visible_text, re.IGNORECASE)
        for email in found_emails:
            all_emails.add(email.lower())
    except Exception as e:
        logger.warning(f"[Email Extract] Error al buscar emails en texto: {e}")

    # 5. Regex flexible para emails ofuscados
    try:
        flexible_pattern = r'([a-zA-Z0-9._%+-]+\s*(?:@|\[at\]|\(at\)|\sarroba\s)\s*[a-zA-Z0-9.-]+\s*(?:\.|\[dot\]|\(dot\)|\spunto\s)\s*[a-zA-Z]{2,})'
        visible_text = soup.body.get_text(separator=' ', strip=True)
        for match in re.findall(flexible_pattern, visible_text, re.IGNORECASE):
            clean = (
                match.replace('[at]', '@').replace('(at)', '@').replace(' arroba ', '@')
                .replace('[dot]', '.').replace('(dot)', '.').replace(' punto ', '.')
                .replace(' ', '')
            )
            if '@' in clean and '.' in clean:
                all_emails.add(clean.lower())
    except Exception as e:
        logger.warning(f"[Email Extract] Error en regex flexible: {e}")

    # Filtrar emails inválidos
    return _filter_emails(list(all_emails))

# ================== CRAWLER PROFUNDO ASÍNCRONO ==================
async def deep_scrape_url_async(url, client, max_depth=MAX_CRAWL_DEPTH, max_pages=MAX_CRAWL_PAGES, visited=None, depth=0, found_emails=None, found_phones=None):
    """
    Crawling profundo: recorre enlaces internos hasta max_depth y max_pages, extrayendo emails/teléfonos de cada página.
    """
    from urllib.parse import urlparse, urljoin
    if visited is None:
        visited = set()
    if found_emails is None:
        found_emails = set()
    if found_phones is None:
        found_phones = set()

    if url in visited or len(visited) >= max_pages or depth > max_depth:
        return found_emails, found_phones
    visited.add(url)

    try:
        resp = await client.get(url, timeout=CRAWL_TIMEOUT_PER_PAGE, follow_redirects=True)
        if resp.status_code != 200 or 'text/html' not in resp.headers.get('content-type', ''):
            return found_emails, found_phones
        html = resp.text
        soup = BeautifulSoup(html, 'html.parser')
        # Extraer emails/teléfonos
        emails = extract_emails_from_html(html, soup)
        for e in emails:
            found_emails.add(e)
        contact = extract_contact_info(html, soup)
        for p in contact.get('phones', []):
            found_phones.add(p)
        # Extraer enlaces internos
        base_domain = urlparse(url).netloc
        links = set()
        for a in soup.find_all('a', href=True):
            link = urljoin(url, a['href'])
            parsed = urlparse(link)
            if parsed.scheme.startswith('http') and parsed.netloc == base_domain:
                # Filtrar archivos
                if not re.search(r'\.(pdf|jpg|jpeg|png|zip|rar|doc|docx|xls|xlsx|ppt|pptx|mp3|mp4|avi|mov|exe|svg)$', parsed.path, re.IGNORECASE):
                    links.add(link.split('#')[0].rstrip('/'))
        # Recursividad controlada
        for link in links:
            if len(visited) >= max_pages:
                break
            await deep_scrape_url_async(link, client, max_depth, max_pages, visited, depth+1, found_emails, found_phones)
    except Exception as e:
        logger.warning(f"[DeepCrawler] Error en {url}: {e}")
    return found_emails, found_phones


def extract_contact_info(html_content: str, soup: BeautifulSoup) -> Dict[str, Any]:
    """
    Extrae información de contacto (emails y teléfonos) del contenido HTML de forma robusta.
    """
    # Descomponer scripts y estilos para limpiar el HTML
    for script_or_style in soup(["script", "style"]):
        script_or_style.decompose()

    # Extraer correos electrónicos
    try:
        emails = extract_emails_from_html(html_content, soup)
    except Exception as e:
        logger.error(f"[Extract Contact] Error extrayendo correos: {e}")
        emails = []

    # Extraer números de teléfono
    phones = set()
    try:
        # 1. Buscar en enlaces tel:
        for a in soup.select('a[href^="tel:"]'):
            try:
                phone_href = a.get('href', '').split('tel:')[-1].strip()
                # Normalizar el número
                normalized_phone = re.sub(r'[^0-9]', '', phone_href)
                if len(normalized_phone) >= 9:
                    phones.add(normalized_phone)
            except Exception:
                pass

        # 2. Buscar en el texto visible con un patrón estricto
        phone_pattern = r'(?:(?:\+|00)34[ -.]*)?([6789]\d{2}[ -.]?\d{3}[ -.]?\d{3})\b'
        visible_text = soup.body.get_text(separator=' ', strip=True)
        
        matches = re.finditer(phone_pattern, visible_text)
        for match in matches:
            pure_phone_digits = re.sub(r'[^0-9]', '', match.group(1))
            if len(pure_phone_digits) == 9:
                phones.add(pure_phone_digits)

    except Exception as e:
        logger.error(f"[Extract Contact] Error extrayendo teléfonos: {e}")

    return {
        "emails": emails,
        "phones": list(phones)
    }

def _filter_emails(emails: List[str]) -> List[str]:
    """
    Filtra y limpia direcciones de correo electrónico con reglas más estrictas.
    """
    if not emails:
        return []
        
    valid_emails = set()
    # Patrón de validación de email estricto
    email_pattern = re.compile(r"^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$")
    
    # Extensiones de archivo comunes para descartar falsos positivos
    discarded_extensions = {
        'png', 'jpg', 'jpeg', 'gif', 'bmp', 'svg', 'webp', 'ico', 'pdf',
        'js', 'css', 'html', 'php', 'asp', 'xml', 'json', 'txt', 'woff', 'woff2', 'ttf',
        'zip', 'rar', 'exe', 'dll', 'bin', 'dat', 'tmp', 'log', 'bak', 'old'
    }
    
    # Dominios de spam o no válidos
    spam_domains = {
        'example.com', 'domain.com', 'email.com', 'sentry.io', 'wixpress.com',
        'localhost', 'yourdomain.com', 'company.com', 'site.com', 'test.com'
    }

    for email in emails:
        email = email.strip().lower()
        
        if not email_pattern.match(email):
            continue
            
        try:
            local_part, domain = email.split('@', 1)
            
            # Descartar si el dominio es una IP
            if re.match(r'^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$', domain):
                continue
            
            # Descartar si el TLD es una extensión de archivo
            tld = domain.split('.')[-1]
            if tld in discarded_extensions:
                continue
            
            # Descartar dominios de spam
            if domain in spam_domains:
                continue
            
            # Descartar si el nombre local parece un placeholder
            if local_part in ['info', 'contact', 'admin', 'test', 'email']:
                # Podría ser legítimo, pero a menudo es un placeholder
                pass

            valid_emails.add(email)
        except (ValueError, IndexError):
            continue # Ignorar emails malformados

    return sorted(list(valid_emails))

def extract_data_from_url(url: str) -> Dict[str, Any]:
    """Extrae nombre, correos y telfono de una URL usando requests (SNCRONO)."""
    data = {
        "nombre": "No encontrado",
        "correos": [],
        "telefono": "No encontrado"
    }
    
    try:
        headers = random_headers()
        response = requests.get(url, headers=headers, timeout=config.HTTP_TIMEOUT, verify=False)
        response.raise_for_status()
        
        soup = BeautifulSoup(response.text, 'html.parser')
        
        # Extraer ttulo de la pgina
        if soup.title and soup.title.string:
            data["nombre"] = soup.title.string.strip()
        
        # Extraer correos electrnicos
        email_pattern = r'[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}'
        emails = re.findall(email_pattern, response.text)
        data["correos"] = _filter_emails(emails)
        
        # Extraer telfonos (patrn simple)
        phone_pattern = r'(?:\+34|0034|34)?[ -]*(6|7|8|9)[0-9 -]{7,}[0-9]'
        phones = re.findall(phone_pattern, response.text)
        if phones:
            # Tomar el primer teléfono encontrado y formatearlo
            phone = re.sub(r'[^0-9]', '', ''.join(phones[0]))
            if len(phone) >= 9:  # Asegurar que tenga al menos 9 dígitos
                data["telefono"] = f"+34 {phone[:3]} {phone[3:6]} {phone[6:]}"
    except Exception as e:
        logger.error(f"[Extract] Error procesando {url}: {e}")
    
    return data

def is_linkedin_url(url: str) -> bool:
    """Verifica si una URL es de LinkedIn."""
    parsed_url = urlparse(url)
    return 'linkedin.com' in parsed_url.netloc

async def extract_data_from_url_async(url: str, client: httpx.AsyncClient) -> Dict[str, Any]:
    """
    Extrae nombre, todos los correos y teléfonos de una URL usando crawling profundo (asíncrono).
    Args:
        url: URL de la que extraer la información
        client: Cliente HTTP asíncrono
    Returns:
        Diccionario con los datos extraídos (nombre, correos, teléfono, url)
    """
    data = {
        "nombre": "No encontrado",
        "correos": [],
        "telefono": "No encontrado",
        "url": url
    }
    try:
        logger.info(f"[Deep Async Extract] Iniciando crawling profundo en: {url}")
        # Usar el crawler profundo
        emails, phones = await deep_scrape_url_async(url, client)
        emails = list(set(emails))
        phones = list(set(phones))
        if emails:
            logger.info(f"[Deep Async Extract] {len(emails)} correos encontrados en crawling profundo de {url}")
            data["correos"] = emails
        if phones:
            logger.info(f"[Deep Async Extract] {len(phones)} teléfonos encontrados en crawling profundo de {url}")
            # Guardar el primero como teléfono principal, el resto quedan en la lista
            data["telefono"] = phones[0] if phones else "No encontrado"
        # Obtener título de la página raíz si es posible
        try:
            resp = await client.get(url, timeout=CRAWL_TIMEOUT_PER_PAGE, follow_redirects=True)
            if resp.status_code == 200 and 'text/html' in resp.headers.get('content-type', ''):
                soup = BeautifulSoup(resp.text, 'html.parser')
                if soup.title and soup.title.string:
                    title = soup.title.string.strip()
                    data["nombre"] = ' '.join(title.split())[:255]
        except Exception:
            pass
    except Exception as e:
        logger.error(f"[Deep Async Extract] Error en crawling profundo de {url}: {e}")
    return data

    # # Saltar LinkedIn ya que bloquea las peticiones
    # if is_linkedin_url(url):
    #     logger.info(f"[Async Extract] Saltando LinkedIn: {url}")
    #     return data
        
    try:
        logger.info(f"[Async Extract] Procesando URL: {url}")
        headers = random_headers()
        
        # Configurar timeout y redirecciones (optimizado)
        timeout = httpx.Timeout(20.0, connect=30.0)  # Reducido de 30.0 a 20.0 y de 60.0 a 30.0
        
        # Realizar la petición con manejo de reintentos (optimizado)
        max_retries = 2
        for attempt in range(max_retries):
            try:
                response = await client.get(
                    url,
                    headers=headers,
                    follow_redirects=True,
                    timeout=timeout
                )
                response.raise_for_status()
                break  # Si la petición es exitosa, salir del bucle de reintentos
            except Exception as e:
                if attempt == max_retries - 1:  # Último intento
                    logger.error(f"[Async Extract] Error en petición HTTP a {url} (intento {attempt + 1}/{max_retries}): {e}")
                    return data
                await asyncio.sleep(0.5)  # Reducido de 1 a 0.5 segundos
        
        # Verificar si es una respuesta HTML
        content_type = response.headers.get('content-type', '').lower()
        if 'text/html' not in content_type:
            logger.warning(f"[Async Extract] Contenido no HTML en {url}: {content_type}")
            return data
        
        # Detectar la codificación de la respuesta
        encoding = response.encoding or 'utf-8'
        
        # Usar el parser de manera segura
        try:
            # Intentar con el encoding detectado
            content = response.content
            try:
                # Primero intentar con el encoding de la respuesta
                html_content = content.decode(encoding, errors='replace')
                soup = BeautifulSoup(html_content, 'html.parser')
            except UnicodeDecodeError:
                # Si falla, intentar con otros encodings comunes
                for enc in ['utf-8', 'iso-8859-1', 'windows-1252']:
                    try:
                        html_content = content.decode(enc, errors='replace')
                        soup = BeautifulSoup(html_content, 'html.parser')
                        break
                    except UnicodeDecodeError:
                        continue
                else:
                    # Si todos los intentos fallan, forzar el parsing
                    soup = BeautifulSoup(content, 'html.parser', from_encoding='utf-8', exclude_encodings=[])
        except Exception as e:
            logger.error(f"[Async Extract] Error al analizar HTML de {url}: {e}")
            return data
        
        # Extraer título de la página
        if soup.title and soup.title.string:
            title = soup.title.string.strip()
            # Limpiar el título (eliminar espacios extras, saltos de línea, etc.)
            title = ' '.join(title.split())
            data["nombre"] = title[:255]  # Limitar la longitud del título
        
        # Extraer información de contacto (emails y teléfonos)
        try:
            contact_info = extract_contact_info(response.text, soup)
            
            # Procesar correos electrónicos
            emails = contact_info.get("emails", [])
            if emails:
                logger.info(f"[Async Extract] Encontrados {len(emails)} correos en {url}")
                data["correos"] = list(set(emails))  # Eliminar duplicados
            
            # Procesar teléfonos
            phones = contact_info.get("phones", [])
            if phones:
                logger.info(f"[Async Extract] Encontrados {len(phones)} teléfonos en {url}")
                # Tomar el primer teléfono y formatearlo
                phone = phones[0]
                if len(phone) == 9:  # Aseguramos que sea un número de 9 dígitos
                    data["telefono"] = phone  # Guardamos solo el número puro de 9 dígitos
                else:
                    data["telefono"] = "No encontrado" # Si no cumple el formato, se considera no encontrado
            
        except Exception as e:
            logger.error(f"[Async Extract] Error al extraer información de contacto de {url}: {e}", exc_info=True)
            
    except httpx.HTTPStatusError as e:
        logger.warning(f"[Async Extract] Error HTTP {e.response.status_code} al acceder a {url}")
    except httpx.RequestError as e:
        logger.warning(f"[Async Extract] Error de conexión al acceder a {url}: {e}")
    except Exception as e:
        logger.error(f"[Async Extract] Error inesperado al procesar {url}: {e}", exc_info=True)
    
    return data

def duckduckgo_search_sync(q: str, n: int) -> List[str]:
    """Realiza una bsqueda SNCRONA en DuckDuckGo y devuelve URLs."""
    results = []
    logger.info(f"[DDG Sync] Buscando: '{q}'")
    try:
        headers = random_headers()
        with DDGS(headers=headers, timeout=config.DDG_TIMEOUT) as ddgs:
            sync_results = ddgs.text(q, max_results=n)
            for r in sync_results:
                if "href" in r:
                    results.append(r["href"])
    except Exception as e:
        logger.error(f"[DDG Sync] Error durante la bsqueda: {e}")
    logger.info(f"[DDG Sync] Encontradas {len(results)} URLs.")
    return results

async def safe_google_search(query: str, num_results: int, lang: str = 'es', timeout: int = 120, user_agent: str = None, max_retries: int = 5):
    """
    Realiza una búsqueda en Google con manejo de errores, reintentos y respeto por los rate limits.
    
    Args:
        query: Término de búsqueda
        num_results: Número máximo de resultados a devolver
        lang: Idioma de la búsqueda (por defecto: 'es')
        timeout: Tiempo máximo de espera por intento (segundos)
        user_agent: User-Agent a utilizar (si no se especifica, se elige uno aleatorio)
        max_retries: Número máximo de reintentos
        
    Returns:
        Lista de URLs de resultados o lista vacía en caso de error
    """
    headers = {
        'User-Agent': user_agent or random.choice(config.UA_LIST),
        'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language': 'en-US,en;q=0.5',
        'DNT': '1',
        'Connection': 'keep-alive',
        'Upgrade-Insecure-Requests': '1'
    }
    
    last_error = None
    for attempt in range(max_retries):
        try:
            # Aumentar el tiempo de espera exponencialmente con jitter
            if attempt > 0:
                base_delay = min(60 * (2 ** attempt), 300)  # Máximo 5 minutos
                jitter = random.uniform(0.8, 1.2)
                wait_time = base_delay * jitter
                logger.warning(f"[Google Search] Reintentando en {wait_time:.1f} segundos (intento {attempt + 1}/{max_retries})...")
                await asyncio.sleep(wait_time)
            
            # Realizar la búsqueda con un timeout específico
            search_timeout = min(timeout, 120)  # Máximo 2 minutos por intento
            logger.info(f"[Google Search] Realizando búsqueda: '{query}' (timeout: {search_timeout}s)")
            
            # Configurar el User-Agent globalmente para requests
            import os
            import requests
            from requests.adapters import HTTPAdapter
            from urllib3.util.retry import Retry
            
            # Guardar el User-Agent original
            original_user_agent = requests.utils.default_user_agent()
            
            try:
                # Configurar el User-Agent global para todas las peticiones
                os.environ['USER_AGENT'] = headers['User-Agent']
                
                # Realizar la búsqueda con los parámetros soportados
                results = list(google_search(
                    query,
                    num_results=num_results,
                    lang=lang,
                    timeout=search_timeout,
                    # Asegurar que no se use SSL verification para evitar problemas
                    ssl_verify=False
                ))
            finally:
                # Restaurar el User-Agent original
                os.environ['USER_AGENT'] = original_user_agent
            
            if results:
                logger.info(f"[Google Search] Búsqueda exitosa. Encontradas {len(results)} URLs")
                return results
            else:
                logger.warning("[Google Search] La búsqueda no devolvió resultados")
                return []
            
        except urllib.error.HTTPError as e:
            last_error = e
            error_msg = str(e).replace('\n', ' ')
            # Intentar leer el cuerpo de la respuesta para obtener más detalles
            response_body = ""
            try:
                response_body = e.read().decode('utf-8', errors='ignore')
            except Exception as read_err:
                response_body = f"(No se pudo leer la respuesta: {read_err})"
            
            logger.error(f"[Google Search] Error HTTP en el intento {attempt + 1}/{max_retries}: {error_msg} - Respuesta: {response_body[:500]}")

            if e.code == 429:
                wait_time = 300  # 5 minutos de espera para rate limiting
                logger.warning(f"[Google Search] Rate limit (429) alcanzado. Esperando {wait_time} segundos...")
                await asyncio.sleep(wait_time)
            elif attempt < max_retries - 1:
                wait_time = random.uniform(10, 30)
                await asyncio.sleep(wait_time)
        except Exception as e:
            last_error = e
            error_msg = str(e).replace('\n', ' ')
            logger.error(f"[Google Search] Error general en el intento {attempt + 1}/{max_retries}: {error_msg}")
            
            if attempt < max_retries - 1:  # No esperar en el último intento fallido
                wait_time = random.uniform(10, 30)
                await asyncio.sleep(wait_time)
    
    # Si llegamos aquí, todos los intentos fallaron
    logger.error(f"[Google Search] Error después de {max_retries} intentos: {last_error}")
    return []

async def send_callback(callback_url: str, payload: Dict[str, Any]):
    """Funcin auxiliar para enviar el resultado al callback_url."""
    if not callback_url:
        return
        
    payload["timestamp_completion"] = time.strftime("%Y-%m-%d %H:%M:%S %Z")
    async with httpx.AsyncClient(timeout=config.CALLBACK_TIMEOUT, verify=False) as client:
        try:
            response = await client.post(callback_url, json=payload)
            response.raise_for_status()
            logger.info(f"Callback enviado exitosamente a {callback_url} (Task ID: {payload.get('task_id', 'N/A')}), Status: {response.status_code}")
        except httpx.RequestError as e:
            logger.error(f"Error al enviar callback a {callback_url} (Task ID: {payload.get('task_id', 'N/A')}): {e}")
        except Exception as e:
            logger.error(f"Error inesperado durante el envo del callback a {callback_url} (Task ID: {payload.get('task_id', 'N/A')}): {e}")

async def run_google_ddg_task(keyword: str, results_num: int, callback_url: str, task_id: str):
    """Tarea de fondo para buscar en Google/DDG y enviar callback."""
    logger.info(f"[BG Task Google/DDG - {task_id}] Iniciando para keyword: '{keyword}'")
    start_time = time.time()
    error_message = None
    resultados_finales = {}
    processed_urls_count = 0
    urls_con_datos = 0
    
    try:
        google_urls = []
        search_config = SearchConfig(
            min_delay=config.GOOGLE_SEARCH_PAUSE_MIN,
            max_delay=config.GOOGLE_SEARCH_PAUSE_MAX
        )
        
        last_error = None
        for attempt in range(search_config.max_retries):
            try:
                # Calcular delay exponencial con jitter
                delay = min(
                    search_config.min_delay * (search_config.backoff_factor ** attempt),
                    search_config.max_delay
                )
                jitter = random.uniform(0.8, 1.2)  # Aadir jitter aleatorio
                actual_delay = delay * jitter
                
                logger.info(f"[Google Search] Intento {attempt + 1}/{search_config.max_retries} - Esperando {actual_delay:.1f}s")
                await asyncio.sleep(actual_delay)
                
                # Rotar User-Agent y realizar bsqueda segura
                user_agent = random.choice(config.UA_LIST)
                search_results = await safe_google_search(
                    keyword,  # query (primer argumento posicional)
                    results_num,  # num_results (segundo argumento)
                    'es',  # lang (tercer argumento)
                    search_config.request_timeout,  # timeout (cuarto argumento)
                    user_agent,  # user_agent (quinto argumento)
                    max_retries=search_config.max_retries  # nmero mximo de reintentos
                )
                
                google_urls = search_results if search_results else []
                logger.info(f"[Google Search] Bsqueda exitosa. Encontradas {len(google_urls)} URLs")
                break  # Salir del bucle si la bsqueda fue exitosa
                
            except Exception as e:
                last_error = e
                logger.error(f"[Google Search] Error en intento {attempt + 1}: {str(e)}")
                if attempt == search_config.max_retries - 1:
                    logger.error("[Google Search] Se agotaron los intentos de bsqueda")
                    raise last_error
        
        # Continuar con el resto de la funcin...
        ddg_urls = await run_in_threadpool(duckduckgo_search_sync, keyword, results_num)
        urls = list(set(google_urls + ddg_urls))
        logger.info(f"[BG Task Google/DDG - {task_id}] Encontradas {len(urls)} URLs nicas para '{keyword}'")
        
        async with httpx.AsyncClient(verify=False) as client:
            tasks = [extract_data_from_url_async(url, client) for url in urls]
            chunk_size = config.MAX_CONCURRENT_URL_FETCHES
            all_extracted_data = []
            
            for i in range(0, len(tasks), chunk_size):
                chunk = tasks[i:i + chunk_size]
                logger.info(f"[BG Task Google/DDG - {task_id}] Procesando chunk de {len(chunk)} URLs...")
                results_chunk = await asyncio.gather(*chunk, return_exceptions=True)
                all_extracted_data.extend(results_chunk)
                await asyncio.sleep(0.5)
        
        processed_urls_count = len(urls)
        
        for i, result in enumerate(all_extracted_data):
            url = urls[i]
            if isinstance(result, Exception):
                logger.error(f"[BG Task Google/DDG - {task_id}] Error procesando URL {url}: {result}")
            elif isinstance(result, dict):
                if result.get("correos") or (result.get("telefono") and result.get("telefono") != "No encontrado"):
                    urls_con_datos += 1
                    resultados_finales[url] = {
                        "nombre": result.get("nombre", "No encontrado"),
                        "correos": result.get("correos", []),
                        "telefono": result.get("telefono", "No encontrado")
                    }
            else:
                logger.warning(f"[BG Task Google/DDG - {task_id}] Resultado inesperado para URL {url}: {type(result)}")
                
    except Exception as e:
        error_message = f"Error general en la tarea: {e}"
        logger.error(f"[BG Task Google/DDG - {task_id}] {error_message}")
    
    end_time = time.time()
    duration = round(end_time - start_time, 2)
    logger.info(f"[BG Task Google/DDG - {task_id}] Tarea completada en {duration} segundos.")
    
    callback_payload = {
        "task_id": task_id,
        "status": "failed" if error_message else "completed",
        "error_message": error_message,
        "duration_seconds": duration,
        "fuente": "google_ddg",
        "keyword": keyword,
        "urls_procesadas": processed_urls_count,
        "urls_con_datos_encontrados": urls_con_datos,
        "resultados": resultados_finales
    }
    
async def duckduckgo_search_async(q: str, n: int) -> List[str]:
    """Realiza una búsqueda asíncrona en DuckDuckGo y devuelve URLs."""
    logger.info(f"[DDG Async] Iniciando búsqueda: '{q}'")
    try:
        # Usar run_in_threadpool para ejecutar la función síncrona en un thread separado
        return await run_in_threadpool(duckduckgo_search_sync, q, n)
    except Exception as e:
        logger.error(f"[DDG Async] Error en la búsqueda: {str(e)}")
        return []

async def run_google_ddg_limpio_task(keyword: str, results_num: int, callback_url: str, task_id: str):
    """
    Tarea de fondo para buscar en múltiples motores de búsqueda (Google, DDG, Gigablast, Bing, Brave) y enviar callback.
    
    Args:
        keyword: Término de búsqueda
        results_num: Número de resultados a devolver por motor
        callback_url: URL para enviar los resultados
        task_id: Identificador único de la tarea
    """
    logger.info(f"[Task {task_id}] Iniciando búsqueda para keyword: '{keyword}'")
    start_time = time.time()
    error_message = None
    flat_results = []
    processed_urls_count = 0
    all_urls = []
    
    try:
        search_config = SearchConfig()
        
        # Determinar qué motores de búsqueda usar (solo Google y Bing)
        engines = []
        if search_config.google_enabled:
            engines.append("google")
        if search_config.duckduckgo_enabled:
            engines.append("duckduckgo")
        if search_config.bing_enabled:
            engines.append("bing")
            
        if not engines:
            raise ValueError("No hay motores de búsqueda habilitados en la configuración")
        
        # Configurar timeouts personalizados (solo para Google y Bing)
        timeouts = {
            'google': {
                'timeout': search_config.google_timeout,
                'max_retries': 3,
                'retry_delay': 10
            },
            'bing': {
                'timeout': search_config.bing_timeout,
                'max_retries': 2,
                'retry_delay': 5
            }
        }
            
        logger.info(f"[Task {task_id}] Usando motores de búsqueda: {', '.join(engines)}")
        logger.info(f"[Task {task_id}] Configuración de timeouts: {timeouts}")
        logger.info(f"[Task {task_id}] URL de callback: {callback_url}")
        
        # Realizar búsqueda en todos los motores configurados
        try:
            search_results = await search_multiple_engines(
                query=keyword,
                engines=engines,
                num_results=search_config.results_per_engine,
                timeouts=timeouts,
                search_config=search_config # Pasar la instancia de SearchConfig
            )
        except Exception as e:
            logger.error(f"[Task {task_id}] Error en la búsqueda: {str(e)}", exc_info=True)
            search_results = {}
        
        # Procesar resultados
        all_urls = []
        for engine, urls in search_results.items():
            if isinstance(urls, list) and urls:  # Solo agregar si hay resultados y es una lista
                logger.info(f"[Task {task_id}] {engine.upper()} devolvió {len(urls)} resultados")
                all_urls.extend(urls)
            elif isinstance(urls, list) and not urls:
                logger.info(f"[Task {task_id}] {engine.upper()} devolvió resultados vacíos (0 resultados).")
            else:
                logger.warning(f"[Task {task_id}] {engine.upper()} no devolvió resultados o devolvió un tipo inesperado: {type(urls)}")
        
        # Eliminar duplicados manteniendo el orden
        seen = set()
        all_urls = [url for url in all_urls if not (url in seen or seen.add(url))]
        
        logger.info(f"[Task {task_id}] Total de URLs únicas encontradas: {len(all_urls)}")
        
        # Limitar el número de URLs si es necesario
        max_urls = min(results_num * 10, 200)  # Aumentado a 10x el número de resultados o 200 URLs como máximo
        if len(all_urls) > max_urls:
            logger.info(f"[Task {task_id}] Limitando a {max_urls} URLs para procesar")
            all_urls = all_urls[:max_urls]
        
        # Procesar URLs para extraer información de contacto (optimizado)
        if all_urls:
            logger.info(f"[Task {task_id}] Procesando {len(all_urls)} URLs para extraer información de contacto...")
        
            # Configuración de timeouts para las peticiones HTTP (optimizado)
            timeout = httpx.Timeout(20.0, connect=30.0)  # Reducido de 30.0 a 20.0 y de 60.0 a 30.0
        
            # Procesar en lotes para no sobrecargar la memoria (optimizado)
            batch_size = 10  # Aumentado de 5 a 10 para procesar más URLs por lote
            semaphore = asyncio.Semaphore(10)  # Aumentado de 5 a 10 peticiones concurrentes
        
            async def process_url(url: str) -> Optional[Dict[str, Any]]:
                async with semaphore:
                    try:
                        async with httpx.AsyncClient(timeout=timeout) as client:
                            result = await extract_data_from_url_async(url, client)
        
                            # Verificar si se encontraron datos válidos
                            has_emails = bool(result.get("correos"))
                            has_phone = result.get("telefono") and result.get("telefono") != "No encontrado"
        
                            if has_emails or has_phone:
                                return {
                                    "url": url,
                                    "nombre": result.get("nombre", "No encontrado"),
                                    "correos": result.get("correos", []),
                                    "telefono": result.get("telefono", "No encontrado")
                                }
                            return None
        
                    except Exception as e:
                        logger.error(f"[Task {task_id}] Error al procesar URL {url}: {str(e)}")
                        return None
        
            # Procesar todas las URLs en lotes
            for i in range(0, len(all_urls), batch_size):
                batch = all_urls[i:i + batch_size]
                logger.info(f"[Task {task_id}] Procesando lote {i//batch_size + 1}/{(len(all_urls)-1)//batch_size + 1}")
        
                # Procesar el lote actual
                tasks = [process_url(url) for url in batch]
                batch_results = await asyncio.gather(*tasks, return_exceptions=True)
        
                # Filtrar resultados exitosos
                for result in batch_results:
                    if isinstance(result, dict):
                        flat_results.append(result)
                        logger.info(f"[Task {task_id}] Datos encontrados en {result.get('url')}")
        
                # Pequeña pausa entre lotes (optimizado)
                if i + batch_size < len(all_urls):
                    await asyncio.sleep(0.5)  # Reducido de 2 a 0.5 segundos
            
            logger.info(f"[Task {task_id}] Procesamiento completado. URLs con datos: {len(flat_results)}/{len(all_urls)}")
        else:
            logger.warning(f"[Task {task_id}] No se encontraron URLs para procesar")
        
    except Exception as e:
        error_message = f"Error en la tarea de búsqueda: {str(e)}"
        logger.error(f"[Task {task_id}] {error_message}", exc_info=True)
    
    # Preparar el payload para el callback
    end_time = time.time()
    duration = round(end_time - start_time, 2)
    processed_urls_count = len(all_urls)

    # Aplanar los resultados para la clave 'empresas' (un registro por correo/teléfono)
    empresas_flat = []
    for result in flat_results:
        correos = result.get("correos", [])
        telefono = result.get("telefono", "No encontrado")
        nombre = result.get("nombre", "No encontrado")
        url = result.get("url", "")

        if correos:
            for correo in correos:
                empresas_flat.append({
                    "url": url,
                    "nombre": nombre,
                    "correo": correo,
                    "telefono": telefono
                })
        elif telefono != "No encontrado":
            empresas_flat.append({
                "url": url,
                "nombre": nombre,
                "correo": "No encontrado",
                "telefono": telefono
            })

    # Crear los diccionarios 'datos' y 'resultados' (agrupados por URL)
    datos_by_url = {
        result["url"]: {
            "nombre": result.get("nombre", "No encontrado"),
            "correos": result.get("correos", []),
            "telefono": result.get("telefono", "No encontrado")
        } for result in flat_results
    }

    # Estadísticas
    total_emails = sum(len(result.get("correos", [])) for result in flat_results)
    total_phones = sum(1 for result in flat_results if result.get("telefono") != "No encontrado")
    
    logger.info(f"[Task {task_id}] Resumen: {len(flat_results)} URLs con datos, {total_emails} correos, {total_phones} teléfonos")
    
    # Crear el payload para el callback
    payload = {
        "task_id": task_id,
        "status": "failed" if error_message else "completed",
        "error_message": error_message,
        "duration_seconds": duration,
        "fuente": "google_ddg_limpio",
        "keyword": keyword,
        "urls_procesadas": processed_urls_count,
        "urls_con_datos_encontrados": len(flat_results),
        "total_entradas_generadas": len(empresas_flat),
        "total_emails": total_emails,
        "total_phones": total_phones,
        "empresas": empresas_flat,
        "datos": datos_by_url,
        "resultados": datos_by_url,
        "timestamp": datetime.utcnow().isoformat()
    }
    
    # Enviar el callback a la API de Laravel
    if callback_url:
        try:
            logger.info(f"[Task {task_id}] Enviando resultados a {callback_url}")
            
            # Configurar el cliente HTTP con un timeout generoso
            timeout = httpx.Timeout(60.0, connect=120.0)
            async with httpx.AsyncClient(timeout=timeout) as client:
                # Intentar hasta 3 veces en caso de error
                max_retries = 3
                for attempt in range(max_retries):
                    try:
                        # Enviar los resultados en formato JSON
                        response = await client.post(
                            callback_url,
                            json=payload,
                            headers={"Content-Type": "application/json", "User-Agent": "ScrapingService/1.0"}
                        )
                        response.raise_for_status()
                        logger.info(f"[Task {task_id}] Resultados enviados correctamente. Respuesta: {response.status_code}")
                        break  # Salir del bucle si el envío es exitoso
                        
                    except (httpx.HTTPStatusError, httpx.RequestError) as e:
                        if attempt == max_retries - 1:  # Último intento
                            logger.error(f"[Task {task_id}] Error al enviar resultados (intento {attempt + 1}/{max_retries}): {str(e)}")
                            # Guardar los resultados en un archivo local como respaldo
                            try:
                                backup_dir = "/var/www/html/python/backups"
                                os.makedirs(backup_dir, exist_ok=True)
                                backup_file = os.path.join(backup_dir, f"backup_{task_id}.json")
                                with open(backup_file, 'w', encoding='utf-8') as f:
                                    json.dump(payload, f, ensure_ascii=False, indent=2)
                                logger.info(f"[Task {task_id}] Resultados guardados en {backup_file}")
                            except Exception as backup_error:
                                logger.error(f"[Task {task_id}] Error al guardar respaldo: {str(backup_error)}")
                        else:
                            # Esperar antes de reintentar
                            await asyncio.sleep(5 * (attempt + 1))  # Espera exponencial
                            
        except Exception as e:
            logger.error(f"[Task {task_id}] Error inesperado al enviar resultados: {str(e)}")
    else:
        logger.warning(f"[Task {task_id}] No se proporcionó URL de callback, omitiendo envío de resultados")
    
    logger.info(f"[Task {task_id}] Tarea completada en {duration} segundos")
    
    
    end_time = time.time()
    duration = round(end_time - start_time, 2)
    logger.info(f"[Task {task_id}] Tarea completada en {duration} segundos. Resultados: {len(flat_results)}")
    
    # Preparar y enviar el callback
    callback_payload = {
        'task_id': task_id,
        'status': 'completed',
        'error_message': None,
        'fuente': 'google_ddg_limpio',  # Asegurar que este campo esté presente
        'keyword': keyword,
        'urls_procesadas': processed_urls_count,
        'total_entradas_generadas': len(flat_results),
        'empresas': flat_results,  # Usar 'empresas' en lugar de 'datos' para consistencia
        'datos': {item['url']: {
            'nombre': item.get('nombre', ''),
            'correos': [item['correo']] if item.get('correo') and item['correo'] != 'No encontrado' else [],
            'telefono': item.get('telefono', '')
        } for item in flat_results},
        'resultados': {item['url']: {
            'nombre': item.get('nombre', ''),
            'correos': [item['correo']] if item.get('correo') and item['correo'] != 'No encontrado' else [],
            'telefono': item.get('telefono', '')
        } for item in flat_results}
    }
    
    try:
        logger.info(f"[Task {task_id}] Enviando callback a {callback_url}")
        await send_callback(callback_url, callback_payload)
        logger.info(f"[Task {task_id}] Callback enviado exitosamente")
    except Exception as e:
        logger.error(f"[Task {task_id}] Error al enviar callback: {str(e)}")
        raise

@app.post(
    "/buscar-google-ddg-limpio",
    summary="Busca en Google y DuckDuckGo (formato limpio)",
    description="""
    Realiza búsquedas en Google y DuckDuckGo, extrae información de contacto de las páginas web encontradas
    y devuelve los resultados en formato estructurado.
    
    - Si se proporciona un `callback_url`, la búsqueda se procesa de forma asíncrona y los resultados
      se envían al endpoint especificado.
    - Si no se proporciona `callback_url`, la búsqueda se ejecuta de forma síncrona.
    
    **Formato de respuesta del callback (JSON):**
    ```json
    {
        "task_id": "string",
        "status": "completed|failed",
        "error_message": "string | null",
        "fuente": "google_ddg_limpio",
        "keyword": "string",
        "urls_procesadas": 0,
        "total_entradas_generadas": 0,
        "empresas": [
            {
                "url": "string",
                "nombre": "string",
                "correo": "string",
                "telefono": "string"
            }
        ],
        "datos": {
            "url": {
                "nombre": "string",
                "correos": ["string"],
                "telefono": "string"
            }
        },
        "resultados": {
            "url": {
                "nombre": "string",
                "correos": ["string"],
                "telefono": "string"
            }
        }
    }
    ```
    """,
    response_description="Resultado de la operación de búsqueda"
)
async def buscador_google_ddg_limpio(
    d: KeywordRequest, 
    background_tasks: BackgroundTasks
) -> Dict[str, Any]:
    """
    Realiza una búsqueda en Google y DuckDuckGo, extrae información de contacto
    y devuelve los resultados en formato estructurado.
    
    - **keyword**: Término de búsqueda (requerido)
    - **results**: Número de resultados a devolver (opcional, por defecto 10)
    - **callback_url**: URL para enviar los resultados de forma asíncrona (opcional)
    
    **Ejemplo de solicitud:**
    ```json
    {
        "keyword": "ejemplo de búsqueda",
        "results": 10,
        "callback_url": "https://tudominio.com/callback"
    }
    ```
    """
    task_id = str(uuid.uuid4())
    logger.info(f"[API] Iniciando búsqueda - Task ID: {task_id}, Keyword: {d.keyword}")
    
    if d.callback_url and d.callback_url.strip().lower() != "false":
        # Modo asíncrono con callback - agregar a la cola
        logger.info(f"[Queue] Agregando búsqueda a la cola - ID: {task_id}, Keyword: {d.keyword}, Callback: {d.callback_url}")
        
        # Agregar la búsqueda a la cola
        try:
            await search_queue.put((d.keyword, d.results, d.callback_url, task_id))
            queue_position = search_queue.qsize()
            logger.info(f"[Queue] Búsqueda {task_id} agregada a la cola. Posición: {queue_position}")
            
            return {
                "status": "enqueued",
                "task_id": task_id,  # Ensure task_id is included in the response
                "message": "Búsqueda agregada a la cola de procesamiento.",
                "queue_position": queue_position,
                "keyword": d.keyword,
                "results_requested": d.results,
                "timestamp": time.strftime("%Y-%m-%dT%H:%M:%S%z")
            }
        except Exception as e:
            logger.error(f"[Queue] Error al agregar a la cola - Task ID: {task_id}, Error: {str(e)}")
            raise HTTPException(
                status_code=500,
                detail={
                    "status": "error",
                    "task_id": task_id,
                    "error": str(e),
                    "message": "Error al encolar la tarea de búsqueda"
                }
            )
    
    # Modo síncrono - ejecutar directamente
    logger.info(f"[Sync] Iniciando búsqueda síncrona - ID: {task_id}, Keyword: {d.keyword}")
    
    try:
        # Usar la función de tarea directamente pero con manejo de errores
        await run_google_ddg_limpio_task(d.keyword, d.results, "", task_id)
        
        # Si llegamos aquí, la tarea se completó correctamente
        return {
            "status": "completed",
            "task_id": task_id,
            "message": "Búsqueda completada correctamente.",
            "keyword": d.keyword,
            "results_requested": d.results
        }
        
    except Exception as e:
        logger.error(f"[Sync] Error en búsqueda síncrona {task_id}: {e}")
        raise HTTPException(
            status_code=500,
            detail={
                "status": "error",
                "task_id": task_id,
                "error": str(e),
                "message": "Error al procesar la búsqueda síncrona"
            }
        )

# Función para procesar la cola de búsquedas
async def process_search_queue():
    """Procesa la cola de búsquedas de forma secuencial.
    
    Esta función se ejecuta en un bucle infinito, tomando tareas de la cola y procesándolas.
    Incluye manejo de errores robusto para asegurar que los callbacks siempre se envíen.
    """
    while True:
        task = None
        try:
            # Obtener la próxima búsqueda de la cola
            task = await search_queue.get()
            if not task or len(task) != 4:
                logger.error(f"Tarea inválida en la cola: {task}")
                if task:
                    search_queue.task_done()
                await asyncio.sleep(5)
                continue
                
            keyword, results_num, callback_url, task_id = task
            logger.info(f"[Queue] Procesando tarea {task_id}: {keyword}")
            
            # Verificar si la tarea tiene una URL de callback válida
            if not callback_url or not isinstance(callback_url, str) or not callback_url.startswith('http'):
                logger.error(f"[Queue] URL de callback inválida para tarea {task_id}: {callback_url}")
                search_queue.task_done()
                await asyncio.sleep(1)
                continue

            try:
                # Ejecutar la búsqueda con un timeout
                await asyncio.wait_for(
                    run_google_ddg_limpio_task(keyword, results_num, callback_url, task_id),
                    timeout=1800  # 30 minutos de timeout por tarea
                )
                logger.info(f"[Queue] Tarea {task_id} completada exitosamente")
                
            except asyncio.TimeoutError:
                logger.error(f"[Queue] Timeout al procesar tarea {task_id}")
                # Enviar callback de error por timeout
                if task_id in current_tasks:
                    current_tasks.remove(task_id)
                await send_callback(callback_url, {
                    'task_id': task_id,
                    'status': 'failed',
                    'error_message': 'La tarea excedió el tiempo máximo de ejecución (5 minutos)',
                    'fuente': 'google_ddg_limpio',
                    'keyword': keyword,
                    'results_requested': results_num,
                    'urls_procesadas': 0,
                    'total_entradas_generadas': 0,
                    'empresas': [],
                    'datos': {},
                    'resultados': {},
                    'warnings': []
                })
                
            except Exception as e:
                logger.error(f"[Queue] Error procesando tarea {task_id}: {str(e)}", exc_info=True)
                # Enviar callback de error
                if task_id in current_tasks:
                    current_tasks.remove(task_id)
                try:
                    error_message = str(e)
                    status = 'failed'
                    
                    # Manejar específicamente el caso de rate limit
                    if '429' in error_message or 'Ratelimit' in error_message:
                        error_message = 'Se alcanzó el límite de tasa en los motores de búsqueda. Por favor, inténtelo de nuevo más tarde.'
                        status = 'completed'  # Marcamos como completado pero con advertencia
                    
                    await send_callback(callback_url, {
                        'task_id': task_id,
                        'status': status,
                        'error_message': error_message if status == 'failed' else None,
                        'fuente': 'google_ddg_limpio',
                        'keyword': keyword,
                        'results_requested': results_num,
                        'urls_procesadas': 0,
                        'total_entradas_generadas': 0,
                        'empresas': [],
                        'datos': {},
                        'resultados': {},
                        'warnings': [error_message] if status == 'completed' else []
                    })
                except Exception as callback_error:
                    logger.error(f"[Queue] Error al enviar callback de error para tarea {task_id}: {str(callback_error)}")
            
        except Exception as e:
            logger.error(f"[Queue] Error crítico en el procesador de cola: {str(e)}", exc_info=True)
            # Esperar un poco antes de reintentar para evitar bucles rápidos de error
            await asyncio.sleep(10)
            
        finally:
            # Asegurarse de marcar la tarea como completada
            if task and len(task) == 4:
                _, _, _, task_id = task
                if task_id in current_tasks:
                    current_tasks.remove(task_id)
                search_queue.task_done()
                logger.info(f"[Queue] Tarea {task_id} finalizada y eliminada del seguimiento")
            
            # Pequeña pausa entre búsquedas para evitar sobrecarga
            await asyncio.sleep(search_config.search_queue_delay)

# Modificar el manejador de eventos de inicio
@app.on_event("startup")
async def startup_event():
    """Inicia el procesador de la cola de búsquedas al arrancar la aplicación."""
    logger.info("Iniciando el servicio de búsqueda...")
    logger.info("Iniciando procesador de cola de búsquedas...")
    asyncio.create_task(process_search_queue())
    logger.info("Procesador de cola iniciado correctamente")

if __name__ == "__main__":
    logger.info("Iniciando servidor FastAPI en http://0.0.0.0:9000")
    uvicorn.run("scraping:app", host="0.0.0.0", port=9000, reload=True, log_level="info")
