<?php
session_start();

$register_error = '';
$register_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    require_once '../includes/database.php';
    
    $clave_unica = trim($_POST['clave_unica'] ?? '');
    $nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validar clave única
    if ($clave_unica !== 'Ado') {
        $register_error = 'Clave única incorrecta. No tienes autorización para registrar administradores.';
    }
    // Validar campos obligatorios
    elseif (empty($nombre_usuario) || empty($nombre_completo) || empty($email) || empty($password)) {
        $register_error = 'Por favor, complete todos los campos obligatorios.';
    }
    // Validar confirmación de contraseña
    elseif ($password !== $confirm_password) {
        $register_error = 'Las contraseñas no coinciden.';
    }
    // Validar longitud de contraseña
    elseif (strlen($password) < 8) {
        $register_error = 'La contraseña debe tener al menos 8 caracteres.';
    }
    // Validar formato de email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_error = 'El formato del email no es válido.';
    }
    else {
        try {
            $db = new Database();
            $conexion = $db->getConnection();
            
            // Verificar si el usuario ya existe
            $stmt_check = $conexion->prepare("SELECT id_admin FROM administradores WHERE nombre_usuario = ? OR email = ?");
            $stmt_check->bind_param("ss", $nombre_usuario, $email);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            
            if ($result_check->num_rows > 0) {
                $register_error = 'El nombre de usuario o email ya están registrados.';
            } else {
                // Hashear la contraseña
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Obtener ID del admin creador (si está logueado)
                $creado_por = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;
                
                // Insertar nuevo administrador
                $stmt_insert = $conexion->prepare("INSERT INTO administradores (nombre_usuario, nombre_completo, email, password_hash, creado_por) VALUES (?, ?, ?, ?, ?)");
                $stmt_insert->bind_param("ssssi", $nombre_usuario, $nombre_completo, $email, $password_hash, $creado_por);
                
                if ($stmt_insert->execute()) {
                    $register_success = 'Administrador registrado exitosamente. Ya puede iniciar sesión.';
                    // Limpiar campos
                    $_POST = [];
                } else {
                    $register_error = 'Error al registrar el administrador. Intente nuevamente.';
                }
            }
        } catch (Exception $e) {
            $register_error = 'Error de conexión. Intente nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Administrador - La Casona Kawai</title>
    
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
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #e74c3c, #c0392b, #a93226);
        }

        .register-header {
            margin-bottom: 30px;
        }

        .register-logo {
            font-size: 3.5rem;
            margin-bottom: 15px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .register-title {
            font-size: 2.5rem;
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .register-subtitle {
            color: #7f8c8d;
            font-size: 1.4rem;
            font-weight: 500;
        }

        .register-form {
            margin-top: 30px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.4rem;
        }

        .required {
            color: #e74c3c;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1.4rem;
            font-family: 'EB Garamond', serif;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-input:focus {
            outline: none;
            border-color: #e74c3c;
            background: white;
            box-shadow: 0 0 0 4px rgba(231, 76, 60, 0.1);
            transform: translateY(-1px);
        }

        .form-input.clave-unica {
            background: #fff3cd;
            border-color: #ffc107;
            text-align: center;
            font-weight: 700;
            letter-spacing: 2px;
        }

        .form-input.clave-unica:focus {
            border-color: #e74c3c;
            background: #fff;
        }

        .register-button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.6rem;
            font-weight: 700;
            font-family: 'EB Garamond', serif;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
            position: relative;
            overflow: hidden;
        }

        .register-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .register-button:hover::before {
            left: 100%;
        }

        .register-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(231, 76, 60, 0.3);
        }

        .register-button:active {
            transform: translateY(0);
        }

        .register-error {
            background: #fde8e8;
            color: #e74c3c;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #e74c3c;
            font-weight: 600;
            animation: shake 0.5s ease-in-out;
        }

        .register-success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #28a745;
            font-weight: 600;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .login-link {
            color: #e74c3c;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.4rem;
            transition: all 0.3s ease;
        }

        .login-link:hover {
            color: #c0392b;
            text-decoration: underline;
        }

        .security-warning {
            margin-top: 25px;
            padding: 15px;
            background: #fff3cd;
            border-radius: 10px;
            border-left: 4px solid #ffc107;
        }

        .security-warning h4 {
            color: #856404;
            margin-bottom: 8px;
            font-size: 1.6rem;
        }

        .security-warning p {
            color: #856404;
            font-size: 1.3rem;
            line-height: 1.4;
        }

        .clave-info {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-family: 'Courier New', monospace;
            font-size: 1.3rem;
            color: #2c3e50;
            font-weight: 600;
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 25px 15px;
            }
            
            .register-title {
                font-size: 2.2rem;
            }
            
            .register-subtitle {
                font-size: 1.3rem;
            }
        }
    </style>
</head>

<body>
    <div class="register-container">
        <div class="register-header">
            <div class="register-logo">🔐</div>
            <h1 class="register-title">Registro de Administrador</h1>
            <p class="register-subtitle">La Casona Kawai</p>
        </div>

        <?php if (!empty($register_error)): ?>
            <div class="register-error">
                ⚠️ <?php echo htmlspecialchars($register_error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($register_success)): ?>
            <div class="register-success">
                ✅ <?php echo htmlspecialchars($register_success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="register-form">
            <input type="hidden" name="action" value="register">
            
            <div class="form-group">
                <label for="clave_unica" class="form-label">Clave Única de Autorización <span class="required">*</span></label>
                <input type="password" id="clave_unica" name="clave_unica" class="form-input clave-unica" placeholder="Ingrese la clave única" required>
                <div class="clave-info">
                    Solo personal autorizado puede registrar nuevos administradores
                </div>
            </div>
            
            <div class="form-group">
                <label for="nombre_usuario" class="form-label">Nombre de Usuario <span class="required">*</span></label>
                <input type="text" id="nombre_usuario" name="nombre_usuario" class="form-input" value="<?php echo htmlspecialchars($_POST['nombre_usuario'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="nombre_completo" class="form-label">Nombre Completo <span class="required">*</span></label>
                <input type="text" id="nombre_completo" name="nombre_completo" class="form-input" value="<?php echo htmlspecialchars($_POST['nombre_completo'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Contraseña <span class="required">*</span></label>
                <input type="password" id="password" name="password" class="form-input" minlength="8" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirmar Contraseña <span class="required">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-input" minlength="8" required>
            </div>
            
            <button type="submit" class="register-button">Registrar Administrador</button>
        </form>

        <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
            <a href="Login.php" class="login-link">¿Ya tienes cuenta? Iniciar Sesión</a>
        </div>

        <div class="security-warning">
            <h4>⚠️ Acceso Restringido</h4>
            <p>Este formulario requiere una clave única de autorización. Solo el personal autorizado puede crear nuevas cuentas de administrador para mantener la seguridad del sistema.</p>
        </div>
    </div>

    <script>
        // Validación de contraseñas en tiempo real
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            function validatePasswords() {
                if (password.value && confirmPassword.value) {
                    if (password.value === confirmPassword.value) {
                        confirmPassword.style.borderColor = '#28a745';
                        confirmPassword.style.background = '#d4edda';
                    } else {
                        confirmPassword.style.borderColor = '#e74c3c';
                        confirmPassword.style.background = '#fde8e8';
                    }
                }
            }
            
            password.addEventListener('input', validatePasswords);
            confirmPassword.addEventListener('input', validatePasswords);
            
            // Focus automático en clave única
            const claveUnica = document.getElementById('clave_unica');
            setTimeout(() => claveUnica.focus(), 500);
        });
    </script>
</body>
</html>
