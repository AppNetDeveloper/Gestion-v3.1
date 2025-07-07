/**
 * Archivo de diagnóstico para detectar problemas con librerías JavaScript
 */
console.log('=== DIAGNÓSTICO DE LIBRERÍAS JAVASCRIPT ===');

// Verificar jQuery
console.log('jQuery disponible:', typeof jQuery !== 'undefined' ? 'SÍ' : 'NO');
if (typeof jQuery !== 'undefined') {
    console.log('Versión de jQuery:', jQuery.fn.jquery);
    
    // Verificar DataTables
    console.log('DataTables disponible:', typeof jQuery.fn.DataTable !== 'undefined' ? 'SÍ' : 'NO');
    
    // Verificar Select2
    console.log('Select2 disponible:', typeof jQuery.fn.select2 !== 'undefined' ? 'SÍ' : 'NO');
}

// Verificar SweetAlert2
console.log('SweetAlert2 disponible:', typeof Swal !== 'undefined' ? 'SÍ' : 'NO');

// Verificar Iconify
console.log('Iconify disponible:', typeof Iconify !== 'undefined' ? 'SÍ' : 'NO');

// Verificar si hay errores en la consola
console.log('=== FIN DEL DIAGNÓSTICO ===');

// Función para probar la funcionalidad de los botones
window.probarBoton = function(selector) {
    console.log('Intentando encontrar el botón:', selector);
    if (typeof jQuery !== 'undefined') {
        const boton = jQuery(selector);
        console.log('Botón encontrado:', boton.length > 0 ? 'SÍ' : 'NO');
        if (boton.length > 0) {
            console.log('Eventos registrados en el botón:', jQuery._data(boton[0], 'events'));
            console.log('Probando clic manual en el botón');
            boton.trigger('click');
        }
    } else {
        console.error('jQuery no está disponible para probar el botón');
    }
};
