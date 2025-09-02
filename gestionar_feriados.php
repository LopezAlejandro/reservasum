<?php
// gestionar_feriados.php
require __DIR__ . '/bootstrap.php';
header('Content-Type: application/json');

try {
    // Proteger el script solo para el encargado
    if (!isset($_SESSION['user_is_encargado']) || $_SESSION['user_is_encargado'] !== true) {
        throw new Exception('Acceso denegado.', 403);
    }

    // --- INICIO DE LA CORRECCIÓN ---
    // Verificar el token de seguridad para todas las acciones POST
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        throw new Exception('Error de validación de seguridad (CSRF).', 403);
    }
    // --- FIN DE LA CORRECCIÓN ---

    // Función para limpiar el caché de feriados
    function limpiar_cache_feriados() {
        $cacheFile = __DIR__ . '/cache/feriados.json';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }

    $accion = $_POST['action'] ?? '';
    $pdo = get_pdo_connection();

    switch ($accion) {
        case 'agregar':
            $fecha = $_POST['fecha'] ?? '';
            $descripcion = trim($_POST['descripcion'] ?? '');

            if (empty($fecha) || empty($descripcion)) {
                throw new Exception('La fecha y la descripción son obligatorias.');
            }

            $stmt = $pdo->prepare("INSERT INTO feriados (fecha, descripcion) VALUES (?, ?)");
            $stmt->execute([$fecha, $descripcion]);
            
            limpiar_cache_feriados();
            echo json_encode(['success' => true, 'message' => 'Feriado agregado con éxito.']);
            break;

        case 'eliminar':
            $id = $_POST['id'] ?? 0;
            if (empty($id)) {
                throw new Exception('ID de feriado inválido.');
            }

            $stmt = $pdo->prepare("DELETE FROM feriados WHERE id = ?");
            $stmt->execute([$id]);

            limpiar_cache_feriados();
            echo json_encode(['success' => true, 'message' => 'Feriado eliminado con éxito.']);
            break;

        default:
            throw new Exception('Acción no válida.');
    }
} catch (Exception $e) {
    $httpCode = $e->getCode() > 0 ? $e->getCode() : 400;
    http_response_code($httpCode);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;