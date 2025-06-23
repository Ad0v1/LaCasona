<?php

class AdminControllers {
    
    public static function autenticarAdmin($usuario, $password) {
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
                // Actualizar fecha de último acceso
                $stmt_update = $conexion->prepare("UPDATE administradores SET fecha_ultimo_acceso = NOW() WHERE id_admin = ?");
                $stmt_update->bind_param("i", $admin['id_admin']);
                $stmt_update->execute();
                
                return [
                    'success' => true, 
                    'message' => 'Autenticación exitosa',
                    'admin_data' => $admin
                ];
            }
            
            return ['success' => false, 'message' => 'Credenciales incorrectas'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error de autenticación: ' . $e->getMessage()];
        }
    }

    public static function obtenerTodasLasReservas($conexion) {
        try {
            $stmt = $conexion->prepare("
                SELECT 
                    r.id_reserva,
                    r.codigo_reserva,
                    r.fecha_reserva,
                    r.hora_reserva,
                    r.cantidad_personas,
                    r.total,
                    r.estado,
                    r.fecha_creacion,
                    r.info_adicional,
                    u.nombre as cliente_nombre,
                    u.telefono as cliente_telefono,
                    u.email as cliente_email,
                    dr.id_desayuno,
                    dr.id_almuerzo,
                    dr.id_cena,
                    d.bebida as desayuno_bebida,
                    d.pan as desayuno_pan,
                    a.entrada as almuerzo_entrada,
                    a.plato_fondo as almuerzo_fondo,
                    a.postre as almuerzo_postre,
                    a.bebida as almuerzo_bebida,
                    c.plato as cena_plato,
                    c.postre as cena_postre,
                    c.bebida as cena_bebida,
                    rp.id_pago,
                    rp.metodo_pago,
                    rp.nombre_titular,
                    rp.numero_operacion,
                    rp.banco,
                    rp.monto_pagado,
                    rp.tipo_comprobante,
                    rp.ruc_factura,
                    rp.fecha_pago
                FROM reservas r
                INNER JOIN usuarios u ON r.id_usuario = u.id_usuario
                LEFT JOIN detalle_reserva dr ON r.id_reserva = dr.id_reserva
                LEFT JOIN desayuno d ON dr.id_desayuno = d.id_desayuno
                LEFT JOIN almuerzo a ON dr.id_almuerzo = a.id_almuerzo
                LEFT JOIN cena c ON dr.id_cena = c.id_cena
                LEFT JOIN registro_pago rp ON r.codigo_reserva = rp.codigo_reserva
                ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC
            ");
            
            $stmt->execute();
            $result = $stmt->get_result();
            $reservas = [];
            
            while ($row = $result->fetch_assoc()) {
                $reservas[] = $row;
            }
            
            return ['success' => true, 'data' => $reservas];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener reservas: ' . $e->getMessage()];
        }
    }

    public static function obtenerReservaDetalle($conexion, $id_reserva) {
        try {
            $stmt = $conexion->prepare("
                SELECT 
                    r.*,
                    u.nombre as cliente_nombre,
                    u.telefono as cliente_telefono,
                    u.email as cliente_email,
                    dr.*,
                    d.bebida as desayuno_bebida,
                    d.pan as desayuno_pan,
                    d.precio as desayuno_precio,
                    a.entrada as almuerzo_entrada,
                    a.plato_fondo as almuerzo_fondo,
                    a.postre as almuerzo_postre,
                    a.bebida as almuerzo_bebida,
                    a.precio as almuerzo_precio,
                    c.plato as cena_plato,
                    c.postre as cena_postre,
                    c.bebida as cena_bebida,
                    c.precio as cena_precio,
                    rp.*
                FROM reservas r
                INNER JOIN usuarios u ON r.id_usuario = u.id_usuario
                LEFT JOIN detalle_reserva dr ON r.id_reserva = dr.id_reserva
                LEFT JOIN desayuno d ON dr.id_desayuno = d.id_desayuno
                LEFT JOIN almuerzo a ON dr.id_almuerzo = a.id_almuerzo
                LEFT JOIN cena c ON dr.id_cena = c.id_cena
                LEFT JOIN registro_pago rp ON r.codigo_reserva = rp.codigo_reserva
                WHERE r.id_reserva = ?
            ");
            
            $stmt->bind_param("i", $id_reserva);
            $stmt->execute();
            $result = $stmt->get_result();
            $reserva = $result->fetch_assoc();
            
            if ($reserva) {
                return ['success' => true, 'data' => $reserva];
            } else {
                return ['success' => false, 'message' => 'Reserva no encontrada'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener detalle: ' . $e->getMessage()];
        }
    }

    public static function cambiarEstadoReserva($conexion, $id_reserva, $nuevo_estado) {
        try {
            $estados_validos = ['Solicitada', 'Anticipo pagado', 'Completada', 'Cancelada'];
            
            if (!in_array($nuevo_estado, $estados_validos)) {
                return ['success' => false, 'message' => 'Estado no válido'];
            }
            
            $stmt = $conexion->prepare("UPDATE reservas SET estado = ? WHERE id_reserva = ?");
            $stmt->bind_param("si", $nuevo_estado, $id_reserva);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                return ['success' => true, 'message' => 'Estado actualizado correctamente'];
            } else {
                return ['success' => false, 'message' => 'No se pudo actualizar el estado'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al cambiar estado: ' . $e->getMessage()];
        }
    }

    public static function obtenerEstadisticas($conexion) {
        try {
            $stats = [];
            
            // 1. Solicitudes en espera de confirmación de pago
            $stmt = $conexion->prepare("
                SELECT COUNT(*) as total
                FROM reservas r
                INNER JOIN registro_pago rp ON r.codigo_reserva = rp.codigo_reserva
                WHERE r.estado = 'Solicitada'
            ");
            $stmt->execute();
            $stats['pendientes_confirmacion'] = $stmt->get_result()->fetch_assoc()['total'];
            
            // 2. Solicitudes por cancelar automáticamente (más de 2 días sin pago)
            $stmt = $conexion->prepare("
                SELECT COUNT(*) as total
                FROM reservas r
                LEFT JOIN registro_pago rp ON r.codigo_reserva = rp.codigo_reserva
                WHERE r.estado = 'Solicitada' 
                AND rp.id_pago IS NULL 
                AND DATEDIFF(NOW(), r.fecha_creacion) > 2
            ");
            $stmt->execute();
            $stats['por_cancelar'] = $stmt->get_result()->fetch_assoc()['total'];
            
            // 3. Solicitudes a completar (día actual)
            $stmt = $conexion->prepare("
                SELECT COUNT(*) as total
                FROM reservas r
                WHERE r.estado = 'Anticipo pagado' 
                AND DATE(r.fecha_reserva) = CURDATE()
            ");
            $stmt->execute();
            $stats['completar_hoy'] = $stmt->get_result()->fetch_assoc()['total'];
            
            // 4. Total de reservas por estado
            $stmt = $conexion->prepare("
                SELECT estado, COUNT(*) as total
                FROM reservas
                GROUP BY estado
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['por_estado'] = [];
            while ($row = $result->fetch_assoc()) {
                $stats['por_estado'][$row['estado']] = $row['total'];
            }
            
            // 5. Reservas del mes actual
            $stmt = $conexion->prepare("
                SELECT COUNT(*) as total
                FROM reservas
                WHERE MONTH(fecha_creacion) = MONTH(CURDATE()) 
                AND YEAR(fecha_creacion) = YEAR(CURDATE())
            ");
            $stmt->execute();
            $stats['mes_actual'] = $stmt->get_result()->fetch_assoc()['total'];
            
            return ['success' => true, 'data' => $stats];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener estadísticas: ' . $e->getMessage()];
        }
    }

    public static function obtenerReservasPendientesConfirmacion($conexion) {
        try {
            $stmt = $conexion->prepare("
                SELECT 
                    r.id_reserva,
                    r.codigo_reserva,
                    r.fecha_reserva,
                    r.hora_reserva,
                    r.cantidad_personas,
                    r.total,
                    u.nombre as cliente_nombre,
                    u.telefono as cliente_telefono,
                    rp.metodo_pago,
                    rp.monto_pagado,
                    rp.fecha_pago
                FROM reservas r
                INNER JOIN usuarios u ON r.id_usuario = u.id_usuario
                INNER JOIN registro_pago rp ON r.codigo_reserva = rp.codigo_reserva
                WHERE r.estado = 'Solicitada'
                ORDER BY rp.fecha_pago ASC
            ");
            
            $stmt->execute();
            $result = $stmt->get_result();
            $reservas = [];
            
            while ($row = $result->fetch_assoc()) {
                $reservas[] = $row;
            }
            
            return ['success' => true, 'data' => $reservas];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public static function obtenerReservasPorCancelar($conexion) {
        try {
            $stmt = $conexion->prepare("
                SELECT 
                    r.id_reserva,
                    r.codigo_reserva,
                    r.fecha_reserva,
                    r.hora_reserva,
                    r.cantidad_personas,
                    r.total,
                    r.fecha_creacion,
                    u.nombre as cliente_nombre,
                    u.telefono as cliente_telefono,
                    DATEDIFF(NOW(), r.fecha_creacion) as dias_transcurridos
                FROM reservas r
                INNER JOIN usuarios u ON r.id_usuario = u.id_usuario
                LEFT JOIN registro_pago rp ON r.codigo_reserva = rp.codigo_reserva
                WHERE r.estado = 'Solicitada' 
                AND rp.id_pago IS NULL 
                AND DATEDIFF(NOW(), r.fecha_creacion) > 2
                ORDER BY r.fecha_creacion ASC
            ");
            
            $stmt->execute();
            $result = $stmt->get_result();
            $reservas = [];
            
            while ($row = $result->fetch_assoc()) {
                $reservas[] = $row;
            }
            
            return ['success' => true, 'data' => $reservas];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public static function obtenerReservasCompletarHoy($conexion) {
        try {
            $stmt = $conexion->prepare("
                SELECT 
                    r.id_reserva,
                    r.codigo_reserva,
                    r.fecha_reserva,
                    r.hora_reserva,
                    r.cantidad_personas,
                    r.total,
                    u.nombre as cliente_nombre,
                    u.telefono as cliente_telefono
                FROM reservas r
                INNER JOIN usuarios u ON r.id_usuario = u.id_usuario
                WHERE r.estado = 'Anticipo pagado' 
                AND DATE(r.fecha_reserva) = CURDATE()
                ORDER BY r.hora_reserva ASC
            ");
            
            $stmt->execute();
            $result = $stmt->get_result();
            $reservas = [];
            
            while ($row = $result->fetch_assoc()) {
                $reservas[] = $row;
            }
            
            return ['success' => true, 'data' => $reservas];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public static function obtenerDatosGraficoBarras($conexion) {
        try {
            $stmt = $conexion->prepare("
                SELECT 
                    DATE(fecha_reserva) as fecha,
                    estado,
                    COUNT(*) as cantidad
                FROM reservas 
                WHERE fecha_reserva >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY DATE(fecha_reserva), estado
                ORDER BY fecha_reserva ASC
            ");
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $data = [];
            $fechas = [];
            $estados = ['Solicitada', 'Anticipo pagado', 'Completada', 'Cancelada'];
            
            // Inicializar estructura de datos
            for ($i = 29; $i >= 0; $i--) {
                $fecha = date('Y-m-d', strtotime("-$i days"));
                $fechas[] = $fecha;
                $data[$fecha] = [
                    'fecha' => $fecha,
                    'Solicitada' => 0,
                    'Anticipo pagado' => 0,
                    'Completada' => 0,
                    'Cancelada' => 0
                ];
            }
            
            // Llenar con datos reales
            while ($row = $result->fetch_assoc()) {
                if (isset($data[$row['fecha']])) {
                    $data[$row['fecha']][$row['estado']] = (int)$row['cantidad'];
                }
            }
            
            return ['success' => true, 'data' => array_values($data)];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener datos del gráfico: ' . $e->getMessage()];
        }
    }

    public static function obtenerResumenEstados($conexion) {
        try {
            $resumen = [];
            $estados = ['Solicitada', 'Anticipo pagado', 'Completada', 'Cancelada'];
            
            foreach ($estados as $estado) {
                // Obtener estadísticas básicas
                $stmt = $conexion->prepare("
                    SELECT 
                        COUNT(*) as total,
                        AVG(cantidad_personas) as promedio_personas,
                        SUM(total) as ingresos
                    FROM reservas 
                    WHERE estado = ?
                ");
                $stmt->bind_param("s", $estado);
                $stmt->execute();
                $stats = $stmt->get_result()->fetch_assoc();
                
                // Obtener tendencia de los últimos 7 días
                $stmt_tendencia = $conexion->prepare("
                    SELECT 
                        DATE(fecha_reserva) as fecha,
                        COUNT(*) as cantidad
                    FROM reservas 
                    WHERE estado = ? 
                    AND fecha_reserva >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    GROUP BY DATE(fecha_reserva)
                    ORDER BY fecha_reserva ASC
                ");
                $stmt_tendencia->bind_param("s", $estado);
                $stmt_tendencia->execute();
                $tendencia_result = $stmt_tendencia->get_result();
                
                $tendencia = [];
                for ($i = 6; $i >= 0; $i--) {
                    $fecha = date('Y-m-d', strtotime("-$i days"));
                    $tendencia[$fecha] = 0;
                }
                
                while ($row = $tendencia_result->fetch_assoc()) {
                    $tendencia[$row['fecha']] = (int)$row['cantidad'];
                }
                
                $resumen[$estado] = [
                    'total' => (int)$stats['total'],
                    'promedio_personas' => (float)$stats['promedio_personas'],
                    'ingresos' => (float)$stats['ingresos'],
                    'tendencia' => array_values($tendencia)
                ];
            }
            
            return ['success' => true, 'data' => $resumen];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener resumen: ' . $e->getMessage()];
        }
    }

    public static function crearReservaManual(
        $conexion,
        $nombre,
        $telefono,
        $email,
        $fecha_reserva,
        $hora_reserva,
        $cantidad_personas,
        $menu_type,
        $menu_options,
        $metodo_pago,
        $estado = 'Anticipo pagado'
    ) {
        $conexion->begin_transaction();
        try {
            // Precios de menú
            $menu_prices = [
                'desayuno' => 9.00,
                'almuerzo' => 14.50,
                'cena' => 16.50,
            ];

            $total_price = $menu_prices[$menu_type] * $cantidad_personas;

            // 1. Insertar o obtener usuario
            $stmt_usuario = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE telefono = ?");
            $stmt_usuario->bind_param("s", $telefono);
            $stmt_usuario->execute();
            $result_usuario = $stmt_usuario->get_result();
            $usuario = $result_usuario->fetch_assoc();

            if ($usuario) {
                $id_usuario = $usuario['id_usuario'];
                // Actualizar datos del usuario
                $stmt_update_usuario = $conexion->prepare("UPDATE usuarios SET nombre = ?, email = ? WHERE id_usuario = ?");
                $stmt_update_usuario->bind_param("ssi", $nombre, $email, $id_usuario);
                $stmt_update_usuario->execute();
            } else {
                $stmt_insert_usuario = $conexion->prepare("INSERT INTO usuarios (nombre, telefono, email) VALUES (?, ?, ?)");
                $stmt_insert_usuario->bind_param("sss", $nombre, $telefono, $email);
                $stmt_insert_usuario->execute();
                $id_usuario = $stmt_insert_usuario->insert_id;
            }

            // 2. Generar código de reserva único
            do {
                $codigo_reserva = ReservaControllers::generarCodigoReserva();
                $stmt_check_code = $conexion->prepare("SELECT COUNT(*) FROM reservas WHERE codigo_reserva = ?");
                $stmt_check_code->bind_param("s", $codigo_reserva);
                $stmt_check_code->execute();
                $count_code = $stmt_check_code->get_result()->fetch_row()[0];
            } while ($count_code > 0);

            // 3. Insertar reserva
            $info_adicional = json_encode($menu_options);
            $stmt_reserva = $conexion->prepare("INSERT INTO reservas (codigo_reserva, id_usuario, fecha_reserva, hora_reserva, cantidad_personas, total, info_adicional, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_reserva->bind_param("sisissds", $codigo_reserva, $id_usuario, $fecha_reserva, $hora_reserva, $cantidad_personas, $total_price, $info_adicional, $estado);
            $stmt_reserva->execute();
            $id_reserva = $stmt_reserva->insert_id;

            // 4. Insertar detalle_reserva
            $id_desayuno = null;
            $id_almuerzo = null;
            $id_cena = null;
            $subtotal = $menu_prices[$menu_type];

            switch ($menu_type) {
                case 'desayuno':
                    $bebida = $menu_options['bebida'] ?? '';
                    $pan = $menu_options['pan'] ?? '';
                    
                    $stmt_menu = $conexion->prepare("SELECT id_desayuno FROM desayuno WHERE bebida = ? AND pan = ?");
                    $stmt_menu->bind_param("ss", $bebida, $pan);
                    $stmt_menu->execute();
                    $result_menu = $stmt_menu->get_result();
                    $menu_item = $result_menu->fetch_assoc();

                    if ($menu_item) {
                        $id_desayuno = $menu_item['id_desayuno'];
                    } else {
                        $stmt_insert_menu = $conexion->prepare("INSERT INTO desayuno (bebida, pan, precio) VALUES (?, ?, ?)");
                        $stmt_insert_menu->bind_param("ssd", $bebida, $pan, $subtotal);
                        $stmt_insert_menu->execute();
                        $id_desayuno = $stmt_insert_menu->insert_id;
                    }
                    break;
                    
                case 'almuerzo':
                    $entrada = $menu_options['entrada'] ?? '';
                    $plato_fondo = $menu_options['plato_fondo'] ?? '';
                    $postre = $menu_options['postre'] ?? '';
                    $bebida = $menu_options['bebida'] ?? '';

                    $stmt_menu = $conexion->prepare("SELECT id_almuerzo FROM almuerzo WHERE entrada = ? AND plato_fondo = ? AND postre = ? AND bebida = ?");
                    $stmt_menu->bind_param("ssss", $entrada, $plato_fondo, $postre, $bebida);
                    $stmt_menu->execute();
                    $result_menu = $stmt_menu->get_result();
                    $menu_item = $result_menu->fetch_assoc();

                    if ($menu_item) {
                        $id_almuerzo = $menu_item['id_almuerzo'];
                    } else {
                        $stmt_insert_menu = $conexion->prepare("INSERT INTO almuerzo (entrada, plato_fondo, postre, bebida, precio) VALUES (?, ?, ?, ?, ?)");
                        $stmt_insert_menu->bind_param("ssssd", $entrada, $plato_fondo, $postre, $bebida, $subtotal);
                        $stmt_insert_menu->execute();
                        $id_almuerzo = $stmt_insert_menu->insert_id;
                    }
                    break;
                    
                case 'cena':
                    $plato = $menu_options['plato'] ?? '';
                    $postre = $menu_options['postre'] ?? '';
                    $bebida = $menu_options['bebida'] ?? '';

                    $stmt_menu = $conexion->prepare("SELECT id_cena FROM cena WHERE plato = ? AND postre = ? AND bebida = ?");
                    $stmt_menu->bind_param("sss", $plato, $postre, $bebida);
                    $stmt_menu->execute();
                    $result_menu = $stmt_menu->get_result();
                    $menu_item = $result_menu->fetch_assoc();

                    if ($menu_item) {
                        $id_cena = $menu_item['id_cena'];
                    } else {
                        $stmt_insert_menu = $conexion->prepare("INSERT INTO cena (plato, postre, bebida, precio) VALUES (?, ?, ?, ?)");
                        $stmt_insert_menu->bind_param("sssd", $plato, $postre, $bebida, $subtotal);
                        $stmt_insert_menu->execute();
                        $id_cena = $stmt_insert_menu->insert_id;
                    }
                    break;
            }

            $cantidad = 1;
            $stmt_detalle = $conexion->prepare("INSERT INTO detalle_reserva (id_reserva, id_desayuno, id_almuerzo, id_cena, cantidad, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_detalle->bind_param("iiiidd", $id_reserva, $id_desayuno, $id_almuerzo, $id_cena, $cantidad, $subtotal);
            $stmt_detalle->execute();

            $conexion->commit();
            return ['success' => true, 'codigo_reserva' => $codigo_reserva, 'message' => 'Reserva manual creada exitosamente.'];

        } catch (Exception $e) {
            $conexion->rollback();
            return ['success' => false, 'message' => 'Error al crear reserva manual: ' . $e->getMessage()];
        }
    }

    public static function cancelarReservasVencidas($conexion) {
        try {
            $stmt = $conexion->prepare("
                UPDATE reservas r
                LEFT JOIN registro_pago rp ON r.codigo_reserva = rp.codigo_reserva
                SET r.estado = 'Cancelada'
                WHERE r.estado = 'Solicitada' 
                AND rp.id_pago IS NULL 
                AND DATEDIFF(NOW(), r.fecha_creacion) > 2
            ");
            
            $stmt->execute();
            $canceladas = $stmt->affected_rows;
            
            return ['success' => true, 'message' => "Se cancelaron {$canceladas} reservas vencidas."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al cancelar reservas: ' . $e->getMessage()];
        }
    }
}
?>
