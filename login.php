<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

redirigirSiAutenticado();

$error = '';
$correo = '';
$mensajeExito = $_SESSION['mensaje_exito'] ?? '';
unset($_SESSION['mensaje_exito']);

$totalUsuarios = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = strtolower(trim($_POST['correo'] ?? ''));
    $clave = $_POST['clave'] ?? '';

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || $clave === '') {
        $error = 'Ingresa un correo y contraseña válidos.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id_usuario, nombre, correo, clave, estado
             FROM usuarios
             WHERE correo = :correo
             LIMIT 1'
        );
        $stmt->execute([':correo' => $correo]);
        $usuario = $stmt->fetch();

        if ($usuario && (int) $usuario['estado'] === 1 && password_verify($clave, $usuario['clave'])) {
            session_regenerate_id(true);

            $_SESSION['usuario'] = [
                'id_usuario' => (int) $usuario['id_usuario'],
                'nombre'     => $usuario['nombre'],
                'correo'     => $usuario['correo'],
            ];

            header('Location: /TecnoStock/dashboard.php');
            exit;
        }

        $error = 'Correo o contraseña incorrectos, o usuario desactivado.';
    }
}

$titulo = 'Iniciar sesión';
require __DIR__ . '/includes/header.php';
?>
<main class="auth-wrapper">
    <div class="card auth-card shadow-sm border-0">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <h1 class="h2 fw-bold">TecnoStock</h1>
                <p class="text-secondary mb-0">Sistema de inventario</p>
            </div>

            <?php if ($mensajeExito): ?>
                <div class="alert alert-success" role="alert">
                    <?= htmlspecialchars($mensajeExito) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($totalUsuarios === 0): ?>
                <div class="alert alert-warning" role="alert">
                    Aún no existe un usuario administrador.
                    <a href="/TecnoStock/crear_admin.php" class="alert-link">Crear administrador inicial</a>.
                </div>
            <?php endif; ?>

            <form method="post" novalidate>
                <div class="mb-3">
                    <label for="correo" class="form-label">Correo</label>
                    <input type="email" class="form-control" id="correo" name="correo" maxlength="150" required autocomplete="username" value="<?= htmlspecialchars($correo) ?>">
                </div>

                <div class="mb-4">
                    <label for="clave" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="clave" name="clave" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-primary w-100">Iniciar sesión</button>
            </form>
        </div>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
