<?php
// gestionar_reserva.php

require __DIR__ . '/bootstrap.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido.', 405);
    }
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        throw new Exception('Error de validación (CSRF).', 403);
    }

    // TODO: Implementar un sistema de autenticación para asegurarse de que solo el encargado pueda ejecutar esta acción.

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';
    $comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';

    if ($id <= 0 || !in_array($accion, ['confirmar', 'rechazar'])) {
        throw new Exception('Parámetros inválidos.');
    }
    if ($accion === 'rechazar' && empty($comentario)) {
        throw new Exception('El comentario es obligatorio al rechazar una reserva.');
    }

    $pdo = get_pdo_connection();
    
    // Obtener datos de la reserva para el correo
    $stmt = $pdo->prepare("SELECT * FROM reservas WHERE id = ?");
    $stmt->execute([$id]);
    $reserva = $stmt->fetch();
    if (!$reserva) {
        throw new Exception('Reserva no encontrada.', 404);
    }

    // Actualizar la reserva
    $estado = ($accion === 'confirmar') ? 'confirmada' : 'rechazada';
    $stmt = $pdo->prepare("UPDATE reservas SET estado = ?, comentario = ? WHERE id = ?");
    $stmt->execute([$estado, $comentario, $id]);

    // Despachar trabajo para notificar al usuario
    \App\Queue\Job::dispatch('emails', [
        'type' => 'actualizacion_reserva',
        'data' => [
            'email' => $reserva['email'],
            'nombre' => $reserva['nombre'],
            'fecha' => $reserva['fecha'],
            'hora_inicio' => $reserva['hora_inicio'],
            'hora_fin' => $reserva['hora_fin'],
            'motivo' => $reserva['motivo'],
            'estado' => $estado,
            'comentario' => $comentario
        ]
    ]);

    echo json_encode(['success' => true, 'message' => "Reserva $estado. Se enviará una notificación al usuario."]);
    exit; // <--- AÑADIR ESTA LÍNEA

} catch (Exception $e) {
    http_response_code($e->getCode() > 0 ? $e->getCode() : 400);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit; // <--- Y AÑADIR ESTA LÍNEA
}