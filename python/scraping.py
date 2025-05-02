# -*- coding: utf-8 -*-
from fastapi import FastAPI
from fastapi.concurrency import run_in_threadpool
from pydantic import BaseModel
from typing import List, Dict, Any, Optional # Añadido Optional
from googlesearch import search as google_search
from duckduckgo_search import DDGS
from playwright.sync_api import sync_playwright, TimeoutError as PlaywrightTimeoutError
import requests, re, random, time, warnings
from bs4 import BeautifulSoup # Asegurarse de que está importado
from pathlib import Path
import uvicorn

# Suprimir advertencias de InsecureRequestWarning al usar verify=False
from requests.packages.urllib3.exceptions import InsecureRequestWarning
warnings.simplefilter('ignore', InsecureRequestWarning)

app = FastAPI(
    title="Buscador Multi-Fuente de Correos, Teléfonos y Nombres",
    description="Google, DuckDuckGo (con nombre/teléfono) y Empresite (con nombre/teléfono/correo)",
    version="3.1.9" # Versión incrementada
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

        # --- Extraer Nombre (del título) ---
        soup = BeautifulSoup(content, 'html.parser')
        title_tag = soup.find('title')
        if title_tag and title_tag.string:
            # Limpieza básica del título
            nombre_bruto = title_tag.string.strip()
            # Eliminar partes comunes (se puede expandir)
            partes_a_quitar = ["|", "-", "Inicio", "Homepage", "Página principal", "Contacto", "Empresa"]
            for parte in partes_a_quitar:
                 # Usar replace para quitar ocurrencias, insensible a mayúsculas/minúsculas con re.sub si es necesario
                 nombre_bruto = nombre_bruto.replace(parte, "")
            data["nombre"] = " ".join(nombre_bruto.split()) # Eliminar espacios extra
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

@app.post("/buscar-correos-multibuscador")
async def buscador_completo(d: KeywordRequest) -> Dict[str, Any]:
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

    return {
        "keyword": d.keyword,
        "urls_procesadas": processed_urls_count,
        "urls_con_datos_encontrados": urls_con_datos, # Cuántas URLs devolvieron algún dato útil
        "resultados": resultados_finales
    }

@app.post("/buscar-correos-limpio")
async def buscador_limpio(d: KeywordRequest) -> Dict[str, Any]:
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
        # Si no se encontraron correos PERO sí teléfono, añadir una entrada sin correo
        elif telefono != "No encontrado":
             flat_results.append({
                 "url": url,
                 "nombre": nombre,
                 "correo": "No encontrado", # O None
                 "telefono": telefono
             })

    return {
        "keyword": d.keyword,
        "urls_procesadas": processed_urls_count,
        "total_entradas_generadas": len(flat_results), # Cuántos dicts {url, nombre, correo, telefono} se crearon
        "datos": flat_results
    }


# ------- Empresite con Playwright -------
BASE = "https://empresite.eleconomista.es"

def scrape_empresite_sync(act: str, prov: str, pages: int) -> List[Dict[str,str]]:
    """Scrapea Empresite usando Playwright, extrayendo email y teléfono."""
    resultados = []
    print(f"Iniciando scraping en Empresite para {act} en {prov} ({pages} páginas)")
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
                # Cambiado a 'domcontentloaded'
                page.goto(url, wait_until="domcontentloaded", timeout=90000)
                # Esperar a que el body esté presente
                page.wait_for_selector('body', state='visible', timeout=30000)
                print(f"Body cargado en página {p}.")


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

                main_selector = "div.cardCompanyBox[itemprop='itemListElement']"
                print(f"Esperando por el selector principal: '{main_selector}'")
                # Timeout ligeramente mayor para páginas > 1
                selector_timeout = 60000 if p > 1 else 45000
                page.wait_for_selector(main_selector, state="visible", timeout=selector_timeout)
                print(f"Selector principal '{main_selector}' encontrado en página {p}.")

                # Comprobación adicional: Asegurarse de que realmente hay elementos
                list_items_check = page.query_selector_all(main_selector)
                if not list_items_check:
                    print(f"⚠ Selector '{main_selector}' encontrado, pero no hay elementos. Probablemente fin de resultados en página {p}.")
                    break # Salir del bucle de páginas si no hay elementos

            except PlaywrightTimeoutError:
                 print(f"⚠ Timeout esperando elementos en página {p} ({url}). La estructura puede haber cambiado o no hay más resultados.")
                 # Asumir fin de resultados si hay timeout en páginas > 1
                 if p > 1:
                     print("Asumiendo fin de resultados debido a Timeout en página > 1.")
                     break # Salir del bucle de páginas
                 else:
                    continue # Si es la página 1, intentar la siguiente
            except Exception as e:
                print(f"⚠ Error inesperado cargando o esperando selector en página {p} ({url}): {e}")
                continue # Saltar a la siguiente página

            # *** Lógica de extracción de enlaces ***
            hrefs = []
            try:
                # Re-obtener los elementos después de la espera exitosa
                list_items = page.query_selector_all(main_selector)
                print(f"Encontrados {len(list_items)} elementos de lista en página {p}.")
                for item in list_items:
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

            # Si no se extrajeron enlaces en esta página, probablemente no hay más resultados útiles
            if not hrefs:
                print(f"No se extrajeron enlaces en la página {p}, deteniendo paginación.")
                break

            # Procesar cada ficha de empresa
            for href in set(hrefs):
                ficha_url = href if href.startswith("http") else BASE + href
                print(f"  Procesando ficha: {ficha_url}")
                try:
                    page.goto(ficha_url, wait_until="domcontentloaded", timeout=60000)
                    content = page.content()

                    # --- Extraer correos ---
                    mails = re.findall(r"[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}", content)
                    filtered_mails = _filter_emails(list(set(mails)))

                    # --- Extraer teléfono ---
                    # Se extrae independientemente de si se encontró correo
                    telefono_encontrado = "No encontrado"
                    telefonos = re.findall(r'\b[6-9]\d{2}\s?\d{3}\s?\d{3}\b|\b[6-9]\d{8}\b', content)
                    if telefonos:
                        telefono_encontrado = re.sub(r'\s+', '', telefonos[0])
                        print(f"    Teléfono encontrado: {telefono_encontrado}")
                    else:
                         print(f"    No se encontraron teléfonos válidos en {ficha_url}")


                    # --- Obtener nombre ---
                    nombre = "Sin nombre"
                    try:
                        title_selector = "h1, .nombre_empresa, [itemprop='name']"
                        title_element = page.query_selector(title_selector)
                        if title_element:
                            nombre = title_element.inner_text().strip()
                    except Exception as e_title:
                        print(f"    Error obteniendo nombre de empresa: {e_title}")

                    # --- Guardar resultado (si se encontró correo O teléfono) ---
                    correo_final = filtered_mails[0] if filtered_mails else "No encontrado"

                    if correo_final != "No encontrado" or telefono_encontrado != "No encontrado":
                        resultados.append({
                            "empresa": nombre,
                            "url": ficha_url,
                            "correo": correo_final,
                            "telefono": telefono_encontrado
                        })
                        print(f"    Datos guardados para {nombre} (Correo: {correo_final}, Tel: {telefono_encontrado})")
                    else:
                        print(f"    No se encontró ni correo ni teléfono válido para {nombre}")


                    # Pausa aleatoria
                    time.sleep(random.uniform(1.5, 3.5))

                except PlaywrightTimeoutError:
                    print(f"  ⚠ Timeout al cargar la ficha: {ficha_url}")
                except Exception as e_ficha:
                    print(f"  ⚠ Error procesando la ficha {ficha_url}: {e_ficha}")

        # Cerrar navegador
        try:
            browser.close()
            print("Navegador Playwright cerrado.")
        except Exception as e_close:
            print(f"Error al cerrar el navegador: {e_close}")

    print(f"Scraping finalizado. Total de empresas con datos encontrados: {len(resultados)}")
    return resultados

@app.post("/buscar-empresite")
async def buscador_empresite(d: EmpresiteRequest) -> Dict[str, Any]:
    """Endpoint para buscar empresas y correos/teléfonos en Empresite."""
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
        "total_empresas_encontradas": len(data),
        "empresas": data
    }

# --- Para ejecutar localmente ---
if __name__ == "__main__":
    print("Iniciando servidor FastAPI en http://0.0.0.0:9000")
    uvicorn.run(app, host="0.0.0.0", port=9000, reload=False)
