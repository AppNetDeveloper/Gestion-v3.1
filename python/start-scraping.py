import uvicorn
import os

if __name__ == "__main__":
    uvicorn.run(
        "scraping:app",  # <- aquí va el nombre real de tu archivo sin .py
        host="0.0.0.0",
        port=9000,
        reload=True,
        reload_dirs=[os.path.dirname(__file__)]
    )
