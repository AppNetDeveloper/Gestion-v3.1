import psutil
import requests
import time

# Configuración de la API
API_URL = "http://127.0.0.1/api/server-monitor"  # Reemplaza con la URL real de la API
API_TOKEN = "8ELJkYlCXlqHaZtY8gQH1t9Tk6RYVmnSsIm5oaLUAJvuzKxCNyWMvdqgPd0p"  # Reemplaza con el token asignado en la tabla host_lists

def collect_metrics():
    """
    Recoge las métricas del sistema:
      - CPU: Porcentaje de uso en 1 segundo.
      - Memoria: Total, libre, usada y porcentaje de uso.
      - Disco: Porcentaje de uso de la partición raíz.
    """
    # Uso de CPU (se mide durante 1 segundo)
    cpu_usage = psutil.cpu_percent(interval=1)

    # Métricas de memoria
    memoria = psutil.virtual_memory()
    total_memory = memoria.total
    memory_free = memoria.free
    memory_used = memoria.used
    memory_used_percent = memoria.percent

    # Métricas de disco (partición '/')
    disco = psutil.disk_usage('/')
    disk_usage_percent = disco.percent

    # Preparar el payload según lo que espera la API
    payload = {
        "token": API_TOKEN,
        "total_memory": total_memory,
        "memory_free": memory_free,
        "memory_used": memory_used,
        "memory_used_percent": memory_used_percent,
        "disk": disk_usage_percent,
        "cpu": cpu_usage
    }
    return payload

def send_data(payload):
    """
    Envía los datos a la API mediante una petición POST.
    """
    headers = {"Content-Type": "application/json"}
    try:
        response = requests.post(API_URL, json=payload, headers=headers)
        return response
    except Exception as e:
        print("Error al enviar la petición:", e)
        return None

def main():
    # Recoger las métricas del sistema
    data = collect_metrics()
    print("Enviando los siguientes datos a la API:")
    print(data)

    # Enviar los datos y evaluar la respuesta
    response = send_data(data)
    if response:
        if response.status_code == 201:
            print("Datos almacenados exitosamente.")
        else:
            print("Error al almacenar datos. Código de estado:", response.status_code)
            print("Respuesta:", response.text)
    else:
        print("No se pudo enviar la petición a la API.")

if __name__ == '__main__':
    try:
        while True:
            main()
            print("Esperando 30 segundos para el siguiente envío...\n")
            time.sleep(30)
    except KeyboardInterrupt:
        print("Script interrumpido por el usuario. Saliendo...")
