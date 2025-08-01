<?php
$host = 'localhost';
$db   = 'busmap';
$user = 'admin';
$pass = 'admin1234';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]
    );
} catch (PDOException $e) {
    // Puedes cambiar die() por una redirección a una página de error
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>
