<?php
require __DIR__ . '/bootstrap.php';

// Verificamos si la variable de sesión existe y es verdadera.
// Si no, redirigimos al login.
if (!isset($_SESSION['user_is_encargado']) || $_SESSION['user_is_encargado'] !== true) {
    header('Location: login.php');
    exit;
}

$pdo = get_pdo_connection();

// --- INICIO DE LA MODIFICACIÓN ---
$estado_filtro = 'pendiente';

// Lógica de paginación
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Contar total de reservas PENDIENTES para la paginación
$countStmt = $pdo->prepare("SELECT COUNT(id) FROM reservas WHERE estado = ?");
$countStmt->execute([$estado_filtro]);
$totalRes = $countStmt->fetchColumn();

$totalPages = ceil($totalRes / $perPage);
$totalPages = $totalPages > 0 ? $totalPages : 1;

// --- INICIO DE LA CORRECCIÓN ---
// Se estandariza la consulta para usar solo parámetros nombrados (:nombre)
$stmt = $pdo->prepare(
    "SELECT * FROM reservas WHERE estado = :estado 
     ORDER BY fecha ASC, hora_inicio ASC 
     LIMIT :limit OFFSET :offset"
);

// Se asignan los valores a los parámetros nombrados
$stmt->bindValue(':estado', $estado_filtro, PDO::PARAM_STR);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$reservas = $stmt->fetchAll();
// --- FIN DE LA CORRECCIÓN ---

// Variables para el calendario
$hora_inicio = defined('HORA_INICIO_PERMITIDA') ? HORA_INICIO_PERMITIDA : '08:00:00';
$hora_fin = defined('HORA_FIN_PERMITIDA') ? HORA_FIN_PERMITIDA : '18:00:00';
$dias_permitidos = defined('DIAS_PERMITIDOS') ? DIAS_PERMITIDOS : [1, 2, 3, 4, 5];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Administrador</title>
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

        <h4>Reservas (Página <?php echo $page; ?> de <?php echo $totalPages; ?>)</h4>
        <div id="mensaje" class="mb-3"></div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="reservasTable">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Fecha y Hora</th>
                        <th>Motivo</th>
                        <th>Bibliografía</th>
                        <th>Estado</th>
                        <th>Comentario</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservas)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No hay reservas para mostrar.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reservas as $reserva): ?>
                            <tr data-id="<?php echo htmlspecialchars($reserva['id']); ?>">
                                <td><?php echo htmlspecialchars($reserva['id']); ?></td>
                                <td><?php echo htmlspecialchars($reserva['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($reserva['fecha'] . ' de ' . $reserva['hora_inicio'] . ' a ' . $reserva['hora_fin']); ?></td>
                                <td class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($reserva['motivo']); ?>"><?php echo htmlspecialchars($reserva['motivo']); ?></td>
                                <td>
                                    <?php if ($reserva['bibliografia_archivo']): ?>
                                        <a href="descargar_bibliografia.php?id=<?php echo $reserva['id']; ?>" target="_blank">Ver Archivo</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="estado"><?php echo htmlspecialchars($reserva['estado']); ?></td>
                                <td class="comentario text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($reserva['comentario'] ?: ''); ?>"><?php echo htmlspecialchars($reserva['comentario'] ?: '-'); ?></td>
                                <td class="acciones">
                                    <?php if ($reserva['estado'] == 'pendiente'): ?>
                                        <button class="btn btn-success btn-sm confirmar-reserva" data-id="<?php echo $reserva['id']; ?>" data-bs-toggle="modal" data-bs-target="#reservaModal">Confirmar</button>
                                        <button class="btn btn-danger btn-sm rechazar-reserva" data-id="<?php echo $reserva['id']; ?>" data-bs-toggle="modal" data-bs-target="#reservaModal">Rechazar</button>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <nav class="mt-4" aria-label="Navegación de páginas">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">Anterior</a>
                </li>
                <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">Siguiente</a>
                </li>
            </ul>
        </nav>
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
                            <label for="comentario" class="form-label">Comentario (obligatorio al rechazar)</label>
                            <textarea class="form-control" id="comentario" name="comentario" rows="4"></textarea>
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