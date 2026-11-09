<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requerirLogin();

$productosActivos = (int) $pdo->query('SELECT COUNT(*) FROM productos WHERE estado = 1')->fetchColumn();
$categoriasActivas = (int) $pdo->query('SELECT COUNT(*) FROM categorias WHERE estado = 1')->fetchColumn();
$stockBajo = (int) $pdo->query('SELECT COUNT(*) FROM vista_productos_stock WHERE estado = 1 AND stock_bajo = 1')->fetchColumn();
$movimientos = (int) $pdo->query('SELECT COUNT(*) FROM movimientos')->fetchColumn();

$productosStockBajo = $pdo->query(
    'SELECT id_producto, codigo, nombre, stock_actual, stock_minimo, categoria
     FROM vista_productos_stock
     WHERE estado = 1 AND stock_bajo = 1
     ORDER BY stock_actual ASC, nombre ASC
     LIMIT 5'
)->fetchAll();

$movimientosRecientes = $pdo->query(
    'SELECT m.fecha_hora, m.tipo, m.cantidad,
            p.codigo, p.nombre AS producto,
            u.nombre AS usuario
     FROM movimientos m
     INNER JOIN productos p ON p.id_producto = m.id_producto
     INNER JOIN usuarios u ON u.id_usuario = m.id_usuario
     ORDER BY m.fecha_hora DESC, m.id_movimiento DESC
     LIMIT 5'
)->fetchAll();

$titulo = 'Panel principal';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<main class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 page-title mb-1">Panel principal</h1>
            <p class="text-secondary mb-0">
                Bienvenido/a, <?= e($_SESSION['usuario']['nombre']) ?>.
            </p>
        </div>
        <span class="badge text-bg-success align-self-start align-self-md-center">Sesión activa</span>
    </div>

    <div class="row g-3">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <p class="text-secondary mb-1">Productos activos</p>
                    <p class="display-6 fw-semibold mb-2"><?= $productosActivos ?></p>
                    <a href="/TecnoStock/productos/index.php" class="small">Ver productos</a>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <p class="text-secondary mb-1">Categorías activas</p>
                    <p class="display-6 fw-semibold mb-2"><?= $categoriasActivas ?></p>
                    <a href="/TecnoStock/categorias/index.php" class="small">Ver categorías</a>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
    <div class="card stat-card h-100 card-stock-bajo">
        <div class="card-body">
            <p class="text-secondary mb-1">Productos con stock bajo</p>
            <p class="display-6 fw-semibold mb-2"><?= $stockBajo ?></p>
            <a href="/TecnoStock/productos/index.php" class="small">Revisar stock</a>
        </div>
    </div>
</div>
        

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <p class="text-secondary mb-1">Movimientos registrados</p>
                    <p class="display-6 fw-semibold mb-2"><?= $movimientos ?></p>
                    <a href="/TecnoStock/movimientos/index.php" class="small">Ver historial</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h2 class="h5 mb-0">Alertas de stock bajo</h2>
                </div>
                <div class="card-body p-4">
                    <?php if (!$productosStockBajo): ?>
                        <div class="alert alert-success mb-0">No hay productos activos bajo su stock mínimo.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($productosStockBajo as $producto): ?>
                                <a href="/TecnoStock/productos/editar.php?id=<?= (int) $producto['id_producto'] ?>" class="list-group-item list-group-item-action px-0 d-flex justify-content-between align-items-center gap-3">
                                    <div>
                                        <div class="fw-semibold"><?= e($producto['codigo']) ?> · <?= e($producto['nombre']) ?></div>
                                        <small class="text-secondary"><?= e($producto['categoria']) ?></small>
                                    </div>
                                    <span class="badge text-bg-warning">Stock <?= (int) $producto['stock_actual'] ?> / mín. <?= (int) $producto['stock_minimo'] ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center gap-3">
                    <h2 class="h5 mb-0">Movimientos recientes</h2>
                    <a href="/TecnoStock/movimientos/index.php" class="small">Ver todos</a>
                </div>
                <div class="card-body p-4">
                    <?php if (!$movimientosRecientes): ?>
                        <div class="alert alert-secondary mb-0">Aún no existen movimientos registrados.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($movimientosRecientes as $movimiento): ?>
                                <div class="list-group-item px-0 d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <div class="fw-semibold"><?= e($movimiento['codigo']) ?> · <?= e($movimiento['producto']) ?></div>
                                        <small class="text-secondary">
                                            <?= e(date('d-m-Y H:i', strtotime($movimiento['fecha_hora']))) ?> · <?= e($movimiento['usuario']) ?>
                                        </small>
                                    </div>
                                    <?php if ($movimiento['tipo'] === 'ENTRADA'): ?>
                                        <span class="badge text-bg-success">+<?= (int) $movimiento['cantidad'] ?></span>
                                    <?php else: ?>
                                        <span class="badge text-bg-danger">-<?= (int) $movimiento['cantidad'] ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body p-4">
            <h2 class="h5">Acciones rápidas</h2>
            <p class="text-secondary">Gestiona el catálogo y el stock desde un mismo panel.</p>
            <div class="d-flex flex-wrap gap-2">
                <a href="/TecnoStock/movimientos/registrar.php?tipo=ENTRADA" class="btn btn-success">Registrar entrada</a>
                <a href="/TecnoStock/movimientos/registrar.php?tipo=SALIDA" class="btn btn-danger">Registrar salida</a>
                <a href="/TecnoStock/productos/crear.php" class="btn btn-primary">Registrar producto</a>
                <a href="/TecnoStock/productos/index.php" class="btn btn-outline-primary">Buscar productos</a>
                <a href="/TecnoStock/categorias/index.php" class="btn btn-outline-secondary">Administrar categorías</a>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
