<?php
session_start();
require_once __DIR__ . '/../includes/database.php';

// Crear instancia de la clase Database y obtener la conexión
$db = new Database();
$conn = $db->getConnection();

// Verificar si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? '');
    $password = trim($_POST["password"] ?? '');

    if (!empty($email) && !empty($password)) {
        // Preparar consulta usando la tabla correcta
        $stmt = $conn->prepare("SELECT email, contraseña FROM administradores WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($db_email, $db_password);
            $stmt->fetch();

            // Verificar la contraseña encriptada
            if (password_verify($password, $db_password)) {
                $_SESSION["admin_logged_in"] = true;
                $_SESSION["admin_email"] = $db_email;

                header("Location: IndexAdmin.php");
                exit();
            } else {
                $error = "Contraseña incorrecta.";
            }
        } else {
            $error = "El usuario no existe.";
        }

        $stmt->close();
    } else {
        $error = "Por favor complete todos los campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión - Admin</title>
</head>
<body>
    <h2>Login Administrador</h2>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="POST">
        <label for="email">Correo electrónico:</label><br>
        <input type="email" name="email" id="email" required><br><br>

        <label for="password">Contraseña:</label><br>
        <input type="password" name="password" id="password" required><br><br>

        <button type="submit">Iniciar sesión</button>
    </form>
</body>
</html>
