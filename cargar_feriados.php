<?php // cargar_feriados.php
require __DIR__ . '/bootstrap.php';
header('Content-Type: application/json');
$cacheDir = __DIR__ . '/cache';
$cacheFile = $cacheDir . '/feriados.json';
$cacheLifetime = 86400; // 24 horas
if (!is_dir($cacheDir)) { mkdir($cacheDir, 0755, true); }
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheLifetime) {
    readfile($cacheFile);
    exit;
}
try {
    $pdo = get_pdo_connection();
    $stmt = $pdo->query("SELECT fecha, descripcion FROM feriados");
    $feriados = $stmt->fetchAll();
    $jsonFeriados = json_encode($feriados);
    file_put_contents($cacheFile, $jsonFeriados);
    echo $jsonFeriados;
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>