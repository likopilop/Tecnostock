<?php
require_once __DIR__ . '/includes/auth.php';

if (usuarioAutenticado()) {
    header('Location: /TecnoStock/dashboard.php');
} else {
    header('Location: /TecnoStock/login.php');
}
exit;
