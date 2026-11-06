<?php
require_once __DIR__ . '/session.php';
$rutaActual = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';

function navActivo(string $fragmento, string $rutaActual): string
{
    return str_contains($rutaActual, $fragmento) ? ' active' : '';
}

?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="/TecnoStock/dashboard.php">TecnoStock</a>
        <button id="btnTema" class="btn btn-outline-light btn-sm">
                  🌙 Tema oscuro
                </button>
                
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal" aria-controls="menuPrincipal" aria-expanded="false" aria-label="Mostrar menú">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menuPrincipal">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link<?= $rutaActual === '/TecnoStock/dashboard.php' ? ' active' : '' ?>" href="/TecnoStock/dashboard.php">Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= navActivo('/categorias/', $rutaActual) ?>" href="/TecnoStock/categorias/index.php">Categorías</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= navActivo('/productos/', $rutaActual) ?>" href="/TecnoStock/productos/index.php">Productos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= navActivo('/movimientos/', $rutaActual) ?>" href="/TecnoStock/movimientos/index.php">Movimientos</a>
                </li>
            </ul>

            <?php if (isset($_SESSION['usuario'])): ?>
                <div class="d-flex align-items-center gap-3 text-white">
                    <span class="small">
                        <?= htmlspecialchars($_SESSION['usuario']['nombre']) ?>
                    </span>
                    <a href="/TecnoStock/logout.php" class="btn btn-outline-light btn-sm">Cerrar sesión</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
