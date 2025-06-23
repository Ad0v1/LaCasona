<?php
// Archivo de prueba para verificar autenticación de administradores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔐 Prueba de Autenticación - Administradores</h1>";
echo "<hr>";

// Verificar estructura de archivos
echo "<h2>📁 Verificación de Archivos</h2>";
$files_to_check = [
    '../includes/database.php',
    '../controllers/AdminControllers.php',
    '../controllers/ReservaControllers.php',
    'Login.php',
    'AdminPanel.php',
    'RegisterAdmin.php'
];

foreach ($files_to_check as $file) {
    $exists = file_exists($file);
    $status = $exists ? '✅ Existe' : '❌ No existe';
    echo "<p><strong>{$file}:</strong> {$status}</p>";
}

// Probar conexión a base de datos
echo "<h2>🔌 Prueba de Conexión</h2>";
try {
    require_once '../includes/database.php';
    echo "<p>✅ Archivo database.php cargado correctamente</p>";
    
    $db = new Database();
    echo "<p>✅ Instancia de Database creada</p>";
    
    $conexion = $db->getConnection();
    echo "<p>✅ Conexión obtenida</p>";
    
    // Verificar tabla de administradores
    echo "<h3>👥 Verificación de Administradores</h3>";
    $result = $conexion->query("SELECT COUNT(*) as total FROM administradores");
    if ($result) {
        $count = $result->fetch_assoc()['total'];
        echo "<p>✅ Total de administradores: {$count}</p>";
    }
    
    // Verificar administrador de prueba
    $stmt = $conexion->prepare("SELECT nombre_usuario, nombre_completo, email, activo FROM administradores WHERE nombre_usuario = ?");
    $usuario_prueba = 'admin_kawai';
    $stmt->bind_param("s", $usuario_prueba);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        echo "<p>✅ Administrador de prueba encontrado:</p>";
        echo "<ul>";
        echo "<li><strong>Usuario:</strong> " . htmlspecialchars($admin['nombre_usuario']) . "</li>";
        echo "<li><strong>Nombre:</strong> " . htmlspecialchars($admin['nombre_completo']) . "</li>";
        echo "<li><strong>Email:</strong> " . htmlspecialchars($admin['email']) . "</li>";
        echo "<li><strong>Activo:</strong> " . ($admin['activo'] ? 'Sí' : 'No') . "</li>";
        echo "</ul>";
        
        // Probar verificación de contraseña
        echo "<h3>🔑 Prueba de Contraseña</h3>";
        $password_prueba = 'kawai2024!';
        
        $stmt_pass = $conexion->prepare("SELECT password_hash FROM administradores WHERE nombre_usuario = ?");
        $stmt_pass->bind_param("s", $usuario_prueba);
        $stmt_pass->execute();
        $result_pass = $stmt_pass->get_result();
        
        if ($result_pass->num_rows > 0) {
            $hash_data = $result_pass->fetch_assoc();
            $password_hash = $hash_data['password_hash'];
            
            echo "<p><strong>Hash almacenado:</strong> " . substr($password_hash, 0, 20) . "...</p>";
            
            if (password_verify($password_prueba, $password_hash)) {
                echo "<p>✅ Contraseña verificada correctamente</p>";
                echo "<p><strong>Credenciales de prueba:</strong></p>";
                echo "<ul>";
                echo "<li><strong>Usuario:</strong> admin_kawai</li>";
                echo "<li><strong>Contraseña:</strong> kawai2024!</li>";
                echo "</ul>";
            } else {
                echo "<p>❌ Error: La contraseña no coincide</p>";
                echo "<p>Intentando regenerar hash...</p>";
                
                // Regenerar hash
                $new_hash = password_hash($password_prueba, PASSWORD_DEFAULT);
                $stmt_update = $conexion->prepare("UPDATE administradores SET password_hash = ? WHERE nombre_usuario = ?");
                $stmt_update->bind_param("ss", $new_hash, $usuario_prueba);
                
                if ($stmt_update->execute()) {
                    echo "<p>✅ Hash regenerado correctamente</p>";
                    echo "<p>Ahora puedes usar las credenciales:</p>";
                    echo "<ul>";
                    echo "<li><strong>Usuario:</strong> admin_kawai</li>";
                    echo "<li><strong>Contraseña:</strong> kawai2024!</li>";
                    echo "</ul>";
                } else {
                    echo "<p>❌ Error al regenerar hash</p>";
                }
            }
        }
    } else {
        echo "<p>❌ Administrador de prueba no encontrado</p>";
        echo "<p>Creando administrador de prueba...</p>";
        
        // Crear administrador de prueba
        $usuario = 'admin_kawai';
        $nombre_completo = 'Administrador Principal';
        $email = 'admin@lacasona.com';
        $password = 'kawai2024!';
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt_create = $conexion->prepare("INSERT INTO administradores (nombre_usuario, nombre_completo, email, password_hash) VALUES (?, ?, ?, ?)");
        $stmt_create->bind_param("ssss", $usuario, $nombre_completo, $email, $password_hash);
        
        if ($stmt_create->execute()) {
            echo "<p>✅ Administrador de prueba creado exitosamente</p>";
            echo "<p><strong>Credenciales:</strong></p>";
            echo "<ul>";
            echo "<li><strong>Usuario:</strong> admin_kawai</li>";
            echo "<li><strong>Contraseña:</strong> kawai2024!</li>";
            echo "</ul>";
        } else {
            echo "<p>❌ Error al crear administrador de prueba</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<h2>🚀 Acciones</h2>";
echo "<p><a href='Login.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Ir al Login</a>";
echo "<a href='../test-connection.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Prueba General</a></p>";

echo "<hr>";
echo "<p><small>Generado el " . date('Y-m-d H:i:s') . "</small></p>";
?>
