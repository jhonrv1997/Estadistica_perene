/**
 * Sistema HIS - JavaScript Principal
 */

$(document).ready(function() {
    
    // Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function (el) {
        return new bootstrap.Tooltip(el, {
            placement: 'auto',
            delay: { show: 500, hide: 100 }
        });
    });
    
    // Auto-dismiss alerts
    $('.alert-dismissible').each(function() {
        var $alert = $(this);
        setTimeout(function() {
            $alert.alert('close');
        }, 8000);
    });
    
    // Loading overlay para formularios de importacion
    // Se omite cuando el formulario ya gestiona su propio overlay
    // (por ejemplo #importForm en import.php usa mostrarOverlayProcesamiento).
    // Para opt-out, agregar la clase "no-auto-loading" al <form>.
    $('form').on('submit', function() {
        if ($(this).hasClass('no-auto-loading')) {
            return;
        }
        if ($(this).find('input[type="file"]').length > 0) {
            showLoading('Procesando archivo...', 'Esto puede tardar varios minutos dependiendo del tamano del archivo.');
        }
    });
    
});

/**
 * Mostrar overlay de carga
 */
function showLoading(title, message) {
    var overlay = $(
        '<div class="loading-overlay">' +
            '<div class="loading-content">' +
                '<div class="loading-spinner"></div>' +
                '<h6 class="fw-bold mt-3">' + (title || 'Procesando...') + '</h6>' +
                '<p class="text-muted mb-0 small">' + (message || 'Por favor espere') + '</p>' +
            '</div>' +
        '</div>'
    );
    $('body').append(overlay);
}

/**
 * Ocultar overlay de carga
 */
function hideLoading() {
    $('.loading-overlay').remove();
}

/**
 * Formatear numero con separador de miles
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}
