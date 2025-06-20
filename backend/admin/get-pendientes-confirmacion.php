<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION["admin_logueado"]) || $_SESSION["admin_logueado"] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

include "../includes/database.php";

header('Content-Type: application/json');

try {
    $sql = "
        SELECT 
            r.*,
            u.nombre,
            u.telefono,
            u.email,
            rp.fecha_pago,
            rp.estado_verificacion,
            rp.metodo_pago,
            rp.monto_pagado
        FROM reservas r
        INNER JOIN registro_pago rp ON r.codigo_reserva = rp.codigo_reserva
        LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
        WHERE r.estado = 'Solicitada' 
        AND rp.estado_verificacion = 'pendiente'
        ORDER BY rp.fecha_pago ASC
    ";
    
    $result = mysqli_query($con, $sql);
    $pendientes = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    echo json_encode($pendientes);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}

mysqli_close($con);
?>