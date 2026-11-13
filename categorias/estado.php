<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requerirLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /TecnoStock/categorias/index.php');
    exit;
}

verificarCsrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$accion = $_POST['accion'] ?? '';

if (!$id || !in_array($accion, ['desactivar', 'reactivar'], true)) {
    flash('danger', 'Solicitud no válida.');
    header('Location: /TecnoStock/categorias/index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT id_categoria, nombre, estado FROM categorias WHERE id_categoria = :id');
$stmt->execute([':id' => $id]);
$categoria = $stmt->fetch();

if (!$categoria) {
    flash('danger', 'La categoría no existe.');
    header('Location: /TecnoStock/categorias/index.php');
    exit;
}

if ($accion === 'desactivar') {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM productos WHERE id_categoria = :id AND estado = 1');
    $stmt->execute([':id' => $id]);
    $productosActivos = (int) $stmt->fetchColumn();

    if ($productosActivos > 0) {
        flash('warning', 'No puedes desactivar la categoría mientras tenga productos activos asociados.');
        header('Location: /TecnoStock/categorias/index.php');
        exit;
    }

    $nuevoEstado = 0;
    $mensaje = 'Categoría desactivada correctamente.';
} else {
    $nuevoEstado = 1;
    $mensaje = 'Categoría reactivada correctamente.';
}

$stmt = $pdo->prepare('UPDATE categorias SET estado = :estado WHERE id_categoria = :id');
$stmt->execute([
    ':estado' => $nuevoEstado,
    ':id' => $id,
]);

flash('success', $mensaje);
header('Location: /TecnoStock/categorias/index.php');
exit;
