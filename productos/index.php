<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requerirLogin();

$busqueda = trim($_GET['q'] ?? '');
$idCategoria = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT) ?: 0;
$estadoFiltro = $_GET['estado'] ?? 'activos';
if (!in_array($estadoFiltro, ['activos', 'inactivos', 'todos'], true)) {
    $estadoFiltro = 'activos';
}

$categorias = $pdo->query('SELECT id_categoria, nombre, estado FROM categorias ORDER BY estado DESC, nombre ASC')->fetchAll();

$sql = 'SELECT p.id_producto, p.codigo, p.nombre, p.descripcion, p.precio,
               p.stock_actual, p.stock_minimo, p.estado,
               c.id_categoria, c.nombre AS categoria
        FROM productos p
        INNER JOIN categorias c ON c.id_categoria = p.id_categoria
        WHERE 1 = 1';
$params = [];

if ($estadoFiltro === 'activos') {
    $sql .= ' AND p.estado = 1';
} elseif ($estadoFiltro === 'inactivos') {
    $sql .= ' AND p.estado = 0';
}

if ($busqueda !== '') {
    $sql .= ' AND (p.nombre LIKE :busqueda_nombre OR p.codigo LIKE :busqueda_codigo)';
    $terminoBusqueda = '%' . $busqueda . '%';
    $params[':busqueda_nombre'] = $terminoBusqueda;
    $params[':busqueda_codigo'] = $terminoBusqueda;
}

if ($idCategoria > 0) {
    $sql .= ' AND p.id_categoria = :categoria';
    $params[':categoria'] = $idCategoria;
}

$sql .= ' ORDER BY p.estado DESC, p.nombre ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();
$flash = obtenerFlash();

$titulo = 'Productos';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 page-title mb-1">Productos</h1>
            <p class="text-secondary mb-0">Consulta, busca, registra, edita y desactiva productos.</p>
        </div>
        <a href="/TecnoStock/productos/crear.php" class="btn btn-primary">Nuevo producto</a>
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
                <div class="col-12 col-lg-5">
                    <label for="q" class="form-label">Buscar</label>
                    <input type="search" class="form-control" id="q" name="q" maxlength="150" value="<?= e($busqueda) ?>" placeholder="Nombre o código">
                </div>

                <div class="col-12 col-md-5 col-lg-3">
                    <label for="categoria" class="form-label">Categoría</label>
                    <select class="form-select" id="categoria" name="categoria">
                        <option value="">Todas</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= (int) $categoria['id_categoria'] ?>" <?= $idCategoria === (int) $categoria['id_categoria'] ? 'selected' : '' ?>>
                                <?= e($categoria['nombre']) ?><?= (int) $categoria['estado'] === 0 ? ' (inactiva)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-4 col-lg-2">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="activos" <?= $estadoFiltro === 'activos' ? 'selected' : '' ?>>Activos</option>
                        <option value="inactivos" <?= $estadoFiltro === 'inactivos' ? 'selected' : '' ?>>Inactivos</option>
                        <option value="todos" <?= $estadoFiltro === 'todos' ? 'selected' : '' ?>>Todos</option>
                    </select>
                </div>

                <div class="col-auto">
                    <button type="submit" class="btn btn-dark">Filtrar</button>
                </div>
                <div class="col-auto">
                    <a href="/TecnoStock/productos/index.php" class="btn btn-outline-secondary">Limpiar</a>
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
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th class="text-end">Precio</th>
                            <th class="text-center">Stock</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$productos): ?>
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">No se encontraron productos.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($productos as $producto): ?>
                            <?php $stockBajo = (int) $producto['stock_actual'] < (int) $producto['stock_minimo']; ?>
                            <tr class="<?= $stockBajo && (int) $producto['estado'] === 1 ? 'stock-bajo-row' : '' ?>">
                                <td class="fw-semibold"><?= e($producto['codigo']) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= e($producto['nombre']) ?></div>
                                    <?php if ($producto['descripcion']): ?>
                                        <small class="text-secondary"><?= e(mb_strimwidth($producto['descripcion'], 0, 70, '…')) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($producto['categoria']) ?></td>
                                <td class="text-end">$<?= number_format((float) $producto['precio'], 0, ',', '.') ?></td>
                                <td class="text-center">
                                    <span class="fw-semibold"><?= (int) $producto['stock_actual'] ?></span>
                                    <small class="text-secondary">/ mín. <?= (int) $producto['stock_minimo'] ?></small>
                                    <?php if ($stockBajo && (int) $producto['estado'] === 1): ?>
                                        <div><span class="badge text-bg-warning mt-1">Stock bajo</span></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ((int) $producto['estado'] === 1): ?>
                                        <span class="badge text-bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end table-actions">
                                    <a href="/TecnoStock/productos/editar.php?id=<?= (int) $producto['id_producto'] ?>" class="btn btn-outline-primary btn-sm">Editar</a>

                                    <?php if ((int) $producto['estado'] === 1): ?>
                                        <a href="/TecnoStock/movimientos/registrar.php?producto=<?= (int) $producto['id_producto'] ?>" class="btn btn-outline-dark btn-sm">Movimiento</a>
                                        <form class="d-inline" method="post" action="/TecnoStock/productos/estado.php" data-confirm="¿Desactivar este producto? No se eliminará de la base de datos.">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                            <input type="hidden" name="id" value="<?= (int) $producto['id_producto'] ?>">
                                            <input type="hidden" name="accion" value="desactivar">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Desactivar</button>
                                        </form>
                                    <?php else: ?>
                                        <form class="d-inline" method="post" action="/TecnoStock/productos/estado.php">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                            <input type="hidden" name="id" value="<?= (int) $producto['id_producto'] ?>">
                                            <input type="hidden" name="accion" value="reactivar">
                                            <button type="submit" class="btn btn-outline-success btn-sm">Reactivar</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
