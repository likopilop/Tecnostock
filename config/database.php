<?php
// TecnoStock - conexión PDO a MySQL/MariaDB (XAMPP)
$host = 'localhost';
$port = '3306';
$db   = 'tecnostock';
$user = 'root';
$pass = ''; // En XAMPP local normalmente root no tiene contraseña.
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // En producción no se debe mostrar el detalle técnico de la conexión.
    die('No fue posible conectar con la base de datos de TecnoStock. Revisa XAMPP y config/database.php.');
}
