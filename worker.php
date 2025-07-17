<?php
// worker.php
// Ejecutar desde la línea de comandos: > php worker.php
// Usar 'supervisor' en producción para mantenerlo activo.

require __DIR__ . '/bootstrap.php';

echo "Worker iniciado a las " . date('Y-m-d H:i:s') . ". Esperando trabajos...\n";

while (true) {
    $pdo = get_pdo_connection();
    $job = null;
    
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT * FROM jobs WHERE queue = 'emails' AND available_at <= ? ORDER BY id ASC LIMIT 1 FOR UPDATE SKIP LOCKED");
        $stmt->execute([time()]);
        $job = $stmt->fetch();

        if ($job) {
            $deleteStmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
            $deleteStmt->execute([$job['id']]);
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "Error al obtener trabajo: " . $e->getMessage() . "\n";
        sleep(10); // Esperar más tiempo si hay un error de BD
        continue;
    }

    if ($job) {
        echo "Procesando trabajo #{$job['id']}...";
        $payload = json_decode($job['payload'], true);
        $data = $payload['data'];
        $success = false;

        switch ($payload['type']) {
            case 'nueva_reserva_usuario':
                $subject = 'Solicitud de Reserva Recibida';
                $body = "<p>Estimado/a " . htmlspecialchars($data['nombre']) . ",</p><p>Hemos recibido su solicitud para el <strong>" . htmlspecialchars($data['fecha']) . " de " . htmlspecialchars($data['hora_inicio']) . " a " . htmlspecialchars($data['hora_fin']) . "</strong>. Será revisada a la brevedad.</p>";
                $success = App\Utils\Mailer::sendMail($data['email'], $data['nombre'], $subject, $body);
                break;

            case 'nueva_reserva_encargado':
                 $subject = 'Nueva Solicitud de Reserva #' . $data['id'];
                 // --- INICIO DE LA MODIFICACIÓN ---
                 // Se añaden los campos de teléfono y carrera/dependencia al cuerpo del email.
                 $body = "<h3>Nueva Solicitud de Reserva #{$data['id']}</h3>
                    <ul>
                        <li><strong>Nombre:</strong> " . htmlspecialchars($data['nombre']) . "</li>
                        <li><strong>Teléfono:</strong> " . htmlspecialchars($data['telefono']) . "</li>
                        <li><strong>Email:</strong> " . htmlspecialchars($data['email']) . "</li>
                        <li><strong>Carrera/Dependencia:</strong> " . htmlspecialchars($data['carrera']) . "</li>
                        <li><strong>Fecha:</strong> " . htmlspecialchars($data['fecha']) . " de " . htmlspecialchars($data['hora_inicio']) . " a " . htmlspecialchars($data['hora_fin']) . "</li>
                        <li><strong>Motivo:</strong> " . htmlspecialchars($data['motivo']) . "</li>
                        <li><strong>Asistentes:</strong> " . htmlspecialchars($data['cantidad_asistentes']) . "</li>
                    </ul>
		    <p>Para aceptar o rechazar la reseseva ir a <a href='https://biblioteca.fadu.uba.ar/reserva/login.php'>este enlace</a></p>";
                 // --- FIN DE LA MODIFICACIÓN ---
                 $success = App\Utils\Mailer::sendMail(ENCARGADO_EMAIL, ENCARGADO_NAME, $subject, $body);
                break;
            
            case 'actualizacion_reserva':
                $subject = 'Actualización de su reserva';
                $body = "<p>Estimado/a " . htmlspecialchars($data['nombre']) . ",</p><p>Su reserva para el <strong>" . htmlspecialchars($data['fecha']) . " de " . htmlspecialchars($data['hora_inicio']) . " a " . htmlspecialchars($data['hora_fin']) . "</strong> ha sido <strong>" . htmlspecialchars($data['estado']) . "</strong>.</p><p><strong>Comentario:</strong> " . htmlspecialchars($data['comentario'] ?: 'Ninguno') . "</p>";
                $success = App\Utils\Mailer::sendMail($data['email'], $data['nombre'], $subject, $body);
                break;
        }

        echo $success ? " Éxito.\n" : " Fallo.\n";
    } else {
        // Si no hay trabajos, esperar 5 segundos antes de volver a consultar
        sleep(5);
    }
}