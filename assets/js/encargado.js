document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const mensajeEl = $('#mensaje');
    const mensajeFeriadosEl = $('#mensajeFeriados');
    let calendar;

    // 1. INICIALIZACIÓN DEL CALENDARIO
    try {
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            slotMinTime: AppConfig.horaInicio,
            slotMaxTime: AppConfig.horaFin,
            allDaySlot: false,
            locale: 'es',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            businessHours: {
                daysOfWeek: AppConfig.diasPermitidos,
                startTime: AppConfig.horaInicio,
                endTime: AppConfig.horaFin
            },
            events: {
                url: 'cargar_reservas_encargado.php',
                failure: function() {
                    mensajeEl.html('<div class="alert alert-danger">Hubo un error al cargar las reservas en el calendario.</div>');
                }
            },
            eventDidMount: function(info) {
                let color = '';
                // --- INICIO DE LA MODIFICACIÓN 1: AÑADIR ESTILO PARA 'CANCELADA' ---
                switch (info.event.extendedProps.estado) {
                    case 'confirmada':
                        color = '#28a745'; // Verde
                        break;
                    case 'pendiente':
                        color = '#ffc107'; // Amarillo
                        break;
                    case 'rechazada':
                        color = '#6c757d'; // Gris
                        break;
                    case 'cancelada':
                        color = '#dc3545'; // Rojo
                        info.el.style.textDecoration = 'line-through'; // Texto tachado
                        break;
                }
                if (color) {
                    info.el.style.backgroundColor = color;
                    info.el.style.borderColor = color;
                }
                // --- FIN DE LA MODIFICACIÓN 1 ---

                new bootstrap.Tooltip(info.el, {
                    title: `Reserva: ${info.event.title}<br>Estado: ${info.event.extendedProps.estado}`,
                    html: true,
                    placement: 'top',
                    trigger: 'hover',
                    container: 'body'
                });
            },
            datesSet: function() {
                fetch('cargar_feriados.php', { cache: 'no-store' })
                .then(response => response.json())
                .then(feriados => {
                    calendar.getEvents().forEach(event => {
                        if (event.extendedProps.isFeriado) event.remove();
                    });
                    feriados.forEach(feriado => {
                        calendar.addEvent({
                            id: `feriado-${feriado.fecha}`,
                            title: feriado.descripcion,
                            start: feriado.fecha,
                            allDay: true,
                            display: 'background',
                            backgroundColor: 'rgba(255, 0, 0, 0.2)',
                            extendedProps: { isFeriado: true }
                        });
                    });
                }).catch(error => console.error('Error cargando feriados:', error));
            }
        });
        calendar.render();
        window.calendar = calendar;
    } catch (error) {
        console.error('Error inicializando el calendario:', error);
        if (calendarEl) {
             calendarEl.innerHTML = '<div class="alert alert-danger">Error fatal al cargar el calendario.</div>';
        }
    }

    // 2. LÓGICA PARA GESTIONAR RESERVAS (MODAL)
    const reservaModal = new bootstrap.Modal(document.getElementById('reservaModal'));

    // --- INICIO DE LA MODIFICACIÓN 2: AÑADIR '.cancelar-reserva' AL LISTENER ---
    $('body').on('click', '.confirmar-reserva, .rechazar-reserva, .cancelar-reserva', function() {
        const reservaId = $(this).data('id');
        let accion = '';
        if ($(this).hasClass('confirmar-reserva')) accion = 'confirmar';
        if ($(this).hasClass('rechazar-reserva')) accion = 'rechazar';
        if ($(this).hasClass('cancelar-reserva')) accion = 'cancelar';

        $('#reservaId').val(reservaId);
        $('#reservaAccion').val(accion);
        $('#reservaModalLabel').text(accion.charAt(0).toUpperCase() + accion.slice(1) + ' Reserva');
        
        const comentarioInput = $('#comentario');
        comentarioInput.val('');
        // El comentario ahora es obligatorio para rechazar O cancelar
        comentarioInput.prop('required', accion === 'rechazar' || accion === 'cancelar');
        
        $('#modalCsrfToken').val(AppConfig.csrfToken);
        $('#modalMensaje').html('');
    });
    // --- FIN DE LA MODIFICACIÓN 2 ---

    $('#gestionarReservaForm').on('submit', function(e) {
        e.preventDefault();
        const form = this;
        const submitBtn = $(form).find('button[type="submit"]');

        const accion = $('#reservaAccion').val();
        const comentario = $('#comentario').val();
        if ((accion === 'rechazar' || accion === 'cancelar') && !comentario.trim()) {
            $('#modalMensaje').html('<div class="alert alert-warning">El comentario es obligatorio.</div>');
            return;
        }

        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...');
        
        $.ajax({
            url: 'gestionar_reserva.php',
            type: 'POST',
            data: new FormData(form),
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    mensajeEl.html(`<div class="alert alert-success">${res.message}</div>`);
                    reservaModal.hide();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    $('#modalMensaje').html(`<div class="alert alert-danger">${res.message}</div>`);
                }
            },
            error: function() {
                $('#modalMensaje').html(`<div class="alert alert-danger">Error de conexión.</div>`);
            },
            complete: function() {
                submitBtn.prop('disabled', false).text('Enviar');
            }
        });
    });

    // 3. LÓGICA PARA GESTIONAR FERIADOS (Sin cambios)
    $('#formAgregarFeriado').on('submit', function(e) {
        e.preventDefault();
        const form = this;
        const submitBtn = $(form).find('button[type="submit"]');
        submitBtn.prop('disabled', true).text('Agregando...');

        let formData = $(form).serializeArray();
        formData.push({ name: "csrf_token", value: AppConfig.csrfToken });

        $.ajax({
            url: 'gestionar_feriados.php',
            type: 'POST',
            data: $.param(formData),
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    mensajeFeriadosEl.html(`<div class="alert alert-success">${res.message}</div>`);
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    mensajeFeriadosEl.html(`<div class="alert alert-danger">${res.message}</div>`);
                }
            },
            error: function() {
                mensajeFeriadosEl.html('<div class="alert alert-danger">Error de conexión al agregar el feriado.</div>');
            },
            complete: function() {
                submitBtn.prop('disabled', false).text('Agregar Feriado');
            }
        });
    });

    $('#tablaFeriados').on('click', '.btn-eliminar-feriado', function() {
        const btn = $(this);
        const feriadoId = btn.data('id');
        
        if (confirm('¿Estás seguro de que quieres eliminar este feriado?')) {
            btn.prop('disabled', true).text('Eliminando...');
            $.ajax({
                url: 'gestionar_feriados.php',
                type: 'POST',
                data: {
                    action: 'eliminar',
                    id: feriadoId,
                    csrf_token: AppConfig.csrfToken
                },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        mensajeFeriadosEl.html(`<div class="alert alert-success">${res.message}</div>`);
                        setTimeout(() => {
                            mensajeFeriadosEl.html('');
                        }, 5000);
                        btn.closest('tr').fadeOut(500, function() { 
                            $(this).remove();
                            if (window.calendar) window.calendar.refetchEvents();
                         });
                    } else {
                        mensajeFeriadosEl.html(`<div class="alert alert-danger">${res.message}</div>`);
                        btn.prop('disabled', false).text('Eliminar');
                    }
                },
                error: function() {
                    mensajeFeriadosEl.html('<div class="alert alert-danger">Error de conexión al eliminar el feriado.</div>');
                    btn.prop('disabled', false).text('Eliminar');
                }
            });
        }
    });
});