<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requerirLogin();

$categorias = $pdo->query('SELECT id_categoria, nombre FROM categorias WHERE estado = 1 ORDER BY nombre ASC')->fetchAll();

$errores = [];
$codigo = '';
$nombre = '';
$descripcion = '';
$precio = '';
$stockActual = '0';
$stockMinimo = '0';
$idCategoria = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();

    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = trim($_POST['precio'] ?? '');
    $stockActual = trim($_POST['stock_actual'] ?? '');
    $stockMinimo = trim($_POST['stock_minimo'] ?? '');
    $idCategoria = trim($_POST['id_categoria'] ?? '');

    if ($codigo === '' || mb_strlen($codigo) > 50) {
        $errores[] = 'El código es obligatorio y no puede superar 50 caracteres.';
    }

    if ($nombre === '' || mb_strlen($nombre) < 2 || mb_strlen($nombre) > 150) {
        $errores[] = 'El nombre debe tener entre 2 y 150 caracteres.';
    }

    if (mb_strlen($descripcion) > 1000) {
        $errores[] = 'La descripción no puede superar los 1000 caracteres.';
    }

    $precioValidado = decimalNoNegativo($precio);
    if ($precioValidado === null) {
        $errores[] = 'El precio debe ser un número igual o mayor que cero.';
    }

    $stockActualValidado = enteroNoNegativo($stockActual);
    if ($stockActualValidado === null) {
        $errores[] = 'El stock actual debe ser un número entero igual o mayor que cero.';
    }

    $stockMinimoValidado = enteroNoNegativo($stockMinimo);
    if ($stockMinimoValidado === null) {
        $errores[] = 'El stock mínimo debe ser un número entero igual o mayor que cero.';
    }

    $categoriaValidada = filter_var($idCategoria, FILTER_VALIDATE_INT);
    if (!$categoriaValidada) {
        $errores[] = 'Selecciona una categoría válida.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM categorias WHERE id_categoria = :id AND estado = 1');
        $stmt->execute([':id' => $categoriaValidada]);
        if ((int) $stmt->fetchColumn() === 0) {
            $errores[] = 'La categoría seleccionada no está disponible.';
        }
    }

    if (!$errores) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO productos
                    (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, estado, id_categoria)
                 VALUES
                    (:codigo, :nombre, :descripcion, :precio, :stock_actual, :stock_minimo, 1, :id_categoria)'
            );
            $stmt->execute([
                ':codigo' => $codigo,
                ':nombre' => $nombre,
                ':descripcion' => $descripcion !== '' ? $descripcion : null,
                ':precio' => number_format($precioValidado, 2, '.', ''),
                ':stock_actual' => $stockActualValidado,
                ':stock_minimo' => $stockMinimoValidado,
                ':id_categoria' => $categoriaValidada,
            ]);

            flash('success', 'Producto registrado correctamente.');
            header('Location: /TecnoStock/productos/index.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errores[] = 'El código de producto ya se encuentra registrado.';
            } else {
                $errores[] = 'No fue posible registrar el producto.';
            }
        }
    }
}

$titulo = 'Nuevo producto';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h3 page-title mb-1">Nuevo producto</h1>
                    <p class="text-secondary mb-0">Registra un producto y su stock inicial.</p>
                </div>
                <a href="/TecnoStock/productos/index.php" class="btn btn-outline-secondary align-self-start">Volver</a>
            </div>

            <?php if (!$categorias): ?>
                <div class="alert alert-warning">Debes crear al menos una categoría activa antes de registrar productos.</div>
            <?php endif; ?>

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

                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label for="codigo" class="form-label">Código <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase" id="codigo" name="codigo" required maxlength="50" value="<?= e($codigo) ?>" placeholder="TEC-006">
                                <div class="invalid-feedback">Ingresa un código de producto.</div>
                            </div>

                            <div class="col-12 col-md-8">
                                <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required minlength="2" maxlength="150" value="<?= e($nombre) ?>">
                                <div class="invalid-feedback">Ingresa el nombre del producto.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="id_categoria" class="form-label">Categoría <span class="text-danger">*</span></label>
                                <select class="form-select" id="id_categoria" name="id_categoria" required <?= !$categorias ? 'disabled' : '' ?>>
                                    <option value="">Selecciona una categoría</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?= (int) $categoria['id_categoria'] ?>" <?= (string) $idCategoria === (string) $categoria['id_categoria'] ? 'selected' : '' ?>><?= e($categoria['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Selecciona una categoría.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="precio" class="form-label">Precio <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="precio" name="precio" required min="0" step="0.01" value="<?= e($precio) ?>">
                                    <div class="invalid-feedback">Ingresa un precio igual o mayor que cero.</div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="stock_actual" class="form-label">Stock actual <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="stock_actual" name="stock_actual" required min="0" step="1" value="<?= e($stockActual) ?>">
                                <div class="invalid-feedback">Ingresa un stock entero no negativo.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="stock_minimo" class="form-label">Stock mínimo <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="stock_minimo" name="stock_minimo" required min="0" step="1" value="<?= e($stockMinimo) ?>">
                                <div class="form-text">Se mostrará una alerta cuando el stock actual sea menor que este valor.</div>
                                <div class="invalid-feedback">Ingresa un stock mínimo entero no negativo.</div>
                            </div>

                            <div class="col-12">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="4" maxlength="1000"><?= e($descripcion) ?></textarea>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary" <?= !$categorias ? 'disabled' : '' ?>>Guardar producto</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
