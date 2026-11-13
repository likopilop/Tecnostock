<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requerirLogin();

$busqueda = trim($_GET['q'] ?? '');

$sql = 'SELECT c.id_categoria, c.nombre, c.descripcion, c.estado,
               COUNT(p.id_producto) AS total_productos,
               SUM(CASE WHEN p.estado = 1 THEN 1 ELSE 0 END) AS productos_activos
        FROM categorias c
        LEFT JOIN productos p ON p.id_categoria = c.id_categoria';
$params = [];

if ($busqueda !== '') {
    $sql .= ' WHERE c.nombre LIKE :busqueda_nombre OR c.descripcion LIKE :busqueda_descripcion';
    $terminoBusqueda = '%' . $busqueda . '%';
    $params[':busqueda_nombre'] = $terminoBusqueda;
    $params[':busqueda_descripcion'] = $terminoBusqueda;
}

$sql .= ' GROUP BY c.id_categoria, c.nombre, c.descripcion, c.estado
          ORDER BY c.estado DESC, c.nombre ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categorias = $stmt->fetchAll();
$flash = obtenerFlash();

$titulo = 'Categorías';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<main class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 page-title mb-1">Categorías</h1>
            <p class="text-secondary mb-0">Administra las categorías del catálogo de TecnoStock.</p>
        </div>
        <a href="/TecnoStock/categorias/crear.php" class="btn btn-primary">Nueva categoría</a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['tipo']) ?> alert-dismissible fade show" role="alert">
            <?= e($flash['mensaje']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-12 col-md-8 col-lg-6">
                    <label for="q" class="form-label">Buscar categoría</label>
                    <input type="search" class="form-control" id="q" name="q" maxlength="100" value="<?= e($busqueda) ?>" placeholder="Nombre o descripción">
                </div>
                <div class="col-auto">
                    <button class="btn btn-dark" type="submit">Buscar</button>
                </div>
                <?php if ($busqueda !== ''): ?>
                    <div class="col-auto">
                        <a class="btn btn-outline-secondary" href="/TecnoStock/categorias/index.php">Limpiar</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th class="text-center">Productos</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$categorias): ?>
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-5">No se encontraron categorías.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categorias as $categoria): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($categoria['nombre']) ?></td>
                                <td><?= e($categoria['descripcion'] ?: '—') ?></td>
                                <td class="text-center"><?= (int) $categoria['total_productos'] ?></td>
                                <td>
                                    <?php if ((int) $categoria['estado'] === 1): ?>
                                        <span class="badge text-bg-success">Activa</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">Inactiva</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end table-actions">
                                    <a href="/TecnoStock/categorias/editar.php?id=<?= (int) $categoria['id_categoria'] ?>" class="btn btn-outline-primary btn-sm">Editar</a>

                                    <?php if ((int) $categoria['estado'] === 1): ?>
                                        <form class="d-inline" method="post" action="/TecnoStock/categorias/estado.php" data-confirm="¿Desactivar esta categoría?" >
                                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                            <input type="hidden" name="id" value="<?= (int) $categoria['id_categoria'] ?>">
                                            <input type="hidden" name="accion" value="desactivar">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Desactivar</button>
                                        </form>
                                    <?php else: ?>
                                        <form class="d-inline" method="post" action="/TecnoStock/categorias/estado.php">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                            <input type="hidden" name="id" value="<?= (int) $categoria['id_categoria'] ?>">
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
