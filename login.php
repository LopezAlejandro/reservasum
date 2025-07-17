<?php
require __DIR__ . '/bootstrap.php';

// Si el usuario ya está logueado, lo redirigimos al panel del encargado.
if (isset($_SESSION['user_is_encargado']) && $_SESSION['user_is_encargado'] === true) {
    header('Location: encargado.php');
    exit;
}

$error = $_GET['error'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Panel del Encargado</title>
    <link href="reserva/assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background-color: #f8f9fa; }
        .login-card { max-width: 400px; width: 100%; }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="card-body">
            <h3 class="card-title text-center mb-4">Acceso al Panel</h3>
            <form action="verificar_login.php" method="POST">
                <?php if ($error): ?>
                    <div class="alert alert-danger">Usuario o contraseña incorrectos.</div>
                <?php endif; ?>
                <div class="mb-3">
                    <label for="username" class="form-label">Usuario</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Ingresar</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>