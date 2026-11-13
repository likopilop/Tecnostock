<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requerirLogin();

$busqueda = trim($_GET['q'] ?? '');
$tipoFiltro = strtoupper(trim($_GET['tipo'] ?? ''));
if (!in_array($tipoFiltro, ['', 'ENTRADA', 'SALIDA'], true)) {
    $tipoFiltro = '';
}

$idProducto = filter_input(INPUT_GET, 'producto', FILTER_VALIDATE_INT) ?: 0;

$productos = $pdo->query(
    'SELECT id_producto, codigo, nombre, estado
     FROM productos
     ORDER BY estado DESC, nombre ASC'
)->fetchAll();

$sql = 'SELECT m.id_movimiento, m.fecha_hora, m.tipo, m.cantidad, m.observacion,
               p.id_producto, p.codigo, p.nombre AS producto,
               u.nombre AS usuario
        FROM movimientos m
        INNER JOIN productos p ON p.id_producto = m.id_producto
        INNER JOIN usuarios u ON u.id_usuario = m.id_usuario
        WHERE 1 = 1';
$params = [];

if ($busqueda !== '') {
    $sql .= ' AND (p.codigo LIKE :q_codigo OR p.nombre LIKE :q_nombre OR m.observacion LIKE :q_observacion)';
    $termino = '%' . $busqueda . '%';
    $params[':q_codigo'] = $termino;
    $params[':q_nombre'] = $termino;
    $params[':q_observacion'] = $termino;
}

if ($tipoFiltro !== '') {
    $sql .= ' AND m.tipo = :tipo';
    $params[':tipo'] = $tipoFiltro;
}

if ($idProducto > 0) {
    $sql .= ' AND m.id_producto = :id_producto';
    $params[':id_producto'] = $idProducto;
}

$sql .= ' ORDER BY m.fecha_hora DESC, m.id_movimiento DESC LIMIT 200';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movimientos = $stmt->fetchAll();

$flash = obtenerFlash();
$titulo = 'Movimientos';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 page-title mb-1">Movimientos de inventario</h1>
            <p class="text-secondary mb-0">Historial de entradas y salidas con producto, usuario responsable y fecha.</p>
        </div>
        <a href="/TecnoStock/movimientos/registrar.php" class="btn btn-primary">Nuevo movimiento</a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['tipo']) ?> alert-dismissible fade show" role="alert">
            <?= e($flash['mensaje']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                    <label for="q" class="form-label">Buscar</label>
                    <input type="search" class="form-control" id="q" name="q" maxlength="150" value="<?= e($busqueda) ?>" placeholder="Código, producto u observación">
                </div>

                <div class="col-12 col-md-5 col-lg-3">
                    <label for="producto" class="form-label">Producto</label>
                    <select class="form-select" id="producto" name="producto">
                        <option value="">Todos</option>
                        <?php foreach ($productos as $producto): ?>
                            <option value="<?= (int) $producto['id_producto'] ?>" <?= $idProducto === (int) $producto['id_producto'] ? 'selected' : '' ?>>
                                <?= e($producto['codigo']) ?> · <?= e($producto['nombre']) ?><?= (int) $producto['estado'] === 0 ? ' (inactivo)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-4 col-lg-2">
                    <label for="tipo" class="form-label">Tipo</label>
                    <select class="form-select" id="tipo" name="tipo">
                        <option value="" <?= $tipoFiltro === '' ? 'selected' : '' ?>>Todos</option>
                        <option value="ENTRADA" <?= $tipoFiltro === 'ENTRADA' ? 'selected' : '' ?>>Entrada</option>
                        <option value="SALIDA" <?= $tipoFiltro === 'SALIDA' ? 'selected' : '' ?>>Salida</option>
                    </select>
                </div>

                <div class="col-auto">
                    <button type="submit" class="btn btn-dark">Filtrar</button>
                </div>
                <div class="col-auto">
                    <a href="/TecnoStock/movimientos/index.php" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha y hora</th>
                            <th>Tipo</th>
                            <th>Producto</th>
                            <th class="text-center">Cantidad</th>
                            <th>Responsable</th>
                            <th>Observación</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$movimientos): ?>
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-5">No se encontraron movimientos.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($movimientos as $movimiento): ?>
                            <tr>
                                <td class="text-nowrap"><?= e(date('d-m-Y H:i', strtotime($movimiento['fecha_hora']))) ?></td>
                                <td>
                                    <?php if ($movimiento['tipo'] === 'ENTRADA'): ?>
                                        <span class="badge text-bg-success">Entrada</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-danger">Salida</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= e($movimiento['codigo']) ?></div>
                                    <small class="text-secondary"><?= e($movimiento['producto']) ?></small>
                                </td>
                                <td class="text-center fw-semibold"><?= (int) $movimiento['cantidad'] ?></td>
                                <td><?= e($movimiento['usuario']) ?></td>
                                <td><?= $movimiento['observacion'] ? e($movimiento['observacion']) : '<span class="text-secondary">—</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <p class="text-secondary small mt-3 mb-0">Se muestran como máximo los 200 movimientos más recientes que coinciden con los filtros.</p>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
