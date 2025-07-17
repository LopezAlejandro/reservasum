<?php
require __DIR__ . '/bootstrap.php';
// Leemos el estado del switch para usarlo en el HTML y JavaScript
$sistema_habilitado = defined('SISTEMA_HABILITADO') ? SISTEMA_HABILITADO : true;

$hora_inicio = defined('HORA_INICIO_PERMITIDA') ? HORA_INICIO_PERMITIDA : '08:00:00';
$hora_fin = defined('HORA_FIN_PERMITIDA') ? HORA_FIN_PERMITIDA : '18:00:00';
$dias_permitidos = defined('DIAS_PERMITIDOS') ? DIAS_PERMITIDOS : [1, 2, 3, 4, 5];
$anticipacion_horas = defined('ANTICIPACION_HORAS') && is_numeric(ANTICIPACION_HORAS) ? ANTICIPACION_HORAS : 48;
$max_asistentes = defined('MAX_ASISTENTES') ? MAX_ASISTENTES : 50;
$telefono_regex = defined('TELEFONO_REGEX') ? preg_replace('/^\/(.*)\/$/', '$1', TELEFONO_REGEX) : '[0-9\s+()\\-]{7,20}';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva del SUM de la Biblioteca</title>
    <link href="vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="vendor/fullcalendar/main.min.css" rel="stylesheet">
    <link href='https://fonts.googleapis.com/css?family=Lato:300,400,700' rel='stylesheet' type='text/css'>
    <link href="assets/css/estilo.css" rel="stylesheet">
    <script>
        // Objeto de configuración para pasar datos de PHP a JavaScript de forma segura
        const AppConfig = {
            csrfToken: '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>',
            horaInicio: '<?php echo $hora_inicio; ?>',
            horaFin: '<?php echo $hora_fin; ?>',
            diasPermitidos: <?php echo json_encode($dias_permitidos); ?>,
            anticipacionHoras: <?php echo $anticipacion_horas; ?>,
            telefonoRegex: '<?php echo $telefono_regex; ?>',
            sistemaHabilitado: <?php echo json_encode($sistema_habilitado); ?>
        };
    </script>
</head>
<body>
    <div class="col-xl-8 offset-xl-2">
        <div class="jumbotron">
            <div class="container">
                <h2>Solicitud de reserva del Salón de Usos Múltiples / Auditorio de nuestra Biblioteca.</h2>
                <p>
                    <ul>
                        <li>El espacio podrá ser reservado para las actividades académicas de los miembros de la comunidad FADU.</li>
                        <li>La Biblioteca funciona de lunes a viernes de 9 a 21.</li>
                        <li>La sala no dispone de retroproyector.</li>
                        <li>Por reglamento, no se permite el ingreso con alimentos y/o bebidas.</li>
                        <li>Al finalizar la actividad el espacio debe mantenerse limpio y ordenado.</li>
                        <li>La reserva deberá realizarse con al menos 24 horas hábiles de anticipación a la fecha requerida.</li>
                        <li>La Biblioteca enviará una respuesta acerca de la disponibilidad del espacio en la fecha y horario solicitado.</li>
                        <li>En caso de modificaciones de horario o suspensión del uso del espacio se solicita avisar de inmediato a <a href="mailto:biblio@fadu.uba.ar">biblio@fadu.uba.ar</a></li>
                    </ul>
                </p>
            </div>
        </div>
    </div>
    <div class="container mt-5">
        <h2 class="text-center">Ingreso de la solicitud de reserva</h2>
        <?php if (!$sistema_habilitado): // <-- AÑADIMOS ESTE BLOQUE ?>
        <div class="alert alert-warning text-center mt-4" role="alert">
            <h4 class="alert-heading">Sistema Deshabilitado</h4>
            <p>El sistema de solicitud de reservas se encuentra temporalmente fuera de servicio. Por favor, intente más tarde.</p>
        </div>
        <?php endif; ?>
        <div class="row">
            <div class="col-md-6">
                <div id="calendar"></div>
            </div>
            <div class="col-md-6">
                <form id="reservaForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre y apellido del solicitante</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono de Contacto</label>
                        <input type="text" class="form-control" id="telefono" name="telefono" pattern="<?php echo htmlspecialchars($telefono_regex); ?>" required title="Ingrese un teléfono válido (7-20 caracteres, solo números, espacios, -, +, o paréntesis)">
                    </div>
                    <div class="mb-3">
                        <label for="cargo_solicitante" class="form-label">Cargo del Solicitante</label>
                        <select class="form-select" id="cargo_solicitante" name="cargo_solicitante" required>
                            <option value="" disabled selected>Seleccione un cargo</option>
                            <option value="Docente">Docente</option>
                            <option value="Ayudante">Ayudante</option>
                            <option value="Investigador">Investigador</option>
                            <option value="Personal Administrativo">Personal Administrativo</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="carrera" class="form-label">Carrera, asignatura y cátedra o dependencia FADU solicitante</label>
                        <input type="text" class="form-control" id="carrera" name="carrera" required>
                    </div>
                    <div class="mb-3">
                        <label for="cantidad_asistentes" class="form-label">Cantidad de Asistentes (máx. <?php echo $max_asistentes; ?>)</label>
                        <input type="number" class="form-control" id="cantidad_asistentes" name="cantidad_asistentes" min="1" max="<?php echo $max_asistentes; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="fecha" class="form-label">Fecha de Reserva (Seleccione del calendario)</label>
                        <input type="date" class="form-control" id="fecha" name="fecha" readonly required>
                    </div>
                    <div class="mb-3">
                        <label for="hora_inicio" class="form-label">Hora de Inicio (Arrastre en el calendario)</label>
                        <input type="time" class="form-control" id="hora_inicio" name="hora_inicio" readonly required>
                    </div>
                    <div class="mb-3">
                        <label for="hora_fin" class="form-label">Hora de Finalización</label>
                        <input type="time" class="form-control" id="hora_fin" name="hora_fin" readonly required>
                    </div>
                    <div class="mb-3">
                        <label for="motivo" class="form-label">Actividad a desarrollar</label>
                        <textarea class="form-control" id="motivo" name="motivo" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="bibliografia" class="form-label">Bibliografía (Archivo .txt, opcional)</label>
                        <input type="file" class="form-control" id="bibliografia" name="bibliografia" accept=".txt">
                    </div>
                    <div class="d-flex gap-2">
		    <button type="submit" class="btn btn-primary">Solicitar Reserva</button>
		        <button type="button" class="btn btn-secondary" id="limpiarForm">Limpiar</button>
		    </div>
                </form>
                <div id="mensaje" class="mt-3"></div>
            </div>
        </div>
    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="vendor/fullcalendar/main.min.js"></script>
    <script src="vendor/fullcalendar/locales/es.js"></script>
    <script src="assets/js/main.js" defer></script>
</body>
</html>