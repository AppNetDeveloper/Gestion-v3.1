#!/usr/bin/env python3

import os
import sys
import signal
import logging
import asyncio
import json
from pathlib import Path
from contextlib import asynccontextmanager
from typing import Dict, Any, Optional

import uvicorn
from fastapi import FastAPI, Request, status, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
import sentry_sdk
from dotenv import load_dotenv

# Cargar variables de entorno
load_dotenv()

# Configuración de Sentry para monitoreo de errores
SENTRY_DSN = os.getenv('SENTRY_DSN')
if SENTRY_DSN:
    sentry_sdk.init(
        dsn=SENTRY_DSN,
        traces_sample_rate=1.0,
        profiles_sample_rate=1.0,
        environment=os.getenv('APP_ENV', 'production')
    )

# Configuración de logging
def setup_logging():
    """Configura el sistema de logging."""
    log_file = Path("/var/www/html/storage/logs/scraping.log")
    log_file.parent.mkdir(parents=True, exist_ok=True)
    
    # Configurar el logger raíz
    logger = logging.getLogger()
    logger.setLevel(logging.INFO)
    
    # Formato para los logs
    formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')
    
    # Handler para consola
    console_handler = logging.StreamHandler(sys.stdout)
    console_handler.setFormatter(formatter)
    
    # Handler para archivo
    file_handler = logging.FileHandler(log_file)
    file_handler.setFormatter(formatter)
    
    # Limpiar handlers existentes
    for handler in logger.handlers[:]:
        logger.removeHandler(handler)
    
    # Agregar los nuevos handlers
    logger.addHandler(console_handler)
    logger.addHandler(file_handler)
    
    # Reducir el nivel de log para algunas librerías ruidosas
    logging.getLogger('httpx').setLevel(logging.WARNING)
    logging.getLogger('httpcore').setLevel(logging.WARNING)
    logging.getLogger('urllib3').setLevel(logging.WARNING)
    
    return logging.getLogger(__name__)

logger = setup_logging()

# Manejo de señales para apagado limpio
def handle_shutdown(signum, frame):
    """Maneja la señal de apagado para un cierre limpio."""
    logger.info("Recibida señal de apagado. Cerrando el servidor...")
    sys.exit(0)

# Configuración de la aplicación
@asynccontextmanager
async def lifespan(app: FastAPI):
    """Maneja el ciclo de vida de la aplicación."""
    # Inicio de la aplicación
    logger.info("Iniciando servicio de scraping...")
    
    # Registrar manejador de señales
    signal.signal(signal.SIGTERM, handle_shutdown)
    signal.signal(signal.SIGINT, handle_shutdown)
    
    # Inicialización de recursos aquí
    try:
        # Aquí podrías inicializar conexiones a bases de datos, etc.
        logger.info("Recursos inicializados correctamente")
        yield
    except Exception as e:
        logger.error(f"Error al inicializar recursos: {str(e)}")
        raise
    finally:
        # Limpieza de recursos aquí
        logger.info("Deteniendo servicio de scraping...")

# Crear la aplicación FastAPI
app = FastAPI(
    title="Scraping Service",
    description="API para realizar búsquedas web y extraer información",
    version="1.0.0",
    lifespan=lifespan
)

# Configurar CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # En producción, especifica los orígenes permitidos
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Middleware para manejo de excepciones globales
@app.middleware("http")
async def catch_exceptions_middleware(request: Request, call_next):
    try:
        return await call_next(request)
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Error no manejado: {str(e)}", exc_info=True)
        return JSONResponse(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            content={"detail": "Internal Server Error"},
        )

# Endpoints
@app.get("/health")
async def health_check() -> Dict[str, str]:
    """Endpoint de verificación de salud."""
    return {"status": "ok"}

@app.get("/version")
async def get_version() -> Dict[str, str]:
    """Obtiene la versión del servicio."""
    return {"version": app.version}

# Aquí irían tus rutas de la API
# @app.post("/buscar")
# async def buscar_datos(...):
#     ...

def get_config() -> Dict[str, Any]:
    """Obtiene la configuración de la aplicación."""
    return {
        "host": os.getenv("HOST", "0.0.0.0"),
        "port": int(os.getenv("PORT", "9001")),
        "workers": int(os.getenv("WORKERS", "1")),
        "log_level": os.getenv("LOG_LEVEL", "info").lower(),
        "reload": os.getenv("RELOAD", "false").lower() == "true",
        "timeout_keep_alive": int(os.getenv("TIMEOUT_KEEP_ALIVE", "5")),
        "limit_concurrency": int(os.getenv("LIMIT_CONCURRENCY", "100")),
    }

if __name__ == "__main__":
    config = get_config()
    
    logger.info(f"Iniciando servidor en {config['host']}:{config['port']} con {config['workers']} workers")
    
    try:
        uvicorn.run(
            "scraping:app",
            host=config["host"],
            port=config["port"],
            workers=config["workers"],
            log_level=config["log_level"],
            reload=config["reload"],
            access_log=True,
            use_colors=True,
            log_config=None,
            proxy_headers=True,
            forwarded_allow_ips="*",
            timeout_keep_alive=config["timeout_keep_alive"],
            limit_concurrency=config["limit_concurrency"],
        )
    except Exception as e:
        logger.error(f"Error al iniciar el servidor: {str(e)}")
        sys.exit(1)
