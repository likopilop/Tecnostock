<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requerirLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /TecnoStock/productos/index.php');
    exit;
}

verificarCsrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$accion = $_POST['accion'] ?? '';

if (!$id || !in_array($accion, ['desactivar', 'reactivar'], true)) {
    flash('danger', 'Solicitud no válida.');
    header('Location: /TecnoStock/productos/index.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT p.id_producto, p.nombre, p.estado, c.estado AS categoria_estado
     FROM productos p
     INNER JOIN categorias c ON c.id_categoria = p.id_categoria
     WHERE p.id_producto = :id'
);
$stmt->execute([':id' => $id]);
$producto = $stmt->fetch();

if (!$producto) {
    flash('danger', 'El producto no existe.');
    header('Location: /TecnoStock/productos/index.php');
    exit;
}

if ($accion === 'reactivar' && (int) $producto['categoria_estado'] !== 1) {
    flash('warning', 'No puedes reactivar el producto mientras su categoría esté inactiva.');
    header('Location: /TecnoStock/productos/index.php?estado=todos');
    exit;
}

$nuevoEstado = $accion === 'desactivar' ? 0 : 1;
$mensaje = $accion === 'desactivar'
    ? 'Producto desactivado correctamente. No fue eliminado de la base de datos.'
    : 'Producto reactivado correctamente.';

$stmt = $pdo->prepare('UPDATE productos SET estado = :estado WHERE id_producto = :id');
$stmt->execute([
    ':estado' => $nuevoEstado,
    ':id' => $id,
]);

flash('success', $mensaje);
header('Location: /TecnoStock/productos/index.php?estado=todos');
exit;
