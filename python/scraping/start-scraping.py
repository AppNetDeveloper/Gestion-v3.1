import uvicorn
import os

if __name__ == "__main__":
    uvicorn.run(
        "scraping:app",
        host="0.0.0.0",
        port=9001,  # Cambiado a puerto 9001
        reload=False,  # Desactivado para producción
        workers=1
    )
