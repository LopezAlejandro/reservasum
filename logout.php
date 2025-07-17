<?php
// logout.php
require __DIR__ . '/bootstrap.php';

// Limpiamos todas las variables de sesión
$_SESSION = [];

// Destruimos la sesión
session_destroy();

// Redirigimos al formulario de login
header('Location: login.php');
exit;