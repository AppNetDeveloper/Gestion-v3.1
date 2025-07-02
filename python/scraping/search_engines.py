import requests
from bs4 import BeautifulSoup
import json
import time
import random
import asyncio
from typing import List, Dict, Tuple, Optional, Any, Union
from dataclasses import dataclass
import logging
import aiohttp
import httpx
from urllib.parse import urlparse, parse_qs, urlencode
import re
from collections import defaultdict # Añadido: Importar defaultdict
from duckduckgo_search import DDGS # Añadido: Importar DDGS
from duckduckgo_search.exceptions import DuckDuckGoSearchException # Añadido: Importar DuckDuckGoSearchException

# Configuración de logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.StreamHandler(),
        logging.FileHandler('/var/www/html/storage/logs/search_engines.log') # Cambiado: Ruta absoluta para el log
    ]
)
logger = logging.getLogger(__name__)

# User-Agents para rotar
USER_AGENTS = [
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4.1 Safari/605.1.15",
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36"
]

def get_random_user_agent() -> str:
    """Devuelve un User-Agent aleatorio."""
    return random.choice(USER_AGENTS)

def get_headers() -> Dict[str, str]:
    """Devuelve los headers para las peticiones HTTP."""
    return {
        "User-Agent": get_random_user_agent(),
        "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
        "Accept-Language": "en-US,en;q=0.5",
        "Accept-Encoding": "gzip, deflate",
        "Connection": "keep-alive",
        "Upgrade-Insecure-Requests": "1",
        "Sec-Fetch-Dest": "document",
        "Sec-Fetch-Mode": "navigate",
        "Sec-Fetch-Site": "none",
        "Sec-Fetch-User": "?1",
        "Cache-Control": "max-age=0"
    }

# ==================== GIGABLAST ====================

# Reemplazado por una implementación más robusta usando la librería googlesearch
from googlesearch import search as google_search_lib

async def search_google(query: str, num_results: int = 100, timeout: int = 30, proxies: Optional[List[str]] = None) -> List[str]:
    """
    Realiza una búsqueda en Google y devuelve las URLs de los resultados.
    
    Args:
        query: Término de búsqueda
        num_results: Número máximo de resultados a devolver
        timeout: Tiempo máximo de espera en segundos
        
    Returns:
        Lista de URLs de resultados
    """
    logger.info(f"Iniciando búsqueda en Google para '{query}' con {num_results} resultados.")
    
    max_retries = 3
    base_delay = 10
    urls = []
    
    for attempt in range(max_retries):
        try:
            # Añadir un retardo exponencial para evitar el bloqueo
            sleep_time = base_delay * (2 ** attempt) + random.uniform(1, 5)
            logger.info(f"Intento {attempt+1}/{max_retries} - Esperando {sleep_time:.2f} segundos antes de la búsqueda en Google...")
            await asyncio.sleep(sleep_time)
            
            # La librería googlesearch-python tiene limitaciones en sus parámetros
            # Usamos solo los parámetros básicos que sabemos que funcionan
            # Si hay proxies disponibles, usar uno aleatorio
            proxy = random.choice(proxies) if proxies else None
            
            urls = list(google_search_lib(
                query, # El término de búsqueda es el primer argumento posicional
                num=num_results, # Usar 'num' para el número de resultados
                lang="es",
                proxy=proxy # Pasar el proxy a la función de búsqueda de Google
            ))
            
            logger.info(f"Búsqueda en Google completada con {len(urls)} resultados.")
            return urls
            
        except Exception as e:
            error_msg = str(e).lower()
            if "429" in error_msg or "too many requests" in error_msg or "captcha" in error_msg or "sorry" in error_msg:
                logger.warning(f"Error de Rate Limit en Google (intento {attempt+1}/{max_retries}): {e}")
                if attempt < max_retries - 1:
                    continue  # Intentar de nuevo con un retardo mayor
                else:
                    logger.error("Se superó el número máximo de reintentos para Google.")
            else:
                logger.error(f"Error inesperado en Google: {e}", exc_info=True)
                break  # No reintentar en caso de errores inesperados
    
    return urls  # Devolver lista vacía o resultados parciales si hubo errores
 
async def search_duckduckgo(query: str, num_results: int = 100, timeout: int = 30) -> List[str]:
    """
    Realiza una búsqueda en DuckDuckGo y devuelve las URLs de los resultados.
    
    Args:
        query: Término de búsqueda
        num_results: Número máximo de resultados a devolver
        timeout: Tiempo máximo de espera en segundos
        
    Returns:
        Lista de URLs de resultados
    """
    logger.info(f"Iniciando búsqueda en DuckDuckGo para '{query}' con {num_results} resultados.")
    
    def sync_search_with_retry():
        # DDGS ya está importado al principio del archivo
        import time

        thread_results = []
        retries = 3
        for i in range(retries):
            try:
                logger.info(f"Intento de búsqueda en DuckDuckGo {i+1}/{retries}...")
                # Aumentar el timeout para evitar errores de timeout
                with DDGS(headers=get_headers(), timeout=timeout * 2) as ddgs:
                    for r in ddgs.text(query, max_results=num_results):
                        if 'href' in r:
                            thread_results.append(r['href'])
                
                logger.info(f"Búsqueda síncrona en DuckDuckGo completada con {len(thread_results)} resultados.")
                return thread_results # Éxito, salir del bucle
            
            except DuckDuckGoSearchException as e: # Usar la excepción específica
                # Detectar errores de rate limit por el mensaje de error
                if "Ratelimit" in str(e) or "429" in str(e) or "Too Many Requests" in str(e) or "202" in str(e):
                    logger.warning(f"Error de Rate Limit en DuckDuckGo (intento {i+1}/{retries}): {e}")
                    if i < retries - 1:
                        wait_time = (i + 1) * 10 # Incrementar el tiempo de espera
                        logger.info(f"Reintentando en {wait_time} segundos...")
                        time.sleep(wait_time)
                    else:
                        logger.error("Se superó el número máximo de reintentos para DuckDuckGo.")
                        raise # Re-lanzar la excepción si se agotan los reintentos
                else:
                    logger.error(f"Error inesperado en la búsqueda síncrona de DuckDuckGo: {e}", exc_info=True)
                    if i < retries - 1:
                        wait_time = 5
                        logger.info(f"Reintentando en {wait_time} segundos...")
                        time.sleep(wait_time)
                    else:
                        raise # Re-lanzar la excepción si se agotan los reintentos

        return thread_results

    try:
        # Ejecutar la búsqueda síncrona con reintentos en un hilo separado
        urls = await asyncio.to_thread(sync_search_with_retry)
        logger.info(f"Búsqueda en DuckDuckGo completada con {len(urls)} resultados.")
        return urls
    except Exception as e:
        logger.error(f"Error al ejecutar la búsqueda de DuckDuckGo en un hilo: {e}", exc_info=True)
        return []

async def search_gigablast(query: str, num_results: int = 100, timeout: int = 30, page: int = 1) -> List[str]:
    """
    Realiza una búsqueda en 100searchengines.com y devuelve las URLs de los resultados.
    
    Args:
        query: Término de búsqueda
        num_results: Número máximo de resultados a devolver
        timeout: Tiempo máximo de espera en segundos
        page: Número de página a buscar (comienza en 1)
        
    Returns:
        Lista de URLs de resultados
    """
    try:
        base_url = "https://www.100searchengines.com/"
        params = {
            "q": query,
            "gsc.tab": "0",
            "gsc.q": query,
            "gsc.page": str(page)
        }
        
        headers = get_headers()
        headers.update({
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language': 'es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3',
            'Referer': 'https://www.100searchengines.com/',
            'DNT': '1',
            'Connection': 'keep-alive',
            'Upgrade-Insecure-Requests': '1',
            'Sec-Fetch-Dest': 'document',
            'Sec-Fetch-Mode': 'navigate',
            'Sec-Fetch-Site': 'same-origin',
            'Sec-Fetch-User': '?1',
            'Cache-Control': 'max-age=0'
        })
        
        # Crear una sesión con un User-Agent realista
        session = aiohttp.ClientSession(
            headers=headers,
            timeout=aiohttp.ClientTimeout(total=timeout),
            cookie_jar=aiohttp.CookieJar(unsafe=True)
        )
        
        try:
            # Primera petición para obtener cookies
            await session.get('https://www.100searchengines.com/', timeout=timeout)
            
            # Segunda petición con la búsqueda real
            async with session.get(base_url, params=params, allow_redirects=True) as response:
                if response.status != 200:
                    logger.error(f"Error en búsqueda 100searchengines: {response.status} {response.reason}")
                    return []
                
                html = await response.text()
        finally:
            await session.close()
        
        # Procesar los resultados con BeautifulSoup
        soup = BeautifulSoup(html, 'html.parser')
        urls = []
        
        # Buscar los enlaces en los resultados de búsqueda
        for result in soup.select('div.g, .rc, .g, .tF2Cxc'):
            link = result.select_one('a[href^="http"]')
            if link and 'href' in link.attrs:
                url = link['href']
                
                # Limpiar la URL
                clean_url = url.split('&')[0].split('?')[0].split('#')[0].rstrip('/')
                
                # Filtrar URLs no deseadas
                if not any(domain in clean_url.lower() for domain in [
                    'google.', 'doubleclick.', 'webcache.', '100searchengines.',
                    'translate.google.', 'webcache.googleusercontent.com'
                ]):
                    # Evitar duplicados
                    if clean_url not in urls:  
                        urls.append(clean_url)
                        if len(urls) >= num_results:
                            break
        
        return urls
        
    except asyncio.TimeoutError:
        logger.error(f"Tiempo de espera agotado en búsqueda 100searchengines (página {page})")
        return []
    except aiohttp.ClientError as e:
        logger.error(f"Error de conexión en búsqueda 100searchengines: {str(e)}")
        return []
    except Exception as e:
        logger.error(f"Error en búsqueda 100searchengines (página {page}): {str(e)}", exc_info=True)
        return []

# ==================== BRAVE SEARCH ====================

# Semaforo para limitar peticiones concurrentes a la API de Brave
brave_semaphore = None

async def search_brave(query: str, num_results: int = 10, timeout: int = 60, offset: int = 0) -> List[str]:
    """
    Realiza una búsqueda usando la API de Brave Search con manejo de concurrencia, 
    paginación y rate limiting mejorado.
    
    Args:
        query: Término de búsqueda
        num_results: Número máximo de resultados a devolver
        timeout: Tiempo máximo de espera en segundos
        
    Returns:
        Lista de URLs de resultados
    """
    import os
    api_key = os.getenv('BRAVE_API_KEY')
    if not api_key:
        logger.error("La variable de entorno BRAVE_API_KEY no está configurada")
        return []
    
    # Configuración
    max_concurrent = int(os.getenv('BRAVE_MAX_CONCURRENT', '1'))
    request_delay = float(os.getenv('BRAVE_REQUEST_DELAY', '5.0'))
    max_retries = int(os.getenv('BRAVE_MAX_RETRIES', '3'))
    
    # Inicializar semáforo para controlar concurrencia
    global brave_semaphore
    if brave_semaphore is None:
        brave_semaphore = asyncio.Semaphore(max_concurrent)
    
    # Configuración de la API
    url = "https://api.search.brave.com/res/v1/web/search"
    headers = {
        "X-Subscription-Token": api_key,
        "Accept": "application/json",
        "Accept-Encoding": "gzip",
        "User-Agent": get_random_user_agent()
    }
    
    # Resultados acumulados
    all_results = []
    seen_urls = set()  # Para evitar duplicados
    
    # Calcular número de páginas necesarias (máximo 10 resultados por página)
    results_per_page = 10
    max_pages = min(10, (num_results + results_per_page - 1) // results_per_page)
    
    # Iterar por páginas
    for page in range(max_pages):
        offset = page * results_per_page
        
        # Si no es la primera página, esperar para evitar rate limits
        if page > 0:
            wait_time = request_delay + random.uniform(0, 2)
            logger.info(f"Esperando {wait_time:.2f} segundos antes de la siguiente página...")
            await asyncio.sleep(wait_time)
        
        # Parámetros de búsqueda
        params = {
            "q": query,
            "count": results_per_page,
            "offset": offset,
            "safesearch": "moderate",
            "result_filter": "web",
            "country": "es",
            "ui_lang": "es-ES"
        }
        
        # Intentar con reintentos y backoff exponencial
        page_results = []
        for attempt in range(max_retries):
            try:
                # Usar semáforo para limitar concurrencia
                async with brave_semaphore:
                    logger.info(f"Enviando petición a Brave API (offset={offset}, count={results_per_page}, intento={attempt+1}/{max_retries})")
                    
                    # Si es un reintento, esperar con backoff exponencial
                    if attempt > 0:
                        backoff_time = min(30, (2 ** attempt) * 5) + random.uniform(0, 2)
                        logger.info(f"Reintento {attempt+1}/{max_retries} - Esperando {backoff_time:.2f} segundos...")
                        await asyncio.sleep(backoff_time)
                    
                    # Realizar petición HTTP
                    # Asegurarse de que httpx esté disponible en este ámbito
                    import httpx
                    async with httpx.AsyncClient(timeout=timeout) as client:
                        response = await client.get(url, params=params, headers=headers)
                        response.raise_for_status()
                        data = response.json()
                    
                    # Procesar resultados
                    if "web" in data and "results" in data["web"]:
                        for result in data["web"]["results"]:
                            url = result.get("url", "")
                            if not url:
                                continue
                            
                            # Limpiar y normalizar URL
                            clean_url = url.split('?')[0].split('#')[0].rstrip('/')
                            
                            # Filtrar dominios no deseados
                            if any(domain in clean_url.lower() for domain in ['google.', 'bing.', 'yandex.', 'facebook.', 'twitter.']):
                                continue
                            
                            # Evitar duplicados
                            if clean_url not in seen_urls:
                                seen_urls.add(clean_url)
                                page_results.append(clean_url)
                        
                        logger.info(f"Búsqueda Brave completada. Resultados: {len(page_results)}/{results_per_page}")
                        
                        # Si obtenemos menos resultados que los solicitados, no hay más páginas
                        if len(page_results) < results_per_page:
                            all_results.extend(page_results)
                            logger.info("No hay más páginas de resultados en Brave.")
                            return all_results[:num_results]
                        
                        # Salir del bucle de reintentos si todo fue bien
                        break
                    else:
                        logger.warning("Formato de respuesta inesperado de Brave API")
                        if attempt < max_retries - 1:
                            continue
                
            except httpx.HTTPStatusError as e:
                # Manejar errores HTTP
                if e.response.status_code == 422:
                    # Error 422 indica que no hay más resultados disponibles
                    logger.warning(f"Error 422 en Brave, probablemente no hay más páginas: {e}")
                    break
                
                elif e.response.status_code in [429, 403]:
                    # Rate limit o bloqueo temporal
                    retry_after = int(e.response.headers.get('Retry-After', 10))
                    logger.warning(f"Rate limit en Brave API. Reintentando en {retry_after} segundos...")
                    
                    if attempt < max_retries - 1:
                        await asyncio.sleep(retry_after)
                        continue
                    else:
                        logger.error("Se superó el número máximo de reintentos para Brave API")
                        break
                
                elif e.response.status_code >= 500:
                    # Error de servidor
                    logger.warning(f"Error del servidor Brave: {e.response.status_code}")
                    if attempt < max_retries - 1:
                        continue
                    else:
                        break
                
                else:
                    # Otros errores HTTP
                    logger.error(f"Error HTTP en Brave API: {e}")
                    if attempt < max_retries - 1:
                        continue
                    else:
                        break
            
            except (httpx.RequestError, asyncio.TimeoutError) as e:
                # Errores de red o timeout
                logger.warning(f"Error de conexión o timeout en Brave API: {e}")
                if attempt < max_retries - 1:
                    continue
                else:
                    logger.error("Se superó el número máximo de reintentos por errores de conexión")
                    break
            
            except Exception as e:
                # Cualquier otro error
                logger.error(f"Error inesperado en Brave API: {e}", exc_info=True)
                if attempt < max_retries - 1:
                    continue
                else:
                    break
        
        # Añadir resultados de esta página al total
        all_results.extend(page_results)
        
        # Si no obtuvimos resultados en esta página, no hay más páginas
        if not page_results:
            break
        
        # Si ya tenemos suficientes resultados, terminar
        if len(all_results) >= num_results:
            break
    
    logger.info(f"Búsqueda en brave completada con {len(all_results)} resultados.")
    return all_results[:num_results]

# ==================== BING (web scraping) ====================

BLACKLISTED_DOMAINS = ["zhihu.com", "baidu.com"]

async def search_bing(query: str, num_results: int = 10, timeout: int = 30, first: int = 1) -> List[str]:
    """
    Realiza una búsqueda en Bing mediante web scraping.
    
    Args:
        query: Término de búsqueda
        num_results: Número máximo de resultados a devolver por página (máx 10)
        timeout: Tiempo máximo de espera en segundos
        first: Índice del primer resultado a devolver (para paginación, comienza en 1)
        
    Returns:
        Lista de URLs de resultados
    """
    try:
        base_url = "https://www.bing.com/search"
        params = {
            "q": query,
            "count": min(num_results, 10),  # Bing muestra máximo 10 resultados por página
            "first": first,
            "FORM": "PORE",  # Formato de resultados
            "qs": "n",  # No sugerencias
            "sp": "-1",  # Búsqueda estándar
            "pq": query,  # Consulta previa
            "sc": "0-0",  # Sin filtro de país
            "cvid": ""  # ID de cliente vacío
        }
        
        headers = get_headers()
        # Añadir headers adicionales para parecer un navegador real
        headers.update({
            'Accept-Language': 'es-ES,es;q=0.9,en;q=0.8',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Referer': 'https://www.bing.com/',
            'Upgrade-Insecure-Requests': '1',
        })
        
        # Usar CookieJar para manejar cookies de sesión
        async with aiohttp.ClientSession(cookie_jar=aiohttp.CookieJar(unsafe=True)) as session:
            async with session.get(base_url, params=params, headers=headers, timeout=timeout) as response:
                response.raise_for_status()
                html = await response.text()

        soup = BeautifulSoup(html, 'html.parser')
        results = []
        
        # Buscar enlaces en los resultados de búsqueda
        for result in soup.select('li.b_algo h2 a'):
            url = result.get('href', '')
            # Verificar que la URL sea válida y no sea un enlace de Bing o un dominio en la lista negra
            if url and url.startswith('http') and 'bing.com' not in url and not any(domain in url for domain in BLACKLISTED_DOMAINS):
                # Asegurarse de que la URL esté correctamente formada
                parsed_url = url.split('?')[0].split('#')[0].rstrip('/')
                if parsed_url not in results:  # Evitar duplicados
                    results.append(parsed_url)
                    if len(results) >= num_results:
                        break
        
        return results
        
    except Exception as e:
        logger.error(f"Error en búsqueda Bing (first={first}): {str(e)}")
        return []

# ==================== FUNCIÓN UNIFICADA ====================

async def safe_search(coroutine, engine_name, timeout):
    """
    Ejecuta una búsqueda con manejo de errores y timeout.
    
    Args:
        coroutine: Corrutina a ejecutar
        engine_name: Nombre del motor de búsqueda
        timeout: Tiempo máximo de espera en segundos
        
    Returns:
        Lista de resultados o lista vacía en caso de error
    """
    try:
        logger.info(f"Iniciando búsqueda en {engine_name}")
        result = await asyncio.wait_for(coroutine, timeout=timeout)
        logger.info(f"Búsqueda en {engine_name} completada con {len(result) if result else 0} resultados")
        return result or []
    except asyncio.TimeoutError:
        logger.warning(f"Tiempo de espera agotado para {engine_name} ({timeout}s)")
        return []
    except Exception as e:
        logger.error(f"Error en búsqueda {engine_name}: {str(e)}", exc_info=True)
        return []

# Importar SearchConfig desde scraping.py
# Esto es necesario para que search_engines.py pueda usar la clase SearchConfig
# y acceder a la configuración de proxies.
try:
    from scraping import SearchConfig
except ImportError:
    # Fallback si scraping no está disponible (ej. para pruebas unitarias de search_engines)
    @dataclass
    class SearchConfig:
        google_timeout: int = 90
        bing_timeout: int = 60
        proxies: Optional[List[str]] = None

async def search_multiple_engines(
    query: str,
    engines: list = ["google", "duckduckgo"],
    num_results: int = 500,  # Aumentado de 10 a 20 resultados por defecto
    timeouts: Dict[str, int] = None,
    pages: int = 10,  # Número de páginas a buscar por motor
    search_config: Optional[SearchConfig] = None # Nuevo parámetro
) -> Dict[str, List[str]]:
    # Eliminar motores duplicados manteniendo el orden
    engines = list(dict.fromkeys(engines))
    
    # Configuración de timeouts y reintentos por motor
    # Si timeouts es None, usar valores por defecto
    if timeouts is None:
        timeouts = {}
    
    # Asegurarse de que timeouts es un diccionario
    if not isinstance(timeouts, dict):
        timeouts = {}
    
    # Configurar timeouts con valores por defecto si no se proporcionan
    engine_timeouts = {
        'google': {
            'timeout': timeouts.get('google', {}).get('timeout', 30) if isinstance(timeouts.get('google'), dict) else timeouts.get('google', 30),
            'max_retries': timeouts.get('google', {}).get('max_retries', 3) if isinstance(timeouts.get('google'), dict) else 3,
            'retry_delay': timeouts.get('google', {}).get('retry_delay', 10) if isinstance(timeouts.get('google'), dict) else 10
        },
        'bing': {
            'timeout': timeouts.get('bing', {}).get('timeout', 20) if isinstance(timeouts.get('bing'), dict) else timeouts.get('bing', 20),
            'max_retries': timeouts.get('bing', {}).get('max_retries', 2) if isinstance(timeouts.get('bing'), dict) else 2,
            'retry_delay': timeouts.get('bing', {}).get('retry_delay', 5) if isinstance(timeouts.get('bing'), dict) else 5
        }
    }
    
    # Preparar tareas de búsqueda con paginación
    search_tasks = []
    
    if "google" in engines:
        # Para Google, la librería maneja la paginación internamente.
        # Hacemos una sola petición a Google (la función maneja internamente el número de resultados)
        # Obtener la configuración para Google
        google_config = engine_timeouts.get('google', {})
        google_timeout = int(google_config.get('timeout', 30))
        
        search_tasks.append((
            "google",
            safe_search(
                search_google(query, num_results=20, proxies=search_config.proxies),
                "google",
                google_timeout + 5  # Añadir margen de 5 segundos
            )
        ))
    
    if "bing" in engines:
        # Bing con paginación - Ampliamos a más páginas (hasta 20)
        bing_pages = min(20, pages * 2)  
        
        # Obtener la configuración para Bing
        bing_config = engine_timeouts.get('bing', {})
        bing_timeout = int(bing_config.get('timeout', 20))
        
        for page in range(bing_pages):
            first = page * 10 + 1  # Bing usa first=1, 11, 21, etc.
            search_tasks.append((
                "bing",
                safe_search(
                    search_bing(query, num_results=10, first=first),
                    "bing",
                    bing_timeout + 5
                )
            ))
    
    # Ejecutar búsquedas de forma concurrente usando asyncio.gather
    results = {}
    if search_tasks:
        # Agrupar tareas por motor
        task_groups = defaultdict(list)
        for name, task in search_tasks:
            task_groups[name].append(task)
        
        logger.info(f"Ejecutando búsquedas en motores: {list(task_groups.keys())}")

        async def run_engine_tasks(engine_name, task_list):
            """Ejecuta todas las tareas para un motor y procesa los resultados."""
            # Usar asyncio.gather para ejecutar todas las páginas de un motor en paralelo
            page_results = await asyncio.gather(*task_list, return_exceptions=True)
            
            # Aplanar la lista de resultados y filtrar errores o resultados vacíos
            final_urls = []
            for res in page_results:
                if isinstance(res, list) and res:
                    final_urls.extend(res)
                elif isinstance(res, Exception):
                    logger.error(f"Error en una tarea de '{engine_name}': {res}")
            
            # Eliminar duplicados manteniendo el orden
            unique_urls = list(dict.fromkeys(final_urls))
            return engine_name, unique_urls

        # Crear una corrutina para cada motor
        engine_coroutines = [
            run_engine_tasks(engine, tasks) for engine, tasks in task_groups.items()
        ]
        
        # Añadir Brave Search si está habilitado
        if "brave" in engines:
            brave_config = engine_timeouts.get('brave', {})
            brave_timeout = int(brave_config.get('timeout', 60))
            
            # Brave API devuelve un máximo de 10 resultados por página
            brave_pages = min(pages, (num_results + 9) // 10)
            
            for page in range(brave_pages):
                offset = page * 10
                engine_coroutines.append(
                    run_engine_tasks(
                        "brave",
                        [safe_search(
                            search_brave(query, num_results=10, offset=offset, timeout=brave_timeout), # Pasar timeout a search_brave
                            "brave",
                            brave_timeout + 5
                        )]
                    )
                )
        
        # Ejecutar las corrutinas de todos los motores en paralelo
        final_results = await asyncio.gather(*engine_coroutines)
        
        # Convertir la lista de tuplas (engine, urls) en un diccionario final
        results = {engine: urls for engine, urls in final_results if urls}
    
    # Resumen de resultados
    result_summary = {k: len(v) for k, v in results.items()}
    logger.info(f"Búsqueda completada. Resultados: {result_summary}")
    
    return results

# Ejemplo de uso
if __name__ == "__main__":
    # Configuración de ejemplo
    test_query = "Python programming"
    # Realizar búsqueda en todos los motores
    results = search_multiple_engines(
        query=test_query,
        engines=["gigablast", "bing"],  # Sin Brave para la prueba
        num_results=5
    )
    
    # Mostrar resultados
    for engine, urls in results.items():
        print(f"\n{engine.upper()} - {len(urls)} resultados:")
        for i, url in enumerate(urls[:5], 1):
            print(f"{i}. {url}")
