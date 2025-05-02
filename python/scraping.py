# -*- coding: utf-8 -*-
from fastapi import FastAPI
from fastapi.concurrency import run_in_threadpool
from pydantic import BaseModel
from typing import List, Dict, Any, Optional
from googlesearch import search as google_search
from duckduckgo_search import DDGS
from playwright.sync_api import sync_playwright, TimeoutError as PlaywrightTimeoutError
import requests, re, random, time, warnings, urllib.parse # Importar urllib.parse
from bs4 import BeautifulSoup
from pathlib import Path
import uvicorn

# Suprimir advertencias de InsecureRequestWarning al usar verify=False
from requests.packages.urllib3.exceptions import InsecureRequestWarning
warnings.simplefilter('ignore', InsecureRequestWarning)

# Crear directorio para capturas si no existe (para depuración de P. Amarillas)
Path("screenshots_pa").mkdir(exist_ok=True)


app = FastAPI(
    title="Buscador Multi-Fuente (Google, DDG, Empresite, Páginas Amarillas)",
    description="Extrae correos, teléfonos y nombres de Google, DuckDuckGo, Empresite y Páginas Amarillas.",
    version="3.2.2" # Versión incrementada
)

# ---------------- Modelos ----------------
class KeywordRequest(BaseModel):
    keyword: str
    results: int = 10

# Modelo genérico para directorios que usan actividad/provincia
class DirectoryRequest(BaseModel):
    actividad: str
    provincia: str
    paginas: int = 1

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
        "Sec-Fetch-Site": "cross-site", # Puede ser 'same-origin' si la navegación es interna
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
    data = {"nombre": None, "correos": [], "telefono": None}
    if not url.startswith(('http://', 'https://')):
        print(f"Ignorando URL relativa o inválida: {url}")
        return data # Devuelve datos vacíos

    try:
        r = requests.get(url, headers=random_headers(), timeout=20, allow_redirects=True, verify=False)
        r.raise_for_status()
        content = r.text
        content_type = r.headers.get("content-type", "").lower()

        # Solo intentar parsear HTML
        if 'html' not in content_type:
             print(f"Contenido no es HTML en {url}, tipo: {content_type}. Saltando extracción de nombre.")
             data["nombre"] = "No aplicable (No HTML)"
        else:
            # --- Extraer Nombre (del título) ---
            soup = BeautifulSoup(content, 'html.parser')
            title_tag = soup.find('title')
            if title_tag and title_tag.string:
                # Limpieza básica del título
                nombre_bruto = title_tag.string.strip()
                # Eliminar partes comunes (se puede expandir)
                partes_a_quitar = ["|", "-", "Inicio", "Homepage", "Página principal", "Contacto", "Empresa"]
                # Añadir el nombre de la propia web si aparece
                try:
                    domain = url.split('/')[2].replace('www.', '')
                    partes_a_quitar.append(domain)
                except:
                    pass

                # Iterar y reemplazar (insensible a mayúsculas/minúsculas)
                for parte in partes_a_quitar:
                    nombre_bruto = re.sub(re.escape(parte), '', nombre_bruto, flags=re.IGNORECASE)

                data["nombre"] = " ".join(nombre_bruto.split()) # Eliminar espacios extra
                if not data["nombre"]: # Si queda vacío después de limpiar
                     data["nombre"] = title_tag.string.strip() # Dejar el título original
            else:
                data["nombre"] = "No encontrado"

        # --- Extraer Correos ---
        mails = re.findall(r'\b[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}\b', content)
        data["correos"] = _filter_emails(list(set(mails)))

        # --- Extraer Teléfono ---
        telefonos = re.findall(r'\b[6-9]\d{2}\s?\d{3}\s?\d{3}\b|\b[6-9]\d{8}\b', content) # Regex mejorada para espacios opcionales
        if telefonos:
            # Limpiar espacios del teléfono encontrado
            data["telefono"] = re.sub(r'\s+', '', telefonos[0])
        else:
            data["telefono"] = "No encontrado"

        return data

    except requests.exceptions.SSLError as e:
        print(f"Error SSL al acceder a {url} (verificación desactivada): {e}")
        return data # Devuelve datos vacíos o parcialmente llenos
    except requests.exceptions.RequestException as e:
        print(f"Error de red al acceder a {url}: {e}")
        return data
    except Exception as e:
        print(f"Error inesperado procesando {url}: {e}")
        return data

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

@app.post("/buscar-google-ddg") # Endpoint renombrado para claridad
async def buscador_google_ddg(d: KeywordRequest) -> Dict[str, Any]:
    """Busca URLs en Google/DDG, extrae nombre, correos y teléfono."""
    google_urls = []
    try:
        time.sleep(random.uniform(1, 3))
        google_urls = list(google_search(d.keyword, num_results=d.results, lang='es'))
    except Exception as e:
        print(f"Error en búsqueda de Google: {e}")

    ddg_urls = duckduckgo_search(d.keyword, d.results)
    urls = list(set(google_urls + ddg_urls))
    print(f"Encontradas {len(urls)} URLs únicas para '{d.keyword}'")

    resultados_finales = {}
    processed_urls_count = 0
    urls_con_datos = 0

    for url in urls:
        processed_urls_count += 1
        print(f"Procesando URL {processed_urls_count}/{len(urls)}: {url}")
        time.sleep(random.uniform(0.5, 1.5))
        # Llamar a la función que extrae todos los datos
        extracted_data = await run_in_threadpool(extract_data_from_url, url)

        # Añadir al resultado solo si se encontró al menos un correo o teléfono
        if extracted_data.get("correos") or (extracted_data.get("telefono") and extracted_data.get("telefono") != "No encontrado"):
            urls_con_datos += 1
            resultados_finales[url] = {
                "nombre": extracted_data.get("nombre", "No encontrado"),
                "correos": extracted_data.get("correos", []), # Asegurar que sea lista
                "telefono": extracted_data.get("telefono", "No encontrado")
            }
            print(f"  -> Datos encontrados: Nombre='{resultados_finales[url]['nombre']}', Correos={len(resultados_finales[url]['correos'])}, Tel='{resultados_finales[url]['telefono']}'")
        else:
            print("  -> No se encontraron datos de contacto útiles.")


    return {
        "keyword": d.keyword,
        "urls_procesadas": processed_urls_count,
        "urls_con_datos_encontrados": urls_con_datos, # Cuántas URLs devolvieron algún dato útil
        "resultados": resultados_finales
    }

@app.post("/buscar-google-ddg-limpio") # Endpoint renombrado para claridad
async def buscador_google_ddg_limpio(d: KeywordRequest) -> Dict[str, Any]:
    """Busca URLs en Google/DDG y devuelve lista plana {url, nombre, correo, telefono}."""
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

    for url in urls:
        processed_urls_count += 1
        print(f"Procesando URL {processed_urls_count}/{len(urls)}: {url}")
        time.sleep(random.uniform(0.5, 1.5))
        extracted_data = await run_in_threadpool(extract_data_from_url, url)

        nombre = extracted_data.get("nombre", "No encontrado")
        telefono = extracted_data.get("telefono", "No encontrado")
        correos = extracted_data.get("correos", [])

        # Si se encontraron correos, añadir una entrada por cada correo
        if correos:
            for mail in correos:
                flat_results.append({
                    "url": url,
                    "nombre": nombre,
                    "correo": mail,
                    "telefono": telefono
                })
            print(f"  -> {len(correos)} correos encontrados. Tel: {telefono}")
        # Si no se encontraron correos PERO sí teléfono, añadir una entrada sin correo
        elif telefono != "No encontrado":
             flat_results.append({
                 "url": url,
                 "nombre": nombre,
                 "correo": "No encontrado", # O None
                 "telefono": telefono
             })
             print(f"  -> Teléfono encontrado ({telefono}), sin correos.")
        else:
             print(f"  -> No se encontraron datos de contacto útiles.")


    return {
        "keyword": d.keyword,
        "urls_procesadas": processed_urls_count,
        "total_entradas_generadas": len(flat_results), # Cuántos dicts {url, nombre, correo, telefono} se crearon
        "datos": flat_results
    }


# ------- Empresite con Playwright -------
BASE_EMPRESITE = "https://empresite.eleconomista.es" # Renombrado para claridad

def scrape_empresite_sync(act: str, prov: str, pages: int) -> List[Dict[str,str]]:
    """Scrapea Empresite usando Playwright, extrayendo email y teléfono."""
    # (Esta función permanece sin cambios respecto a la versión anterior)
    resultados = []
    print(f"[Empresite] Iniciando scraping para {act} en {prov} ({pages} páginas)")
    with sync_playwright() as pw:
        try:
            browser = pw.chromium.launch(
                headless=True,
                args=[
                    "--disable-blink-features=AutomationControlled",
                    "--no-sandbox",
                    "--disable-dev-shm-usage"
                ]
            )
        except Exception as e:
            print(f"[Empresite] Error al iniciar el navegador Playwright: {e}")
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
                url = f"{BASE_EMPRESITE}/Actividad/{act}/provincia/{prov}/"
            else:
                url = f"{BASE_EMPRESITE}/Actividad/{act}/provincia/{prov}/PgNum-{p}/"

            print(f"[Empresite] Accediendo a página {p}: {url}")
            try:
                page.goto(url, wait_until="domcontentloaded", timeout=90000)
                page.wait_for_selector('body', state='visible', timeout=30000)
                print(f"[Empresite] Body cargado en página {p}.")

                # Aceptar cookies
                try:
                    accept_button_selector = "#onetrust-accept-btn-handler, button[aria-label*='Aceptar'], button:has-text('Aceptar')"
                    page.wait_for_selector(accept_button_selector, timeout=7000)
                    page.click(accept_button_selector)
                    print("[Empresite] Banner de cookies aceptado.")
                    page.wait_for_timeout(1500)
                except PlaywrightTimeoutError:
                    print("[Empresite] No se encontró banner de cookies o ya estaba aceptado.")
                except Exception as e_cookie:
                    print(f"[Empresite] Error al intentar aceptar cookies: {e_cookie}")

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
                 if p > 1:
                     print("[Empresite] Asumiendo fin de resultados.")
                     break
                 else:
                    continue
            except Exception as e:
                print(f"[Empresite] ⚠ Error inesperado cargando o esperando selector en página {p} ({url}): {e}")
                continue

            # Extracción de enlaces
            hrefs = []
            try:
                list_items = page.query_selector_all(main_selector)
                print(f"[Empresite] Encontrados {len(list_items)} elementos de lista en página {p}.")
                for item in list_items:
                    link_element = item.query_selector("h3 a[href$='.html']")
                    if link_element:
                         href = link_element.get_attribute('href')
                         if href:
                             hrefs.append(href)
                print(f"[Empresite] Extraídos {len(hrefs)} enlaces únicos a fichas en página {p}.")
            except Exception as e:
                 print(f"[Empresite] Error extrayendo enlaces en página {p}: {e}")

            if not hrefs:
                print(f"[Empresite] No se extrajeron enlaces en la página {p}, deteniendo paginación.")
                break

            # Procesar fichas
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
                    if telefonos:
                        telefono_encontrado = re.sub(r'\s+', '', telefonos[0])

                    nombre = "Sin nombre"
                    try:
                        title_selector = "h1, .nombre_empresa, [itemprop='name']"
                        title_element = page.query_selector(title_selector)
                        if title_element:
                            nombre = title_element.inner_text().strip()
                    except Exception as e_title:
                        print(f"[Empresite]     Error obteniendo nombre: {e_title}")

                    if correo_final != "No encontrado" or telefono_encontrado != "No encontrado":
                        resultados.append({
                            "empresa": nombre,
                            "url": ficha_url,
                            "correo": correo_final,
                            "telefono": telefono_encontrado,
                            "fuente": "Empresite" # Añadir fuente
                        })
                        print(f"[Empresite]     Datos guardados para {nombre} (Correo: {correo_final}, Tel: {telefono_encontrado})")
                    else:
                        print(f"[Empresite]     No se encontró ni correo ni teléfono válido para {nombre}")

                    time.sleep(random.uniform(1.5, 3.5))

                except PlaywrightTimeoutError:
                    print(f"[Empresite]   ⚠ Timeout al cargar la ficha: {ficha_url}")
                except Exception as e_ficha:
                    print(f"[Empresite]   ⚠ Error procesando la ficha {ficha_url}: {e_ficha}")

        try:
            browser.close()
            print("[Empresite] Navegador Playwright cerrado.")
        except Exception as e_close:
            print(f"[Empresite] Error al cerrar el navegador: {e_close}")

    print(f"[Empresite] Scraping finalizado. Total de empresas con datos encontrados: {len(resultados)}")
    return resultados

@app.post("/buscar-empresite")
async def buscador_empresite(d: DirectoryRequest) -> Dict[str, Any]: # Usar DirectoryRequest
    """Endpoint para buscar empresas y correos/teléfonos en Empresite."""
    act = d.actividad.strip().upper()
    prov = d.provincia.strip().upper()
    pages = max(1, d.paginas)

    if not act or not prov:
        return {"error": "Actividad y provincia son requeridas."}

    data = await run_in_threadpool(scrape_empresite_sync, act, prov, pages)

    return {
        "fuente": "Empresite",
        "actividad": act,
        "provincia": prov,
        "paginas_solicitadas": pages,
        "total_empresas_encontradas": len(data),
        "empresas": data
    }

# ------- Páginas Amarillas con Playwright -------
BASE_PAGINAS_AMARILLAS = "https://www.paginasamarillas.es"

def scrape_paginas_amarillas_sync(act: str, prov: str, pages: int) -> List[Dict[str, str]]:
    """Scrapea Páginas Amarillas usando Playwright, extrayendo nombre y teléfono."""
    resultados = []
    print(f"[P. Amarillas] Iniciando scraping para '{act}' en '{prov}' ({pages} páginas)")
    act_encoded = urllib.parse.quote_plus(act.lower())
    prov_encoded = urllib.parse.quote_plus(prov.lower())

    with sync_playwright() as pw:
        try:
            browser = pw.chromium.launch(
                headless=True,
                args=[
                    "--disable-blink-features=AutomationControlled",
                    "--no-sandbox",
                    "--disable-dev-shm-usage"
                ]
            )
        except Exception as e:
            print(f"[P. Amarillas] Error al iniciar el navegador Playwright: {e}")
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
            base_search_url = f"{BASE_PAGINAS_AMARILLAS}/search/{act_encoded}/all-ma/{prov_encoded}/all-is/{prov_encoded}/all-ba/all-pu/all-nc"
            query_params = f"?what={act_encoded}&where={prov_encoded}"
            if p == 1:
                 url = f"{base_search_url}/1{query_params}&qc=true"
            else:
                 url = f"{base_search_url}/{p}{query_params}"

            print(f"[P. Amarillas] Accediendo a página {p}: {url}")
            try:
                # *** Usar networkidle para páginas > 1 ***
                wait_condition = "networkidle" if p > 1 else "domcontentloaded"
                page.goto(url, wait_until=wait_condition, timeout=90000)
                page.wait_for_selector('body', state='visible', timeout=30000)
                print(f"[P. Amarillas] Body cargado en página {p} (wait_until={wait_condition}).")

                # Aceptar cookies
                try:
                    pa_cookie_selector = "button#onetrust-accept-btn-handler, button.optanon-allow-all, button[data-id='accept']"
                    page.wait_for_selector(pa_cookie_selector, timeout=7000)
                    page.click(pa_cookie_selector)
                    print("[P. Amarillas] Banner de cookies aceptado.")
                    page.wait_for_timeout(1500)
                except PlaywrightTimeoutError:
                    print("[P. Amarillas] No se encontró banner de cookies o ya estaba aceptado.")
                except Exception as e_cookie:
                    print(f"[P. Amarillas] Error al intentar aceptar cookies: {e_cookie}")

                main_selector = "div.listado-item"
                print(f"[P. Amarillas] Esperando por el selector principal: '{main_selector}'")
                # *** Timeout aumentado para páginas > 1 ***
                selector_timeout = 75000 if p > 1 else 45000 # Aumentado a 75s
                page.wait_for_selector(main_selector, state="visible", timeout=selector_timeout)
                print(f"[P. Amarillas] Selector principal '{main_selector}' encontrado en página {p}.")

                list_items_check = page.query_selector_all(main_selector)
                if not list_items_check:
                    print(f"[P. Amarillas] ⚠ Selector '{main_selector}' encontrado, pero no hay elementos. Fin de resultados en página {p}.")
                    break

            except PlaywrightTimeoutError:
                 # *** Añadir captura de pantalla para depuración ***
                 screenshot_path = f"screenshots_pa/error_pa_page_{p}_{act}_{prov}.png"
                 try:
                     page.screenshot(path=screenshot_path, full_page=True)
                     print(f"[P. Amarillas] ⚠ Timeout esperando elementos en página {p} ({url}). Captura guardada en: {screenshot_path}")
                 except Exception as e_screen:
                     print(f"[P. Amarillas] ⚠ Timeout esperando elementos en página {p} ({url}). Falló al guardar captura: {e_screen}")

                 if p > 1:
                     print("[P. Amarillas] Asumiendo fin de resultados.")
                     break
                 else:
                     print("[P. Amarillas] Timeout en la primera página, no hay resultados para esta búsqueda.")
                     break
            except Exception as e:
                print(f"[P. Amarillas] ⚠ Error inesperado cargando o esperando selector en página {p} ({url}): {e}")
                break

            # Extracción de datos de la lista
            try:
                list_items = page.query_selector_all(main_selector)
                print(f"[P. Amarillas] Encontrados {len(list_items)} elementos de lista en página {p}.")
                items_processed_page = 0
                for item in list_items:
                    nombre = "No encontrado"
                    telefono = "No encontrado"
                    url_ficha = None

                    # Extraer nombre
                    try:
                         name_element = item.query_selector("h2 a span[itemprop=name]")
                         link_element = item.query_selector("h2 a")
                         if name_element:
                             nombre = name_element.inner_text().strip()
                         elif link_element:
                              nombre = link_element.inner_text().strip()

                         if link_element:
                             url_ficha = link_element.get_attribute('href')
                             if url_ficha and not url_ficha.startswith('http'):
                                 url_ficha = BASE_PAGINAS_AMARILLAS + url_ficha
                    except Exception as e_name:
                        print(f"[P. Amarillas]   Error extrayendo nombre: {e_name}")

                    # Extraer teléfono
                    try:
                        item_html = item.inner_html()
                        telefonos = re.findall(r'\b[6-9]\d{2}\s?\d{3}\s?\d{3}\b|\b[6-9]\d{8}\b', item_html)
                        if telefonos:
                            telefono = re.sub(r'\s+', '', telefonos[0])
                    except Exception as e_phone:
                         print(f"[P. Amarillas]   Error extrayendo teléfono: {e_phone}")

                    # Guardar si se encontró nombre o teléfono
                    if nombre != "No encontrado" or telefono != "No encontrado":
                        resultados.append({
                            "empresa": nombre,
                            "url": url_ficha if url_ficha else url,
                            "correo": "No buscado",
                            "telefono": telefono,
                            "fuente": "Paginas Amarillas"
                        })
                        items_processed_page += 1
                        print(f"[P. Amarillas]     Datos guardados para '{nombre}' (Tel: {telefono})")
                    else:
                         print(f"[P. Amarillas]     No se encontró nombre ni teléfono útil en este item.")

                print(f"[P. Amarillas] Procesados {items_processed_page} elementos con datos en página {p}.")
                if items_processed_page == 0 and len(list_items) > 0:
                     print("[P. Amarillas] Se encontraron elementos pero no se extrajeron datos útiles, posible cambio de selectores internos.")
                     break

            except Exception as e:
                 print(f"[P. Amarillas] Error extrayendo datos de la lista en página {p}: {e}")
                 break

            # Pausa antes de ir a la siguiente página
            time.sleep(random.uniform(2.5, 4.5))

        # Cerrar navegador
        try:
            browser.close()
            print("[P. Amarillas] Navegador Playwright cerrado.")
        except Exception as e_close:
            print(f"[P. Amarillas] Error al cerrar el navegador: {e_close}")

    print(f"[P. Amarillas] Scraping finalizado. Total de empresas con datos encontrados: {len(resultados)}")
    return resultados


@app.post("/buscar-paginas-amarillas")
async def buscador_paginas_amarillas(d: DirectoryRequest) -> Dict[str, Any]:
    """Endpoint para buscar empresas y teléfonos en Páginas Amarillas."""
    act = d.actividad.strip()
    prov = d.provincia.strip()
    pages = max(1, d.paginas)

    if not act or not prov:
        return {"error": "Actividad y provincia son requeridas."}

    if re.search(r'[<>:"/\\|?*]', prov):
         return {"error": "Provincia contiene caracteres inválidos."}

    data = await run_in_threadpool(scrape_paginas_amarillas_sync, act, prov, pages)

    return {
        "fuente": "Paginas Amarillas",
        "actividad": act,
        "provincia": prov,
        "paginas_solicitadas": pages,
        "total_empresas_encontradas": len(data),
        "empresas": data
    }


# --- Para ejecutar localmente ---
if __name__ == "__main__":
    print("Iniciando servidor FastAPI en http://0.0.0.0:9000")
    uvicorn.run(app, host="0.0.0.0", port=9000, reload=False)
