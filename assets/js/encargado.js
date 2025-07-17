document.addEventListener('DOMContentLoaded', function() {
    // --- ESTA SECCIÓN NO CAMBIA ---
    const calendarEl = document.getElementById('calendar');
    let calendar;
    try {
        calendar = new FullCalendar.Calendar(calendarEl, {
            // ... Toda la configuración del calendario va aquí, sin cambios ...
            initialView: 'timeGridWeek',
            slotMinTime: AppConfig.horaInicio,
            slotMaxTime: AppConfig.horaFin,
            allDaySlot: false,
            locale: 'es',
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
            businessHours: { daysOfWeek: AppConfig.diasPermitidos, startTime: AppConfig.horaInicio, endTime: AppConfig.horaFin },
            events: { url: 'cargar_reservas_encargado.php', failure: () => alert('Hubo un error al cargar las reservas.') },
            eventDidMount: function(info) {
                if (info.event.extendedProps.estado === 'confirmada') {
                    info.el.style.backgroundColor = '#28a745'; info.el.style.borderColor = '#28a745';
                } else if (info.event.extendedProps.estado === 'pendiente') {
                    info.el.style.backgroundColor = '#ffc107'; info.el.style.borderColor = '#ffc107';
                } else if (info.event.extendedProps.estado === 'rechazada') {
                    info.el.style.backgroundColor = '#6c757d'; info.el.style.borderColor = '#6c757d';
                }
            }
        });
        calendar.render();
    } catch (error) {
        console.error('Error inicializando el calendario:', error);
        if (calendarEl) {
             calendarEl.innerHTML = '<div class="alert alert-danger">Error fatal al cargar el calendario.</div>';
        }
    }

    const reservaModal = new bootstrap.Modal(document.getElementById('reservaModal'));
    $('.confirmar-reserva, .rechazar-reserva').on('click', function() {
        const reservaId = $(this).data('id');
        const accion = $(this).hasClass('confirmar-reserva') ? 'confirmar' : 'rechazar';
        $('#reservaId').val(reservaId);
        $('#reservaAccion').val(accion);
        $('#reservaModalLabel').text(accion === 'confirmar' ? 'Confirmar Reserva' : 'Rechazar Reserva');
        const comentarioInput = $('#comentario');
        comentarioInput.val('');
        comentarioInput.prop('required', accion === 'rechazar');
        $('#modalCsrfToken').val(AppConfig.csrfToken);
        $('#modalMensaje').html('');
    });

    // --- INICIO DE LA CORRECCIÓN DEFINITIVA ---
    // Se reemplaza el 'fetch' nativo por el método AJAX de jQuery para mantener la consistencia.
    $('#gestionarReservaForm').on('submit', function(e) {
        e.preventDefault();
        const form = this;
        const submitBtn = $(form).find('button[type="submit"]');

        const accion = $('#reservaAccion').val();
        const comentario = $('#comentario').val();
        if (accion === 'rechazar' && !comentario.trim()) {
            $('#modalMensaje').html('<div class="alert alert-warning">El comentario es obligatorio para rechazar.</div>');
            return;
        }

        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...');
        
        const formData = new FormData(form);

        $.ajax({
            url: 'gestionar_reserva.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json', // Especifica que esperas un JSON de vuelta
            success: function(res) {
                if (res.success) {
                    // Ahora se usa jQuery para seleccionar el div de mensajes, igual que el resto del script
                    $('#mensaje').html(`<div class="alert alert-success">${res.message}</div>`);
                    reservaModal.hide();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    $('#modalMensaje').html(`<div class="alert alert-danger">Error: ${res.message || 'Ocurrió un problema.'}</div>`);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // El manejador de errores de jQuery es más detallado
                console.error("Error en AJAX:", textStatus, errorThrown, jqXHR.responseText);
                $('#modalMensaje').html(`<div class="alert alert-danger">Error de conexión o del servidor.</div>`);
            },
            complete: function() {
                submitBtn.prop('disabled', false).text('Enviar');
            }
        });
    });
    // --- FIN DE LA CORRECCIÓN DEFINITIVA ---
});