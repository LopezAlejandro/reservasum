<?php
// database.php

function get_pdo_connection() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // En un entorno de producción, loguea el error en un archivo
            // en lugar de mostrarlo al usuario.
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            // Muestra un mensaje genérico
            http_response_code(500);
            die("Error de conexión al servidor.");
        }
    }

    return $pdo;
}