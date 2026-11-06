<?php
require_once __DIR__ . '/session.php';

function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verificarCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Solicitud no válida. Vuelve atrás, recarga la página e inténtalo nuevamente.');
    }
}

function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'] = [
        'tipo' => $tipo,
        'mensaje' => $mensaje,
    ];
}

function obtenerFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function enteroNoNegativo(mixed $valor): ?int
{
    if ($valor === '' || $valor === null || filter_var($valor, FILTER_VALIDATE_INT) === false) {
        return null;
    }

    $numero = (int) $valor;
    return $numero >= 0 ? $numero : null;
}

function decimalNoNegativo(mixed $valor): ?float
{
    if ($valor === '' || $valor === null || !is_numeric($valor)) {
        return null;
    }

    $numero = (float) $valor;
    return $numero >= 0 ? $numero : null;
}
