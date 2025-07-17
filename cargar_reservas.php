<?php // cargar_reservas.php
require __DIR__ . '/bootstrap.php';
header('Content-Type: application/json');
try {
    $pdo = get_pdo_connection();
    $start = $_GET['start'] ?? date('Y-m-d');
    $end = $_GET['end'] ?? date('Y-m-d', strtotime('+7 days'));
    $stmt = $pdo->prepare("SELECT id, nombre, fecha, hora_inicio, hora_fin, motivo, estado FROM reservas WHERE estado IN ('pendiente', 'confirmada') AND fecha BETWEEN ? AND ?");
    $stmt->execute([$start, $end]);
    $reservas = [];
    while ($row = $stmt->fetch()) {
        $reservas[] = ['id' => $row['id'],'title' => 'Reservado','start' => $row['fecha'] . 'T' . $row['hora_inicio'],'end' => $row['fecha'] . 'T' . $row['hora_fin'],'estado' => $row['estado'], 'display' => 'block'];
    }
    echo json_encode($reservas);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>