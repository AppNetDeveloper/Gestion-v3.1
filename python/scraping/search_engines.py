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
from collections import defaultdict

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

# Reemplazado por una implementación más robusta usando la librería googlesearch
from googlesearch import search as google_search_lib

async def search_google(
    query: str, 
    num_results: int = 10, 
    lang: str = "es"
) -> List[str]:
    """
    Realiza una búsqueda en Google usando la librería googlesearch-python.
    
    Args:
        query: Término de búsqueda
        num_results: Número de resultados a devolver
        lang: Idioma de la búsqueda
        
    Returns:
        Lista de URLs encontradas
    """
    logger.info(f"Iniciando búsqueda en Google para '{query}' con {num_results} resultados.")
    # Añadir un retardo aleatorio para evitar el bloqueo
    sleep_time = random.uniform(5, 15)
    logger.info(f"Esperando {sleep_time:.2f} segundos antes de la búsqueda en Google...")
    await asyncio.sleep(sleep_time)
    try:
        # La librería googlesearch es síncrona, por lo que la ejecutamos en un thread
        # para no bloquear el bucle de eventos de asyncio.
        results = await asyncio.to_thread(
            google_search_lib,
            query,  # Pasado como argumento posicional
            num_results=num_results,
            lang=lang
        )
        
        # La librería devuelve un generador, lo convertimos a lista
        urls = list(results)
        
        logger.info(f"Búsqueda en Google completada con {len(urls)} resultados.")
        return urls

    except Exception as e:
        # Capturamos errores específicos si es necesario, como bloqueos de IP
        if "HTTP Error 429" in str(e) or "Too Many Requests" in str(e):
            logger.error(f"Error de Rate Limit en Google: {e}")
        else:
            logger.error(f"Error inesperado en búsqueda Google: {e}", exc_info=True)
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
    logger.info(f"Iniciando búsqueda en DuckDuckGo para '{query}' con {num_results} resultados.")
    
    def sync_search_with_retry():
        from duckduckgo_search import DDGS
        import time

        thread_results = []
        retries = 3
        for i in range(retries):
            try:
                logger.info(f"Intento de búsqueda en DuckDuckGo {i+1}/{retries}...")
                with DDGS(headers=get_headers(), timeout=timeout) as ddgs:
                    for r in ddgs.text(query, max_results=num_results):
                        if 'href' in r:
                            thread_results.append(r['href'])
                
                logger.info(f"Búsqueda síncrona en DuckDuckGo completada con {len(thread_results)} resultados.")
                return thread_results # Éxito, salir del bucle
            
            except Exception as e:
                # Detectar errores de rate limit por el mensaje de error
                if "Ratelimit" in str(e) or "429" in str(e) or "Too Many Requests" in str(e) or "202" in str(e):
                    logger.warning(f"Error de Rate Limit en DuckDuckGo (intento {i+1}/{retries}): {e}")
                    if i < retries - 1:
                        wait_time = (i + 1) * 10 # Incrementar el tiempo de espera
                        logger.info(f"Reintentando en {wait_time} segundos...")
                        time.sleep(wait_time)
                    else:
                        logger.error("Se superó el número máximo de reintentos para DuckDuckGo.")
                else:
                    logger.error(f"Error inesperado en la búsqueda síncrona de DuckDuckGo: {e}", exc_info=True)
                    if i < retries - 1:
                        wait_time = 5
                        logger.info(f"Reintentando en {wait_time} segundos...")
                        time.sleep(wait_time)
                    else:
                        break # No reintentar en errores inesperados

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

async def search_brave(query: str, num_results: int = 10, timeout: int = 60, offset: int = 0, retry_count: int = 0) -> List[str]:
    """
    Realiza una búsqueda usando la API de Brave Search con manejo de concurrencia y rate limiting.
    
    Args:
        query: Término de búsqueda
        num_results: Número máximo de resultados a devolver (máx 100 por petición)
        timeout: Tiempo máximo de espera en segundos
        offset: Desplazamiento para la paginación de resultados
        retry_count: Número de reintentos realizados (para uso interno)
        
    Returns:
        Lista de URLs de resultados
    """
    import os
    api_key = os.getenv('BRAVE_API_KEY')
    if not api_key:
        raise ValueError("La variable de entorno BRAVE_API_KEY no está configurada")
    global brave_semaphore
    
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
                "ui_lang": "es-ES"    # Idioma de la interfaz
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
                            return await search_brave(query, num_results, timeout, offset, min(retry_count + 1, max_retries))
                            
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

                        # Si hemos obtenido menos resultados de los solicitados, no hay más páginas.
                        if len(results) < count:
                            logger.info("No hay más páginas de resultados en Brave.")
                            return results
                            
                        return results
                        
                except aiohttp.ClientError as e:
                    if "422" in str(e):
                        logger.warning(f"Error 422 en Brave, probablemente no hay más páginas: {e}")
                        return [] # Devolver lista vacía para que no se propague el error
                    if retry_count < max_retries:
                        logger.warning(f"Error de conexión (intento {retry_count + 1}/{max_retries}): {str(e)}")
                        return await search_brave(query, num_results, timeout, offset, retry_count + 1)
                    logger.error(f"Error de cliente en Brave después de {max_retries} intentos: {e}")
                    return []
                
    except asyncio.TimeoutError as e:
        if retry_count < max_retries:
            logger.warning(f"Timeout en la petición (intento {retry_count + 1}/{max_retries}). Reintentando...")
            return await search_brave(query, num_results, timeout, offset, retry_count + 1)
        logger.error(f"Tiempo de espera agotado en búsqueda Brave después de {max_retries} intentos")
        return []
        
    except Exception as e:
        if retry_count < max_retries and not isinstance(e, aiohttp.ClientResponseError):
            logger.warning(f"Error en la petición (intento {retry_count + 1}/{max_retries}): {str(e)}")
            return await search_brave(query, num_results, timeout, offset, retry_count + 1)
            
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
    num_results: int = 500,  # Aumentado de 10 a 20 resultados por defecto
    timeouts: Dict[str, int] = None,
    pages: int = 10  # Número de páginas a buscar por motor
) -> Dict[str, List[str]]:
    # Eliminar motores duplicados manteniendo el orden
    engines = list(dict.fromkeys(engines))
    
    # Inicializar engine_timeouts con valores por defecto si no se proporciona
    engine_timeouts = {
        'google': 30,
        'duckduckgo': 25,
        'gigablast': 20,
        'bing': 20,
        'brave': 30
    }
    if timeouts:
        engine_timeouts.update(timeouts)
    """
    Busca en múltiples motores de búsqueda y devuelve los resultados combinados.
    
    Args:
        query: Término de búsqueda
        engines: Lista de motores a usar ("google", "duckduckgo", "gigablast", "bing", "brave")
        num_results: Número máximo de resultados por motor
        timeouts: Diccionario con timeouts personalizados por motor
        
    Returns:
        Diccionario con los resultados de cada motor
    """
    # Configurar timeouts y reintentos por motor
    engine_config = {
        'google': {
            'timeout': timeouts.get('google', 30),
            'max_retries': 2,
            'retry_delay': 5
        },
        'duckduckgo': {
            'timeout': timeouts.get('duckduckgo', 25),
            'max_retries': 3,
            'retry_delay': 10
        },
        'gigablast': {
            'timeout': timeouts.get('gigablast', 20),
            'max_retries': 1,
            'retry_delay': 3
        },
        'bing': {
            'timeout': timeouts.get('bing', 20),
            'max_retries': 2,
            'retry_delay': 5
        },
        'brave': {
            'timeout': timeouts.get('brave', 30),
            'max_retries': 3,
            'retry_delay': 15
        }
    }
    
    # Preparar tareas de búsqueda con paginación
    search_tasks = []
    
    if "google" in engines:
        # Para Google, la librería maneja la paginación internamente.
        # Hacemos una sola petición con el número total de resultados deseados (e.g., 20).
        search_tasks.append((
            "google",
            safe_search(
                search_google(query, num_results=20),
                "google",
                engine_timeouts['google'] + 5
            )
        ))
    
    if "duckduckgo" in engines:
        # DuckDuckGo maneja la paginación internamente
        search_tasks.append((
            "duckduckgo",
            safe_search(
                search_duckduckgo(query, num_results * pages),
                "duckduckgo",
                engine_timeouts['duckduckgo'] + 5 * pages  # Aumentar timeout para más resultados
            )
        ))
    
    if "gigablast" in engines:
        search_tasks.append((
            "gigablast",
            safe_search(
                search_gigablast(query, num_results),
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
                    search_bing(query, num_results=10, first=first),
                    "bing",
                    engine_timeouts['bing'] + 5
                )
            ))
    
    if "brave" in engines:
        # Brave Search con paginación
        for page in range(pages):
            offset = page * 10  # Brave usa offset para paginación
            search_tasks.append((
                "brave",
                safe_search(
                    search_brave(query, num_results=10, offset=offset),
                    "brave",
                    engine_timeouts['brave'] + 5
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
