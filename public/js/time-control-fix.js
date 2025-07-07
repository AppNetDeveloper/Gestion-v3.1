// Script para arreglar el problema de los botones de control horario
document.addEventListener('DOMContentLoaded', function() {
    console.log('Script time-control-fix.js cargado');
    
    // Esperar a que jQuery esté disponible
    function checkJQuery() {
        if (window.jQuery) {
            console.log('jQuery detectado, inicializando eventos de control horario');
            initTimeControlEvents();
        } else {
            console.log('jQuery no detectado, esperando...');
            setTimeout(checkJQuery, 100);
        }
    }
    
    // Inicializar eventos
    function initTimeControlEvents() {
        console.log('Inicializando eventos de control horario');
        
        // Verificar elementos en el DOM
        console.log('Elementos en el DOM:', {
            'test-direct-button': document.getElementById('test-direct-button') ? 'Encontrado' : 'No encontrado',
            'buttons-container': document.getElementById('buttons-container') ? 'Encontrado' : 'No encontrado',
            'attendance-buttons': document.querySelectorAll('.attendance-button').length
        });
        
        // Registrar evento click directamente en los botones
        document.querySelectorAll('.attendance-button').forEach(function(button) {
            console.log('Registrando evento click en botón:', button);
            
            // Eliminar eventos previos
            button.removeEventListener('click', handleAttendanceButtonClick);
            
            // Añadir nuevo evento
            button.addEventListener('click', handleAttendanceButtonClick);
        });
        
        // Ya no hay botón de prueba
    }
    
    // Manejador de eventos para botones de asistencia
    function handleAttendanceButtonClick(event) {
        event.preventDefault();
        console.log('¡Click detectado en botón de control horario!', this);
        
        // Obtener el ID del estado
        const statusId = this.getAttribute('data-status-id');
        console.log('Status ID:', statusId);
        
        // Verificar geolocalización
        if (navigator.geolocation) {
            console.log('Solicitando ubicación actual...');
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    console.log('Ubicación obtenida:', position.coords.latitude, position.coords.longitude);
                    
                    // Enviar la solicitud AJAX al servidor
                    sendTimeControlRequest(statusId, position.coords.latitude, position.coords.longitude);
                },
                function(error) {
                    console.error('Error obteniendo ubicación:', error);
                }
            );
        } else {
            console.error('Geolocalización no soportada');
        }
    }
    
    // Función para enviar la solicitud al servidor
    function sendTimeControlRequest(statusId, lat, long) {
        console.log('Enviando solicitud al servidor:', { statusId, lat, long });
        
        // Obtener el token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) {
            console.error('Token CSRF no encontrado');
            return;
        }
        
        // Mostrar indicador de carga
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay) loadingOverlay.style.display = 'flex';
        
        // Enviar solicitud AJAX
        jQuery.ajax({
            url: '/add-new-time-control',
            method: 'POST',
            data: {
                _token: csrfToken,
                status_id: statusId,
                lat: lat,
                long: long
            },
            dataType: 'json',
            success: function(response) {
                console.log('Respuesta del servidor:', response);
                if (response.success) {
                    // Recargar la sección de botones
                    refreshTimeControlSection();
                } else {
                    console.error('Error en la respuesta:', response.message || 'Error desconocido');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en la solicitud AJAX:', status, error);
            },
            complete: function() {
                // Ocultar indicador de carga
                if (loadingOverlay) loadingOverlay.style.display = 'none';
            }
        });
    }
    
    // Función para refrescar la sección de control horario
    function refreshTimeControlSection() {
        console.log('Refrescando sección de control horario');
        
        jQuery.ajax({
            url: '/get-time-control-section',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Respuesta de refresco:', response);
                if (response && response.html) {
                    // Actualizar el contenido del contenedor de botones
                    const buttonsContainer = document.getElementById('buttons-container');
                    if (buttonsContainer) {
                        buttonsContainer.innerHTML = response.html;
                        console.log('Contenido actualizado');
                        
                        // Reinicializar eventos en los nuevos botones
                        setTimeout(initTimeControlEvents, 100);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Error refrescando sección:', status, error);
            }
        });
    }
    
    // Iniciar la verificación de jQuery
    checkJQuery();
});
