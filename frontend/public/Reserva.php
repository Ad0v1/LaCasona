<?php
session_start();

// LIMPIAR SESIÓN COMPLETAMENTE AL CARGAR LA PÁGINA (sin POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Limpiar TODA la sesión al cargar la página
    session_destroy();
    session_start();
    
    // Reiniciar variables a estado inicial - SIEMPRE
    $current_activity = 'none';
    $solicitud_step = 1;
    $pago_step = 1;
    $solicitud_data = [];
    $pago_data = [];
    $errors = [];
    $success_message = '';
    $reservation_code = '';
    
    // Asegurar que no se muestren contenedores
    $_SESSION['current_activity'] = 'none';
} else {
    // Solo en POST, procesar las acciones
    require_once 'includes/database.php';
    require_once 'controllers/ReservaControllers.php';

    $db = new Database();
    $conexion = $db->getConnection();

    // Estados de las actividades
    $current_activity = isset($_SESSION['current_activity']) ? $_SESSION['current_activity'] : 'none';
    $solicitud_step = isset($_SESSION['solicitud_step']) ? $_SESSION['solicitud_step'] : 1;
    $pago_step = isset($_SESSION['pago_step']) ? $_SESSION['pago_step'] : 1;

    // Datos persistentes
    $solicitud_data = isset($_SESSION['solicitud_data']) ? $_SESSION['solicitud_data'] : [];
    $pago_data = isset($_SESSION['pago_data']) ? $_SESSION['pago_data'] : [];

    $errors = [];
    $success_message = '';
    $reservation_code = '';
}

// Solo cargar datos si estamos en POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/database.php';
    require_once 'controllers/ReservaControllers.php';

    $db = new Database();
    $conexion = $db->getConnection();

    // Precios de menú
    $menu_prices = [
        'desayuno' => 9.00,
        'almuerzo' => 14.50,
        'cena' => 16.50,
    ];

    // Datos de la carta
    $menu_items = [
        'desayuno' => [
            'bebidas' => [
                'Café con leche', 'Quaker con manzana', 'Quaker con leche', 'Chocolate caliente',
                'Quinua con piña', 'Café', 'Papaya', 'Surtido'
            ],
            'panes' => [
                'Tortilla de verduras', 'Huevo revuelto', 'Huevo frito', 'Camote',
                'Jamonada', 'Pollo', 'Mantequilla', 'Mermelada'
            ]
        ],
        'almuerzo' => [
            'entradas' => [
                'Papa a la Huancaína', 'Ocopa', 'Ensalada de Fideo', 'Crema de Rocoto', 'Sopa de Casa'
            ],
            'platos_fondo' => [
                'Arroz con Pollo', 'Pollo al Sillao', 'Asado con Puré', 'Estofado de Pollo',
                'Ají de Gallina', 'Arroz Chaufa'
            ],
            'postres' => [
                'Plátano', 'Manzana', 'Sandía'
            ],
            'bebidas' => [
                'Chicha Morada', 'Limonada', 'Agua Mineral', 'Gaseosa'
            ]
        ],
        'cena' => [
            'platos_principales' => [
                'Hamburguesa Completa', 'Pan con Hamburguesa', 'Tallarines Rojos',
                'Tallarín Saltado', 'Lomo Saltado', 'Pollo Broaster'
            ],
            'postres' => [
                'Pudín de Chocolate', 'Flan', 'Mazamorra Morada', 'Arroz con Leche', 'Torta de Chocolate'
            ],
            'bebidas' => [
                'Vino Tinto', 'Agua Mineral', 'Gaseosa'
            ]
        ]
    ];

    // Obtener horarios disponibles
    $horarios_disponibles = ReservaControllers::obtenerHorariosDisponibles();

    // Obtener información de pago
    $info_pago = ReservaControllers::obtenerInformacionPago();

    // Bancos disponibles
    $bancos_disponibles = ['BCP', 'BBVA', 'Interbank', 'Scotiabank'];

    $action = $_POST['action'] ?? '';

    // ===== ACCIONES SIN VALIDACIÓN - PROCESAMIENTO INMEDIATO =====
    
    // Cambio/Toggle de actividad - SIN VALIDACIÓN
    if ($action === 'show_solicitud') {
        // Si ya estamos en solicitud, solo toggle (plegar/desplegar)
        if ($current_activity === 'solicitud') {
            $_SESSION['current_activity'] = 'none';
            $current_activity = 'none';
        } else {
            // Cambio desde otra actividad o desde ninguna - limpiar todo
            if ($current_activity === 'pago') {
                // Limpiar completamente el flujo de pago
                unset($_SESSION['pago_data']);
                $_SESSION['pago_step'] = 1;
            }
        
            // Activar solicitud
            $_SESSION['current_activity'] = 'solicitud';
            $current_activity = 'solicitud';
        
            // Asegurar que empiece en paso 1 si no hay datos previos
            if (!isset($_SESSION['solicitud_data']) || empty($_SESSION['solicitud_data'])) {
                $_SESSION['solicitud_step'] = 1;
                $solicitud_step = 1;
            }
        }
    
        // Limpiar errores al cambiar de actividad
        $errors = [];
        
        // NO REDIRIGIR - Mantener en la misma página
    }

    elseif ($action === 'show_pago') {
        // Si ya estamos en pago, solo toggle (plegar/desplegar)
        if ($current_activity === 'pago') {
            $_SESSION['current_activity'] = 'none';
            $current_activity = 'none';
        } else {
            // Cambio desde otra actividad o desde ninguna - limpiar todo
            if ($current_activity === 'solicitud') {
                // Limpiar completamente el flujo de solicitud
                unset($_SESSION['solicitud_data']);
                $_SESSION['solicitud_step'] = 1;
            }
        
            // Activar pago
            $_SESSION['current_activity'] = 'pago';
            $current_activity = 'pago';
        
            // Asegurar que empiece en paso 1 si no hay datos previos
            if (!isset($_SESSION['pago_data']) || empty($_SESSION['pago_data'])) {
                $_SESSION['pago_step'] = 1;
                $pago_step = 1;
            }
        }
    
        // Limpiar errores al cambiar de actividad
        $errors = [];
        
        // NO REDIRIGIR - Mantener en la misma página
    }

    // Navegación hacia atrás - SIN VALIDACIÓN
    elseif ($action === 'solicitud_back') {
        $step = (int)($_POST['step'] ?? 1);
        $new_step = max(1, $step - 1);
        $_SESSION['solicitud_step'] = $new_step;
        $solicitud_step = $new_step;
        
        // Asegurar que estamos en la actividad correcta
        $_SESSION['current_activity'] = 'solicitud';
        $current_activity = 'solicitud';
        
        // IMPORTANTE: Limpiar TODOS los errores al retroceder
        $errors = [];
        
        // NO REDIRIGIR - Mantener en la misma página
    }

    elseif ($action === 'pago_back') {
        $step = (int)($_POST['step'] ?? 1);
        $new_step = max(1, $step - 1);
        $_SESSION['pago_step'] = $new_step;
        $pago_step = $new_step;
        
        // Asegurar que estamos en la actividad correcta
        $_SESSION['current_activity'] = 'pago';
        $current_activity = 'pago';
        
        // Si volvemos al paso 1, limpiar datos de pago para permitir nuevo código
        if ($new_step === 1) {
            unset($_SESSION['pago_data']);
            $pago_data = [];
        }
        
        // IMPORTANTE: Limpiar TODOS los errores al retroceder
        $errors = [];
        
        // NO REDIRIGIR - Mantener en la misma página
    }

    // Reiniciar proceso - SIN VALIDACIÓN
    elseif ($action === 'reset') {
        session_destroy();
        header('Location: Reserva.php');
        exit();
    }

    // ===== ACCIONES CON VALIDACIÓN - SOLO AVANCE =====
    
    // SOLICITUD DE RESERVA
    elseif ($action === 'solicitud_next_step1') {
        // VALIDACIÓN ESTRICTA DEL PASO 1
        $menu_type = $_POST['menu_type'] ?? '';
        $fecha_reserva = $_POST['fecha_reserva'] ?? '';
        $hora_reserva = $_POST['hora_reserva'] ?? '';
        $cantidad_personas = (int)($_POST['cantidad_personas'] ?? 0);

        // Validaciones obligatorias
        if (empty($menu_type)) {
            $errors[] = 'Por favor, selecciona un tipo de menú.';
        }
        if (empty($fecha_reserva)) {
            $errors[] = 'La fecha de reserva es obligatoria.';
        } else {
            $fecha_validation = ReservaControllers::validarFechaReserva($fecha_reserva);
            if (!$fecha_validation['valid']) {
                $errors[] = $fecha_validation['message'];
            }
        }
        if (empty($hora_reserva)) {
            $errors[] = 'La hora de reserva es obligatoria.';
        } elseif (!array_key_exists($hora_reserva, $horarios_disponibles)) {
            $errors[] = 'La hora seleccionada no está disponible.';
        }
        if ($cantidad_personas <= 0) {
            $errors[] = 'La cantidad de personas debe ser mayor a 0.';
        } elseif ($cantidad_personas > 250) {
            $errors[] = 'La cantidad máxima de personas por reserva es 250.';
        }

        // Validar opciones de menú según el tipo seleccionado
        if (!empty($menu_type)) {
            switch ($menu_type) {
                case 'desayuno':
                    $bebida = $_POST['desayuno_bebida'] ?? '';
                    $pan = $_POST['desayuno_pan'] ?? '';
                    if (empty($bebida)) {
                        $errors[] = 'Debes seleccionar una bebida para el desayuno.';
                    }
                    if (empty($pan)) {
                        $errors[] = 'Debes seleccionar un pan para el desayuno.';
                    }
                    break;
                case 'almuerzo':
                    $entrada = $_POST['almuerzo_entrada'] ?? '';
                    $fondo = $_POST['almuerzo_fondo'] ?? '';
                    $postre = $_POST['almuerzo_postre'] ?? '';
                    $bebida = $_POST['almuerzo_bebida'] ?? '';
                    if (empty($entrada)) {
                        $errors[] = 'Debes seleccionar una entrada para el almuerzo.';
                    }
                    if (empty($fondo)) {
                        $errors[] = 'Debes seleccionar un plato de fondo para el almuerzo.';
                    }
                    if (empty($postre)) {
                        $errors[] = 'Debes seleccionar un postre para el almuerzo.';
                    }
                    if (empty($bebida)) {
                        $errors[] = 'Debes seleccionar una bebida para el almuerzo.';
                    }
                    break;
                case 'cena':
                    $plato = $_POST['cena_plato'] ?? '';
                    $postre = $_POST['cena_postre'] ?? '';
                    $bebida = $_POST['cena_bebida'] ?? '';
                    if (empty($plato)) {
                        $errors[] = 'Debes seleccionar un plato principal para la cena.';
                    }
                    if (empty($postre)) {
                        $errors[] = 'Debes seleccionar un postre para la cena.';
                    }
                    if (empty($bebida)) {
                        $errors[] = 'Debes seleccionar una bebida para la cena.';
                    }
                    break;
            }
        }

        // Verificar disponibilidad de horario solo si no hay errores previos
        if (empty($errors)) {
            $disponibilidad = ReservaControllers::verificarDisponibilidadHorario($conexion, $fecha_reserva, $hora_reserva, $cantidad_personas);
            if (!$disponibilidad['disponible']) {
                $errors[] = $disponibilidad['message'];
            }
        }

        // Solo avanzar si NO hay errores
        if (empty($errors)) {
            $menu_options = [];
            switch ($menu_type) {
                case 'desayuno':
                    $menu_options['desayunoBebida'] = $_POST['desayuno_bebida'] ?? '';
                    $menu_options['desayunoPan'] = $_POST['desayuno_pan'] ?? '';
                    break;
                case 'almuerzo':
                    $menu_options['almuerzoEntrada'] = $_POST['almuerzo_entrada'] ?? '';
                    $menu_options['almuerzoFondo'] = $_POST['almuerzo_fondo'] ?? '';
                    $menu_options['almuerzoPostre'] = $_POST['almuerzo_postre'] ?? '';
                    $menu_options['almuerzoBebida'] = $_POST['almuerzo_bebida'] ?? '';
                    break;
                case 'cena':
                    $menu_options['cenaPlato'] = $_POST['cena_plato'] ?? '';
                    $menu_options['cenaPostre'] = $_POST['cena_postre'] ?? '';
                    $menu_options['cenaBebida'] = $_POST['cena_bebida'] ?? '';
                    break;
            }

            $solicitud_data['menu_type'] = $menu_type;
            $solicitud_data['menu_options'] = $menu_options;
            $solicitud_data['fecha_reserva'] = $fecha_reserva;
            $solicitud_data['hora_reserva'] = $hora_reserva;
            $solicitud_data['cantidad_personas'] = $cantidad_personas;

            $_SESSION['solicitud_data'] = $solicitud_data;
            $_SESSION['solicitud_step'] = 2;
            $solicitud_step = 2;
        }
    }

    elseif ($action === 'solicitud_next_step2') {
        // VALIDACIÓN ESTRICTA DEL PASO 2
        $nombre = trim($_POST['nombre'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($nombre)) {
            $errors[] = 'El nombre es obligatorio.';
        }
        if (empty($apellidos)) {
            $errors[] = 'Los apellidos son obligatorios.';
        }
        if (empty($telefono)) {
            $errors[] = 'El teléfono es obligatorio.';
        } elseif (!preg_match('/^9\d{8}$/', $telefono)) {
            $errors[] = 'El teléfono debe empezar con 9 y tener exactamente 9 dígitos.';
        }
        if (empty($email)) {
            $errors[] = 'El correo electrónico es obligatorio.';
        } else {
            $email_validation = ReservaControllers::validarEmail($email);
            if (!$email_validation['valid']) {
                $errors[] = $email_validation['message'];
            }
        }

        // Solo avanzar si NO hay errores
        if (empty($errors)) {
            $solicitud_data['nombre'] = $nombre;
            $solicitud_data['apellidos'] = $apellidos;
            $solicitud_data['telefono'] = $telefono;
            $solicitud_data['email'] = $email;

            // Calcular precios
            $price_per_person = $menu_prices[$solicitud_data['menu_type']] ?? 0;
            $total_price = $price_per_person * $solicitud_data['cantidad_personas'];
            $advance_payment = $total_price * 0.50;

            $solicitud_data['total_price'] = $total_price;
            $solicitud_data['advance_payment'] = $advance_payment;

            $_SESSION['solicitud_data'] = $solicitud_data;
            $_SESSION['solicitud_step'] = 3;
            $solicitud_step = 3;
        }
    }

    elseif ($action === 'solicitud_confirm') {
        // Paso 3: Confirmar reserva
        try {
            $result = ReservaControllers::crearReservaCompleta(
                $conexion,
                $solicitud_data['nombre'] . ' ' . $solicitud_data['apellidos'],
                $solicitud_data['telefono'],
                $solicitud_data['email'],
                $solicitud_data['fecha_reserva'],
                $solicitud_data['hora_reserva'],
                $solicitud_data['cantidad_personas'],
                $solicitud_data['menu_type'],
                $solicitud_data['total_price'],
                json_encode($solicitud_data['menu_options']),
                $solicitud_data['menu_options']
            );

            if ($result['success']) {
                $success_message = 'Tu solicitud de reserva ha sido registrada correctamente';
                $reservation_code = $result['codigo_reserva'];
                
                // Limpiar datos de solicitud pero mantener el código para mostrarlo
                unset($_SESSION['solicitud_data']);
                $_SESSION['solicitud_step'] = 1;
                $_SESSION['reservation_code'] = $reservation_code;
                $_SESSION['success_message'] = $success_message;
                $_SESSION['show_reservation_code'] = true; // Nueva bandera
                
                // NO redirigir, mantener en la misma página para mostrar el código
                $solicitud_step = 1; // Reset step
            } else {
                $errors[] = $result['message'];
            }
        } catch (Exception $e) {
            $errors[] = 'Error al procesar la reserva: ' . $e->getMessage();
        }
    }

    // REGISTRO DE PAGO
    elseif ($action === 'pago_next_step1') {
        // VALIDACIÓN ESTRICTA DEL PASO 1 DE PAGO
        $codigo_reserva = trim($_POST['codigo_reserva'] ?? '');

        if (empty($codigo_reserva)) {
            $errors[] = 'El código de reserva es obligatorio.';
        } else {
            // Verificar que la reserva existe y está pendiente de pago
            $stmt = $conexion->prepare("SELECT * FROM reservas WHERE codigo_reserva = ? AND estado = 'Solicitada'");
            $stmt->bind_param("s", $codigo_reserva);
            $stmt->execute();
            $result = $stmt->get_result();
            $reserva = $result->fetch_assoc();

            if (!$reserva) {
                $errors[] = 'No se encontró una reserva pendiente de pago con ese código.';
            } else {
                $pago_data['codigo_reserva'] = $codigo_reserva;
                $pago_data['reserva_info'] = $reserva;
                $_SESSION['pago_data'] = $pago_data;
                $_SESSION['pago_step'] = 2;
                $pago_step = 2;
                // NO REDIRIGIR - mantener en la misma página
            }
        }
    }

    elseif ($action === 'pago_next_step2') {
        // VALIDACIÓN ESTRICTA DEL PASO 2 DE PAGO
        $metodo_pago = $_POST['metodo_pago'] ?? '';
        $nombre_titular = trim($_POST['nombre_titular'] ?? '');
        $numero_operacion = trim($_POST['numero_operacion'] ?? '');
        $codigo_seguridad = trim($_POST['codigo_seguridad'] ?? '');
        $banco = trim($_POST['banco'] ?? '');

        if (empty($metodo_pago)) {
            $errors[] = 'Por favor, selecciona un método de pago.';
        }
        if (empty($nombre_titular)) {
            $errors[] = 'El nombre del titular es obligatorio.';
        }
        if (empty($numero_operacion)) {
            $errors[] = 'El número de operación es obligatorio.';
        } elseif ($metodo_pago === 'yape' && !preg_match('/^\d{8}$/', $numero_operacion)) {
            $errors[] = 'El número de operación de Yape debe tener exactamente 8 dígitos.';
        } elseif ($metodo_pago === 'transferencia' && !preg_match('/^\d{8,11}$/', $numero_operacion)) {
            $errors[] = 'El número de operación de transferencia debe tener entre 8 y 11 dígitos.';
        }

        if ($metodo_pago === 'yape') {
            if (!empty($codigo_seguridad) && !preg_match('/^\d{3}$/', $codigo_seguridad)) {
                $errors[] = 'El código de seguridad debe tener exactamente 3 dígitos.';
            }
        } elseif ($metodo_pago === 'transferencia') {
            if (empty($banco)) {
                $errors[] = 'Por favor, selecciona el banco de origen.';
            }
        }

        // Solo avanzar si NO hay errores
        if (empty($errors)) {
            $pago_data['metodo_pago'] = $metodo_pago;
            $pago_data['nombre_titular'] = $nombre_titular;
            $pago_data['numero_operacion'] = $numero_operacion;
            $pago_data['codigo_seguridad'] = $codigo_seguridad;
            $pago_data['banco'] = $banco;

            $_SESSION['pago_data'] = $pago_data;
            $_SESSION['pago_step'] = 3;
            $pago_step = 3;
        }
    }

    elseif ($action === 'pago_confirm') {
        // VALIDACIÓN ESTRICTA DEL PASO 3 DE PAGO
        $tipo_comprobante = $_POST['tipo_comprobante'] ?? 'boleta';
        $ruc_factura = ($tipo_comprobante == 'factura') ? trim($_POST['ruc_factura'] ?? '') : null;
        $monto_pagado = (float)($_POST['monto_pagado'] ?? 0);

        if ($tipo_comprobante == 'factura' && empty($ruc_factura)) {
            $errors[] = 'El RUC es obligatorio para factura.';
        } elseif ($tipo_comprobante == 'factura' && !preg_match('/^\d{11}$/', $ruc_factura)) {
            $errors[] = 'El RUC debe tener exactamente 11 dígitos.';
        }
        if ($monto_pagado <= 0) {
            $errors[] = 'El monto pagado debe ser mayor a 0.';
        }

        // Solo procesar si NO hay errores
        if (empty($errors)) {
            $result_pago = ReservaControllers::registrarPago(
                $conexion,
                $pago_data['codigo_reserva'],
                $pago_data['metodo_pago'],
                $pago_data['nombre_titular'],
                $pago_data['numero_operacion'],
                $pago_data['codigo_seguridad'],
                $pago_data['banco'],
                $monto_pagado,
                $tipo_comprobante,
                $ruc_factura,
                null,
                null
            );

            if ($result_pago['success']) {
                $success_message = $result_pago['message'];
                
                // Limpiar datos de pago
                unset($_SESSION['pago_data']);
                $_SESSION['pago_step'] = 1;
                $_SESSION['success_message'] = $success_message;
                $_SESSION['pago_registered'] = true; // Nueva bandera para mostrar notificación especial
                
                header('Location: Reserva.php');
                exit();
            } else {
                $errors[] = $result_pago['message'];
            }
        }
    }

    // Mostrar mensajes de éxito si existen
    if (isset($_SESSION['success_message'])) {
        $success_message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
    }

    if (isset($_SESSION['reservation_code'])) {
        $reservation_code = $_SESSION['reservation_code'];
        unset($_SESSION['reservation_code']);
    }
} else {
    // Para GET requests, inicializar variables vacías
    $menu_prices = [
        'desayuno' => 9.00,
        'almuerzo' => 14.50,
        'cena' => 16.50,
    ];

    $menu_items = [
        'desayuno' => [
            'bebidas' => [
                'Café con leche', 'Quaker con manzana', 'Quaker con leche', 'Chocolate caliente',
                'Quinua con piña', 'Café', 'Papaya', 'Surtido'
            ],
            'panes' => [
                'Tortilla de verduras', 'Huevo revuelto', 'Huevo frito', 'Camote',
                'Jamonada', 'Pollo', 'Mantequilla', 'Mermelada'
            ]
        ],
        'almuerzo' => [
            'entradas' => [
                'Papa a la Huancaína', 'Ocopa', 'Ensalada de Fideo', 'Crema de Rocoto', 'Sopa de Casa'
            ],
            'platos_fondo' => [
                'Arroz con Pollo', 'Pollo al Sillao', 'Asado con Puré', 'Estofado de Pollo',
                'Ají de Gallina', 'Arroz Chaufa'
            ],
            'postres' => [
                'Plátano', 'Manzana', 'Sandía'
            ],
            'bebidas' => [
                'Chicha Morada', 'Limonada', 'Agua Mineral', 'Gaseosa'
            ]
        ],
        'cena' => [
            'platos_principales' => [
                'Hamburguesa Completa', 'Pan con Hamburguesa', 'Tallarines Rojos',
                'Tallarín Saltado', 'Lomo Saltado', 'Pollo Broaster'
            ],
            'postres' => [
                'Pudín de Chocolate', 'Flan', 'Mazamorra Morada', 'Arroz con Leche', 'Torta de Chocolate'
            ],
            'bebidas' => [
                'Vino Tinto', 'Agua Mineral', 'Gaseosa'
            ]
        ]
    ];

    require_once 'includes/database.php';
    require_once 'controllers/ReservaControllers.php';
    $horarios_disponibles = ReservaControllers::obtenerHorariosDisponibles();
    $bancos_disponibles = ['BCP', 'BBVA', 'Interbank', 'Scotiabank'];
    
    $current_activity = 'none';
    $solicitud_step = 1;
    $pago_step = 1;
    $solicitud_data = [];
    $pago_data = [];
    $errors = [];
    $success_message = '';
    $reservation_code = '';
}

?>
<!DOCTYPE html>
<html lang="es" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservas - La Casona Kawai</title>
    
    <!-- Meta tags adicionales -->
    <meta name="description" content="Sistema de reservas de La Casona Kawai. Reserva tu mesa y disfruta de nuestra deliciosa comida peruana en un ambiente acogedor.">
    <meta name="keywords" content="reservas, restaurante, comida peruana, La Casona Kawai, reservar mesa">
    <meta name="author" content="La Casona Kawai">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    
    <!-- Fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS del diseño principal -->
    <link rel="stylesheet" href="../assets/css/Reserva.css">
    
    <script>
        document.documentElement.classList.remove('no-js');
        document.documentElement.classList.add('js');
    </script>
</head>

<body id="top">
    <!-- Preloader para evitar flashes -->
    <div class="page-loader" id="pageLoader">
        <div class="loader-spinner"></div>
    </div>

    <!-- Header principal estilo Index.html -->
    <header class="main-header">
        <div class="header-container">
            <a href="Index.html" class="logo">
                <span class="logo-icon">🍽️</span>
                La Casona Kawai
            </a>
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item"><a href="Index.html">Inicio</a></li>
                    <li class="nav-item"><a href="Carta.html">Carta</a></li>
                    <li class="nav-item active"><a href="Reserva.php">Reservas</a></li>
                    <li class="nav-item"><a href="About.html">Nosotros</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Partículas flotantes decorativas -->
    <div class="floating-particles">
        <div class="particle" style="left: 10%; animation-delay: 0s;"></div>
        <div class="particle" style="left: 20%; animation-delay: 1s;"></div>
        <div class="particle" style="left: 30%; animation-delay: 2s;"></div>
        <div class="particle" style="left: 40%; animation-delay: 3s;"></div>
        <div class="particle" style="left: 50%; animation-delay: 4s;"></div>
        <div class="particle" style="left: 60%; animation-delay: 5s;"></div>
        <div class="particle" style="left: 70%; animation-delay: 0.5s;"></div>
        <div class="particle" style="left: 80%; animation-delay: 1.5s;"></div>
        <div class="particle" style="left: 90%; animation-delay: 2.5s;"></div>
    </div>

    <!-- Contenido principal -->
    <main class="reservation-container">
        <h1 class="page-title reservation-intro-title">Sistema de Reservas La Casona Kawai</h1>

        <!-- Mostrar mensajes de éxito -->
        <?php if (!empty($success_message)): ?>
            <div class="confirmation-message animate-scale-in animate">
                <div class="success-icon">✓</div>
                <h3><?php echo htmlspecialchars($success_message); ?></h3>
                
                <?php if (!empty($reservation_code) && isset($_SESSION['show_reservation_code'])): ?>
                    <div class="reservation-code-display">
                        <h4>Tu Código Único de Reserva</h4>
                        <div class="code-box">
                            <?php echo htmlspecialchars($reservation_code); ?>
                        </div>
                        <p class="code-instructions">
                            <strong>IMPORTANTE:</strong> Guarda este código para registrar tu pago en la sección "Registro de Pago"
                        </p>
                    </div>
                    <?php unset($_SESSION['show_reservation_code']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['pago_registered'])): ?>
                    <div class="payment-success-info">
                        <h4>Pago Registrado Exitosamente</h4>
                        <p>Tu registro de pago ha sido enviado al administrador para su verificación.</p>
                        <p>Recibirás una confirmación una vez que sea validado.</p>
                    </div>
                    <?php unset($_SESSION['pago_registered']); ?>
                <?php endif; ?>
                
                <div class="success-actions">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="reset">
                        <button type="submit" class="btn btn--primary animate-button animate">Hacer Nueva Reserva</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Selector de actividades principales -->
        <div class="main-activity-selector animate-fade-in animate">
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="show_solicitud">
                <button type="submit" class="main-activity-btn <?php echo ($current_activity === 'solicitud') ? 'active' : ''; ?>">
                    <div class="btn-icon">📝</div>
                    <div class="btn-title">SOLICITUD DE RESERVA</div>
                    <div class="btn-subtitle">Reserva tu mesa y selecciona tu menú</div>
                </button>
            </form>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="show_pago">
                <button type="submit" class="main-activity-btn <?php echo ($current_activity === 'pago') ? 'active' : ''; ?>">
                    <div class="btn-icon">💳</div>
                    <div class="btn-title">REGISTRO DE PAGO</div>
                    <div class="btn-subtitle">Registra el pago de tu reserva existente</div>
                </button>
            </form>
        </div>

        <!-- Mostrar errores -->
        <?php if (!empty($errors)): ?>
            <div class="alert-box alert-box--error animate-slide-up animate">
                <div class="alert-icon">⚠</div>
                <div class="alert-content">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- ACTIVIDAD 1: SOLICITUD DE RESERVA -->
        <div class="activity-container animate-fade-in" id="container-solicitud" style="display: none;">
            <div id="solicitud" class="activity active">
                <h2 class="activity-title">Solicitud de Reserva</h2>
                
                <!-- Indicador de pasos para solicitud -->
                <div class="steps-indicator step-animate animate">
                    <div class="step <?php echo ($solicitud_step >= 1) ? 'active' : ''; ?> <?php echo ($solicitud_step > 1) ? 'completed' : ''; ?>">
                        <div class="step-number">1</div>
                        <div class="step-label">Selección del Menú</div>
                    </div>
                    <div class="step-line <?php echo ($solicitud_step > 1) ? 'completed' : ''; ?>"></div>
                    <div class="step <?php echo ($solicitud_step >= 2) ? 'active' : ''; ?> <?php echo ($solicitud_step > 2) ? 'completed' : ''; ?>">
                        <div class="step-number">2</div>
                        <div class="step-label">Datos Personales</div>
                    </div>
                    <div class="step-line <?php echo ($solicitud_step > 2) ? 'completed' : ''; ?>"></div>
                    <div class="step <?php echo ($solicitud_step >= 3) ? 'active' : ''; ?>">
                        <div class="step-number">3</div>
                        <div class="step-label">Resumen y Confirmación</div>
                    </div>
                </div>

                <form method="POST" novalidate>
                    <!-- Paso 1: Selección del menú -->
                    <div class="form-step <?php echo ($solicitud_step == 1) ? 'active' : ''; ?>">
                        <h3 class="step-title">Paso 1: Selección del Menú</h3>
                        
                        <div class="info-box warning animate-fade-in-left animate">
                            <div class="info-icon">ℹ</div>
                            <div class="info-content">
                                <h4>Información Importante</h4>
                                <p><strong>Horarios disponibles:</strong></p>
                                <p><strong>Desayuno:</strong> 7:00 AM - 12:00 PM (intervalos de 30 min)</p>
                                <p><strong>Almuerzo:</strong> 12:00 PM - 6:00 PM (intervalos de 30 min)</p>
                                <p><strong>Cena:</strong> 6:00 PM - 12:00 AM (intervalos de 30 min)</p>
                                <p><strong>Capacidad máxima:</strong> 250 personas por hora</p>
                                <p><strong>Fecha mínima:</strong> 2 días después de hoy</p>
                                <p><strong>IMPORTANTE:</strong> Debes completar TODOS los campos para continuar al siguiente paso.</p>
                            </div>
                        </div>

                        <div class="form-group animate">
                            <label>Tipo de Menú <span class="required">*</span></label>
                            <div class="radio-group">
                                <div class="radio-option">
                                    <input type="radio" id="menu_desayuno" name="menu_type" value="desayuno" <?php echo (isset($solicitud_data['menu_type']) && $solicitud_data['menu_type'] == 'desayuno') ? 'checked' : ''; ?> required>
                                    <label for="menu_desayuno" class="radio-label">Desayuno (S/<?php echo number_format($menu_prices['desayuno'], 2); ?>)</label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" id="menu_almuerzo" name="menu_type" value="almuerzo" <?php echo (isset($solicitud_data['menu_type']) && $solicitud_data['menu_type'] == 'almuerzo') ? 'checked' : ''; ?> required>
                                    <label for="menu_almuerzo" class="radio-label">Almuerzo (S/<?php echo number_format($menu_prices['almuerzo'], 2); ?>)</label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" id="menu_cena" name="menu_type" value="cena" <?php echo (isset($solicitud_data['menu_type']) && $solicitud_data['menu_type'] == 'cena') ? 'checked' : ''; ?> required>
                                    <label for="menu_cena" class="radio-label">Cena (S/<?php echo number_format($menu_prices['cena'], 2); ?>)</label>
                                </div>
                            </div>
                        </div>

                        <!-- Opciones de menú dinámicas -->
                        <div id="desayuno_options" class="menu-options-group animate">
                            <h4>Opciones de Desayuno</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="desayuno_bebida">Bebida Principal <span class="required">*</span>:</label>
                                    <select id="desayuno_bebida" name="desayuno_bebida" class="form-control" required>
                                        <option value="">Selecciona una bebida</option>
                                        <?php foreach ($menu_items['desayuno']['bebidas'] as $item): ?>
                                            <option value="<?php echo htmlspecialchars($item); ?>" <?php echo (isset($solicitud_data['menu_options']['desayunoBebida']) && $solicitud_data['menu_options']['desayunoBebida'] == $item) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($item); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="desayuno_pan">Pan (con) <span class="required">*</span>:</label>
                                    <select id="desayuno_pan" name="desayuno_pan" class="form-control" required>
                                        <option value="">Selecciona un relleno</option>
                                        <?php foreach ($menu_items['desayuno']['panes'] as $item): ?>
                                            <option value="<?php echo htmlspecialchars($item); ?>" <?php echo (isset($solicitud_data['menu_options']['desayunoPan']) && $solicitud_data['menu_options']['desayunoPan'] == $item) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($item); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div id="almuerzo_options" class="menu-options-group animate">
                            <h4>Opciones de Almuerzo</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="almuerzo_entrada">Entrada <span class="required">*</span>:</label>
                                    <select id="almuerzo_entrada" name="almuerzo_entrada" class="form-control" required>
                                        <option value="">Selecciona una entrada</option>
                                        <?php foreach ($menu_items['almuerzo']['entradas'] as $item): ?>
                                            <option value="<?php echo htmlspecialchars($item); ?>" <?php echo (isset($solicitud_data['menu_options']['almuerzoEntrada']) && $solicitud_data['menu_options']['almuerzoEntrada'] == $item) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($item); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="almuerzo_fondo">Plato de Fondo <span class="required">*</span>:</label>
                                    <select id="almuerzo_fondo" name="almuerzo_fondo" class="form-control" required>
                                        <option value="">Selecciona un plato de fondo</option>
                                        <?php foreach ($menu_items['almuerzo']['platos_fondo'] as $item): ?>
                                            <option value="<?php echo htmlspecialchars($item); ?>" <?php echo (isset($solicitud_data['menu_options']['almuerzoFondo']) && $solicitud_data['menu_options']['almuerzoFondo'] == $item) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($item); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="almuerzo_postre">Postre <span class="required">*</span>:</label>
                                    <select id="almuerzo_postre" name="almuerzo_postre" class="form-control" required>
                                        <option value="">Selecciona un postre</option>
                                        <?php foreach ($menu_items['almuerzo']['postres'] as $item): ?>
                                            <option value="<?php echo htmlspecialchars($item); ?>" <?php echo (isset($solicitud_data['menu_options']['almuerzoPostre']) && $solicitud_data['menu_options']['almuerzoPostre'] == $item) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($item); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="almuerzo_bebida">Bebida <span class="required">*</span>:</label>
                                    <select id="almuerzo_bebida" name="almuerzo_bebida" class="form-control" required>
                                        <option value="">Selecciona una bebida</option>
                                        <?php foreach ($menu_items['almuerzo']['bebidas'] as $item): ?>
                                            <option value="<?php echo htmlspecialchars($item); ?>" <?php echo (isset($solicitud_data['menu_options']['almuerzoBebida']) && $solicitud_data['menu_options']['almuerzoBebida'] == $item) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($item); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div id="cena_options" class="menu-options-group animate">
                            <h4>Opciones de Cena</h4>
                            <div class="form-group">
                                <label for="cena_plato">Plato Principal <span class="required">*</span>:</label>
                                <select id="cena_plato" name="cena_plato" class="form-control" required>
                                    <option value="">Selecciona un plato principal</option>
                                    <?php foreach ($menu_items['cena']['platos_principales'] as $item): ?>
                                        <option value="<?php echo htmlspecialchars($item); ?>" <?php echo (isset($solicitud_data['menu_options']['cenaPlato']) && $solicitud_data['menu_options']['cenaPlato'] == $item) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($item); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="cena_postre">Postre <span class="required">*</span>:</label>
                                    <select id="cena_postre" name="cena_postre" class="form-control" required>
                                        <option value="">Selecciona un postre</option>
                                        <?php foreach ($menu_items['cena']['postres'] as $item): ?>
                                            <option value="<?php echo htmlspecialchars($item); ?>" <?php echo (isset($solicitud_data['menu_options']['cenaPostre']) && $solicitud_data['menu_options']['cenaPostre'] == $item) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($item); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="cena_bebida">Bebida <span class="required">*</span>:</label>
                                    <select id="cena_bebida" name="cena_bebida" class="form-control" required>
                                        <option value="">Selecciona una bebida</option>
                                        <?php foreach ($menu_items['cena']['bebidas'] as $item): ?>
                                            <option value="<?php echo htmlspecialchars($item); ?>" <?php echo (isset($solicitud_data['menu_options']['cenaBebida']) && $solicitud_data['menu_options']['cenaBebida'] == $item) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($item); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-row animate">
                            <div class="form-group">
                                <label for="fecha_reserva">Fecha de Reserva <span class="required">*</span></label>
                                <input type="date" id="fecha_reserva" name="fecha_reserva" class="form-control" value="<?php echo htmlspecialchars($solicitud_data['fecha_reserva'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="hora_reserva">Hora de Reserva <span class="required">*</span></label>
                                <select id="hora_reserva" name="hora_reserva" class="form-control" required>
                                    <option value="">Selecciona una hora</option>
                                    <?php foreach ($horarios_disponibles as $hora => $info): ?>
                                        <option value="<?php echo $hora; ?>" <?php echo (isset($solicitud_data['hora_reserva']) && $solicitud_data['hora_reserva'] == $hora) ? 'selected' : ''; ?>>
                                            <?php echo $info['display'] . ' (' . $info['tipo'] . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group animate">
                            <label for="cantidad_personas">Cantidad de Personas (máx. 250) <span class="required">*</span></label>
                            <input type="number" id="cantidad_personas" name="cantidad_personas" class="form-control" min="1" max="250" value="<?php echo htmlspecialchars($solicitud_data['cantidad_personas'] ?? ''); ?>" required>
                        </div>

                        <div class="button-group animate-button animate">
                            <button type="submit" name="action" value="solicitud_next_step1" class="btn btn--primary">Siguiente</button>
                        </div>
                    </div>

                    <!-- Paso 2: Datos personales -->
                    <div class="form-step <?php echo ($solicitud_step == 2) ? 'active' : ''; ?>">
                        <h3 class="step-title">Paso 2: Datos Personales</h3>
                        
                        <div class="info-box info animate-fade-in-left animate">
                            <div class="info-icon">ℹ</div>
                            <div class="info-content">
                                <h4>Información Personal</h4>
                                <p><strong>IMPORTANTE:</strong> Todos los campos son obligatorios para continuar.</p>
                                <p>El teléfono debe empezar con 9 y tener exactamente 9 dígitos.</p>
                                <p>El correo debe terminar en @gmail.com</p>
                            </div>
                        </div>
                        
                        <div class="form-row animate">
                            <div class="form-group">
                                <label for="nombre">Nombres <span class="required">*</span></label>
                                <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo htmlspecialchars($solicitud_data['nombre'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="apellidos">Apellidos <span class="required">*</span></label>
                                <input type="text" id="apellidos" name="apellidos" class="form-control" value="<?php echo htmlspecialchars($solicitud_data['apellidos'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="form-row animate">
                            <div class="form-group">
                                <label for="telefono">Teléfono (9 dígitos, empieza con 9) <span class="required">*</span></label>
                                <input type="tel" id="telefono" name="telefono" class="form-control" pattern="^9\d{8}$" value="<?php echo htmlspecialchars($solicitud_data['telefono'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Correo Electrónico (@gmail.com) <span class="required">*</span></label>
                                <input type="email" id="email" name="email" class="form-control" pattern=".*@gmail\.com$" value="<?php echo htmlspecialchars($solicitud_data['email'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="button-group animate-button animate">
                            <button type="submit" name="action" value="solicitud_back" class="btn btn--stroke">
                                <input type="hidden" name="step" value="2">
                                Anterior
                            </button>
                            <button type="submit" name="action" value="solicitud_next_step2" class="btn btn--primary">Siguiente</button>
                        </div>
                    </div>

                    <!-- Paso 3: Resumen y confirmación -->
                    <div class="form-step <?php echo ($solicitud_step == 3) ? 'active' : ''; ?>">
                        <h3 class="step-title">Paso 3: Resumen de la Solicitud</h3>
                        
                        <?php if (!empty($solicitud_data)): ?>
                            <div class="summary-details animate-fade-in animate">
                                <h4>Resumen de tu Reserva</h4>
                                
                                <div class="summary-row">
                                    <span class="summary-label">Nombre Completo:</span>
                                    <span class="summary-value"><?php echo htmlspecialchars(($solicitud_data['nombre'] ?? '') . ' ' . ($solicitud_data['apellidos'] ?? '')); ?></span>
                                </div>
                                
                                <div class="summary-row">
                                    <span class="summary-label">Teléfono:</span>
                                    <span class="summary-value"><?php echo htmlspecialchars($solicitud_data['telefono'] ?? ''); ?></span>
                                </div>
                                
                                <div class="summary-row">
                                    <span class="summary-label">Email:</span>
                                    <span class="summary-value"><?php echo htmlspecialchars($solicitud_data['email'] ?? ''); ?></span>
                                </div>
                                
                                <div class="summary-row">
                                    <span class="summary-label">Fecha de Reserva:</span>
                                    <span class="summary-value"><?php echo htmlspecialchars($solicitud_data['fecha_reserva'] ?? ''); ?></span>
                                </div>
                                
                                <div class="summary-row">
                                    <span class="summary-label">Hora de Reserva:</span>
                                    <span class="summary-value"><?php echo htmlspecialchars($solicitud_data['hora_reserva'] ?? ''); ?></span>
                                </div>
                                
                                <div class="summary-row">
                                    <span class="summary-label">Cantidad de Personas:</span>
                                    <span class="summary-value"><?php echo htmlspecialchars($solicitud_data['cantidad_personas'] ?? ''); ?></span>
                                </div>
                                
                                <div class="summary-row">
                                    <span class="summary-label">Tipo de Menú:</span>
                                    <span class="summary-value"><?php echo ucfirst(htmlspecialchars($solicitud_data['menu_type'] ?? '')); ?></span>
                                </div>
                                
                                <div class="summary-row">
                                    <span class="summary-label">Precio por Persona:</span>
                                    <span class="summary-value">S/<?php echo number_format($menu_prices[$solicitud_data['menu_type']] ?? 0, 2); ?></span>
                                </div>
                                
                                <div class="summary-row">
                                    <span class="summary-label">Precio Total:</span>
                                    <span class="summary-value">S/<?php echo number_format($solicitud_data['total_price'] ?? 0, 2); ?></span>
                                </div>
                                
                                <div class="summary-row">
                                    <span class="summary-label">Adelanto (50%):</span>
                                    <span class="summary-value">S/<?php echo number_format($solicitud_data['advance_payment'] ?? 0, 2); ?></span>
                                </div>
                            </div>

                            <div class="info-box info animate-fade-in-right animate">
                                <div class="info-icon">ℹ</div>
                                <div class="info-content">
                                    <h4>Información Importante</h4>
                                    <p>Al confirmar tu reserva, se generará un código único que necesitarás para registrar tu pago.</p>
                                    <p>Tienes un plazo máximo de 2 días para registrar el pago del adelanto.</p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="button-group animate-button animate">
                            <button type="submit" name="action" value="solicitud_back" class="btn btn--stroke">
                                <input type="hidden" name="step" value="3">
                                Anterior
                            </button>
                            <button type="submit" name="action" value="solicitud_confirm" class="btn btn--primary">Confirmar Reserva</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- ACTIVIDAD 2: REGISTRO DE PAGO -->
        <div class="activity-container animate-fade-in" id="container-pago" style="display: none;">
            <div id="pago" class="activity active">
                <h2 class="activity-title">Registro de Pago</h2>
                
                <div class="info-box info animate-fade-in-left animate" style="margin-bottom: 30px;">
                    <div class="info-icon">ℹ</div>
                    <div class="info-content">
                        <h4>Información Importante</h4>
                        <p><strong>Esta sección es INDEPENDIENTE</strong> - puedes registrar el pago de cualquier reserva existente.</p>
                        <p>Solo necesitas el <strong>código único de reserva</strong> que recibiste al completar tu solicitud.</p>
                        <p>Si no tienes un código de reserva, primero debes completar una "Solicitud de Reserva".</p>
                    </div>
                </div>
                
                <!-- Indicador de pasos para pago -->
                <div class="steps-indicator step-animate animate">
                    <div class="step <?php echo ($pago_step >= 1) ? 'active' : ''; ?> <?php echo ($pago_step > 1) ? 'completed' : ''; ?>">
                        <div class="step-number">1</div>
                        <div class="step-label">Código de Reserva</div>
                    </div>
                    <div class="step-line <?php echo ($pago_step > 1) ? 'completed' : ''; ?>"></div>
                    <div class="step <?php echo ($pago_step >= 2) ? 'active' : ''; ?> <?php echo ($pago_step > 2) ? 'completed' : ''; ?>">
                        <div class="step-number">2</div>
                        <div class="step-label">Método de Pago</div>
                    </div>
                    <div class="step-line <?php echo ($pago_step > 2) ? 'completed' : ''; ?>"></div>
                    <div class="step <?php echo ($pago_step >= 3) ? 'active' : ''; ?>">
                        <div class="step-number">3</div>
                        <div class="step-label">Comprobante</div>
                    </div>
                </div>

                <form method="POST" novalidate>
                    <!-- Paso 1: Código de reserva -->
                    <div class="form-step <?php echo ($pago_step == 1) ? 'active' : ''; ?>">
                        <h3 class="step-title">Paso 1: Ingreso del Código de Reserva</h3>
                        
                        <div class="info-box warning animate-fade-in-left animate">
                            <div class="info-icon">⚠</div>
                            <div class="info-content">
                                <h4>¿Tienes tu código de reserva?</h4>
                                <p><strong>Necesitas el código único</strong> que recibiste al completar tu solicitud de reserva.</p>
                                <p><strong>Formato:</strong> 8 caracteres alfanuméricos (ej: ABC12345)</p>
                                <p><strong>¿No tienes código?</strong> Ve a "Solicitud de Reserva" para crear una nueva reserva.</p>
                            </div>
                        </div>

                        <div class="form-group animate">
                            <label for="codigo_reserva">Código de Reserva <span class="required">*</span></label>
                            <input type="text" id="codigo_reserva" name="codigo_reserva" class="form-control" placeholder="Ingresa tu código de reserva" value="<?php echo htmlspecialchars($pago_data['codigo_reserva'] ?? ''); ?>" style="text-transform: uppercase; letter-spacing: 3px; font-family: 'Courier New', monospace; font-size: 2.2rem; text-align: center; font-weight: 800;" required>
                        </div>

                        <div class="button-group animate-button animate">
                            <button type="submit" name="action" value="pago_next_step1" class="btn btn--primary">Verificar Código</button>
                        </div>
                    </div>

                    <!-- Paso 2: Método de pago -->
                    <div class="form-step <?php echo ($pago_step == 2) ? 'active' : ''; ?>">
                        <h3 class="step-title">Paso 2: Selección del Método de Pago</h3>
                        
                        <?php if (!empty($pago_data['reserva_info'])): ?>
                            <div class="summary-details animate-fade-in animate">
                                <h4>Información de la Reserva</h4>
                                <div class="summary-row">
                                    <span class="summary-label">Código:</span>
                                    <span class="summary-value"><?php echo htmlspecialchars($pago_data['codigo_reserva']); ?></span>
                                </div>
                                <div class="summary-row">
                                    <span class="summary-label">Fecha:</span>
                                    <span class="summary-value"><?php echo htmlspecialchars($pago_data['reserva_info']['fecha_reserva']); ?></span>
                                </div>
                                <div class="summary-row">
                                    <span class="summary-label">Hora:</span>
                                    <span class="summary-value"><?php echo htmlspecialchars($pago_data['reserva_info']['hora_reserva']); ?></span>
                                </div>
                                <div class="summary-row">
                                    <span class="summary-label">Total a Pagar (50%):</span>
                                    <span class="summary-value">S/<?php echo number_format($pago_data['reserva_info']['total'] * 0.5, 2); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="info-box warning animate-fade-in-left animate">
                            <div class="info-icon">⚠</div>
                            <div class="info-content">
                                <h4>Validación Estricta</h4>
                                <p><strong>TODOS los campos son obligatorios para continuar.</strong></p>
                                <p>Yape: 8 dígitos exactos | Transferencia: 8-11 dígitos</p>
                            </div>
                        </div>

                        <div class="form-group animate">
                            <label>Método de Pago <span class="required">*</span></label>
                            <div class="radio-group">
                                <div class="radio-option">
                                    <input type="radio" id="metodo_yape" name="metodo_pago" value="yape" <?php echo (isset($pago_data['metodo_pago']) && $pago_data['metodo_pago'] == 'yape') ? 'checked' : ''; ?> required>
                                    <label for="metodo_yape" class="radio-label">Yape</label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" id="metodo_transferencia" name="metodo_pago" value="transferencia" <?php echo (isset($pago_data['metodo_pago']) && $pago_data['metodo_pago'] == 'transferencia') ? 'checked' : ''; ?> required>
                                    <label for="metodo_transferencia" class="radio-label">Transferencia Bancaria</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group animate">
                            <label for="nombre_titular">Nombre del Titular <span class="required">*</span></label>
                            <input type="text" id="nombre_titular" name="nombre_titular" class="form-control" value="<?php echo htmlspecialchars($pago_data['nombre_titular'] ?? ''); ?>" required>
                        </div>

                        <div class="form-row animate">
                            <div class="form-group">
                                <label for="numero_operacion">Número de Operación <span class="required">*</span></label>
                                <input type="text" id="numero_operacion" name="numero_operacion" class="form-control" placeholder="8 dígitos para Yape, 8-11 para transferencia" value="<?php echo htmlspecialchars($pago_data['numero_operacion'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group" id="codigo_seguridad_group" style="display: none;">
                                <label for="codigo_seguridad">Código de Seguridad (3 dígitos)</label>
                                <input type="text" id="codigo_seguridad" name="codigo_seguridad" class="form-control" pattern="\d{3}" value="<?php echo htmlspecialchars($pago_data['codigo_seguridad'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group animate" id="banco_group" style="display: none;">
                            <label for="banco">Banco de Origen <span class="required">*</span></label>
                            <select id="banco" name="banco" class="form-control" required>
                                <option value="">Selecciona tu banco</option>
                                <?php foreach ($bancos_disponibles as $banco): ?>
                                    <option value="<?php echo $banco; ?>" <?php echo (isset($pago_data['banco']) && $pago_data['banco'] == $banco) ? 'selected' : ''; ?>>
                                        <?php echo $banco; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="button-group animate-button animate">
                            <button type="submit" name="action" value="pago_back" class="btn btn--stroke">
                                <input type="hidden" name="step" value="2">
                                Anterior
                            </button>
                            <button type="submit" name="action" value="pago_next_step2" class="btn btn--primary">Siguiente</button>
                        </div>
                    </div>

                    <!-- Paso 3: Comprobante -->
                    <div class="form-step <?php echo ($pago_step == 3) ? 'active' : ''; ?>">
                        <h3 class="step-title">Paso 3: Selección del Comprobante</h3>
                        
                        <div class="info-box warning animate-fade-in-left animate">
                            <div class="info-icon">⚠</div>
                            <div class="info-content">
                                <h4>Validación de Comprobante</h4>
                                <p><strong>TODOS los campos son obligatorios.</strong></p>
                                <p>Si eliges factura, el RUC debe tener exactamente 11 dígitos.</p>
                                <p>El monto debe ser mayor a 0.</p>
                            </div>
                        </div>
                        
                        <div class="form-group animate">
                            <label>Tipo de Comprobante <span class="required">*</span></label>
                            <div class="radio-group">
                                <div class="radio-option">
                                    <input type="radio" id="comprobante_boleta" name="tipo_comprobante" value="boleta" checked required>
                                    <label for="comprobante_boleta" class="radio-label">Boleta</label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" id="comprobante_factura" name="tipo_comprobante" value="factura" required>
                                    <label for="comprobante_factura" class="radio-label">Factura</label>
                                </div>
                            </div>
                        </div>

                        <div id="ruc_factura_group" class="form-group animate" style="display: none;">
                            <label for="ruc_factura">RUC (11 dígitos) <span class="required">*</span></label>
                            <input type="text" id="ruc_factura" name="ruc_factura" class="form-control" pattern="\d{11}" placeholder="Ingresa el RUC de 11 dígitos" required>
                        </div>

                        <div class="form-group animate">
                            <label for="monto_pagado">Monto Pagado <span class="required">*</span></label>
                            <input type="number" id="monto_pagado" name="monto_pagado" class="form-control" step="0.01" min="0.01" value="<?php echo isset($pago_data['reserva_info']) ? number_format($pago_data['reserva_info']['total'] * 0.5, 2, '.', '') : ''; ?>" required>
                        </div>

                        <div class="button-group animate-button animate">
                            <button type="submit" name="action" value="pago_back" class="btn btn--stroke">
                                <input type="hidden" name="step" value="3">
                                Anterior
                            </button>
                            <button type="submit" name="action" value="pago_confirm" class="btn btn--primary">Confirmar Pago</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Footer principal estilo Index.html -->
    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>La Casona Kawai</h3>
                    <p>Disfruta de la auténtica comida peruana en un ambiente acogedor y familiar. Más de 20 años sirviendo los mejores sabores del Perú.</p>
                    <p><strong>Dirección:</strong> Av. Principal 123, Lima, Perú</p>
                    <p><strong>Teléfono:</strong> +51 1 234-5678</p>
                </div>
                
                <div class="footer-section">
                    <h3>Horarios</h3>
                    <ul>
                        <li><strong>Desayuno:</strong> 7:00 AM - 12:00 PM</li>
                        <li><strong>Almuerzo:</strong> 12:00 PM - 6:00 PM</li>
                        <li><strong>Cena:</strong> 6:00 PM - 12:00 AM</li>
                        <li><strong>Todos los días</strong></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Enlaces Rápidos</h3>
                    <ul>
                        <li><a href="Index.html">Inicio</a></li>
                        <li><a href="Carta.html">Nuestro Menú</a></li>
                        <li><a href="Reserva.php">Hacer Reserva</a></li>
                        <li><a href="About.html">Nosotros</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Síguenos</h3>
                    <p>Mantente conectado con nosotros en nuestras redes sociales para conocer las últimas novedades y promociones.</p>
                    <ul>
                        <li><a href="#">Facebook</a></li>
                        <li><a href="#">Instagram</a></li>
                        <li><a href="#">Twitter</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 La Casona Kawai. Todos los derechos reservados. | Diseñado con amor para los amantes de la comida peruana.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="../assets/js/Reserva.js"></script>

    <script>
        // Eliminar preloader y evitar flashes
        window.addEventListener('load', function() {
            const loader = document.getElementById('pageLoader');
            setTimeout(() => {
                loader.classList.add('hidden');
                setTimeout(() => {
                    loader.style.display = 'none';
                }, 300);
            }, 500);
        });

        // Mostrar notificación especial para pago registrado
        <?php if (isset($_SESSION['pago_registered'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Crear notificación toast
            const toast = document.createElement('div');
            toast.className = 'payment-toast';
            toast.innerHTML = `
                <div class="toast-icon">✓</div>
                <div class="toast-content">
                    <h4>¡Pago Registrado!</h4>
                    <p>Tu pago ha sido enviado para verificación</p>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Mostrar toast con animación
            setTimeout(() => toast.classList.add('show'), 100);
            
            // Ocultar toast después de 5 segundos
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        });
        <?php endif; ?>

        // Animaciones al hacer scroll optimizadas
        function animateOnScroll() {
            const elements = document.querySelectorAll('.animate-fade-in, .animate-fade-in-left, .animate-fade-in-right, .animate-scale-in, .animate-slide-up, .animate-button, .step-animate');
            
            elements.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const elementVisible = 100;
                
                if (elementTop < window.innerHeight - elementVisible) {
                    element.classList.add('animate');
                }
            });
        }

        // Ejecutar animaciones al cargar y hacer scroll
        window.addEventListener('scroll', animateOnScroll);
        window.addEventListener('load', () => {
            setTimeout(animateOnScroll, 50);
        });

        // Animaciones específicas para elementos de formulario
        document.addEventListener('DOMContentLoaded', function() {
            // Animar elementos del formulario cuando se muestran
            const formGroups = document.querySelectorAll('.form-group');
            formGroups.forEach((group, index) => {
                setTimeout(() => {
                    group.classList.add('animate');
                }, index * 30);
            });

            // Efectos de hover mejorados
            const buttons = document.querySelectorAll('.btn, .activity-btn');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px) scale(1.02)';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Animación inicial de la página más rápida
            setTimeout(() => {
                document.querySelectorAll('.animate-fade-in, .animate-scale-in, .animate-button').forEach(el => {
                    el.classList.add('animate');
                });
            }, 100);
        });

        console.log('Sistema de Reservas Kawai - Animaciones optimizadas cargadas');
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar/ocultar campos RUC y código de seguridad
            const facturaRadio = document.getElementById('comprobante_factura');
            const rucFacturaGroup = document.getElementById('ruc_factura_group');
            const yapeRadio = document.getElementById('metodo_yape');
            const transferenciaRadio = document.getElementById('metodo_transferencia');
            const codigoSeguridadGroup = document.getElementById('codigo_seguridad_group');
            const bancoGroup = document.getElementById('banco_group');

            function toggleRucVisibility() {
                if (rucFacturaGroup) {
                    rucFacturaGroup.style.display = facturaRadio && facturaRadio.checked ? 'block' : 'none';
                    rucFacturaGroup.querySelectorAll('input').forEach(input => {
                        input.required = facturaRadio && facturaRadio.checked;
                    });
                }
            }

            function togglePagoFields() {
                if (codigoSeguridadGroup && bancoGroup) {
                    codigoSeguridadGroup.style.display = yapeRadio && yapeRadio.checked ? 'block' : 'none';
                    bancoGroup.style.display = transferenciaRadio && transferenciaRadio.checked ? 'block' : 'none';

                    codigoSeguridadGroup.querySelectorAll('input').forEach(input => {
                        input.required = yapeRadio && yapeRadio.checked;
                    });
                    bancoGroup.querySelectorAll('select').forEach(select => {
                        select.required = transferenciaRadio && transferenciaRadio.checked;
                    });
                }
            }

            if (facturaRadio) {
                facturaRadio.addEventListener('change', toggleRucVisibility);
                document.getElementById('comprobante_boleta').addEventListener('change', toggleRucVisibility);
                toggleRucVisibility();
            }

            if (yapeRadio && transferenciaRadio) {
                yapeRadio.addEventListener('change', togglePagoFields);
                transferenciaRadio.addEventListener('change', togglePagoFields);
                togglePagoFields();
            }

            // Mostrar/ocultar contenedores de actividad
            const containerSolicitud = document.getElementById('container-solicitud');
            const containerPago = document.getElementById('container-pago');

            function showSolicitud() {
                if (containerSolicitud) {
                    containerSolicitud.style.display = 'block';
                    containerSolicitud.classList.add('show');
                }
                if (containerPago) {
                    containerPago.style.display = 'none';
                    containerPago.classList.remove('show');
                }
            }

            function showPago() {
                if (containerPago) {
                    containerPago.style.display = 'block';
                    containerPago.classList.add('show');
                }
                if (containerSolicitud) {
                    containerSolicitud.style.display = 'none';
                    containerSolicitud.classList.remove('show');
                }
            }

            function hideAll() {
                if (containerSolicitud) {
                    containerSolicitud.style.display = 'none';
                    containerSolicitud.classList.remove('show');
                }
                if (containerPago) {
                    containerPago.style.display = 'none';
                    containerPago.classList.remove('show');
                }
            }

            // Inicializar la visibilidad basada en la actividad actual
            <?php if ($current_activity === 'solicitud'): ?>
                showSolicitud();
            <?php elseif ($current_activity === 'pago'): ?>
                showPago();
            <?php else: ?>
                hideAll();
            <?php endif; ?>
        });
    </script>

</body>
</html>
