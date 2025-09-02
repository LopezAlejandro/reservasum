<?php
require __DIR__ . '/bootstrap.php';

// 1. Proteger la página
if (!isset($_SESSION['user_is_encargado']) || $_SESSION['user_is_encargado'] !== true) {
    header('Location: login.php');
    exit;
}

// 2. Conexión y configuración inicial
$pdo = get_pdo_connection();
$perPage = 15;
$active_tab = $_GET['tab'] ?? 'pendientes';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// --- INICIO DE LA CORRECCIÓN ---
// 3. CALCULAR SIEMPRE LOS TOTALES PARA LAS INSIGNIAS DE LAS PESTAÑAS
// Estas consultas son muy rápidas y necesarias en cada carga para que los números sean correctos.
$countStmt_pendientes = $pdo->query("SELECT COUNT(id) FROM reservas WHERE estado = 'pendiente'");
$totalRes_pendientes = $countStmt_pendientes->fetchColumn();

$countStmt_confirmadas = $pdo->query("SELECT COUNT(id) FROM reservas WHERE estado = 'confirmada' AND fecha >= CURDATE()");
$totalRes_confirmadas = $countStmt_confirmadas->fetchColumn();
// --- FIN DE LA CORRECCIÓN ---

// 4. Inicializar variables de datos
$reservas_pendientes = $reservas_confirmadas = $estadisticas_mensuales = $anios_disponibles = [];
$totalPages_pendientes = $totalRes_pendientes > 0 ? ceil($totalRes_pendientes / $perPage) : 1;
$totalPages_confirmadas = $totalRes_confirmadas > 0 ? ceil($totalRes_confirmadas / $perPage) : 1;
$page_pendientes = $page_confirmadas = 1;
$anio_seleccionado = date('Y');

// 5. Cargar los DATOS PRINCIPALES (la parte lenta) SOLO para la pestaña activa
if ($active_tab === 'pendientes') {
    $page_pendientes = $page;
    $offset_pendientes = ($page_pendientes - 1) * $perPage;
    $stmt_pendientes = $pdo->prepare("SELECT * FROM reservas WHERE estado = 'pendiente' ORDER BY fecha ASC, hora_inicio ASC LIMIT :limit OFFSET :offset");
    $stmt_pendientes->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt_pendientes->bindValue(':offset', $offset_pendientes, PDO::PARAM_INT);
    $stmt_pendientes->execute();
    $reservas_pendientes = $stmt_pendientes->fetchAll();

} elseif ($active_tab === 'confirmadas') {
    $page_confirmadas = $page;
    $offset_confirmadas = ($page_confirmadas - 1) * $perPage;
    $stmt_confirmadas = $pdo->prepare("SELECT * FROM reservas WHERE estado = 'confirmada' AND fecha >= CURDATE() ORDER BY fecha ASC, hora_inicio ASC LIMIT :limit OFFSET :offset");
    $stmt_confirmadas->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt_confirmadas->bindValue(':offset', $offset_confirmadas, PDO::PARAM_INT);
    $stmt_confirmadas->execute();
    $reservas_confirmadas = $stmt_confirmadas->fetchAll();

} elseif ($active_tab === 'estadisticas') {
    $anio_seleccionado = $_GET['anio'] ?? date('Y');
    $stmt_anios = $pdo->query("SELECT DISTINCT YEAR(fecha) as anio FROM reservas WHERE estado = 'confirmada' ORDER BY anio DESC");
    $anios_disponibles = $stmt_anios->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($anios_disponibles)) {
        $stmt_stats = $pdo->prepare("SELECT MONTH(fecha) AS mes, COUNT(id) AS total FROM reservas WHERE estado = 'confirmada' AND YEAR(fecha) = :anio GROUP BY MONTH(fecha) ORDER BY mes ASC");
        $stmt_stats->execute([':anio' => $anio_seleccionado]);
        $stats_raw = $stmt_stats->fetchAll(PDO::FETCH_KEY_PAIR);
        for ($i = 1; $i <= 12; $i++) {
            $estadisticas_mensuales[$i] = $stats_raw[$i] ?? 0;
        }
    }
}

// 6. Datos que se cargan siempre (para el calendario)
$meses_espanol = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
$feriadosStmt = $pdo->query("SELECT id, fecha, descripcion FROM feriados ORDER BY fecha ASC");
$feriados = $feriadosStmt->fetchAll();

// 7. Variables para el JavaScript del calendario
$hora_inicio = defined('HORA_INICIO_PERMITIDA') ? HORA_INICIO_PERMITIDA : '08:00:00';
$hora_fin = defined('HORA_FIN_PERMITIDA') ? HORA_FIN_PERMITIDA : '18:00:00';
$dias_permitidos = defined('DIAS_PERMITIDOS') ? DIAS_PERMITIDOS : [1, 2, 3, 4, 5];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Encargado</title>
    <link href="vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="vendor/fullcalendar/main.min.css" rel="stylesheet">
    <link href="assets/css/estilo.css" rel="stylesheet">
    <script>
        const AppConfig = {
            csrfToken: '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>',
            horaInicio: '<?php echo $hora_inicio; ?>',
            horaFin: '<?php echo $hora_fin; ?>',
            diasPermitidos: <?php echo json_encode($dias_permitidos); ?>
        };
    </script>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-center mb-0">Panel del Administrador</h2>
            <a href="logout.php" class="btn btn-outline-danger">Cerrar Sesión</a>
        </div>

        <div class="row">
            <div class="col-md-2"></div>
            <div class="col-md-8">
                <div id="calendar" class="my-5"></div>
            </div>
            <div class="col-md-2"></div>
        </div>

        <h4>Gestión de Reservas</h4>
        <div id="mensaje" class="mb-3"></div>

        <ul class="nav nav-tabs" id="reservasTab" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo ($active_tab === 'pendientes') ? 'active' : ''; ?>" href="?tab=pendientes">
                    Pendientes <span class="badge bg-warning text-dark"><?php echo $totalRes_pendientes; ?></span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo ($active_tab === 'confirmadas') ? 'active' : ''; ?>" href="?tab=confirmadas">
                    Confirmadas <span class="badge bg-success"><?php echo $totalRes_confirmadas; ?></span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link <?php echo ($active_tab === 'estadisticas') ? 'active' : ''; ?>" href="?tab=estadisticas">
                    Estadísticas
               </a>
           </li>
        </ul>

        <div class="tab-content" id="reservasTabContent">
            <div class="tab-pane fade <?php echo ($active_tab === 'pendientes') ? 'show active' : ''; ?>" id="pendientes-pane" role="tabpanel">
                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr><th>ID</th><th>Nombre</th><th>Fecha y Hora</th><th>Motivo</th><th>Bibliografía</th><th>Estado</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reservas_pendientes)): ?>
                                <tr><td colspan="7" class="text-center">No hay reservas pendientes.</td></tr>
                            <?php else: ?>
                                <?php foreach ($reservas_pendientes as $reserva): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($reserva['id']); ?></td>
                                        <td><?php echo htmlspecialchars($reserva['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($reserva['fecha'] . ' de ' . $reserva['hora_inicio'] . ' a ' . $reserva['hora_fin']); ?></td>
                                        <td class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($reserva['motivo']); ?>"><?php echo htmlspecialchars($reserva['motivo']); ?></td>
                                        <td><?php if ($reserva['bibliografia_archivo']): ?><a href="descargar_bibliografia.php?id=<?php echo $reserva['id']; ?>" target="_blank">Ver Archivo</a><?php else: ?> - <?php endif; ?></td>
                                        <td><span class="badge bg-warning text-dark"><?php echo htmlspecialchars($reserva['estado']); ?></span></td>
                                        <td class="acciones"><div class="d-flex flex-column gap-1"><button class="btn btn-success btn-sm confirmar-reserva" data-id="<?php echo $reserva['id']; ?>" data-bs-toggle="modal" data-bs-target="#reservaModal">Confirmar</button><button class="btn btn-danger btn-sm rechazar-reserva" data-id="<?php echo $reserva['id']; ?>" data-bs-toggle="modal" data-bs-target="#reservaModal">Rechazar</button></div></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page_pendientes <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="?tab=pendientes&page=<?php echo $page_pendientes - 1; ?>">Anterior</a></li>
                        <li class="page-item disabled"><span class="page-link">Página <?php echo $page_pendientes; ?> de <?php echo $totalPages_pendientes; ?></span></li>
                        <li class="page-item <?php echo ($page_pendientes >= $totalPages_pendientes) ? 'disabled' : ''; ?>"><a class="page-link" href="?tab=pendientes&page=<?php echo $page_pendientes + 1; ?>">Siguiente</a></li>
                    </ul>
                </nav>
            </div>

            <div class="tab-pane fade <?php echo ($active_tab === 'confirmadas') ? 'show active' : ''; ?>" id="confirmadas-pane" role="tabpanel">
                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                             <tr><th>ID</th><th>Nombre</th><th>Fecha y Hora</th><th>Motivo</th><th>Bibliografía</th><th>Estado</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reservas_confirmadas)): ?>
                                <tr><td colspan="7" class="text-center">No hay reservas confirmadas.</td></tr>
                            <?php else: ?>
                                <?php foreach ($reservas_confirmadas as $reserva): ?>
                                     <tr>
                                        <td><?php echo htmlspecialchars($reserva['id']); ?></td>
                                        <td><?php echo htmlspecialchars($reserva['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($reserva['fecha'] . ' de ' . $reserva['hora_inicio'] . ' a ' . $reserva['hora_fin']); ?></td>
                                        <td class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($reserva['motivo']); ?>"><?php echo htmlspecialchars($reserva['motivo']); ?></td>
                                        <td><?php if ($reserva['bibliografia_archivo']): ?><a href="descargar_bibliografia.php?id=<?php echo $reserva['id']; ?>" target="_blank">Ver Archivo</a><?php else: ?> - <?php endif; ?></td>
                                        <td><span class="badge bg-success"><?php echo htmlspecialchars($reserva['estado']); ?></span></td>
                                        <td class="acciones"><button class="btn btn-warning btn-sm cancelar-reserva" data-id="<?php echo $reserva['id']; ?>" data-bs-toggle="modal" data-bs-target="#reservaModal">Cancelar</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page_confirmadas <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="?tab=confirmadas&page=<?php echo $page_confirmadas - 1; ?>">Anterior</a></li>
                        <li class="page-item disabled"><span class="page-link">Página <?php echo $page_confirmadas; ?> de <?php echo $totalPages_confirmadas; ?></span></li>
                        <li class="page-item <?php echo ($page_confirmadas >= $totalPages_confirmadas) ? 'disabled' : ''; ?>"><a class="page-link" href="?tab=confirmadas&page=<?php echo $page_confirmadas + 1; ?>">Siguiente</a></li>
                    </ul>
                </nav>
            </div>
        
            <div class="tab-pane fade <?php echo ($active_tab === 'estadisticas') ? 'show active' : ''; ?>" id="estadisticas-pane" role="tabpanel">
    <div class="card mt-3">
        <div class="card-body">
            <h5 class="card-title">Reservas Confirmadas por Mes</h5>

            <form action="encargado.php" method="get" class="row g-3 align-items-center my-3">
                <input type="hidden" name="tab" value="estadisticas">
                <div class="col-auto">
                    <label for="anio" class="form-label">Seleccionar Año:</label>
                </div>
                <div class="col-auto">
                    <select name="anio" id="anio" class="form-select">
                        <?php foreach ($anios_disponibles as $anio): ?>
                            <option value="<?php echo $anio; ?>" <?php echo ($anio == $anio_seleccionado) ? 'selected' : ''; ?>>
                                <?php echo $anio; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Ver</button>
                </div>
            </form>

            <?php if (empty($anios_disponibles)): ?>
                <div class="alert alert-info">No hay datos de reservas confirmadas para mostrar estadísticas.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>Mes</th>
                                <th>Cantidad de Reservas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($estadisticas_mensuales as $mes_num => $total): ?>
                                <tr>
                                    <td><?php echo $meses_espanol[$mes_num]; ?></td>
                                    <td><strong><?php echo $total; ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>    
        </div>

        <hr class="my-5">
        
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Gestión de Feriados</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-7">
                        <h5>Feriados Actuales</h5>
                        <div class="table-responsive" style="max-height: 400px;">
                            <table class="table table-striped" id="tablaFeriados">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Descripción</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($feriados)): ?>
                                        <tr><td colspan="3" class="text-center">No hay feriados cargados.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($feriados as $feriado): ?>
                                            <tr data-id="<?php echo $feriado['id']; ?>">
                                                <td><?php echo htmlspecialchars($feriado['fecha']); ?></td>
                                                <td><?php echo htmlspecialchars($feriado['descripcion']); ?></td>
                                                <td><button class="btn btn-danger btn-sm btn-eliminar-feriado" data-id="<?php echo $feriado['id']; ?>">Eliminar</button></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <h5>Agregar Nuevo Feriado</h5>
                        <form id="formAgregarFeriado">
                            <input type="hidden" name="action" value="agregar">
                            <div class="mb-3">
                                <label for="feriadoFecha" class="form-label">Fecha</label>
                                <input type="date" class="form-control" id="feriadoFecha" name="fecha" required>
                            </div>
                            <div class="mb-3">
                                <label for="feriadoDescripcion" class="form-label">Descripción</label>
                                <input type="text" class="form-control" id="feriadoDescripcion" name="descripcion" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Agregar Feriado</button>
                        </form>
                    </div>
                </div>
                <div id="mensajeFeriados" class="mt-3"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reservaModal" tabindex="-1" aria-labelledby="reservaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reservaModalLabel">Gestionar Reserva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="gestionarReservaForm" novalidate>
                        <input type="hidden" name="csrf_token" id="modalCsrfToken">
                        <input type="hidden" id="reservaId" name="id">
                        <input type="hidden" id="reservaAccion" name="accion">
                        <div class="mb-3">
                            <label for="comentario" class="form-label">Comentario</label>
                            <textarea class="form-control" id="comentario" name="comentario" rows="4"></textarea>
                            <div class="form-text">El comentario es obligatorio al rechazar o cancelar una reserva.</div>
                        </div>
                        <div id="modalMensaje" class="mb-3"></div>
                        <button type="submit" class="btn btn-primary">Enviar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="vendor/fullcalendar/main.min.js"></script>
    <script src="vendor/fullcalendar/locales/es.js"></script>
    <script src="assets/js/encargado.js" defer></script>
</body>
</html>
