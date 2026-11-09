<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

redirigirSiAutenticado();

$errores = [];
$nombre = '';
$correo = '';

$totalUsuarios = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();

if ($totalUsuarios > 0) {
    $titulo = 'Administrador ya configurado';
    require __DIR__ . '/includes/header.php';
    ?>
    <main class="auth-wrapper">
        <div class="card auth-card shadow-sm border-0">
            <div class="card-body p-4 p-md-5">
                <h1 class="h3 mb-3">Administrador ya configurado</h1>
                <p class="text-secondary">Ya existe al menos un usuario registrado. Por seguridad, esta página ya no permite crear otro usuario inicial.</p>
                <a href="/TecnoStock/login.php" class="btn btn-primary w-100">Ir al inicio de sesión</a>
            </div>
        </div>
    </main>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = strtolower(trim($_POST['correo'] ?? ''));
    $clave = $_POST['clave'] ?? '';
    $confirmacion = $_POST['confirmacion'] ?? '';

    if ($nombre === '' || mb_strlen($nombre) < 3) {
        $errores[] = 'El nombre debe tener al menos 3 caracteres.';
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresa un correo electrónico válido.';
    }

    if (strlen($clave) < 8) {
        $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    }

    if ($clave !== $confirmacion) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    if (!$errores) {
        try {
            $hash = password_hash($clave, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                'INSERT INTO usuarios (nombre, correo, clave, estado) VALUES (:nombre, :correo, :clave, 1)'
            );
            $stmt->execute([
                ':nombre' => $nombre,
                ':correo' => $correo,
                ':clave'  => $hash,
            ]);

            $_SESSION['mensaje_exito'] = 'Administrador creado correctamente. Ya puedes iniciar sesión.';
            header('Location: /TecnoStock/login.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errores[] = 'El correo ya se encuentra registrado.';
            } else {
                $errores[] = 'No fue posible crear el usuario. Intenta nuevamente.';
            }
        }
    }
}

$titulo = 'Crear administrador';
require __DIR__ . '/includes/header.php';
?>
<main class="auth-wrapper">
    <div class="card auth-card shadow-sm border-0">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <h1 class="h3 mb-2">Configurar TecnoStock</h1>
                <p class="text-secondary mb-0">Crea el primer usuario encargado de inventario.</p>
            </div>

            <?php if ($errores): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errores as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" novalidate>
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" required minlength="3" maxlength="100" value="<?= htmlspecialchars($nombre) ?>">
                </div>

                <div class="mb-3">
                    <label for="correo" class="form-label">Correo</label>
                    <input type="email" class="form-control" id="correo" name="correo" required maxlength="150" value="<?= htmlspecialchars($correo) ?>">
                </div>

                <div class="mb-3">
                    <label for="clave" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="clave" name="clave" required minlength="8" autocomplete="new-password">
                    <div class="form-text">Mínimo 8 caracteres.</div>
                </div>

                <div class="mb-4">
                    <label for="confirmacion" class="form-label">Confirmar contraseña</label>
                    <input type="password" class="form-control" id="confirmacion" name="confirmacion" required minlength="8" autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-primary w-100">Crear administrador</button>
            </form>
        </div>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
