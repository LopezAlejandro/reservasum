<?php
// bootstrap.php

// 1. Iniciar la sesión para manejar tokens CSRF y autenticación futura
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']), // En producción, usar siempre true
        'cookie_samesite' => 'Lax'
    ]);
}

// 2. Cargar el autoloader de Composer
require_once __DIR__ . '/vendor/autoload.php';

// 3. Cargar la configuración principal
require_once __DIR__ . '/config.php';

// 4. Cargar el gestor de la base de datos
require_once __DIR__ . '/database.php';

// 5. Generar y almacenar el token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Función de ayuda para verificar el token CSRF de forma segura.
 * @param string $token El token recibido del formulario.
 * @return bool
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Establecer la zona horaria para toda la aplicación
date_default_timezone_set(defined('TIMEZONE') ? TIMEZONE : 'UTC');