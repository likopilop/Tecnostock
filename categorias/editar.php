<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requerirLogin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(404);
    exit('Categoría no encontrada.');
}

$stmt = $pdo->prepare('SELECT id_categoria, nombre, descripcion, estado FROM categorias WHERE id_categoria = :id');
$stmt->execute([':id' => $id]);
$categoria = $stmt->fetch();

if (!$categoria) {
    http_response_code(404);
    exit('Categoría no encontrada.');
}

$errores = [];
$nombre = $categoria['nombre'];
$descripcion = $categoria['descripcion'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();

    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($nombre === '' || mb_strlen($nombre) < 2 || mb_strlen($nombre) > 100) {
        $errores[] = 'El nombre debe tener entre 2 y 100 caracteres.';
    }

    if (mb_strlen($descripcion) > 255) {
        $errores[] = 'La descripción no puede superar los 255 caracteres.';
    }

    if (!$errores) {
        try {
            $stmt = $pdo->prepare(
                'UPDATE categorias
                 SET nombre = :nombre, descripcion = :descripcion
                 WHERE id_categoria = :id'
            );
            $stmt->execute([
                ':nombre' => $nombre,
                ':descripcion' => $descripcion !== '' ? $descripcion : null,
                ':id' => $id,
            ]);

            flash('success', 'Categoría actualizada correctamente.');
            header('Location: /TecnoStock/categorias/index.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errores[] = 'Ya existe una categoría con ese nombre.';
            } else {
                $errores[] = 'No fue posible actualizar la categoría.';
            }
        }
    }
}

$titulo = 'Editar categoría';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 page-title mb-1">Editar categoría</h1>
                    <p class="text-secondary mb-0">Modifica los datos de la categoría seleccionada.</p>
                </div>
                <a href="/TecnoStock/categorias/index.php" class="btn btn-outline-secondary">Volver</a>
            </div>

            <?php if ($errores): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errores as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="post" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required minlength="2" maxlength="100" value="<?= e($nombre) ?>">
                            <div class="invalid-feedback">Ingresa un nombre de entre 2 y 100 caracteres.</div>
                        </div>

                        <div class="mb-4">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3" maxlength="255"><?= e($descripcion) ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
