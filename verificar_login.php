<?php
// verificar_login.php
require __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Verificamos si el usuario es correcto y si la contraseña coincide con el hash guardado
if ($username === ENCARGADO_USER && password_verify($password, ENCARGADO_PASS_HASH)) {
    // Regeneramos el ID de sesión para prevenir ataques de fijación de sesión
    session_regenerate_id(true);
    
    // Guardamos la variable de sesión que indica que el usuario está autenticado
    $_SESSION['user_is_encargado'] = true;
    
    // Redirigimos al panel principal del encargado
    header('Location: encargado.php');
    exit;
} else {
    // Si las credenciales son incorrectas, redirigimos de vuelta al login con un error
    header('Location: login.php?error=1');
    exit;
}