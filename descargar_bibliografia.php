<?php
// descargar_bibliografia.php

require __DIR__ . '/bootstrap.php';

// TODO: IMPLEMENTAR AUTENTICACIÓN.
// Solo un 'encargado' logueado debería poder acceder a este script.
/*
if (!isset($_SESSION['user_is_encargado']) || $_SESSION['user_is_encargado'] !== true) {
    http_response_code(403);
    die('Acceso denegado.');
}
*/

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400); die('ID inválido.');
}

try {
    $pdo = get_pdo_connection();
    $stmt = $pdo->prepare("SELECT bibliografia_archivo FROM reservas WHERE id = ?");
    $stmt->execute([$id]);
    $filePathRelative = $stmt->fetchColumn();

    if (!$filePathRelative) {
        http_response_code(404); die('Archivo no encontrado.');
    }

    $filePathAbsolute = __DIR__ . '/' . $filePathRelative;
    if (!file_exists($filePathAbsolute) || !is_readable($filePathAbsolute)) {
        http_response_code(404); die('El archivo no existe o no se puede leer.');
    }

    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . basename($filePathAbsolute) . '"');
    header('Content-Length: ' . filesize($filePathAbsolute));
    header('Cache-Control: private');
    header('Pragma: private');

    ob_clean();
    flush();
    readfile($filePathAbsolute);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    error_log($e->getMessage());
    die('Error al procesar la solicitud.');
}