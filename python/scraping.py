# -*- coding: utf-8 -*-
from fastapi import FastAPI
from fastapi.concurrency import run_in_threadpool
from pydantic import BaseModel
from typing import List, Dict, Any
from googlesearch import search as google_search
from duckduckgo_search import DDGS
from playwright.sync_api import sync_playwright, TimeoutError as PlaywrightTimeoutError
import requests, re, random, time, warnings
from bs4 import BeautifulSoup
from pathlib import Path
import uvicorn

# Suprimir advertencias de InsecureRequestWarning al usar verify=False
from requests.packages.urllib3.exceptions import InsecureRequestWarning
warnings.simplefilter('ignore', InsecureRequestWarning)

app = FastAPI(
    title="Buscador Multi-Fuente de Correos",
    description="Google, DuckDuckGo y Empresite (Playwright) con filtro de imágenes",
    version="3.1.6" # Versión incrementada
)

# ---------------- Modelos ----------------
class KeywordRequest(BaseModel):
    keyword: str
    results: int = 10

class EmpresiteRequest(BaseModel):
    actividad: str    # p.ej. TRANSPORTE
    provincia: str    # p.ej. MURCIA
    paginas: int = 1  # número de páginas

# --------- Utilidades comunes ----------
UA_LIST = [
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:125.0) Gecko/20100101 Firefox/125.0",
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36",
    "Mozilla/5.0 (iPhone; CPU iPhone OS 17_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4.1 Mobile/15E148 Safari/604.1"
]

def random_headers() -> dict:
    """Genera cabeceras HTTP aleatorias."""
    return {
        "User-Agent": random.choice(UA_LIST),
        "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7",
        "Accept-Language": "es-ES,es;q=0.9,en;q=0.8",
        "Referer": "https://www.google.com/",
        "Sec-Ch-Ua": '"Chromium";v="124", "Google Chrome";v="124", "Not-A.Brand";v="99"',
        "Sec-Ch-Ua-Mobile": "?0",
        "Sec-Ch-Ua-Platform": '"Windows"',
        "Sec-Fetch-Dest": "document",
        "Sec-Fetch-Mode": "navigate",
        "Sec-Fetch-Site": "cross-site",
        "Sec-Fetch-User": "?1",
        "Upgrade-Insecure-Requests": "1"
    }

def _filter_emails(emails: List[str]) -> List[str]:
    """Elimina cadenas que acaben en extensión de imagen o dominios irrelevantes."""
    cleaned = []
    ignore_extensions = ('.jpg', '.jpeg', '.png', '.gif', '.svg', '.webp', '.bmp', '.tif', '.tiff')
    ignore_domains = ('@w3.org', '@sentry.io', '@example.com', '@domain.com', '@yourdomain.com')

    for e in emails:
        lower_e = e.lower()
        if any(lower_e.endswith(ext) for ext in ignore_extensions):
            continue
        if any(domain in lower_e for domain in ignore_domains):
            continue
        # Validar formato básico y evitar duplicados insensibles a mayúsculas/minúsculas
        if re.match(r"^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$", e):
            if lower_e not in [c.lower() for c in cleaned]:
                cleaned.append(e)
    return cleaned

def extract_emails_from_url(url: str) -> List[str]:
    """Extrae correos de una URL usando requests, ignorando URLs relativas y errores SSL."""
    if not url.startswith(('http://', 'https://')):
        print(f"Ignorando URL relativa o inválida: {url}")
        return []
    try:
        # verify=False: Ignora errores de verificación SSL (¡INSEGURO!)
        r = requests.get(url, headers=random_headers(), timeout=20, allow_redirects=True, verify=False)
        r.raise_for_status()
        # Usar regex mejorada para encontrar emails
        found = re.findall(r'\b[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}\b', r.text)
        return _filter_emails(list(set(found))) # Eliminar duplicados antes de filtrar
    except requests.exceptions.SSLError as e:
        print(f"Error SSL al acceder a {url} (verificación desactivada): {e}")
        return []
    except requests.exceptions.RequestException as e:
        print(f"Error de red al acceder a {url}: {e}")
        return []
    except Exception as e:
        print(f"Error inesperado procesando {url}: {e}")
        return []

# ------- Google + DuckDuckGo -------
def duckduckgo_search(q: str, n: int) -> List[str]:
    """Realiza una búsqueda en DuckDuckGo y devuelve URLs."""
    results = []
    try:
        headers = random_headers()
        with DDGS(headers=headers, timeout=20) as ddgs:
            for r in ddgs.text(q, max_results=n):
                if "href" in r:
                    results.append(r["href"])
    except Exception as e:
        print(f"Error durante la búsqueda en DuckDuckGo: {e}")
    return results

@app.post("/buscar-correos-multibuscador")
async def buscador_completo(d: KeywordRequest) -> Dict[str, Any]:
    """Busca URLs en Google y DuckDuckGo, luego extrae correos."""
    google_urls = []
    try:
        time.sleep(random.uniform(1, 3))
        google_urls = list(google_search(d.keyword, num_results=d.results, lang='es'))
    except Exception as e:
        print(f"Error en búsqueda de Google: {e}")

    ddg_urls = duckduckgo_search(d.keyword, d.results)
    urls = list(set(google_urls + ddg_urls))
    print(f"Encontradas {len(urls)} URLs únicas para '{d.keyword}'")

    resultados_dict = {}
    processed_urls_count = 0
    for url in urls:
        processed_urls_count += 1
        time.sleep(random.uniform(0.5, 1.5))
        emails = await run_in_threadpool(extract_emails_from_url, url)
        if emails:
             resultados_dict[url] = emails

    return {
        "keyword": d.keyword,
        "urls_procesadas": processed_urls_count,
        "urls_con_correos": len(resultados_dict),
        "resultados": resultados_dict
    }

@app.post("/buscar-correos-limpio")
async def buscador_limpio(d: KeywordRequest) -> Dict[str, Any]:
    """Busca URLs y devuelve una lista plana de {url, correo}."""
    google_urls = []
    try:
        time.sleep(random.uniform(1, 3))
        google_urls = list(google_search(d.keyword, num_results=d.results, lang='es'))
    except Exception as e:
        print(f"Error en búsqueda de Google: {e}")

    ddg_urls = duckduckgo_search(d.keyword, d.results)
    urls = list(set(google_urls + ddg_urls))
    print(f"Encontradas {len(urls)} URLs únicas para '{d.keyword}'")

    flat_results = []
    processed_urls_count = 0
    async def process_url(url):
        nonlocal processed_urls_count
        processed_urls_count += 1
        time.sleep(random.uniform(0.5, 1.5))
        emails = await run_in_threadpool(extract_emails_from_url, url)
        return [{"url": url, "correo": mail} for mail in emails]

    for url in urls:
        results_for_url = await process_url(url)
        flat_results.extend(results_for_url)

    return {
        "keyword": d.keyword,
        "urls_procesadas": processed_urls_count,
        "total_correos_encontrados": len(flat_results),
        "correos": flat_results
    }


# ------- Empresite con Playwright -------
BASE = "https://empresite.eleconomista.es"

def scrape_empresite_sync(act: str, prov: str, pages: int) -> List[Dict[str,str]]:
    """Scrapea Empresite usando Playwright de forma síncrona."""
    resultados = []
    print(f"Iniciando scraping en Empresite para {act} en {prov} ({pages} páginas)")
    with sync_playwright() as pw:
        try:
            browser = pw.chromium.launch(
                headless=True, # Cambiar a False para depuración visual
                args=[
                    "--disable-blink-features=AutomationControlled",
                    "--no-sandbox",
                    "--disable-dev-shm-usage"
                ]
            )
        except Exception as e:
            print(f"Error al iniciar el navegador Playwright: {e}")
            return []

        context = browser.new_context(
            user_agent=random.choice(UA_LIST),
            locale="es-ES",
            viewport={"width": 1920, "height": 1080},
            java_script_enabled=True,
            ignore_https_errors=True
        )
        page = context.new_page()

        for p in range(1, pages + 1):
            if p == 1:
                url = f"{BASE}/Actividad/{act}/provincia/{prov}/"
            else:
                url = f"{BASE}/Actividad/{act}/provincia/{prov}/PgNum-{p}/"

            print(f"Accediendo a página {p}: {url}")
            try:
                page.goto(url, wait_until="networkidle", timeout=90000)

                # --- Aceptar cookies si aparece el banner ---
                try:
                    accept_button_selector = "#onetrust-accept-btn-handler, button[aria-label*='Aceptar'], button:has-text('Aceptar')"
                    page.wait_for_selector(accept_button_selector, timeout=7000)
                    page.click(accept_button_selector)
                    print("Banner de cookies aceptado.")
                    page.wait_for_timeout(1500)
                except PlaywrightTimeoutError:
                    print("No se encontró banner de cookies o ya estaba aceptado.")
                except Exception as e_cookie:
                    print(f"Error al intentar aceptar cookies: {e_cookie}")
                # -------------------------------------------

                # *** Selector ACTUALIZADO basado en el HTML proporcionado ***
                # Busca cada 'tarjeta' de empresa individual
                main_selector = "div.cardCompanyBox[itemprop='itemListElement']"
                print(f"Esperando por el selector principal: '{main_selector}'")
                # Esperar a que aparezca al menos el primer elemento de la lista
                page.wait_for_selector(main_selector, state="visible", timeout=45000)
                print(f"Selector principal '{main_selector}' encontrado en página {p}.")

            except PlaywrightTimeoutError:
                print(f"⚠ Timeout esperando '{main_selector}' en página {p} ({url}). La estructura de la página puede haber cambiado o no hay resultados.")
                continue # Saltar a la siguiente página
            except Exception as e:
                print(f"⚠ Error inesperado cargando o esperando selector en página {p} ({url}): {e}")
                continue # Saltar a la siguiente página

            # *** Lógica de extracción de enlaces ACTUALIZADA ***
            hrefs = []
            try:
                # Obtener todos los elementos que coinciden con el selector principal
                list_items = page.query_selector_all(main_selector)
                print(f"Encontrados {len(list_items)} elementos de lista en página {p} con selector '{main_selector}'.")

                # Iterar sobre cada elemento de la lista para encontrar el enlace
                for item in list_items:
                    # Selector para el enlace dentro del h3 de cada item
                    link_element = item.query_selector("h3 a[href$='.html']")
                    if link_element:
                         href = link_element.get_attribute('href')
                         if href:
                             hrefs.append(href)
                         else:
                             print(f"  Elemento <a> encontrado pero sin atributo href dentro de {main_selector}")
                    else:
                        print(f"  No se encontró 'h3 a[href$=\".html\"]' dentro de un {main_selector}")

                print(f"Extraídos {len(hrefs)} enlaces únicos a fichas en página {p}.")
            except Exception as e:
                 print(f"Error extrayendo enlaces en página {p}: {e}")
                 # hrefs ya será []

            # Procesar cada ficha de empresa
            for href in set(hrefs): # Usar set para evitar procesar duplicados si los hubiera
                # Asegurarse de que la URL es absoluta
                ficha_url = href if href.startswith("http") else BASE + href
                print(f"  Procesando ficha: {ficha_url}")
                try:
                    page.goto(ficha_url, wait_until="domcontentloaded", timeout=60000)

                    # Extraer contenido y buscar correos
                    content = page.content()
                    mails = re.findall(r"[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}", content)
                    filtered_mails = _filter_emails(list(set(mails)))

                    if filtered_mails:
                        nombre = "Sin nombre"
                        try:
                            # Intentar obtener el nombre desde el h1 o un selector similar
                            # Ajustar si es necesario basado en la estructura de la página de ficha
                            title_selector = "h1, .nombre_empresa, [itemprop='name']"
                            title_element = page.query_selector(title_selector)
                            if title_element:
                                nombre = title_element.inner_text().strip()
                        except Exception as e_title:
                            print(f"    Error obteniendo nombre de empresa: {e_title}")

                        # Guardar el primer correo encontrado
                        resultados.append({
                            "empresa": nombre,
                            "url": ficha_url,
                            "correo": filtered_mails[0]
                        })
                        print(f"    Correo encontrado: {filtered_mails[0]} para {nombre}")
                    else:
                        print(f"    No se encontraron correos válidos en {ficha_url}")

                    # Pausa aleatoria entre visitas a fichas
                    time.sleep(random.uniform(1.5, 3.5))

                except PlaywrightTimeoutError:
                    print(f"  ⚠ Timeout al cargar la ficha: {ficha_url}")
                except Exception as e_ficha:
                    print(f"  ⚠ Error procesando la ficha {ficha_url}: {e_ficha}")

        # Cerrar navegador al final
        try:
            browser.close()
            print("Navegador Playwright cerrado.")
        except Exception as e_close:
            print(f"Error al cerrar el navegador: {e_close}")

    print(f"Scraping finalizado. Total de empresas con correo encontradas: {len(resultados)}")
    return resultados

@app.post("/buscar-empresite")
async def buscador_empresite(d: EmpresiteRequest) -> Dict[str, Any]:
    """Endpoint para buscar empresas y correos en Empresite."""
    act = d.actividad.strip().upper()
    prov = d.provincia.strip().upper()
    pages = max(1, d.paginas)

    if not act or not prov:
        return {"error": "Actividad y provincia son requeridas."}

    data = await run_in_threadpool(scrape_empresite_sync, act, prov, pages)

    return {
        "actividad": act,
        "provincia": prov,
        "paginas_solicitadas": pages,
        "total_empresas_con_correo": len(data),
        "empresas": data
    }

# --- Para ejecutar localmente ---
if __name__ == "__main__":
    print("Iniciando servidor FastAPI en http://0.0.0.0:9000")
    uvicorn.run(app, host="0.0.0.0", port=9000, reload=False)
