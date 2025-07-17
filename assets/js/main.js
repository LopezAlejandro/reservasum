document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const mensajeEl = document.getElementById('mensaje');
    const form = document.getElementById('reservaForm');
    const limpiarBtn = document.getElementById('limpiarForm');
    let calendar;

    try {
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            slotMinTime: AppConfig.horaInicio,
            slotMaxTime: AppConfig.horaFin,
            allDaySlot: false,
            locale: 'es',
            // La propiedad 'selectable' ahora depende directamente del estado del sistema.
            selectable: AppConfig.sistemaHabilitado,
            selectOverlap: false,
            businessHours: { daysOfWeek: AppConfig.diasPermitidos, startTime: AppConfig.horaInicio, endTime: AppConfig.horaFin },
            selectConstraint: {
                start: new Date(Date.now() + AppConfig.anticipacionHoras * 60 * 60 * 1000).toISOString().split('T')[0]
            },
            select: function(info) {
                // Esta función solo se podrá ejecutar si 'selectable' es true.
                document.getElementById('fecha').value = info.startStr.split('T')[0];
                document.getElementById('hora_inicio').value = info.startStr.split('T')[1].substring(0, 5);
                document.getElementById('hora_fin').value = info.endStr.split('T')[1].substring(0, 5);
                form.classList.remove('was-validated');
            },
            events: { 
                url: 'cargar_reservas.php', 
                failure: () => {
                    mensajeEl.innerHTML = '<div class="alert alert-danger">Error al cargar las reservas en el calendario.</div>';
                }
            },
            eventDidMount: function(info) {
                // Colorear eventos según su estado
                if (info.event.extendedProps.estado === 'confirmada') {
                    info.el.style.backgroundColor = '#28a745';
                    info.el.style.borderColor = '#28a745';
                } else if (info.event.extendedProps.estado === 'pendiente') {
                    info.el.style.backgroundColor = '#ffc107';
                    info.el.style.borderColor = '#ffc107';
                }
                
                // Añadir Tooltip de Bootstrap
                new bootstrap.Tooltip(info.el, {
                    title: `Estado: ${info.event.extendedProps.estado}`,
                    placement: 'top',
                    trigger: 'hover',
                    container: 'body'
                });
            },
            eventClick: function(info) {
                // Alerta al hacer clic en un evento existente
                alert('Este horario ya está reservado y se encuentra en estado: ' + info.event.extendedProps.estado);
                info.jsEvent.preventDefault(); // Evitar cualquier acción por defecto
            },
            datesSet: function(info) {
                // Cargar y mostrar los feriados
                fetch('cargar_feriados.php', { cache: 'no-store' })
                .then(response => response.json())
                .then(feriados => {
                    feriados.forEach(feriado => {
                        if (!calendar.getEventById(feriado.fecha)) {
                            calendar.addEvent({
                                id: feriado.fecha,
                                title: feriado.descripcion,
                                start: feriado.fecha,
                                allDay: true,
                                display: 'background',
                                backgroundColor: 'rgba(255, 0, 0)'
                            });
                        }
                    });
                }).catch(error => console.error('Error cargando feriados:', error));
            }
        });
        calendar.render();

        // Deshabilitamos el formulario DESPUÉS de renderizar el calendario, si es necesario.
        if (AppConfig.sistemaHabilitado === false) {
            form.querySelectorAll('input, select, textarea, button').forEach(el => {
                el.disabled = true;
            });
        }

    } catch (error) {
        mensajeEl.innerHTML = `<div class="alert alert-danger">Error fatal al cargar el calendario.</div>`;
        console.error("Error en Calendario:", error);
    }

    // Evento 'click' para el botón de limpiar
    limpiarBtn.addEventListener('click', function() {
        form.reset();
        mensajeEl.innerHTML = '';
        calendar.unselect();
        form.classList.remove('was-validated');
    });

    // Lógica para enviar el formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();

        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
        
        const formData = new FormData(form);
        fetch('procesar_reserva.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                // 1. Mostramos el mensaje de éxito.
                mensajeEl.innerHTML = `<div class="alert alert-success">${res.message}</div>`;
                
                // 2. Limpiamos el formulario y el calendario por separado, sin tocar el mensaje.
                form.reset();
                calendar.unselect();
                form.classList.remove('was-validated');

                // 3. Programamos que el mensaje se borre después de 5 segundos.
                setTimeout(function() {
                    mensajeEl.innerHTML = '';
                }, 5000); // 5000 milisegundos = 5 segundos
            } else {
                mensajeEl.innerHTML = `<div class="alert alert-danger">Error: ${res.message || 'Ocurrió un problema.'}</div>`;
            }
        })
        .catch(error => {
            mensajeEl.innerHTML = `<div class="alert alert-danger">Error de conexión con el servidor.</div>`;
            console.error('Error en AJAX:', error);
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Solicitar Reserva';
        });
    });
});