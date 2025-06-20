<?php
// admin/registeradmin.php

session_start();
require_once __DIR__ . '/../includes/database.php'; // Importar clase Database

// Crear instancia de la clase Database y obtener la conexión
$db = new Database();
$conn = $db->getConnection();

// Datos del nuevo administrador
$nombre = "root";
$email = "root@gmail.com";
$password = "root";

// Encriptar la contraseña
$hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// SQL para insertar o actualizar
$sql = "INSERT INTO administradores (nombre, email, contraseña) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE contraseña = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $nombre, $email, $hashed_password, $hashed_password);

if ($stmt->execute()) {
    echo "✅ Administrador registrado o actualizado:<br>Email: $email<br>Contraseña: $password";
} else {
    echo "❌ Error al registrar: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
