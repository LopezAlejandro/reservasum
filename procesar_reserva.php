<?php
// procesar_reserva.php

require __DIR__ . '/bootstrap.php';
header('Content-Type: application/json');

try {
    // 1. Verificar método y token CSRF
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido.', 405);
    }
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        throw new Exception('Error de validación (CSRF). Recargue la página.', 403);
    }

    // 2. Asignación de variables desde POST
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $cargo_solicitante = trim($_POST['cargo_solicitante'] ?? '');
    $carrera = trim($_POST['carrera'] ?? '');
    $cantidad_asistentes = trim($_POST['cantidad_asistentes'] ?? '');
    $fecha = trim($_POST['fecha'] ?? '');
    $hora_inicio = trim($_POST['hora_inicio'] ?? '');
    $hora_fin = trim($_POST['hora_fin'] ?? '');
    $motivo = trim($_POST['motivo'] ?? '');

    // 3. Validación de campos obligatorios
    if (empty($nombre) || empty($email) || empty($telefono) || empty($cargo_solicitante) || empty($carrera) || empty($cantidad_asistentes) || empty($fecha) || empty($hora_inicio) || empty($hora_fin) || empty($motivo)) {
        throw new Exception('Todos los campos obligatorios deben completarse.');
    }

    // 4. Validación de formatos específicos
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('El formato del correo electrónico no es válido.');
    }

    $telefono_regex = defined('TELEFONO_REGEX') ? TELEFONO_REGEX : '/^[0-9\s\+\(\)\-]{7,20}$/';
    if (!preg_match($telefono_regex, $telefono)) {
        throw new Exception('El formato del teléfono no es válido.');
    }

    $max_asistentes = defined('MAX_ASISTENTES') ? MAX_ASISTENTES : 50;
    if (!is_numeric($cantidad_asistentes) || $cantidad_asistentes < 1 || $cantidad_asistentes > $max_asistentes) {
        throw new Exception("La cantidad de asistentes debe ser un número entre 1 y $max_asistentes.");
    }

    // 5. Validación de reglas de negocio (fechas y horarios)
    $anticipacion_horas = defined('ANTICIPACION_HORAS') ? ANTICIPACION_HORAS : 48;
    $fecha_reserva = new DateTime($fecha . ' ' . $hora_inicio);
    $ahora = new DateTime();
    $fecha_minima = (clone $ahora)->modify("+{$anticipacion_horas} hours");

    if ($fecha_reserva < $fecha_minima) {
        throw new Exception("La reserva debe realizarse con al menos $anticipacion_horas horas de anticipación.");
    }

    $hora_inicio_permitida = defined('HORA_INICIO_PERMITIDA') ? HORA_INICIO_PERMITIDA : '08:00:00';
    $hora_fin_permitida = defined('HORA_FIN_PERMITIDA') ? HORA_FIN_PERMITIDA : '18:00:00';
    if ($hora_inicio < $hora_inicio_permitida || $hora_fin > $hora_fin_permitida || $hora_inicio >= $hora_fin) {
        throw new Exception("El horario debe estar entre las $hora_inicio_permitida y las $hora_fin_permitida.");
    }

    $dias_permitidos = defined('DIAS_PERMITIDOS') ? DIAS_PERMITIDOS : [1, 2, 3, 4, 5];
    $dia_semana = (int)$fecha_reserva->format('N'); // 1 (Lunes) a 7 (Domingo)
    if (!in_array($dia_semana, $dias_permitidos)) {
        throw new Exception('Solo se pueden realizar reservas en días hábiles.');
    }

    // 6. Conexión y validaciones contra la base de datos
    $pdo = get_pdo_connection();

    // Validar que no sea un feriado
    $stmtFeriado = $pdo->prepare("SELECT fecha FROM feriados WHERE fecha = ?");
    $stmtFeriado->execute([$fecha]);
    if ($stmtFeriado->fetch()) {
        throw new Exception('No se pueden realizar reservas en días feriados.');
    }

    // Validar que no haya superposición de horarios
    $stmtOverlap = $pdo->prepare("SELECT id FROM reservas WHERE fecha = ? AND estado IN ('pendiente', 'confirmada') AND (hora_inicio < ? AND hora_fin > ?)");
    $stmtOverlap->execute([$fecha, $hora_fin, $hora_inicio]);
    if ($stmtOverlap->fetch()) {
        throw new Exception('El horario seleccionado ya está reservado o se solapa con otra reserva.');
    }

    // 7. Manejo de archivo subido (bibliografía)
    $bibliografia_path = null;
    if (isset($_FILES['bibliografia']) && $_FILES['bibliografia']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['bibliografia']['type'] !== 'text/plain') {
            throw new Exception('El archivo de bibliografía debe ser de tipo .txt.');
        }
        $max_file_size = defined('MAX_FILE_SIZE') ? MAX_FILE_SIZE : 2 * 1024 * 1024;
        if ($_FILES['bibliografia']['size'] > $max_file_size) {
            throw new Exception('El archivo excede el tamaño máximo permitido de 2MB.');
        }
        $upload_dir = UPLOAD_DIR;
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $bibliografia_path = $upload_dir . uniqid('biblio_', true) . '_' . basename($_FILES['bibliografia']['name']);
        if (!move_uploaded_file($_FILES['bibliografia']['tmp_name'], $bibliografia_path)) {
            throw new Exception('Error al subir el archivo de bibliografía.');
        }
    }
    
    // 8. Inserción en la base de datos
    $stmt = $pdo->prepare(
        "INSERT INTO reservas (nombre, email, telefono, cargo_solicitante, carrera, cantidad_asistentes, fecha, hora_inicio, hora_fin, motivo, bibliografia_archivo, estado) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')"
    );
    $stmt->execute([$nombre, $email, $telefono, $cargo_solicitante, $carrera, $cantidad_asistentes, $fecha, $hora_inicio, $hora_fin, $motivo, $bibliografia_path]);
    $reservaId = $pdo->lastInsertId();

    // 9. Despachar trabajos a la cola para enviar emails
    $reservaData = $_POST; // Usamos los datos del post que ya tenemos
    $reservaData['id'] = $reservaId;
    $reservaData['bibliografia_path'] = $bibliografia_path;

    \App\Queue\Job::dispatch('emails', ['type' => 'nueva_reserva_usuario', 'data' => $reservaData]);
    \App\Queue\Job::dispatch('emails', ['type' => 'nueva_reserva_encargado', 'data' => $reservaData]);

    // 10. Enviar respuesta de éxito
    echo json_encode(['success' => true, 'message' => 'Reserva solicitada con éxito. Recibirá una confirmación por correo.']);
    exit;

} catch (Exception $e) {
    // Manejo centralizado de errores
    $httpCode = $e->getCode();
    if ($httpCode < 400 || $httpCode >= 600) {
        $httpCode = 400; // Código de error por defecto para el cliente
    }
    http_response_code($httpCode);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}