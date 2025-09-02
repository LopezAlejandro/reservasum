document.addEventListener("DOMContentLoaded", function () {
    const calendarEl = document.getElementById("calendar");
    const mensajeEl = $("#mensaje"); // Mensajes para la tabla de reservas
    const mensajeFeriadosEl = $("#mensajeFeriados"); // Mensajes para la gestión de feriados
    let calendar;

    // 1. INICIALIZACIÓN DEL CALENDARIO
    try {
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: "timeGridWeek",
            slotMinTime: AppConfig.horaInicio,
            slotMaxTime: AppConfig.horaFin,
            allDaySlot: false,
            locale: "es",
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "dayGridMonth,timeGridWeek,timeGridDay",
            },
            businessHours: {
                daysOfWeek: AppConfig.diasPermitidos,
                startTime: AppConfig.horaInicio,
                endTime: AppConfig.horaFin,
            },
            events: {
                url: "cargar_reservas_encargado.php",
                failure: function () {
                    mensajeEl.html(
                        '<div class="alert alert-danger">Hubo un error al cargar las reservas en el calendario.</div>'
                    );
                },
            },
            eventDidMount: function (info) {
                // Colores según el estado de la reserva
                if (info.event.extendedProps.estado === "confirmada") {
                    info.el.style.backgroundColor = "#28a745";
                    info.el.style.borderColor = "#28a745";
                } else if (info.event.extendedProps.estado === "pendiente") {
                    info.el.style.backgroundColor = "#ffc107";
                    info.el.style.borderColor = "#ffc107";
                } else if (info.event.extendedProps.estado === "rechazada") {
                    info.el.style.backgroundColor = "#6c757d";
                    info.el.style.borderColor = "#6c757d";
                }

                // Tooltip para ver detalles
                new bootstrap.Tooltip(info.el, {
                    title: `Reserva: ${info.event.title}<br>Estado: ${info.event.extendedProps.estado}`,
                    html: true,
                    placement: "top",
                    trigger: "hover",
                    container: "body",
                });
            },
            datesSet: function () {
                // Carga de feriados
                fetch("cargar_feriados.php", { cache: "no-store" })
                    .then((response) => response.json())
                    .then((feriados) => {
                        // Limpiar feriados viejos para evitar duplicados al cambiar de vista
                        calendar.getEvents().forEach((event) => {
                            if (event.extendedProps.isFeriado) {
                                event.remove();
                            }
                        });
                        feriados.forEach((feriado) => {
                            calendar.addEvent({
                                id: `feriado-${feriado.fecha}`,
                                title: feriado.descripcion,
                                start: feriado.fecha,
                                allDay: true,
                                display: "background",
                                backgroundColor: "rgba(255, 0, 0, 0.2)",
                                extendedProps: { isFeriado: true },
                            });
                        });
                    })
                    .catch((error) => console.error("Error cargando feriados:", error));
            },
        });
        calendar.render();
        // Guardar la instancia del calendario en window para poder accederla globalmente si es necesario
        window.calendar = calendar;
    } catch (error) {
        console.error("Error inicializando el calendario:", error);
        if (calendarEl) {
            calendarEl.innerHTML =
                '<div class="alert alert-danger">Error fatal al cargar el calendario.</div>';
        }
    }

    // 2. LÓGICA PARA GESTIONAR RESERVAS (MODAL)
    const reservaModal = new bootstrap.Modal(
        document.getElementById("reservaModal")
    );

    $(".confirmar-reserva, .rechazar-reserva").on("click", function () {
        const reservaId = $(this).data("id");
        const accion = $(this).hasClass("confirmar-reserva")
            ? "confirmar"
            : "rechazar";

        $("#reservaId").val(reservaId);
        $("#reservaAccion").val(accion);
        $("#reservaModalLabel").text(
            accion === "confirmar" ? "Confirmar Reserva" : "Rechazar Reserva"
        );

        const comentarioInput = $("#comentario");
        comentarioInput.val("");
        comentarioInput.prop("required", accion === "rechazar");

        $("#modalCsrfToken").val(AppConfig.csrfToken);
        $("#modalMensaje").html("");
    });

    $("#gestionarReservaForm").on("submit", function (e) {
        e.preventDefault();
        const form = this;
        const submitBtn = $(form).find('button[type="submit"]');

        const accion = $("#reservaAccion").val();
        const comentario = $("#comentario").val();
        if (accion === "rechazar" && !comentario.trim()) {
            $("#modalMensaje").html(
                '<div class="alert alert-warning">El comentario es obligatorio para rechazar.</div>'
            );
            return;
        }

        submitBtn
            .prop("disabled", true)
            .html(
                '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...'
            );

        $.ajax({
            url: "gestionar_reserva.php",
            type: "POST",
            data: new FormData(form),
            processData: false,
            contentType: false,
            dataType: "json",
            success: function (res) {
                if (res.success) {
                    mensajeEl.html(
                        `<div class="alert alert-success">${res.message}</div>`
                    );
                    reservaModal.hide();
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    $("#modalMensaje").html(
                        `<div class="alert alert-danger">${res.message}</div>`
                    );
                }
            },
            error: function () {
                $("#modalMensaje").html(
                    `<div class="alert alert-danger">Error de conexión.</div>`
                );
            },
            complete: function () {
                submitBtn.prop("disabled", false).text("Enviar");
            },
        });
    });

    // 3. LÓGICA PARA GESTIONAR FERIADOS
    $("#formAgregarFeriado").on("submit", function (e) {
        e.preventDefault();
        const form = this;
        const submitBtn = $(form).find('button[type="submit"]');
        submitBtn.prop("disabled", true).text("Agregando...");

        let formData = $(form).serializeArray();
        formData.push({ name: "csrf_token", value: AppConfig.csrfToken });

        $.ajax({
            url: "gestionar_feriados.php",
            type: "POST",
            data: $.param(formData),
            dataType: "json",
            success: function (res) {
                if (res.success) {
                    mensajeFeriadosEl.html(
                        `<div class="alert alert-success">${res.message}</div>`
                    );
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    mensajeFeriadosEl.html(
                        `<div class="alert alert-danger">${res.message}</div>`
                    );
                }
            },
            error: function () {
                mensajeFeriadosEl.html(
                    '<div class="alert alert-danger">Error de conexión al agregar el feriado.</div>'
                );
            },
            complete: function () {
                submitBtn.prop("disabled", false).text("Agregar Feriado");
            },
        });
    });

    $("#tablaFeriados").on("click", ".btn-eliminar-feriado", function () {
        const btn = $(this);
        const feriadoId = btn.data("id");

        if (confirm("¿Estás seguro de que quieres eliminar este feriado?")) {
            btn.prop("disabled", true).text("Eliminando...");
            $.ajax({
                url: "gestionar_feriados.php",
                type: "POST",
                data: {
                    action: "eliminar",
                    id: feriadoId,
                    csrf_token: AppConfig.csrfToken,
                },
                dataType: "json",
                success: function (res) {
                    if (res.success) {
                        mensajeFeriadosEl.html(
                            `<div class="alert alert-success">${res.message}</div>`
                        );

                        setTimeout(() => {
                            mensajeFeriadosEl.html("");
                        }, 5000);

                        btn.closest("tr").fadeOut(500, function () {
                            $(this).remove();
                            if (window.calendar) {
                                window.calendar.refetchEvents();
                            }
                        });
                    } else {
                        mensajeFeriadosEl.html(
                            `<div class="alert alert-danger">${res.message}</div>`
                        );
                        btn.prop("disabled", false).text("Eliminar");
                    }
                },
                error: function () {
                    mensajeFeriadosEl.html(
                        '<div class="alert alert-danger">Error de conexión al eliminar el feriado.</div>'
                    );
                    btn.prop("disabled", false).text("Eliminar");
                },
            });
        }
    });
});
