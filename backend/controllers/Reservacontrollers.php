<?php

class ReservaControllers {
    
    public static function generarCodigoReserva($length = 8) {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function validarEmail($email) {
        if (empty($email)) {
            return ['valid' => false, 'message' => 'El correo electrónico es obligatorio.'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'El formato del correo electrónico no es válido.'];
        }
        
        if (!preg_match('/@gmail\.com$/', $email)) {
            return ['valid' => false, 'message' => 'El correo debe terminar en @gmail.com'];
        }
        
        return ['valid' => true, 'message' => ''];
    }

    public static function validarFechaReserva($fecha_reserva) {
        $fecha_actual = new DateTime();
        $fecha_minima = clone $fecha_actual;
        $fecha_minima->add(new DateInterval('P2D')); // Agregar 2 días
        
        $fecha_seleccionada = new DateTime($fecha_reserva);
        
        if ($fecha_seleccionada < $fecha_minima) {
            return [
                'valid' => false, 
                'message' => 'La fecha debe ser mínimo 2 días después de hoy (' . $fecha_minima->format('d/m/Y') . ')'
            ];
        }
        
        return ['valid' => true, 'message' => ''];
    }

    public static function obtenerHorariosDisponibles() {
        $horarios = [];
        
        // Desayuno: 7:00 AM - 12:00 PM (intervalos de 30 min)
        for ($hour = 7; $hour < 12; $hour++) {
            $horarios[sprintf('%02d:00', $hour)] = [
                'hora' => sprintf('%02d:00', $hour),
                'display' => ($hour <= 12 ? $hour : $hour - 12) . ':00 ' . ($hour < 12 ? 'AM' : 'PM'),
                'tipo' => 'Desayuno'
            ];
            $horarios[sprintf('%02d:30', $hour)] = [
                'hora' => sprintf('%02d:30', $hour),
                'display' => ($hour <= 12 ? $hour : $hour - 12) . ':30 ' . ($hour < 12 ? 'AM' : 'PM'),
                'tipo' => 'Desayuno'
            ];
        }
        
        // Almuerzo: 12:00 PM - 6:00 PM (intervalos de 30 min)
        for ($hour = 12; $hour < 18; $hour++) {
            $horarios[sprintf('%02d:00', $hour)] = [
                'hora' => sprintf('%02d:00', $hour),
                'display' => ($hour <= 12 ? $hour : $hour - 12) . ':00 ' . ($hour < 12 ? 'AM' : 'PM'),
                'tipo' => 'Almuerzo'
            ];
            $horarios[sprintf('%02d:30', $hour)] = [
                'hora' => sprintf('%02d:30', $hour),
                'display' => ($hour <= 12 ? $hour : $hour - 12) . ':30 ' . ($hour < 12 ? 'AM' : 'PM'),
                'tipo' => 'Almuerzo'
            ];
        }
        
        // Cena: 6:00 PM - 12:00 AM (intervalos de 30 min)
        for ($hour = 18; $hour < 24; $hour++) {
            $horarios[sprintf('%02d:00', $hour)] = [
                'hora' => sprintf('%02d:00', $hour),
                'display' => ($hour <= 12 ? $hour : $hour - 12) . ':00 ' . ($hour < 12 ? 'AM' : 'PM'),
                'tipo' => 'Cena'
            ];
            $horarios[sprintf('%02d:30', $hour)] = [
                'hora' => sprintf('%02d:30', $hour),
                'display' => ($hour <= 12 ? $hour : $hour - 12) . ':30 ' . ($hour < 12 ? 'AM' : 'PM'),
                'tipo' => 'Cena'
            ];
        }
        
        return $horarios;
    }

    public static function verificarDisponibilidadHorario($conexion, $fecha_reserva, $hora_reserva, $cantidad_personas) {
        $stmt = $conexion->prepare("
            SELECT COALESCE(SUM(cantidad_personas), 0) as total_personas 
            FROM reservas 
            WHERE fecha_reserva = ? 
            AND hora_reserva = ? 
            AND estado IN ('Solicitada', 'Anticipo pagado', 'Completada')
        ");
        
        $stmt->bind_param("ss", $fecha_reserva, $hora_reserva);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $personas_reservadas = $row['total_personas'];
        $disponibles = 250 - $personas_reservadas;
        
        if ($cantidad_personas > $disponibles) {
            return [
                'disponible' => false,
                'message' => "Solo quedan {$disponibles} lugares disponibles para esa hora. Solicitaste {$cantidad_personas} personas.",
                'disponibles' => $disponibles
            ];
        }
        
        return [
            'disponible' => true,
            'message' => "Horario disponible. Quedan " . ($disponibles - $cantidad_personas) . " lugares después de tu reserva.",
            'disponibles' => $disponibles
        ];
    }

    public static function crearReservaCompleta(
        $conexion,
        $nombre,
        $telefono,
        $email,
        $fecha_reserva,
        $hora_reserva,
        $cantidad_personas,
        $menu_type,
        $total_price,
        $info_adicional,
        $menu_options
    ) {
        $conexion->begin_transaction();
        try {
            // Validar email si se proporciona
            if (!empty($email)) {
                $email_validation = self::validarEmail($email);
                if (!$email_validation['valid']) {
                    throw new Exception($email_validation['message']);
                }
            }

            // Validar fecha de reserva
            $fecha_validation = self::validarFechaReserva($fecha_reserva);
            if (!$fecha_validation['valid']) {
                throw new Exception($fecha_validation['message']);
            }

            // Verificar disponibilidad de horario
            $disponibilidad = self::verificarDisponibilidadHorario($conexion, $fecha_reserva, $hora_reserva, $cantidad_personas);
            if (!$disponibilidad['disponible']) {
                throw new Exception($disponibilidad['message']);
            }

            // 1. Insertar o obtener id_usuario
            $stmt_usuario = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE telefono = ?");
            $stmt_usuario->bind_param("s", $telefono);
            $stmt_usuario->execute();
            $result_usuario = $stmt_usuario->get_result();
            $usuario = $result_usuario->fetch_assoc();
            $id_usuario = null;

            if ($usuario) {
                $id_usuario = $usuario['id_usuario'];
                // Actualizar datos del usuario si es necesario
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
                $codigo_reserva = self::generarCodigoReserva();
                $stmt_check_code = $conexion->prepare("SELECT COUNT(*) FROM reservas WHERE codigo_reserva = ?");
                $stmt_check_code->bind_param("s", $codigo_reserva);
                $stmt_check_code->execute();
                $count_code = $stmt_check_code->get_result()->fetch_row()[0];
            } while ($count_code > 0);

            // 3. Insertar reserva
            $stmt_reserva = $conexion->prepare("INSERT INTO reservas (codigo_reserva, id_usuario, fecha_reserva, hora_reserva, cantidad_personas, total, info_adicional) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_reserva->bind_param("sisssds", $codigo_reserva, $id_usuario, $fecha_reserva, $hora_reserva, $cantidad_personas, $total_price, $info_adicional);
            $stmt_reserva->execute();
            $id_reserva = $stmt_reserva->insert_id;

            // 4. Insertar detalle_reserva según el tipo de menú
            $id_desayuno = null;
            $id_almuerzo = null;
            $id_cena = null;
            $cantidad = 1;
            $subtotal = 0;

            switch ($menu_type) {
                case 'desayuno':
                    $bebida = $menu_options['desayunoBebida'] ?? '';
                    $pan = $menu_options['desayunoPan'] ?? '';
                    
                    $stmt_menu = $conexion->prepare("SELECT id_desayuno, precio FROM desayuno WHERE bebida = ? AND pan = ?");
                    $stmt_menu->bind_param("ss", $bebida, $pan);
                    $stmt_menu->execute();
                    $result_menu = $stmt_menu->get_result();
                    $menu_item = $result_menu->fetch_assoc();

                    if ($menu_item) {
                        $id_desayuno = $menu_item['id_desayuno'];
                        $subtotal = $menu_item['precio'];
                    } else {
                        $precio_desayuno = 9.00;
                        $stmt_insert_menu = $conexion->prepare("INSERT INTO desayuno (bebida, pan, precio) VALUES (?, ?, ?)");
                        $stmt_insert_menu->bind_param("ssd", $bebida, $pan, $precio_desayuno);
                        $stmt_insert_menu->execute();
                        $id_desayuno = $stmt_insert_menu->insert_id;
                        $subtotal = $precio_desayuno;
                    }
                    break;
                    
                case 'almuerzo':
                    $entrada = $menu_options['almuerzoEntrada'] ?? '';
                    $plato_fondo = $menu_options['almuerzoFondo'] ?? '';
                    $postre = $menu_options['almuerzoPostre'] ?? '';
                    $bebida = $menu_options['almuerzoBebida'] ?? '';

                    $stmt_menu = $conexion->prepare("SELECT id_almuerzo, precio FROM almuerzo WHERE entrada = ? AND plato_fondo = ? AND postre = ? AND bebida = ?");
                    $stmt_menu->bind_param("ssss", $entrada, $plato_fondo, $postre, $bebida);
                    $stmt_menu->execute();
                    $result_menu = $stmt_menu->get_result();
                    $menu_item = $result_menu->fetch_assoc();

                    if ($menu_item) {
                        $id_almuerzo = $menu_item['id_almuerzo'];
                        $subtotal = $menu_item['precio'];
                    } else {
                        $precio_almuerzo = 14.50;
                        $stmt_insert_menu = $conexion->prepare("INSERT INTO almuerzo (entrada, plato_fondo, postre, bebida, precio) VALUES (?, ?, ?, ?, ?)");
                        $stmt_insert_menu->bind_param("ssssd", $entrada, $plato_fondo, $postre, $bebida, $precio_almuerzo);
                        $stmt_insert_menu->execute();
                        $id_almuerzo = $stmt_insert_menu->insert_id;
                        $subtotal = $precio_almuerzo;
                    }
                    break;
                    
                case 'cena':
                    $plato = $menu_options['cenaPlato'] ?? '';
                    $postre = $menu_options['cenaPostre'] ?? '';
                    $bebida = $menu_options['cenaBebida'] ?? '';

                    $stmt_menu = $conexion->prepare("SELECT id_cena, precio FROM cena WHERE plato = ? AND postre = ? AND bebida = ?");
                    $stmt_menu->bind_param("sss", $plato, $postre, $bebida);
                    $stmt_menu->execute();
                    $result_menu = $stmt_menu->get_result();
                    $menu_item = $result_menu->fetch_assoc();

                    if ($menu_item) {
                        $id_cena = $menu_item['id_cena'];
                        $subtotal = $menu_item['precio'];
                    } else {
                        $precio_cena = 16.50;
                        $stmt_insert_menu = $conexion->prepare("INSERT INTO cena (plato, postre, bebida, precio) VALUES (?, ?, ?, ?)");
                        $stmt_insert_menu->bind_param("sssd", $plato, $postre, $bebida, $precio_cena);
                        $stmt_insert_menu->execute();
                        $id_cena = $stmt_insert_menu->insert_id;
                        $subtotal = $precio_cena;
                    }
                    break;
            }

            $stmt_detalle = $conexion->prepare("INSERT INTO detalle_reserva (id_reserva, id_desayuno, id_almuerzo, id_cena, cantidad, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_detalle->bind_param("iiiidd", $id_reserva, $id_desayuno, $id_almuerzo, $id_cena, $cantidad, $subtotal);
            $stmt_detalle->execute();

            $conexion->commit();
            return ['success' => true, 'codigo_reserva' => $codigo_reserva, 'message' => 'Reserva creada exitosamente.'];

        } catch (mysqli_sql_exception $e) {
            $conexion->rollback();
            return ['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()];
        } catch (Exception $e) {
            $conexion->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // MÉTODO CORREGIDO: NO cambia automáticamente el estado de la reserva
    public static function registrarPago(
        $conexion,
        $codigo_reserva,
        $metodo_pago,
        $nombre_titular,
        $numero_operacion,
        $codigo_seguridad,
        $banco,
        $monto_pagado,
        $tipo_comprobante,
        $ruc_factura,
        $comprobante_url,
        $comentarios
    ) {
        $conexion->begin_transaction();
        try {
            // Verificar que la reserva existe y está en estado válido
            $stmt_check = $conexion->prepare("SELECT estado, fecha_creacion FROM reservas WHERE codigo_reserva = ?");
            $stmt_check->bind_param("s", $codigo_reserva);
            $stmt_check->execute();
            $result = $stmt_check->get_result();
            $reserva = $result->fetch_assoc();

            if (!$reserva) {
                throw new Exception("No se encontró la reserva con el código proporcionado.");
            }

            // Verificar que no han pasado más de 2 días desde la creación de la reserva
            $fecha_creacion = new DateTime($reserva['fecha_creacion']);
            $fecha_limite = clone $fecha_creacion;
            $fecha_limite->add(new DateInterval('P2D'));
            $fecha_actual = new DateTime();

            if ($fecha_actual > $fecha_limite) {
                // Marcar reserva como caducada
                $stmt_caducar = $conexion->prepare("UPDATE reservas SET estado = 'Cancelada' WHERE codigo_reserva = ?");
                $stmt_caducar->bind_param("s", $codigo_reserva);
                $stmt_caducar->execute();
                
                throw new Exception("El plazo para registrar el pago ha vencido. La reserva ha sido cancelada.");
            }

            // Validar número de operación según método de pago
            if ($metodo_pago === 'yape') {
                if (!preg_match('/^\d{8}$/', $numero_operacion)) {
                    throw new Exception("El número de operación de Yape debe tener 8 dígitos.");
                }
                if (!empty($codigo_seguridad) && !preg_match('/^\d{3}$/', $codigo_seguridad)) {
                    throw new Exception("El código de seguridad debe tener 3 dígitos.");
                }
            } elseif ($metodo_pago === 'transferencia') {
                if (!preg_match('/^\d{8,11}$/', $numero_operacion)) {
                    throw new Exception("El número de operación de transferencia debe tener entre 8 y 11 dígitos.");
                }
            }

            // Verificar si ya existe un registro de pago para esta reserva
            $stmt_check_pago = $conexion->prepare("SELECT COUNT(*) FROM registro_pago WHERE codigo_reserva = ?");
            $stmt_check_pago->bind_param("s", $codigo_reserva);
            $stmt_check_pago->execute();
            $pago_existente = $stmt_check_pago->get_result()->fetch_row()[0];

            if ($pago_existente > 0) {
                throw new Exception("Ya existe un registro de pago para esta reserva.");
            }

            // Insertar registro de pago
            $stmt_pago = $conexion->prepare("INSERT INTO registro_pago (codigo_reserva, metodo_pago, nombre_titular, numero_operacion, codigo_seguridad, banco, monto_pagado, tipo_comprobante, ruc_factura, comprobante_url, comentarios) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_pago->bind_param("ssssssdssss", $codigo_reserva, $metodo_pago, $nombre_titular, $numero_operacion, $codigo_seguridad, $banco, $monto_pagado, $tipo_comprobante, $ruc_factura, $comprobante_url, $comentarios);
            $stmt_pago->execute();

            // IMPORTANTE: NO cambiar el estado de la reserva automáticamente
            // El administrador lo hará manualmente después de verificar el pago

            $conexion->commit();
            return ['success' => true, 'message' => 'Pago registrado exitosamente. El administrador verificará tu pago y actualizará el estado de tu reserva.'];
        } catch (mysqli_sql_exception $e) {
            $conexion->rollback();
            return ['success' => false, 'message' => 'Error en la base de datos al registrar pago: ' . $e->getMessage()];
        } catch (Exception $e) {
            $conexion->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function obtenerInformacionPago() {
        return [
            'yape' => [
                'numero' => '980436234',
                'titular' => 'Casona Kawai',
                'nombre_completo' => 'Restaurante La Casona Kawai'
            ],
            'transferencia' => [
                'titular' => 'Casona Kawai',
                'numero_cuenta' => '123-456789-0-12',
                'cci' => '00312345678901234567',
                'banco' => 'Banco de Crédito del Perú'
            ]
        ];
    }
}
?>
