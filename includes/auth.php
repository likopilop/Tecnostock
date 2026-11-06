<?php
require_once __DIR__ . '/session.php';

function usuarioAutenticado(): bool
{
    return isset($_SESSION['usuario']) && isset($_SESSION['usuario']['id_usuario']);
}

function requerirLogin(): void
{
    if (!usuarioAutenticado()) {
        header('Location: /TecnoStock/login.php');
        exit;
    }
}

function redirigirSiAutenticado(): void
{
    if (usuarioAutenticado()) {
        header('Location: /TecnoStock/dashboard.php');
        exit;
    }
}
