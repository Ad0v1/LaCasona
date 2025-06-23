<?php
session_start();

// Si ya está logueado, redirigir al panel
if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
    header('Location: AdminPanel.php');
    exit();
}

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    require_once '../includes/database.php';
    
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($usuario) || empty($password)) {
        $login_error = 'Por favor, complete todos los campos.';
    } else {
        try {
            $db = new Database();
            $conexion = $db->getConnection();
            
            // Buscar administrador por nombre de usuario
            $stmt = $conexion->prepare("SELECT id_admin, nombre_usuario, nombre_completo, email, password_hash, activo FROM administradores WHERE nombre_usuario = ? AND activo = 1");
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();
            
            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Login exitoso
                $_SESSION['admin_authenticated'] = true;
                $_SESSION['admin_id'] = $admin['id_admin'];
                $_SESSION['admin_usuario'] = $admin['nombre_usuario'];
                $_SESSION['admin_nombre'] = $admin['nombre_completo'];
                $_SESSION['admin_email'] = $admin['email'];
                
                // Actualizar fecha de último acceso
                $stmt_update = $conexion->prepare("UPDATE administradores SET fecha_ultimo_acceso = NOW() WHERE id_admin = ?");
                $stmt_update->bind_param("i", $admin['id_admin']);
                $stmt_update->execute();
                
                header('Location: AdminPanel.php');
                exit();
            } else {
                $login_error = 'Usuario o contraseña incorrectos.';
            }
        } catch (Exception $e) {
            $login_error = 'Error de conexión. Intente nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrador - La Casona Kawai</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'EB Garamond', serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 50px;
            width: 100%;
            max-width: 450px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #ff9800, #f44336, #e91e63, #9c27b0);
        }

        .login-header {
            margin-bottom: 40px;
        }

        .login-logo {
            font-size: 4rem;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        .login-title {
            font-size: 2.8rem;
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .login-subtitle {
            color: #7f8c8d;
            font-size: 1.6rem;
            font-weight: 500;
        }

        .login-form {
            margin-top: 40px;
        }

        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.6rem;
        }

        .form-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1.6rem;
            font-family: 'EB Garamond', serif;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .login-button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.8rem;
            font-weight: 700;
            font-family: 'EB Garamond', serif;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
            position: relative;
            overflow: hidden;
        }

        .login-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .login-button:hover::before {
            left: 100%;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .login-error {
            background: #fde8e8;
            color: #e74c3c;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
            font-weight: 600;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .login-actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .register-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .register-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .security-info {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 4px solid #3498db;
        }

        .security-info h4 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 1.8rem;
        }

        .security-info p {
            color: #7f8c8d;
            font-size: 1.4rem;
            line-height: 1.5;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
            
            .login-title {
                font-size: 2.4rem;
            }
            
            .login-subtitle {
                font-size: 1.4rem;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">🏛️</div>
            <h1 class="login-title">Panel de Administración</h1>
            <p class="login-subtitle">La Casona Kawai</p>
        </div>

        <?php if (!empty($login_error)): ?>
            <div class="login-error">
                ⚠️ <?php echo htmlspecialchars($login_error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <input type="hidden" name="action" value="login">
            
            <div class="form-group">
                <label for="usuario" class="form-label">Usuario</label>
                <input type="text" id="usuario" name="usuario" class="form-input" value="<?php echo htmlspecialchars($_POST['usuario'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>
            
            <button type="submit" class="login-button">Iniciar Sesión</button>
        </form>

        <div class="login-actions">
            <a href="RegisterAdmin.php" class="register-link">¿Necesitas registrar un nuevo administrador?</a>
        </div>

        <div class="security-info">
            <h4>🔐 Acceso Seguro</h4>
            <p>Este panel utiliza autenticación con contraseñas encriptadas y sesiones seguras para proteger la información del restaurante.</p>
        </div>
    </div>

    <script>
        // Efecto de focus automático
        document.addEventListener('DOMContentLoaded', function() {
            const usuarioInput = document.getElementById('usuario');
            if (usuarioInput && !usuarioInput.value) {
                setTimeout(() => usuarioInput.focus(), 500);
            }
        });

        // Efecto de typing en el placeholder
        const inputs = document.querySelectorAll('.form-input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>
