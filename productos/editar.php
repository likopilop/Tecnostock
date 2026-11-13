<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requerirLogin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(404);
    exit('Producto no encontrado.');
}

$stmt = $pdo->prepare(
    'SELECT id_producto, codigo, nombre, descripcion, precio, stock_actual,
            stock_minimo, estado, id_categoria
     FROM productos
     WHERE id_producto = :id'
);
$stmt->execute([':id' => $id]);
$producto = $stmt->fetch();

if (!$producto) {
    http_response_code(404);
    exit('Producto no encontrado.');
}

$stmt = $pdo->prepare(
    'SELECT id_categoria, nombre, estado
     FROM categorias
     WHERE estado = 1 OR id_categoria = :actual
     ORDER BY estado DESC, nombre ASC'
);
$stmt->execute([':actual' => $producto['id_categoria']]);
$categorias = $stmt->fetchAll();

$errores = [];
$codigo = $producto['codigo'];
$nombre = $producto['nombre'];
$descripcion = $producto['descripcion'] ?? '';
$precio = $producto['precio'];
$stockActual = $producto['stock_actual'];
$stockMinimo = $producto['stock_minimo'];
$idCategoria = $producto['id_categoria'];

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
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM categorias
             WHERE id_categoria = :id
               AND (estado = 1 OR id_categoria = :categoria_actual)'
        );
        $stmt->execute([
            ':id' => $categoriaValidada,
            ':categoria_actual' => $producto['id_categoria'],
        ]);
        if ((int) $stmt->fetchColumn() === 0) {
            $errores[] = 'La categoría seleccionada no está disponible.';
        }
    }

    if (!$errores) {
        try {
            $stmt = $pdo->prepare(
                'UPDATE productos
                 SET codigo = :codigo,
                     nombre = :nombre,
                     descripcion = :descripcion,
                     precio = :precio,
                     stock_actual = :stock_actual,
                     stock_minimo = :stock_minimo,
                     id_categoria = :id_categoria
                 WHERE id_producto = :id'
            );
            $stmt->execute([
                ':codigo' => $codigo,
                ':nombre' => $nombre,
                ':descripcion' => $descripcion !== '' ? $descripcion : null,
                ':precio' => number_format($precioValidado, 2, '.', ''),
                ':stock_actual' => $stockActualValidado,
                ':stock_minimo' => $stockMinimoValidado,
                ':id_categoria' => $categoriaValidada,
                ':id' => $id,
            ]);

            flash('success', 'Producto actualizado correctamente.');
            header('Location: /TecnoStock/productos/index.php?estado=todos');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errores[] = 'El código de producto ya se encuentra registrado en otro producto.';
            } else {
                $errores[] = 'No fue posible actualizar el producto.';
            }
        }
    }
}

$titulo = 'Editar producto';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h3 page-title mb-1">Editar producto</h1>
                    <p class="text-secondary mb-0">Modifica la información, precio y cantidades del producto.</p>
                </div>
                <a href="/TecnoStock/productos/index.php?estado=todos" class="btn btn-outline-secondary align-self-start">Volver</a>
            </div>

            <?php if ((int) $producto['estado'] === 0): ?>
                <div class="alert alert-secondary">Este producto está desactivado. Puedes editar sus datos, pero seguirá inactivo hasta que lo reactives desde el listado.</div>
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
                                <input type="text" class="form-control text-uppercase" id="codigo" name="codigo" required maxlength="50" value="<?= e($codigo) ?>">
                                <div class="invalid-feedback">Ingresa un código de producto.</div>
                            </div>

                            <div class="col-12 col-md-8">
                                <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required minlength="2" maxlength="150" value="<?= e($nombre) ?>">
                                <div class="invalid-feedback">Ingresa el nombre del producto.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="id_categoria" class="form-label">Categoría <span class="text-danger">*</span></label>
                                <select class="form-select" id="id_categoria" name="id_categoria" required>
                                    <option value="">Selecciona una categoría</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?= (int) $categoria['id_categoria'] ?>" <?= (string) $idCategoria === (string) $categoria['id_categoria'] ? 'selected' : '' ?>>
                                            <?= e($categoria['nombre']) ?><?= (int) $categoria['estado'] === 0 ? ' (inactiva)' : '' ?>
                                        </option>
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
                                <div class="form-text">Si el stock actual queda bajo este valor, se mostrará una alerta.</div>
                                <div class="invalid-feedback">Ingresa un stock mínimo entero no negativo.</div>
                            </div>

                            <div class="col-12">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="4" maxlength="1000"><?= e($descripcion) ?></textarea>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Guardar cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
