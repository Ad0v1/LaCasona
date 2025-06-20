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
    $fecha_limite = date('Y-m-d H:i:s', strtotime('-2 days'));
    
    $sql = "
        SELECT 
            r.*,
            u.nombre,
            u.telefono,
            u.email,
            DATEDIFF(NOW(), r.fecha_creacion) as dias_transcurridos
        FROM reservas r
        LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
        LEFT JOIN registro_pago rp ON r.codigo_reserva = rp.codigo_reserva
        WHERE r.estado = 'Solicitada' 
        AND r.fecha_creacion <= ?
        AND rp.codigo_reserva IS NULL
        ORDER BY r.fecha_creacion ASC
    ";
    
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "s", $fecha_limite);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $por_cancelar = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    echo json_encode($por_cancelar);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}

mysqli_close($con);
?>