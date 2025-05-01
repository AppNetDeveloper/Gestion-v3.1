from fastapi import FastAPI
from pydantic import BaseModel
from typing import List, Dict, Any
from googlesearch import search as google_search
from duckduckgo_search import DDGS
import requests
import re
import time
import random
from bs4 import BeautifulSoup

app = FastAPI(
    title="Buscador Multi-Fuente de Correos",
    description="Extrae correos desde Google, DuckDuckGo y Empresite",
    version="2.4.0"
)

# ----------------------- MODELOS -----------------------

class KeywordRequest(BaseModel):
    keyword: str
    results: int = 10

class EmpresiteRequest(BaseModel):
    actividad: str   # Ej: TRANSPORTES
    provincia: str   # Ej: MURCIA
    paginas: int = 1

# ------------------- CABECERAS REALISTAS ------------------

UA_LIST = [
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)",
    "Mozilla/5.0 (X11; Linux x86_64)",
    "Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)"
]

def get_headers() -> dict:
    return {
        "User-Agent": random.choice(UA_LIST),
        "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
        "Accept-Language": "es-ES,es;q=0.9",
        "Referer": "https://www.google.com/"
    }

# ------------------- GOOGLE + DUCKDUCKGO ------------------

def buscar_duckduckgo(query: str, max_results: int) -> List[str]:
    urls = []
    with DDGS() as ddgs:
        for r in ddgs.text(query, max_results=max_results):
            if "href" in r:
                urls.append(r["href"])
    return urls

def extraer_correos(url: str) -> List[str]:
    correos = set()
    try:
        resp = requests.get(url, headers=get_headers(), timeout=10)
        if resp.status_code == 200:
            texto = resp.text
            encontrados = re.findall(r"[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}", texto)
            correos.update(encontrados)
    except Exception as e:
        print(f"Error al extraer correos de {url}: {e}")
    return list(correos)

@app.post("/buscar-correos-multibuscador", summary="Google + DuckDuckGo (detalle)")
async def buscar_multiples(data: KeywordRequest) -> Dict[str, Any]:
    urls_google = list(google_search(data.keyword, num_results=data.results))
    urls_duck   = buscar_duckduckgo(data.keyword, data.results)
    urls = list(set(urls_google + urls_duck))

    resultados = {u: extraer_correos(u) for u in urls}
    return {
        "keyword": data.keyword,
        "buscadores": ["Google", "DuckDuckGo"],
        "urls_procesadas": len(urls),
        "resultados": resultados
    }

@app.post("/buscar-correos-limpio", summary="Google + DuckDuckGo (solo correos)")
async def buscar_limpio(data: KeywordRequest) -> Dict[str, Any]:
    urls_google = list(google_search(data.keyword, num_results=data.results))
    urls_duck   = buscar_duckduckgo(data.keyword, data.results)
    urls = list(set(urls_google + urls_duck))

    lista = []
    for u in urls:
        for c in extraer_correos(u):
            lista.append({"url": u, "correo": c})
    return {
        "keyword": data.keyword,
        "total_correos": len(lista),
        "correos": lista
    }

# ------------------- SCRAPING EMPRESITE ------------------

BASE_URL = "https://empresite.eleconomista.es"

def extraer_empresas_empresite(actividad: str, provincia: str, paginas: int) -> List[Dict[str, str]]:
    resultados: List[Dict[str, str]] = []

    for page in range(1, paginas + 1):
        # Construye la URL con /PgNum-N/ a partir de la 2ª página
        if page == 1:
            url = f"{BASE_URL}/Actividad/{actividad.upper()}/provincia/{provincia.upper()}/"
        else:
            url = f"{BASE_URL}/Actividad/{actividad.upper()}/provincia/{provincia.upper()}/PgNum-{page}/"

        try:
            resp = requests.get(url, headers=get_headers(), timeout=10)
            if resp.status_code != 200:
                print(f"Error HTTP {resp.status_code} en {url}")
                continue

            soup = BeautifulSoup(resp.text, "html.parser")
            # Selector genérico: div con clase infoempresa y enlace directo
            enlaces = soup.select("div.infoempresa > a")

            if not enlaces:
                print(f"⚠ No se encontraron fichas en página {page}")
                continue

            for a in enlaces:
                href = a.get("href")
                if not href:
                    continue
                ficha_url = href if href.startswith("http") else BASE_URL + href

                # Petición a la ficha
                ficha_resp = requests.get(ficha_url, headers=get_headers(), timeout=10)
                if ficha_resp.status_code != 200:
                    continue

                ficha_soup = BeautifulSoup(ficha_resp.text, "html.parser")
                nombre_tag = ficha_soup.find("h1")
                nombre = nombre_tag.text.strip() if nombre_tag else "Sin nombre"

                # Extrae el primer correo que encuentre en el texto completo
                mails = re.findall(r"[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}", ficha_resp.text)
                if mails:
                    resultados.append({
                        "empresa": nombre,
                        "url": ficha_url,
                        "correo": mails[0]
                    })

            # Para no disparar anti-bot
            time.sleep(3 + random.random())

        except Exception as e:
            print(f"✖ Error en página {page}: {e}")

    return resultados

@app.post("/buscar-empresite", summary="Scraping Empresite (requests)")
async def buscar_empresite(data: EmpresiteRequest) -> Dict[str, Any]:
    act = data.actividad.upper()
    prov = data.provincia.upper()
    empresas = extraer_empresas_empresite(act, prov, data.paginas)
    return {
        "actividad": act,
        "provincia": prov,
        "total_encontrados": len(empresas),
        "empresas": empresas
    }
