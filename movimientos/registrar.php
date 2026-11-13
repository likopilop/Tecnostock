<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requerirLogin();

$productos = $pdo->query(
    'SELECT id_producto, codigo, nombre, stock_actual, stock_minimo
     FROM productos
     WHERE estado = 1
     ORDER BY nombre ASC'
)->fetchAll();

$errores = [];
$idProducto = '';
$tipo = '';
$cantidad = '';
$observacion = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $productoGet = filter_input(INPUT_GET, 'producto', FILTER_VALIDATE_INT);
    if ($productoGet && $productoGet > 0) {
        $idProducto = (string) $productoGet;
    }

    $tipoGet = strtoupper(trim($_GET['tipo'] ?? ''));
    if (in_array($tipoGet, ['ENTRADA', 'SALIDA'], true)) {
        $tipo = $tipoGet;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();

    $idProducto = trim($_POST['id_producto'] ?? '');
    $tipo = strtoupper(trim($_POST['tipo'] ?? ''));
    $cantidad = trim($_POST['cantidad'] ?? '');
    $observacion = trim($_POST['observacion'] ?? '');

    $productoValidado = filter_var($idProducto, FILTER_VALIDATE_INT);
    if (!$productoValidado || $productoValidado <= 0) {
        $errores[] = 'Selecciona un producto válido.';
    }

    if (!in_array($tipo, ['ENTRADA', 'SALIDA'], true)) {
        $errores[] = 'Selecciona un tipo de movimiento válido.';
    }

    $cantidadValidada = filter_var($cantidad, FILTER_VALIDATE_INT);
    if ($cantidadValidada === false || $cantidadValidada <= 0) {
        $errores[] = 'La cantidad debe ser un número entero mayor que cero.';
    }

    if (mb_strlen($observacion) > 255) {
        $errores[] = 'La observación no puede superar los 255 caracteres.';
    }

    if (!$errores) {
        try {
            $pdo->beginTransaction();

            // Bloqueamos la fila mientras calculamos y guardamos el nuevo stock.
            // Así evitamos que dos movimientos simultáneos dejen una cantidad incoherente.
            $stmtProducto = $pdo->prepare(
                'SELECT id_producto, codigo, nombre, stock_actual, stock_minimo, estado
                 FROM productos
                 WHERE id_producto = :id_producto
                 LIMIT 1
                 FOR UPDATE'
            );
            $stmtProducto->execute([':id_producto' => $productoValidado]);
            $producto = $stmtProducto->fetch();

            if (!$producto) {
                throw new DomainException('El producto seleccionado no existe.');
            }

            if ((int) $producto['estado'] !== 1) {
                throw new DomainException('No se pueden registrar movimientos para un producto inactivo.');
            }

            $stockAnterior = (int) $producto['stock_actual'];
            $nuevoStock = $stockAnterior;

            if ($tipo === 'ENTRADA') {
                $nuevoStock = $stockAnterior + (int) $cantidadValidada;
            } else {
                if ((int) $cantidadValidada > $stockAnterior) {
                    throw new DomainException(
                        'La salida no puede superar el stock disponible (' . $stockAnterior . ' unidades).'
                    );
                }

                $nuevoStock = $stockAnterior - (int) $cantidadValidada;
            }

            $stmtMovimiento = $pdo->prepare(
                'INSERT INTO movimientos
                    (tipo, cantidad, observacion, id_producto, id_usuario)
                 VALUES
                    (:tipo, :cantidad, :observacion, :id_producto, :id_usuario)'
            );
            $stmtMovimiento->execute([
                ':tipo' => $tipo,
                ':cantidad' => (int) $cantidadValidada,
                ':observacion' => $observacion !== '' ? $observacion : null,
                ':id_producto' => $productoValidado,
                ':id_usuario' => (int) $_SESSION['usuario']['id_usuario'],
            ]);

            $stmtStock = $pdo->prepare(
                'UPDATE productos
                 SET stock_actual = :stock_actual
                 WHERE id_producto = :id_producto'
            );
            $stmtStock->execute([
                ':stock_actual' => $nuevoStock,
                ':id_producto' => $productoValidado,
            ]);

            $pdo->commit();

            $descripcionMovimiento = $tipo === 'ENTRADA' ? 'Entrada' : 'Salida';
            flash(
                'success',
                $descripcionMovimiento . ' registrada correctamente para ' . $producto['codigo'] .
                '. Stock anterior: ' . $stockAnterior . ', stock actual: ' . $nuevoStock . '.'
            );

            header('Location: /TecnoStock/movimientos/index.php');
            exit;
        } catch (DomainException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = $e->getMessage();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = 'No fue posible registrar el movimiento. No se realizaron cambios en el stock.';
        }
    }
}

$titulo = 'Registrar movimiento';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-9">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h3 page-title mb-1">Registrar movimiento</h1>
                    <p class="text-secondary mb-0">Registra una entrada o salida y actualiza el stock automáticamente.</p>
                </div>
                <a href="/TecnoStock/movimientos/index.php" class="btn btn-outline-secondary align-self-start">Ver historial</a>
            </div>

            <?php if (!$productos): ?>
                <div class="alert alert-warning">No existen productos activos disponibles para registrar movimientos.</div>
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
                            <div class="col-12">
                                <label for="id_producto" class="form-label">Producto <span class="text-danger">*</span></label>
                                <select class="form-select" id="id_producto" name="id_producto" required <?= !$productos ? 'disabled' : '' ?>>
                                    <option value="">Selecciona un producto</option>
                                    <?php foreach ($productos as $producto): ?>
                                        <option
                                            value="<?= (int) $producto['id_producto'] ?>"
                                            data-stock="<?= (int) $producto['stock_actual'] ?>"
                                            data-minimo="<?= (int) $producto['stock_minimo'] ?>"
                                            <?= (string) $idProducto === (string) $producto['id_producto'] ? 'selected' : '' ?>
                                        >
                                            <?= e($producto['codigo']) ?> · <?= e($producto['nombre']) ?> — stock: <?= (int) $producto['stock_actual'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Selecciona un producto.</div>
                                <div class="form-text" id="stockProducto">El stock mostrado se vuelve a comprobar en el servidor al guardar.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="tipo" class="form-label">Tipo <span class="text-danger">*</span></label>
                                <select class="form-select" id="tipo" name="tipo" required>
                                    <option value="">Selecciona el tipo</option>
                                    <option value="ENTRADA" <?= $tipo === 'ENTRADA' ? 'selected' : '' ?>>Entrada</option>
                                    <option value="SALIDA" <?= $tipo === 'SALIDA' ? 'selected' : '' ?>>Salida</option>
                                </select>
                                <div class="invalid-feedback">Selecciona entrada o salida.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="cantidad" class="form-label">Cantidad <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="cantidad" name="cantidad" required min="1" step="1" value="<?= e($cantidad) ?>">
                                <div class="invalid-feedback">Ingresa una cantidad entera mayor que cero.</div>
                            </div>

                            <div class="col-12">
                                <label for="observacion" class="form-label">Observación</label>
                                <textarea class="form-control" id="observacion" name="observacion" rows="3" maxlength="255" placeholder="Ej.: Compra a proveedor, ajuste de despacho, devolución..."><?= e($observacion) ?></textarea>
                                <div class="form-text">Opcional. Máximo 255 caracteres.</div>
                            </div>
                        </div>

                        <div class="alert alert-info mt-4 mb-0">
                            <strong>Regla de inventario:</strong> una salida nunca puede dejar el stock bajo cero. Si la cantidad solicitada supera el stock disponible, el movimiento se rechazará y el stock no cambiará.
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="/TecnoStock/movimientos/index.php" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary" <?= !$productos ? 'disabled' : '' ?>>Registrar movimiento</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
<script>
(function () {
    const producto = document.getElementById('id_producto');
    const stockTexto = document.getElementById('stockProducto');

    if (!producto || !stockTexto) return;

    function actualizarStock() {
        const opcion = producto.options[producto.selectedIndex];
        if (!opcion || !opcion.value) {
            stockTexto.textContent = 'El stock mostrado se vuelve a comprobar en el servidor al guardar.';
            return;
        }

        stockTexto.textContent = 'Stock actual: ' + opcion.dataset.stock + ' · Stock mínimo: ' + opcion.dataset.minimo + '.';
    }

    producto.addEventListener('change', actualizarStock);
    actualizarStock();
})();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
