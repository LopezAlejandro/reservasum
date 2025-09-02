document.addEventListener('DOMContentLoaded', function() {
// --- INICIO DE LA MODIFICACIÓN ---
    const form = document.getElementById('reservaForm');

    // Si el sistema está deshabilitado, bloqueamos todo y no continuamos.
    if (AppConfig.sistemaHabilitado === false) {
        // Deshabilitamos todos los campos, textareas, selects y botones del formulario.
        form.querySelectorAll('input, select, textarea, button').forEach(el => {
            el.disabled = true;
        });
        // Salimos para no inicializar el calendario ni los event listeners.
        // El calendario no se mostrará si está dentro del 'try'
        return; 
    }
    // --- FIN DE LA MODIFICACIÓN ---
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
            selectable: true,
            unselectAuto: false,
            selectOverlap: false,
            businessHours: { daysOfWeek: AppConfig.diasPermitidos, startTime: AppConfig.horaInicio, endTime: AppConfig.horaFin },
            selectConstraint: {
                start: new Date(Date.now() + AppConfig.anticipacionHoras * 60 * 60 * 1000).toISOString().split('T')[0]
            },
            select: function(info) {
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
            // --- INICIO DEL CÓDIGO RESTAURADO ---
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
                                backgroundColor: 'rgb(255, 0, 0)',
                                classNames: ['fc-event-feriado']
                            });
                        }
                    });
                }).catch(error => console.error('Error cargando feriados:', error));
            }
            // --- FIN DEL CÓDIGO RESTAURADO ---
        });
        calendar.render();
    } catch (error) {
        mensajeEl.innerHTML = `<div class="alert alert-danger">Error fatal al cargar el calendario.</div>`;
        console.error("Error en Calendario:", error);
    }

    // Lógica del botón de limpiar (sin cambios)
    limpiarBtn.addEventListener('click', function() {
        form.reset();
        mensajeEl.innerHTML = '';
        calendar.unselect();
        form.classList.remove('was-validated');
    });

    // Lógica para enviar el formulario (sin cambios)
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
                mensajeEl.innerHTML = `<div class="alert alert-success">${res.message}</div>`;
		form.reset();
                calendar.unselect();
                form.classList.remove('was-validated');

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