# -*- coding: utf-8 -*-
from fastapi import FastAPI, BackgroundTasks
from fastapi.concurrency import run_in_threadpool
from pydantic import BaseModel
from typing import List, Dict, Any, Optional
from googlesearch import search as google_search
# from duckduckgo_search import DDGS, AsyncDDGS # AsyncDDGS puede no estar disponible
from duckduckgo_search import DDGS # Usar solo DDGS síncrono
from playwright.sync_api import sync_playwright, TimeoutError as PlaywrightTimeoutError
import requests, re, random, time, warnings, urllib.parse
import shutil
import httpx
from bs4 import BeautifulSoup
from pathlib import Path
import uvicorn
import asyncio

# Suprimir advertencias de InsecureRequestWarning al usar verify=False
from requests.packages.urllib3.exceptions import InsecureRequestWarning
warnings.simplefilter('ignore', InsecureRequestWarning)

# Directorio para capturas de P. Amarillas
SCREENSHOT_PA_DIR = Path("screenshots_pa")

# --- Constantes Base URL ---
BASE_EMPRESITE = "https://empresite.eleconomista.es"
BASE_PAGINAS_AMARILLAS = "https://www.paginasamarillas.es"


app = FastAPI(
    title="Buscador Multi-Fuente Asíncrono (Google, DDG, Empresite, P. Amarillas)",
    description="Extrae correos, teléfonos y nombres. Soporta procesamiento síncrono o asíncrono con callback URL.",
    version="3.3.4" # Versión incrementada
)

# ---------------- Modelos (con callback_url) ----------------
class KeywordRequest(BaseModel):
    keyword: str
    results: int = 10
    callback_url: Optional[str] = None # URL opcional para recibir resultados

class DirectoryRequest(BaseModel):
    actividad: str
    provincia: str
    paginas: int = 1
    callback_url: Optional[str] = None # URL opcional para recibir resultados

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

def extract_data_from_url(url: str) -> Dict[str, Optional[str] | List[str]]:
    """Extrae nombre (del título), correos y teléfono de una URL usando requests."""
    # (Sin cambios)
    data = {"nombre": None, "correos": [], "telefono": None}
    if not url.startswith(('http://', 'https://')):
        print(f"Ignorando URL relativa o inválida: {url}")
        return data

    try:
        r = requests.get(url, headers=random_headers(), timeout=20, allow_redirects=True, verify=False)
        r.raise_for_status()
        content = r.text
        content_type = r.headers.get("content-type", "").lower()

        if 'html' not in content_type:
             print(f"Contenido no es HTML en {url}, tipo: {content_type}. Saltando extracción de nombre.")
             data["nombre"] = "No aplicable (No HTML)"
        else:
            soup = BeautifulSoup(content, 'html.parser')
            title_tag = soup.find('title')
            if title_tag and title_tag.string:
                nombre_bruto = title_tag.string.strip()
                partes_a_quitar = ["|", "-", "Inicio", "Homepage", "Página principal", "Contacto", "Empresa"]
                try:
                    domain = url.split('/')[2].replace('www.', '')
                    partes_a_quitar.append(domain)
                except: pass
                for parte in partes_a_quitar:
                    nombre_bruto = re.sub(re.escape(parte), '', nombre_bruto, flags=re.IGNORECASE)
                data["nombre"] = " ".join(nombre_bruto.split())
                if not data["nombre"]: data["nombre"] = title_tag.string.strip()
            else:
                data["nombre"] = "No encontrado"

        mails = re.findall(r'\b[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}\b', content)
        data["correos"] = _filter_emails(list(set(mails)))

        telefonos = re.findall(r'\b[6-9]\d{2}\s?\d{3}\s?\d{3}\b|\b[6-9]\d{8}\b', content)
        if telefonos:
            data["telefono"] = re.sub(r'\s+', '', telefonos[0])
        else:
            data["telefono"] = "No encontrado"
        return data
    except requests.exceptions.RequestException as e:
        print(f"Error de red/SSL al acceder a {url}: {e}")
        return data
    except Exception as e:
        print(f"Error inesperado procesando {url}: {e}")
        return data

# ------- Búsquedas en DuckDuckGo (Solo versión Síncrona) -------

def duckduckgo_search_sync(q: str, n: int) -> List[str]:
    """Realiza una búsqueda SÍNCRONA en DuckDuckGo y devuelve URLs."""
    # (Sin cambios)
    results = []
    print(f"[DDG Sync] Buscando: '{q}'")
    try:
        headers = random_headers()
        with DDGS(headers=headers, timeout=20) as ddgs:
            sync_results = ddgs.text(q, max_results=n)
            for r in sync_results:
                if "href" in r:
                    results.append(r["href"])
    except Exception as e:
        print(f"[DDG Sync] Error durante la búsqueda: {e}")
    print(f"[DDG Sync] Encontradas {len(results)} URLs.")
    return results

# --- Funciones Asíncronas para Tareas de Fondo y Callbacks ---

async def send_callback(callback_url: str, payload: Dict[str, Any]):
    """Función auxiliar para enviar el resultado al callback_url."""
    # (Sin cambios)
    if not callback_url: return
    async with httpx.AsyncClient(timeout=30.0) as client:
        try:
            response = await client.post(callback_url, json=payload)
            response.raise_for_status()
            print(f"Callback enviado exitosamente a {callback_url}, Status: {response.status_code}")
        except httpx.RequestError as e: print(f"Error al enviar callback a {callback_url}: {e}")
        except Exception as e: print(f"Error inesperado durante el envío del callback a {callback_url}: {e}")

async def run_google_ddg_task(keyword: str, results_num: int, callback_url: str):
    """Tarea de fondo para buscar en Google/DDG y enviar callback."""
    # (Sin cambios)
    print(f"[BG Task Google/DDG] Iniciando para keyword: '{keyword}'")
    google_urls = []
    try:
        google_urls = await run_in_threadpool(list, google_search(keyword, num_results=results_num, lang='es'))
    except Exception as e: print(f"[BG Task Google/DDG] Error en búsqueda de Google: {e}")
    ddg_urls = await run_in_threadpool(duckduckgo_search_sync, keyword, results_num)
    urls = list(set(google_urls + ddg_urls))
    print(f"[BG Task Google/DDG] Encontradas {len(urls)} URLs únicas para '{keyword}'")
    resultados_finales = {}
    processed_urls_count = 0
    urls_con_datos = 0
    for url in urls:
        processed_urls_count += 1
        print(f"[BG Task Google/DDG] Procesando URL {processed_urls_count}/{len(urls)}: {url}")
        extracted_data = await run_in_threadpool(extract_data_from_url, url)
        if extracted_data.get("correos") or (extracted_data.get("telefono") and extracted_data.get("telefono") != "No encontrado"):
            urls_con_datos += 1
            resultados_finales[url] = {
                "nombre": extracted_data.get("nombre", "No encontrado"),
                "correos": extracted_data.get("correos", []),
                "telefono": extracted_data.get("telefono", "No encontrado")
            }
            print(f"  -> Datos encontrados")
        else: print("  -> No se encontraron datos útiles.")
    callback_payload = {
        "status": "completed", "keyword": keyword, "urls_procesadas": processed_urls_count,
        "urls_con_datos_encontrados": urls_con_datos, "resultados": resultados_finales
    }
    await send_callback(callback_url, callback_payload)
    print(f"[BG Task Google/DDG] Tarea completada para keyword: '{keyword}'")


async def run_google_ddg_limpio_task(keyword: str, results_num: int, callback_url: str):
    """Tarea de fondo para buscar en Google/DDG (formato limpio) y enviar callback."""
    # (Sin cambios)
    print(f"[BG Task Google/DDG Limpio] Iniciando para keyword: '{keyword}'")
    google_urls = []
    try:
        google_urls = await run_in_threadpool(list, google_search(keyword, num_results=results_num, lang='es'))
    except Exception as e: print(f"[BG Task Google/DDG Limpio] Error en búsqueda de Google: {e}")
    ddg_urls = await run_in_threadpool(duckduckgo_search_sync, keyword, results_num)
    urls = list(set(google_urls + ddg_urls))
    print(f"[BG Task Google/DDG Limpio] Encontradas {len(urls)} URLs únicas para '{keyword}'")
    flat_results = []
    processed_urls_count = 0
    for url in urls:
        processed_urls_count += 1
        print(f"[BG Task Google/DDG Limpio] Procesando URL {processed_urls_count}/{len(urls)}: {url}")
        extracted_data = await run_in_threadpool(extract_data_from_url, url)
        nombre = extracted_data.get("nombre", "No encontrado")
        telefono = extracted_data.get("telefono", "No encontrado")
        correos = extracted_data.get("correos", [])
        if correos:
            for mail in correos: flat_results.append({"url": url, "nombre": nombre, "correo": mail, "telefono": telefono})
            print(f"  -> {len(correos)} correos encontrados.")
        elif telefono != "No encontrado":
             flat_results.append({"url": url, "nombre": nombre, "correo": "No encontrado", "telefono": telefono})
             print(f"  -> Teléfono encontrado ({telefono}), sin correos.")
        else: print(f"  -> No se encontraron datos útiles.")
    callback_payload = {
        "status": "completed", "keyword": keyword, "urls_procesadas": processed_urls_count,
        "total_entradas_generadas": len(flat_results), "datos": flat_results
    }
    await send_callback(callback_url, callback_payload)
    print(f"[BG Task Google/DDG Limpio] Tarea completada para keyword: '{keyword}'")


async def run_empresite_task(actividad: str, provincia: str, paginas: int, callback_url: str):
    """Tarea de fondo para buscar en Empresite y enviar callback."""
    # (Sin cambios)
    print(f"[BG Task Empresite] Iniciando para {actividad} en {provincia}")
    data = await run_in_threadpool(scrape_empresite_sync, actividad, provincia, paginas)
    callback_payload = {
        "status": "completed", "fuente": "Empresite", "actividad": actividad, "provincia": provincia,
        "paginas_procesadas": paginas, "total_empresas_encontradas": len(data), "empresas": data
    }
    await send_callback(callback_url, callback_payload)
    print(f"[BG Task Empresite] Tarea completada para {actividad} en {provincia}")


async def run_paginas_amarillas_task(actividad: str, provincia: str, paginas: int, callback_url: str):
    """Tarea de fondo para buscar en Páginas Amarillas y enviar callback."""
    print(f"[BG Task P. Amarillas] Iniciando para {actividad} en {provincia}")
    # *** Pasar BASE_PAGINAS_AMARILLAS a la función ejecutada en el threadpool ***
    data = await run_in_threadpool(scrape_paginas_amarillas_sync, actividad, provincia, paginas, BASE_PAGINAS_AMARILLAS)
    callback_payload = {
        "status": "completed", "fuente": "Paginas Amarillas", "actividad": actividad, "provincia": provincia,
        "paginas_solicitadas": paginas, "paginas_procesadas": 1,
        "total_empresas_encontradas": len(data), "empresas": data
    }
    await send_callback(callback_url, callback_payload)
    print(f"[BG Task P. Amarillas] Tarea completada para {actividad} en {provincia}")

# ------- Endpoints -------
# (Sin cambios en los endpoints)

@app.post("/buscar-google-ddg")
async def buscador_google_ddg(d: KeywordRequest, background_tasks: BackgroundTasks) -> Dict[str, Any]:
    """Busca URLs en Google/DDG. Síncrono o asíncrono con callback."""
    if d.callback_url and d.callback_url.strip().lower() != "false":
        print(f"Recibida solicitud ASÍNCRONA para Google/DDG (keyword: {d.keyword}) -> {d.callback_url}")
        background_tasks.add_task(run_google_ddg_task, d.keyword, d.results, d.callback_url)
        return {"status": "accepted", "message": "Proceso de búsqueda en Google/DDG iniciado. Resultados se enviarán a la callback URL."}
    else:
        print(f"Recibida solicitud SÍNCRONA para Google/DDG (keyword: {d.keyword})")
        google_urls = []
        try:
            google_urls = list(google_search(d.keyword, num_results=d.results, lang='es'))
        except Exception as e: print(f"Error en búsqueda de Google: {e}")
        ddg_urls = duckduckgo_search_sync(d.keyword, d.results)
        urls = list(set(google_urls + ddg_urls))
        print(f"Encontradas {len(urls)} URLs únicas para '{d.keyword}'")
        resultados_finales = {}
        processed_urls_count = 0
        urls_con_datos = 0
        for url in urls:
            processed_urls_count += 1
            print(f"Procesando URL {processed_urls_count}/{len(urls)}: {url}")
            extracted_data = extract_data_from_url(url)
            if extracted_data.get("correos") or (extracted_data.get("telefono") and extracted_data.get("telefono") != "No encontrado"):
                urls_con_datos += 1
                resultados_finales[url] = {
                    "nombre": extracted_data.get("nombre", "No encontrado"),
                    "correos": extracted_data.get("correos", []),
                    "telefono": extracted_data.get("telefono", "No encontrado")
                }
                print(f"  -> Datos encontrados")
            else: print("  -> No se encontraron datos útiles.")
        return {
            "keyword": d.keyword,
            "urls_procesadas": processed_urls_count,
            "urls_con_datos_encontrados": urls_con_datos,
            "resultados": resultados_finales
        }

@app.post("/buscar-google-ddg-limpio")
async def buscador_google_ddg_limpio(d: KeywordRequest, background_tasks: BackgroundTasks) -> Dict[str, Any]:
    """Busca URLs en Google/DDG (formato limpio). Síncrono o asíncrono con callback."""
    if d.callback_url and d.callback_url.strip().lower() != "false":
        print(f"Recibida solicitud ASÍNCRONA para Google/DDG Limpio (keyword: {d.keyword}) -> {d.callback_url}")
        background_tasks.add_task(run_google_ddg_limpio_task, d.keyword, d.results, d.callback_url)
        return {"status": "accepted", "message": "Proceso de búsqueda Google/DDG (limpio) iniciado. Resultados se enviarán a la callback URL."}
    else:
        print(f"Recibida solicitud SÍNCRONA para Google/DDG Limpio (keyword: {d.keyword})")
        google_urls = []
        try:
            google_urls = list(google_search(d.keyword, num_results=d.results, lang='es'))
        except Exception as e: print(f"Error en búsqueda de Google: {e}")
        ddg_urls = duckduckgo_search_sync(d.keyword, d.results)
        urls = list(set(google_urls + ddg_urls))
        print(f"Encontradas {len(urls)} URLs únicas para '{d.keyword}'")
        flat_results = []
        processed_urls_count = 0
        for url in urls:
            processed_urls_count += 1
            print(f"Procesando URL {processed_urls_count}/{len(urls)}: {url}")
            extracted_data = extract_data_from_url(url)
            nombre = extracted_data.get("nombre", "No encontrado")
            telefono = extracted_data.get("telefono", "No encontrado")
            correos = extracted_data.get("correos", [])
            if correos:
                for mail in correos:
                    flat_results.append({"url": url, "nombre": nombre, "correo": mail, "telefono": telefono})
                print(f"  -> {len(correos)} correos encontrados.")
            elif telefono != "No encontrado":
                 flat_results.append({"url": url, "nombre": nombre, "correo": "No encontrado", "telefono": telefono})
                 print(f"  -> Teléfono encontrado ({telefono}), sin correos.")
            else: print(f"  -> No se encontraron datos útiles.")
        return {
            "keyword": d.keyword,
            "urls_procesadas": processed_urls_count,
            "total_entradas_generadas": len(flat_results),
            "datos": flat_results
        }

@app.post("/buscar-empresite")
async def buscador_empresite(d: DirectoryRequest, background_tasks: BackgroundTasks) -> Dict[str, Any]:
    """Busca en Empresite. Síncrono o asíncrono con callback."""
    act = d.actividad.strip().upper()
    prov = d.provincia.strip().upper()
    pages = max(1, d.paginas)
    if not act or not prov: return {"error": "Actividad y provincia son requeridas."}

    if d.callback_url and d.callback_url.strip().lower() != "false":
        print(f"Recibida solicitud ASÍNCRONA para Empresite ({act} en {prov}) -> {d.callback_url}")
        background_tasks.add_task(run_empresite_task, act, prov, pages, d.callback_url)
        return {"status": "accepted", "message": "Proceso de búsqueda en Empresite iniciado. Resultados se enviarán a la callback URL."}
    else:
        print(f"Recibida solicitud SÍNCRONA para Empresite ({act} en {prov})")
        data = await run_in_threadpool(scrape_empresite_sync, act, prov, pages)
        return {
            "fuente": "Empresite", "actividad": act, "provincia": prov,
            "paginas_solicitadas": pages, "total_empresas_encontradas": len(data), "empresas": data
        }

@app.post("/buscar-paginas-amarillas")
async def buscador_paginas_amarillas(d: DirectoryRequest, background_tasks: BackgroundTasks) -> Dict[str, Any]:
    """Busca en Páginas Amarillas (Pág 1). Síncrono o asíncrono con callback."""
    act = d.actividad.strip()
    prov = d.provincia.strip()
    pages = 1 # Forzar siempre a 1 página
    print(f"[P. Amarillas] Solicitud recibida. Forzando búsqueda a 1 página para evitar bloqueos.")
    if not act or not prov: return {"error": "Actividad y provincia son requeridas."}
    if re.search(r'[<>:"/\\|?*]', prov): return {"error": "Provincia contiene caracteres inválidos."}

    if d.callback_url and d.callback_url.strip().lower() != "false":
        print(f"Recibida solicitud ASÍNCRONA para P. Amarillas ({act} en {prov}) -> {d.callback_url}")
        # *** Pasar BASE_PAGINAS_AMARILLAS a la tarea de fondo ***
        background_tasks.add_task(run_paginas_amarillas_task, act, prov, d.paginas, d.callback_url)
        return {"status": "accepted", "message": "Proceso de búsqueda en Páginas Amarillas (pág 1) iniciado. Resultados se enviarán a la callback URL."}
    else:
        print(f"Recibida solicitud SÍNCRONA para P. Amarillas ({act} en {prov})")
        # *** Pasar BASE_PAGINAS_AMARILLAS a la llamada síncrona ***
        data = await run_in_threadpool(scrape_paginas_amarillas_sync, act, prov, pages, BASE_PAGINAS_AMARILLAS)
        return {
            "fuente": "Paginas Amarillas", "actividad": act, "provincia": prov,
            "paginas_solicitadas": d.paginas, "paginas_procesadas": pages,
            "total_empresas_encontradas": len(data), "empresas": data
        }


# --- Funciones de Scraping Síncronas ---

def scrape_empresite_sync(act: str, prov: str, pages: int) -> List[Dict[str,str]]:
    """Scrapea Empresite usando Playwright, extrayendo email y teléfono."""
    # (Código idéntico a la versión anterior)
    resultados = []
    print(f"[Empresite] Iniciando scraping para {act} en {prov} ({pages} páginas)")
    with sync_playwright() as pw:
        try:
            browser = pw.chromium.launch(headless=True, args=["--disable-blink-features=AutomationControlled", "--no-sandbox", "--disable-dev-shm-usage"])
        except Exception as e:
            print(f"[Empresite] Error al iniciar el navegador Playwright: {e}")
            return []
        context = browser.new_context(user_agent=random.choice(UA_LIST), locale="es-ES", viewport={"width": 1920, "height": 1080}, java_script_enabled=True, ignore_https_errors=True)
        page = context.new_page()
        for p in range(1, pages + 1):
            if p == 1: url = f"{BASE_EMPRESITE}/Actividad/{act}/provincia/{prov}/"
            else: url = f"{BASE_EMPRESITE}/Actividad/{act}/provincia/{prov}/PgNum-{p}/"
            print(f"[Empresite] Accediendo a página {p}: {url}")
            try:
                page.goto(url, wait_until="domcontentloaded", timeout=90000)
                page.wait_for_selector('body', state='visible', timeout=30000)
                print(f"[Empresite] Body cargado en página {p}.")
                try:
                    accept_button_selector = "#onetrust-accept-btn-handler, button[aria-label*='Aceptar'], button:has-text('Aceptar')"
                    page.wait_for_selector(accept_button_selector, timeout=7000)
                    page.click(accept_button_selector)
                    print("[Empresite] Banner de cookies aceptado.")
                    page.wait_for_timeout(1500)
                except PlaywrightTimeoutError: print("[Empresite] No se encontró banner de cookies o ya estaba aceptado.")
                except Exception as e_cookie: print(f"[Empresite] Error al intentar aceptar cookies: {e_cookie}")
                main_selector = "div.cardCompanyBox[itemprop='itemListElement']"
                print(f"[Empresite] Esperando por el selector principal: '{main_selector}'")
                selector_timeout = 60000 if p > 1 else 45000
                page.wait_for_selector(main_selector, state="visible", timeout=selector_timeout)
                print(f"[Empresite] Selector principal '{main_selector}' encontrado en página {p}.")
                list_items_check = page.query_selector_all(main_selector)
                if not list_items_check:
                    print(f"[Empresite] ⚠ Selector '{main_selector}' encontrado, pero no hay elementos. Fin de resultados en página {p}.")
                    break
            except PlaywrightTimeoutError:
                 print(f"[Empresite] ⚠ Timeout esperando elementos en página {p} ({url}).")
                 if p > 1: print("[Empresite] Asumiendo fin de resultados."); break
                 else: continue
            except Exception as e:
                print(f"[Empresite] ⚠ Error inesperado cargando o esperando selector en página {p} ({url}): {e}")
                continue
            hrefs = []
            try:
                list_items = page.query_selector_all(main_selector)
                print(f"[Empresite] Encontrados {len(list_items)} elementos de lista en página {p}.")
                for item in list_items:
                    link_element = item.query_selector("h3 a[href$='.html']")
                    if link_element:
                         href = link_element.get_attribute('href')
                         if href: hrefs.append(href)
                print(f"[Empresite] Extraídos {len(hrefs)} enlaces únicos a fichas en página {p}.")
            except Exception as e: print(f"[Empresite] Error extrayendo enlaces en página {p}: {e}")
            if not hrefs: print(f"[Empresite] No se extrajeron enlaces en la página {p}, deteniendo paginación."); break
            for href in set(hrefs):
                ficha_url = href if href.startswith("http") else BASE_EMPRESITE + href
                print(f"[Empresite]   Procesando ficha: {ficha_url}")
                try:
                    page.goto(ficha_url, wait_until="domcontentloaded", timeout=60000)
                    content = page.content()
                    mails = re.findall(r"[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}", content)
                    filtered_mails = _filter_emails(list(set(mails)))
                    correo_final = filtered_mails[0] if filtered_mails else "No encontrado"
                    telefono_encontrado = "No encontrado"
                    telefonos = re.findall(r'\b[6-9]\d{2}\s?\d{3}\s?\d{3}\b|\b[6-9]\d{8}\b', content)
                    if telefonos: telefono_encontrado = re.sub(r'\s+', '', telefonos[0])
                    nombre = "Sin nombre"
                    try:
                        title_selector = "h1, .nombre_empresa, [itemprop='name']"
                        title_element = page.query_selector(title_selector)
                        if title_element: nombre = title_element.inner_text().strip()
                    except Exception as e_title: print(f"[Empresite]     Error obteniendo nombre: {e_title}")
                    if correo_final != "No encontrado" or telefono_encontrado != "No encontrado":
                        resultados.append({"empresa": nombre, "url": ficha_url, "correo": correo_final, "telefono": telefono_encontrado, "fuente": "Empresite"})
                        print(f"[Empresite]     Datos guardados para {nombre} (Correo: {correo_final}, Tel: {telefono_encontrado})")
                    else: print(f"[Empresite]     No se encontró ni correo ni teléfono válido para {nombre}")
                    time.sleep(random.uniform(1.5, 3.5))
                except PlaywrightTimeoutError: print(f"[Empresite]   ⚠ Timeout al cargar la ficha: {ficha_url}")
                except Exception as e_ficha: print(f"[Empresite]   ⚠ Error procesando la ficha {ficha_url}: {e_ficha}")
        try:
            browser.close(); print("[Empresite] Navegador Playwright cerrado.")
        except Exception as e_close: print(f"[Empresite] Error al cerrar el navegador: {e_close}")
    print(f"[Empresite] Scraping finalizado. Total de empresas con datos encontrados: {len(resultados)}")
    return resultados

# *** Modificar scrape_paginas_amarillas_sync para aceptar base_url ***
def scrape_paginas_amarillas_sync(act: str, prov: str, pages: int, base_url: str) -> List[Dict[str, str]]:
    """Scrapea Páginas Amarillas usando Playwright, extrayendo nombre y teléfono."""
    resultados = []
    print(f"[P. Amarillas] Iniciando scraping para '{act}' en '{prov}' ({pages} páginas)")
    if SCREENSHOT_PA_DIR.exists():
        try: shutil.rmtree(SCREENSHOT_PA_DIR); print(f"[P. Amarillas] Directorio de screenshots anterior eliminado: {SCREENSHOT_PA_DIR}")
        except Exception as e_rm: print(f"[P. Amarillas] Error eliminando directorio de screenshots: {e_rm}")
    try: SCREENSHOT_PA_DIR.mkdir(exist_ok=True); print(f"[P. Amarillas] Directorio de screenshots asegurado/creado: {SCREENSHOT_PA_DIR}")
    except Exception as e_mkdir: print(f"[P. Amarillas] Error creando directorio de screenshots: {e_mkdir}")
    act_encoded = urllib.parse.quote_plus(act.lower())
    prov_encoded = urllib.parse.quote_plus(prov.lower())
    with sync_playwright() as pw:
        try:
            browser = pw.chromium.launch(headless=True, args=["--disable-blink-features=AutomationControlled", "--no-sandbox", "--disable-dev-shm-usage"])
        except Exception as e: print(f"[P. Amarillas] Error al iniciar el navegador Playwright: {e}"); return []
        context = browser.new_context(user_agent=random.choice(UA_LIST), locale="es-ES", viewport={"width": 1920, "height": 1080}, java_script_enabled=True, ignore_https_errors=True)
        page = context.new_page()
        # *** Usar el argumento base_url ***
        for p in range(1, pages + 1): # Aunque 'pages' sea > 1, el endpoint lo limita a 1
            base_search_url = f"{base_url}/search/{act_encoded}/all-ma/{prov_encoded}/all-is/{prov_encoded}/all-ba/all-pu/all-nc"
            query_params = f"?what={act_encoded}&where={prov_encoded}"
            if p == 1: url = f"{base_search_url}/1{query_params}&qc=true"
            else: url = f"{base_search_url}/{p}{query_params}"
            print(f"[P. Amarillas] Accediendo a página {p}: {url}")
            try:
                wait_condition = "networkidle" if p > 1 else "domcontentloaded"
                page.goto(url, wait_until=wait_condition, timeout=90000)
                page.wait_for_selector('body', state='visible', timeout=30000)
                print(f"[P. Amarillas] Body cargado en página {p} (wait_until={wait_condition}).")
                if p > 1: print(f"[P. Amarillas] Pausando 5 segundos extra para página {p}..."); page.wait_for_timeout(5000)
                else: page.wait_for_timeout(2000)
                try:
                    pa_cookie_selector = "button#onetrust-accept-btn-handler, button.optanon-allow-all, button[data-id='accept']"
                    page.click(pa_cookie_selector, timeout=5000)
                    print("[P. Amarillas] Banner de cookies aceptado."); page.wait_for_timeout(1000)
                except PlaywrightTimeoutError: print("[P. Amarillas] No se encontró banner de cookies o ya estaba aceptado (timeout corto).")
                except Exception as e_cookie: print(f"[P. Amarillas] Error al intentar aceptar cookies: {e_cookie}")
                list_container_selector = "div.central"
                print(f"[P. Amarillas] Esperando por el contenedor de la lista: '{list_container_selector}'")
                page.wait_for_selector(list_container_selector, state="visible", timeout=45000)
                print(f"[P. Amarillas] Contenedor '{list_container_selector}' encontrado.")
                main_selector = "div.listado-item"
                print(f"[P. Amarillas] Esperando por los items: '{main_selector}'")
                selector_timeout = 75000 if p > 1 else 60000
                page.wait_for_selector(f"{list_container_selector} {main_selector}", state="visible", timeout=selector_timeout)
                print(f"[P. Amarillas] Items '{main_selector}' encontrados en página {p}.")
                list_items_check = page.query_selector_all(f"{list_container_selector} {main_selector}")
                if not list_items_check:
                    print(f"[P. Amarillas] ⚠ Contenedor encontrado, pero no hay elementos '{main_selector}' dentro. Fin de resultados en página {p}.")
                    screenshot_path = SCREENSHOT_PA_DIR / f"no_items_page_{p}_{act_encoded}_{prov_encoded}.png"
                    try: page.screenshot(path=screenshot_path, full_page=True); print(f"[P. Amarillas] Captura guardada en: {screenshot_path}")
                    except Exception as e_screen: print(f"[P. Amarillas] Falló al guardar captura de 'no items': {e_screen}")
                    break
            except PlaywrightTimeoutError as timeout_err:
                 screenshot_path = SCREENSHOT_PA_DIR / f"error_pa_timeout_page_{p}_{act_encoded}_{prov_encoded}.png"
                 try: page.screenshot(path=screenshot_path, full_page=True); print(f"[P. Amarillas] ⚠ Timeout esperando elementos en página {p} ({url}). Error: {timeout_err}. Captura guardada en: {screenshot_path}")
                 except Exception as e_screen: print(f"[P. Amarillas] ⚠ Timeout esperando elementos en página {p} ({url}). Error: {timeout_err}. Falló al guardar captura: {e_screen}")
                 if p > 1: print("[P. Amarillas] Asumiendo fin de resultados debido a Timeout."); break
                 else: print("[P. Amarillas] Timeout en la primera página, no hay resultados para esta búsqueda."); break
            except Exception as e: print(f"[P. Amarillas] ⚠ Error inesperado cargando o esperando selector en página {p} ({url}): {e}"); break
            try:
                list_items = page.query_selector_all(f"{list_container_selector} {main_selector}")
                print(f"[P. Amarillas] Encontrados {len(list_items)} elementos de lista en página {p}.")
                items_processed_page = 0
                for item in list_items:
                    nombre = "No encontrado"; telefono = "No encontrado"; url_ficha = None
                    try:
                         name_element = item.query_selector("h2 a span[itemprop=name]"); link_element = item.query_selector("h2 a")
                         if name_element: nombre = name_element.inner_text().strip()
                         elif link_element: nombre = link_element.inner_text().strip()
                         if link_element:
                             url_ficha = link_element.get_attribute('href')
                             # *** Usar el argumento base_url para completar URL relativa ***
                             if url_ficha and not url_ficha.startswith('http'): url_ficha = base_url + url_ficha
                    except Exception as e_name: print(f"[P. Amarillas]   Error extrayendo nombre: {e_name}")
                    try:
                        item_html = item.inner_html(); telefonos = re.findall(r'\b[6-9]\d{2}\s?\d{3}\s?\d{3}\b|\b[6-9]\d{8}\b', item_html)
                        if telefonos: telefono = re.sub(r'\s+', '', telefonos[0])
                    except Exception as e_phone: print(f"[P. Amarillas]   Error extrayendo teléfono: {e_phone}")
                    if nombre != "No encontrado" or telefono != "No encontrado":
                        resultados.append({"empresa": nombre, "url": url_ficha if url_ficha else url, "correo": "No buscado", "telefono": telefono, "fuente": "Paginas Amarillas"})
                        items_processed_page += 1
                        print(f"[P. Amarillas]     Datos guardados para '{nombre}' (Tel: {telefono})")
                    else: print(f"[P. Amarillas]     No se encontró nombre ni teléfono útil en este item.")
                print(f"[P. Amarillas] Procesados {items_processed_page} elementos con datos en página {p}.")
                if items_processed_page == 0 and len(list_items) > 0: print("[P. Amarillas] Se encontraron elementos pero no se extrajeron datos útiles, posible cambio de selectores internos."); break
            except Exception as e: print(f"[P. Amarillas] Error extrayendo datos de la lista en página {p}: {e}"); break
            time.sleep(random.uniform(2.5, 4.5))
        try:
            browser.close(); print("[P. Amarillas] Navegador Playwright cerrado.")
        except Exception as e_close: print(f"[P. Amarillas] Error al cerrar el navegador: {e_close}")
    print(f"[P. Amarillas] Scraping finalizado. Total de empresas con datos encontrados: {len(resultados)}")
    return resultados


# --- Para ejecutar localmente ---
if __name__ == "__main__":
    print("Iniciando servidor FastAPI en http://0.0.0.0:9000")
    uvicorn.run(app, host="0.0.0.0", port=9000, reload=False)
