import requests
from bs4 import BeautifulSoup
import json
import time
import random
import asyncio
from typing import List, Dict, Tuple, Optional, Any, Union
import logging
import aiohttp
from urllib.parse import urlparse, parse_qs, urlencode
import re

# Configuración de logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.StreamHandler(),
        logging.FileHandler('search_engines.log')
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

async def search_google(query: str, num_results: int = 10, lang: str = 'es', timeout: int = 60, start: int = 0) -> List[str]:
    """
    Realiza una búsqueda en Google y devuelve las URLs de los resultados.
    
    Args:
        query: Término de búsqueda
        num_results: Número máximo de resultados a devolver
        lang: Idioma de la búsqueda
        timeout: Tiempo máximo de espera en segundos
        start: Índice del primer resultado a devolver (para paginación)
        
    Returns:
        Lista de URLs de resultados
    """
    try:
        base_url = "https://www.google.com/search"
        params = {
            'q': query,
            'num': min(num_results, 10),  # Google muestra máximo 10 resultados por página
            'hl': lang,
            'start': start,
            'safe': 'active',
            'filter': '0',  # Desactivar agrupación de resultados similares
            'pws': '0'  # Desactivar búsqueda personalizada
        }
        
        headers = get_headers()
        
        async with aiohttp.ClientSession() as session:
            async with session.get(base_url, params=params, headers=headers, timeout=timeout) as response:
                response.raise_for_status()
                html = await response.text()
        
        soup = BeautifulSoup(html, 'html.parser')
        urls = []
        
        # Buscar enlaces en los resultados de búsqueda
        urls = []
        for result in soup.select('div.g'):
            # Buscar el enlace principal del resultado
            link = result.select_one('a[href^="/url?q="]')
            if link:
                # Extraer la URL real del parámetro q=
                url = link['href']
                if url.startswith('/url?q='):
                    url = url.split('&')[0][7:]  # Eliminar '/url?q=' y cualquier parámetro adicional
                if url.startswith('http'):
                    # Decodificar caracteres especiales en la URL
                    url = requests.utils.unquote(url)
                    urls.append(url)
                    if len(urls) >= num_results:
                        break
        
        return urls
        
    except Exception as e:
        logger.error(f"Error en búsqueda Google (start={start}): {str(e)}")
        return []

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
    try:
        # Primero intentamos con la biblioteca duckduckgo-search
        try:
            from duckduckgo_search import DDGS
            with DDGS() as ddgs:
                results = [r['href'] for r in ddgs.text(query, max_results=num_results)]
                if results:
                    return results[:num_results]
        except Exception as e:
            logger.warning(f"Error con duckduckgo-search: {str(e)}")
        
        # Si falla, intentamos con requests directamente
        base_url = "https://html.duckduckgo.com/html/"
        headers = get_headers()
        headers.update({
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Content-Type': 'application/x-www-form-urlencoded',
            'Origin': 'https://html.duckduckgo.com',
            'Referer': 'https://html.duckduckgo.com/'
        })
        
        data = {
            'q': query,
            'b': '',
            'kl': 'es-es',
            'df': ''
        }
        
        response = requests.post(
            base_url, 
            headers=headers, 
            data=data, 
            timeout=timeout,
            allow_redirects=True
        )
        response.raise_for_status()
        
        soup = BeautifulSoup(response.text, 'html.parser')
        urls = []
        
        # Buscar enlaces en los resultados de búsqueda
        for result in soup.select('div.result__body'):
            link = result.find('a', {'class': 'result__a'}, href=True)
            if link:
                url = link['href']
                
                # Limpiar la URL de DuckDuckGo
                if url.startswith('//'):
                    url = 'https:' + url
                
                # Extraer la URL real de los parámetros
                if 'uddg=' in url:
                    parsed = parse_qs(urlparse(url).query)
                    if 'uddg' in parsed:
                        url = parsed['uddg'][0]
                
                # Asegurarse de que la URL sea válida
                if url.startswith(('http://', 'https://')) and 'duckduckgo.com' not in url:
                    urls.append(url)
                    if len(urls) >= num_results:
                        break
        
        return urls
        
    except Exception as e:
        logger.error(f"Error en búsqueda DuckDuckGo: {str(e)}")
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
                # Filtrar URLs no deseadas
                if not any(domain in url.lower() for domain in [
                    'google.', 'doubleclick.', 'webcache.', '100searchengines.',
                    'translate.google.', 'webcache.googleusercontent.com'
                ]):
                    # Limpiar la URL
                    clean_url = url.split('&')[0].split('?')[0].split('#')[0].rstrip('/')
                    if clean_url not in urls:  # Evitar duplicados
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

async def search_brave(query: str, api_key: str, num_results: int = 10, timeout: int = 60, offset: int = 0, retry_count: int = 0) -> List[str]:
    """
    Realiza una búsqueda usando la API de Brave Search con manejo de concurrencia y rate limiting.
    
    Args:
        query: Término de búsqueda
        api_key: Clave API de Brave Search
        num_results: Número máximo de resultados a devolver (máx 100 por petición)
        timeout: Tiempo máximo de espera en segundos
        offset: Desplazamiento para la paginación de resultados
        retry_count: Número de reintentos realizados (para uso interno)
        
    Returns:
        Lista de URLs de resultados
    """
    global brave_semaphore
    import os
    
    # Obtener configuración de delays y reintentos
    max_concurrent = int(os.getenv('BRAVE_MAX_CONCURRENT', '1'))
    request_delay = float(os.getenv('BRAVE_REQUEST_DELAY', '5.0'))
    error_delay = float(os.getenv('BRAVE_ERROR_DELAY', '10.0'))
    max_retries = int(os.getenv('BRAVE_MAX_RETRIES', '3'))
    
    # Inicializar el semáforo si no existe
    if brave_semaphore is None:
        brave_semaphore = asyncio.Semaphore(max_concurrent)
    
    # Si no es el primer intento, aplicar delay de error
    if retry_count > 0:
        logger.warning(f"Reintento {retry_count}/{max_retries} después de {error_delay} segundos...")
        await asyncio.sleep(error_delay)
    
    try:
        # Usar semáforo para limitar peticiones concurrentes
        async with brave_semaphore:
            # Agregar delay entre peticiones consecutivas
            if offset > 0 or retry_count > 0:
                wait_time = error_delay if retry_count > 0 else request_delay
                logger.info(f"Esperando {wait_time} segundos antes de la siguiente petición...")
                await asyncio.sleep(wait_time)
            
            # Configuración de la petición
            url = "https://api.search.brave.com/res/v1/web/search"
            headers = {
                "X-Subscription-Token": api_key,
                "Accept": "application/json",
                "Accept-Encoding": "gzip",
                "User-Agent": get_random_user_agent()
            }
            
            # Limitar resultados por petición a 20 (máximo permitido por la API)
            count = min(num_results, 20)
            
            params = {
                "q": query,
                "count": count,
                "offset": offset,
                "safesearch": "moderate",
                "result_filter": "web",
                "country": "es",  # Priorizar resultados de España
                "ui_lang": "es"    # Idioma de la interfaz
            }
            
            # Usar aiohttp para mejor manejo de timeouts asíncronos
            async with aiohttp.ClientSession() as session:
                logger.info(f"Enviando petición a Brave API (offset={offset}, count={count}, intento={retry_count+1}/{max_retries+1})")
                
                try:
                    async with session.get(
                        url, 
                        headers=headers, 
                        params=params, 
                        timeout=timeout,
                        ssl=False  # Desactivar verificación SSL si hay problemas
                    ) as response:
                        # Verificar estado de la respuesta
                        if response.status == 401:
                            error_msg = "Error de autenticación en la API de Brave. Verifica tu API key."
                            logger.error(error_msg)
                            raise Exception(error_msg)
                            
                        elif response.status == 429:
                            retry_after = int(response.headers.get('Retry-After', error_delay))
                            logger.warning(f"Demasiadas peticiones. Rate limit alcanzado. Reintentando en {retry_after} segundos...")
                            await asyncio.sleep(retry_after)
                            return await search_brave(query, api_key, num_results, timeout, offset, min(retry_count + 1, max_retries))
                            
                        elif response.status >= 500:
                            logger.error(f"Error del servidor (HTTP {response.status}). Reintentando...")
                            raise aiohttp.ClientError(f"HTTP {response.status}")
                            
                        response.raise_for_status()
                        data = await response.json()
                        
                        # Procesar resultados
                        results = []
                        seen_urls = set()  # Para evitar duplicados
                        
                        for result in data.get("web", {}).get("results", []):
                            url = result.get("url", "")
                            if not url:
                                continue
                                
                            # Limpiar y normalizar la URL
                            clean_url = url.split('?')[0].split('#')[0].rstrip('/')
                            
                            # Filtrar dominios no deseados
                            if any(domain in clean_url.lower() for domain in ['google.', 'bing.', 'yandex.', 'facebook.', 'twitter.']):
                                continue
                                
                            # Evitar duplicados
                            if clean_url not in seen_urls:
                                seen_urls.add(clean_url)
                                results.append(clean_url)
                                
                                # Limitar al número de resultados solicitados
                                if len(results) >= num_results:
                                    break
                        
                        logger.info(f"Búsqueda Brave completada. Resultados: {len(results)}/{num_results}")
                        return results
                        
                except aiohttp.ClientError as e:
                    if retry_count < max_retries:
                        logger.warning(f"Error de conexión (intento {retry_count + 1}/{max_retries}): {str(e)}")
                        return await search_brave(query, api_key, num_results, timeout, offset, retry_count + 1)
                    raise
                
    except asyncio.TimeoutError as e:
        if retry_count < max_retries:
            logger.warning(f"Timeout en la petición (intento {retry_count + 1}/{max_retries}). Reintentando...")
            return await search_brave(query, api_key, num_results, timeout, offset, retry_count + 1)
        logger.error(f"Tiempo de espera agotado en búsqueda Brave después de {max_retries} intentos")
        return []
        
    except Exception as e:
        if retry_count < max_retries and not isinstance(e, aiohttp.ClientResponseError):
            logger.warning(f"Error en la petición (intento {retry_count + 1}/{max_retries}): {str(e)}")
            return await search_brave(query, api_key, num_results, timeout, offset, retry_count + 1)
            
        logger.error(f"Error en búsqueda Brave después de {retry_count + 1} intentos: {str(e)}", exc_info=True)
        return []

# ==================== BING (web scraping) ====================

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
        
        async with aiohttp.ClientSession() as session:
            async with session.get(base_url, params=params, headers=headers, timeout=timeout) as response:
                response.raise_for_status()
                html = await response.text()
        
        soup = BeautifulSoup(html, 'html.parser')
        results = []
        
        # Buscar enlaces en los resultados de búsqueda
        for result in soup.select('li.b_algo h2 a'):
            url = result.get('href', '')
            # Verificar que la URL sea válida y no sea un enlace de Bing
            if url and url.startswith('http') and 'bing.com' not in url:
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

async def search_multiple_engines(
    query: str, 
    engines: list = ["google", "duckduckgo"], 
    brave_api_key: str = None,
    num_results: int = 500,  # Aumentado de 10 a 20 resultados por defecto
    timeouts: Dict[str, int] = None,
    pages: int = 10  # Número de páginas a buscar por motor
) -> Dict[str, List[str]]:
    """
    Busca en múltiples motores de búsqueda y devuelve los resultados combinados.
    
    Args:
        query: Término de búsqueda
        engines: Lista de motores a usar ("google", "duckduckgo", "gigablast", "bing", "brave")
        brave_api_key: Clave API para Brave Search (requerido si se usa "brave")
        num_results: Número máximo de resultados por motor
        timeouts: Diccionario con timeouts personalizados por motor
        
    Returns:
        Diccionario con los resultados de cada motor
    """
    # Configurar timeouts por defecto (más cortos para evitar esperas largas)
    default_timeout = 15  # Aumentado de 10 a 15 segundos por defecto
    if timeouts is None:
        timeouts = {}
    
    # Configurar timeouts específicos para cada motor
    engine_timeouts = {
        'google': timeouts.get('google', 20),     # Google puede ser más lento
        'duckduckgo': timeouts.get('duckduckgo', default_timeout),
        'gigablast': timeouts.get('gigablast', default_timeout),
        'bing': timeouts.get('bing', default_timeout),
        'brave': timeouts.get('brave', 20)        # Brave API puede ser más lenta
    }
    
    # Preparar tareas de búsqueda con paginación
    search_tasks = []
    
    if "google" in engines:
        # Para Google, manejamos la paginación manualmente
        # Limitar a máximo 2 intentos para Google
        google_pages = min(2, pages)
        for page in range(google_pages):
            start = page * 10  # Google muestra 10 resultados por página
            search_tasks.append((
                "google",
                safe_search(
                    search_google(query, num_results=10, start=start, timeout=engine_timeouts['google']),
                    "google",
                    engine_timeouts['google'] + 5  # Dar 5 segundos adicionales al timeout
                )
            ))
    
    if "duckduckgo" in engines:
        # DuckDuckGo maneja la paginación internamente
        search_tasks.append((
            "duckduckgo",
            safe_search(
                search_duckduckgo(query, num_results * pages, timeout=engine_timeouts['duckduckgo']),
                "duckduckgo",
                engine_timeouts['duckduckgo'] + 5 * pages  # Aumentar timeout para más resultados
            )
        ))
    
    if "gigablast" in engines:
        search_tasks.append((
            "gigablast",
            safe_search(
                search_gigablast(query, num_results, timeout=engine_timeouts['gigablast']),
                "gigablast",
                engine_timeouts['gigablast'] + 5
            )
        ))
    
    if "bing" in engines:
        # Bing con paginación
        for page in range(pages):
            first = page * 10 + 1  # Bing usa first=1, 11, 21, etc.
            search_tasks.append((
                "bing",
                safe_search(
                    search_bing(query, num_results=10, first=first, timeout=engine_timeouts['bing']),
                    "bing",
                    engine_timeouts['bing'] + 5
                )
            ))
    
    if "brave" in engines and brave_api_key:
        # Brave Search con paginación
        for page in range(pages):
            offset = page * 10  # Brave usa offset para paginación
            search_tasks.append((
                "brave",
                safe_search(
                    search_brave(query, brave_api_key, num_results=10, offset=offset, timeout=engine_timeouts['brave']),
                    "brave",
                    engine_timeouts['brave'] + 5
                )
            ))
    
    # Ejecutar búsquedas en paralelo
    results = {}
    if search_tasks:
        logger.info(f"Ejecutando búsquedas en paralelo: {[name for name, _ in search_tasks]}")
        
        # Ejecutar todas las búsquedas en paralelo
        search_results = await asyncio.gather(
            *[task for _, task in search_tasks],
            return_exceptions=False  # Ya manejamos las excepciones en safe_search
        )
        
        # Procesar resultados
        for (engine_name, _), result in zip(search_tasks, search_results):
            results[engine_name] = result if isinstance(result, list) else []
    
    # Resumen de resultados
    result_summary = {k: len(v) for k, v in results.items()}
    logger.info(f"Búsqueda completada. Resultados: {result_summary}")
    
    return results

# Ejemplo de uso
if __name__ == "__main__":
    # Configuración de ejemplo
    test_query = "Python programming"
    brave_api = "BSAcXMk3AVqFu0jmv0iQ35tsxdwjuhO"  # Usar la clave real en producción
    
    # Realizar búsqueda en todos los motores
    results = search_multiple_engines(
        query=test_query,
        engines=["gigablast", "bing"],  # Sin Brave para la prueba
        brave_api_key=brave_api,
        num_results=5
    )
    
    # Mostrar resultados
    for engine, urls in results.items():
        print(f"\n{engine.upper()} - {len(urls)} resultados:")
        for i, url in enumerate(urls[:5], 1):
            print(f"{i}. {url}")
