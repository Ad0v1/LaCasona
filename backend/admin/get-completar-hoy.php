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
    $fecha_hoy = date('Y-m-d');
    
    $sql = "
        SELECT 
            r.*,
            u.nombre,
            u.telefono,
            u.email
        FROM reservas r
        LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
        WHERE r.estado = 'Anticipo pagado' 
        AND r.fecha_reserva = ?
        ORDER BY r.hora_reserva ASC
    ";
    
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "s", $fecha_hoy);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $completar_hoy = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    echo json_encode($completar_hoy);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}

mysqli_close($con);
?>