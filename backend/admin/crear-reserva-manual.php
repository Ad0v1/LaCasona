<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION["admin_logueado"]) || $_SESSION["admin_logueado"] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

include "../includes/database.php";
require_once "../controllers/ReservaControllers.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

try {
    // Validar datos requeridos
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $fecha_reserva = $_POST['fecha_reserva'] ?? '';
    $hora_reserva = $_POST['hora_reserva'] ?? '';
    $cantidad_personas = (int)($_POST['cantidad_personas'] ?? 0);
    $tipo_menu = $_POST['tipo_menu'] ?? '';
    $metodo_pago = $_POST['metodo_pago'] ?? '';
    $info_adicional = trim($_POST['info_adicional'] ?? '');
    
    // Validaciones básicas
    if (empty($nombre) || empty($telefono) || empty($fecha_reserva) || 
        empty($hora_reserva) || $cantidad_personas <= 0 || empty($tipo_menu)) {
        throw new Exception('Todos los campos obligatorios deben ser completados');
    }
    
    // Validar fecha (no puede ser en el pasado)
    if (strtotime($fecha_reserva) < strtotime(date('Y-m-d'))) {
        throw new Exception('La fecha de reserva no puede ser en el pasado');
    }
    
    // Precios por tipo de menú
    $precios = [
        'desayuno' => 9.00,
        'almuerzo' => 14.50,
        'cena' => 16.50
    ];
    
    if (!isset($precios[$tipo_menu])) {
        throw new Exception('Tipo de menú no válido');
    }
    
    $precio_unitario = $precios[$tipo_menu];
    $total = $precio_unitario * $cantidad_personas;
    
    // Generar código único de reserva
    $codigo_reserva = 'ADM' . strtoupper(substr(uniqid(), -5));
    
    // Iniciar transacción
    mysqli_begin_transaction($con);
    
    try {
        // 1. Crear o buscar usuario
        $id_usuario = null;
        if (!empty($email)) {
            $stmt_user = mysqli_prepare($con, "SELECT id_usuario FROM usuarios WHERE email = ?");
            mysqli_stmt_bind_param($stmt_user, "s", $email);
            mysqli_stmt_execute($stmt_user);
            $result_user = mysqli_stmt_get_result($stmt_user);
            
            if ($row = mysqli_fetch_assoc($result_user)) {
                $id_usuario = $row['id_usuario'];
                
                // Actualizar datos del usuario existente
                $stmt_update = mysqli_prepare($con, "UPDATE usuarios SET nombre = ?, telefono = ? WHERE id_usuario = ?");
                mysqli_stmt_bind_param($stmt_update, "ssi", $nombre, $telefono, $id_usuario);
                mysqli_stmt_execute($stmt_update);
            }
        }
        
        // Si no existe usuario, crear uno nuevo
        if (!$id_usuario) {
            $stmt_new_user = mysqli_prepare($con, "INSERT INTO usuarios (nombre, telefono, email) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt_new_user, "sss", $nombre, $telefono, $email);
            mysqli_stmt_execute($stmt_new_user);
            $id_usuario = mysqli_insert_id($con);
        }
        
        // 2. Crear reserva
        $stmt_reserva = mysqli_prepare($con, "
            INSERT INTO reservas (
                codigo_reserva, id_usuario, id_admin, fecha_reserva, hora_reserva, 
                cantidad_personas, total, estado, info_adicional
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Solicitada', ?)
        ");
        
        mysqli_stmt_bind_param($stmt_reserva, "siissids", 
            $codigo_reserva, $id_usuario, $_SESSION['user_id'], 
            $fecha_reserva, $hora_reserva, $cantidad_personas, $total, $info_adicional
        );
        
        if (!mysqli_stmt_execute($stmt_reserva)) {
            throw new Exception('Error al crear la reserva');
        }
        
        $id_reserva = mysqli_insert_id($con);
        
        // 3. Crear entrada en la tabla correspondiente del menú
        $id_menu_item = null;
        switch ($tipo_menu) {
            case 'desayuno':
                $stmt_menu = mysqli_prepare($con, "INSERT INTO desayuno (bebida, pan, precio) VALUES ('Por definir', 'Por definir', ?)");
                mysqli_stmt_bind_param($stmt_menu, "d", $precio_unitario);
                mysqli_stmt_execute($stmt_menu);
                $id_menu_item = mysqli_insert_id($con);
                
                // Crear detalle de reserva
                $stmt_detalle = mysqli_prepare($con, "INSERT INTO detalle_reserva (id_reserva, id_desayuno, cantidad, subtotal) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt_detalle, "iiid", $id_reserva, $id_menu_item, $cantidad_personas, $total);
                break;
                
            case 'almuerzo':
                $stmt_menu = mysqli_prepare($con, "INSERT INTO almuerzo (entrada, plato_fondo, postre, bebida, precio) VALUES ('Por definir', 'Por definir', 'Por definir', 'Por definir', ?)");
                mysqli_stmt_bind_param($stmt_menu, "d", $precio_unitario);
                mysqli_stmt_execute($stmt_menu);
                $id_menu_item = mysqli_insert_id($con);
                
                // Crear detalle de reserva
                $stmt_detalle = mysqli_prepare($con, "INSERT INTO detalle_reserva (id_reserva, id_almuerzo, cantidad, subtotal) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt_detalle, "iiid", $id_reserva, $id_menu_item, $cantidad_personas, $total);
                break;
                
            case 'cena':
                $stmt_menu = mysqli_prepare($con, "INSERT INTO cena (plato, postre, bebida, precio) VALUES ('Por definir', 'Por definir', 'Por definir', ?)");
                mysqli_stmt_bind_param($stmt_menu, "d", $precio_unitario);
                mysqli_stmt_execute($stmt_menu);
                $id_menu_item = mysqli_insert_id($con);
                
                // Crear detalle de reserva
                $stmt_detalle = mysqli_prepare($con, "INSERT INTO detalle_reserva (id_reserva, id_cena, cantidad, subtotal) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt_detalle, "iiid", $id_reserva, $id_menu_item, $cantidad_personas, $total);
                break;
        }
        
        if (!mysqli_stmt_execute($stmt_detalle)) {
            throw new Exception('Error al crear el detalle de la reserva');
        }
        
        // 4. Si hay método de pago, crear registro de pago
        if (!empty($metodo_pago)) {
            $monto_anticipo = $total * 0.5;
            $stmt_pago = mysqli_prepare($con, "
                INSERT INTO registro_pago (
                    codigo_reserva, metodo_pago, nombre_titular, numero_operacion, 
                    monto_pagado, estado_verificacion, verificado_por, fecha_verificacion
                ) VALUES (?, ?, ?, 'MANUAL-ADM', ?, 'verificado', ?, NOW())
            ");
            
            mysqli_stmt_bind_param($stmt_pago, "sssdi", 
                $codigo_reserva, $metodo_pago, $nombre, $monto_anticipo, $_SESSION['user_id']
            );
            
            if (mysqli_stmt_execute($stmt_pago)) {
                // Actualizar estado de la reserva a "Anticipo pagado"
                $stmt_update_estado = mysqli_prepare($con, "UPDATE reservas SET estado = 'Anticipo pagado' WHERE id_reserva = ?");
                mysqli_stmt_bind_param($stmt_update_estado, "i", $id_reserva);
                mysqli_stmt_execute($stmt_update_estado);
            }
        }
        
        // Confirmar transacción
        mysqli_commit($con);
        
        echo json_encode([
            'success' => true,
            'message' => 'Reserva creada exitosamente',
            'codigo_reserva' => $codigo_reserva,
            'id_reserva' => $id_reserva
        ]);
        
    } catch (Exception $e) {
        mysqli_rollback($con);
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

mysqli_close($con);
?>