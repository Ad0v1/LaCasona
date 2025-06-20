<?php
session_start();

// Verificar si el administrador está logueado


// Incluir conexión a la base de datos
require_once __DIR__ . '/../includes/database.php';
$db = new Database();
$conn = $db->getConnection();

// Obtener mes y año actual o desde parámetros GET
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('n');
$año = isset($_GET['año']) ? (int)$_GET['año'] : date('Y');

// Validar mes y año
if ($mes < 1 || $mes > 12) $mes = date('n');
if ($año < 2020 || $año > 2030) $año = date('Y');

// Calcular navegación del calendario
$mes_anterior = $mes - 1;
$año_anterior = $año;
if ($mes_anterior < 1) {
    $mes_anterior = 12;
    $año_anterior--;
}

$mes_siguiente = $mes + 1;
$año_siguiente = $año;
if ($mes_siguiente > 12) {
    $mes_siguiente = 1;
    $año_siguiente++;
}

// Obtener reservas del mes actual con información completa
$primer_dia = "$año-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01";
$ultimo_dia = date('Y-m-t', strtotime($primer_dia));

$sql_reservas = "
    SELECT 
        r.id_reserva,
        r.codigo_reserva,
        r.fecha_reserva,
        r.hora_reserva,
        r.cantidad_personas,
        r.total,
        r.estado,
        r.info_adicional,
        r.fecha_creacion,
        u.nombre as nombre_usuario,
        u.telefono,
        u.email,
        -- Información del menú
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
        -- Información de pago
        rp.metodo_pago,
        rp.nombre_titular,
        rp.numero_operacion,
        rp.banco,
        rp.monto_pagado,
        rp.tipo_comprobante,
        rp.comprobante_url,
        rp.estado_verificacion,
        rp.fecha_pago
    FROM reservas r
    LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
    LEFT JOIN detalle_reserva dr ON r.id_reserva = dr.id_reserva
    LEFT JOIN desayuno d ON dr.id_desayuno = d.id_desayuno
    LEFT JOIN almuerzo a ON dr.id_almuerzo = a.id_almuerzo
    LEFT JOIN cena c ON dr.id_cena = c.id_cena
    LEFT JOIN registro_pago rp ON r.codigo_reserva = rp.codigo_reserva
    WHERE r.fecha_reserva BETWEEN ? AND ?
    ORDER BY r.fecha_reserva, r.hora_reserva
";

$stmt = $conn->prepare($sql_reservas);
$stmt->bind_param("ss", $primer_dia, $ultimo_dia);
$stmt->execute();
$result_reservas = $stmt->get_result();


// Organizar reservas por fecha
$reservas_por_fecha = [];
while ($reserva = mysqli_fetch_assoc($result_reservas)) {
    $fecha = $reserva['fecha_reserva'];
    if (!isset($reservas_por_fecha[$fecha])) {
        $reservas_por_fecha[$fecha] = [];
    }
    $reservas_por_fecha[$fecha][] = $reserva;
}

// Obtener estadísticas del mes
$sql_stats = "
    SELECT 
        COUNT(*) as total_reservas,
        SUM(CASE WHEN estado = 'Solicitada' THEN 1 ELSE 0 END) as solicitadas,
        SUM(CASE WHEN estado = 'Anticipo pagado' THEN 1 ELSE 0 END) as anticipo_pagado,
        SUM(CASE WHEN estado = 'Completada' THEN 1 ELSE 0 END) as completadas,
        SUM(CASE WHEN estado = 'Cancelada' THEN 1 ELSE 0 END) as canceladas,
        SUM(total) as ingresos_total
    FROM reservas 
    WHERE fecha_reserva BETWEEN ? AND ?
";

$stmt_stats = mysqli_prepare($con, $sql_stats);
mysqli_stmt_bind_param($stmt_stats, "ss", $primer_dia, $ultimo_dia);
mysqli_stmt_execute($stmt_stats);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_stats));

// Obtener gráficos de gestión operativa
// 1. Solicitudes en espera de confirmación de pago
$sql_pendientes_pago = "
    SELECT r.*, rp.fecha_pago, rp.estado_verificacion
    FROM reservas r
    INNER JOIN registro_pago rp ON r.codigo_reserva = rp.codigo_reserva
    WHERE r.estado = 'Solicitada' AND rp.estado_verificacion = 'pendiente'
    ORDER BY rp.fecha_pago ASC
";
$result_pendientes = mysqli_query($con, $sql_pendientes_pago);
$pendientes_confirmacion = mysqli_fetch_all($result_pendientes, MYSQLI_ASSOC);

// 2. Solicitudes por cancelar automáticamente (más de 2 días sin pago)
$fecha_limite = date('Y-m-d', strtotime('-2 days'));
$sql_por_cancelar = "
    SELECT r.*, u.nombre, u.telefono
    FROM reservas r
    LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
    LEFT JOIN registro_pago rp ON r.codigo_reserva = rp.codigo_reserva
    WHERE r.estado = 'Solicitada' 
    AND r.fecha_creacion <= '$fecha_limite'
    AND rp.codigo_reserva IS NULL
    ORDER BY r.fecha_creacion ASC
";
$result_cancelar = mysqli_query($con, $sql_por_cancelar);
$por_cancelar = mysqli_fetch_all($result_cancelar, MYSQLI_ASSOC);

// 3. Solicitudes a completar (día actual)
$fecha_hoy = date('Y-m-d');
$sql_completar_hoy = "
    SELECT r.*, u.nombre, u.telefono
    FROM reservas r
    LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
    WHERE r.estado = 'Anticipo pagado' 
    AND r.fecha_reserva = '$fecha_hoy'
    ORDER BY r.hora_reserva ASC
";
$result_completar = mysqli_query($con, $sql_completar_hoy);
$completar_hoy = mysqli_fetch_all($result_completar, MYSQLI_ASSOC);

// Nombres de meses
$nombres_meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

// Función para obtener el color según el estado
function getColorEstado($estado) {
    switch ($estado) {
        case 'Solicitada': return '#ffc107'; // Amarillo
        case 'Anticipo pagado': return '#17a2b8'; // Azul
        case 'Completada': return '#28a745'; // Verde
        case 'Cancelada': return '#dc3545'; // Rojo
        default: return '#6c757d'; // Gris
    }
}

// Procesar acciones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'cambiar_estado':
            $id_reserva = (int)$_POST['id_reserva'];
            $nuevo_estado = $_POST['nuevo_estado'];
            
            $sql_update = "UPDATE reservas SET estado = ?, fecha_actualizacion = NOW() WHERE id_reserva = ?";
            $stmt_update = mysqli_prepare($con, $sql_update);
            mysqli_stmt_bind_param($stmt_update, "si", $nuevo_estado, $id_reserva);
            
            if (mysqli_stmt_execute($stmt_update)) {
                echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado']);
            }
            exit;
            
        case 'verificar_pago':
            $codigo_reserva = $_POST['codigo_reserva'];
            
            // Actualizar estado de verificación del pago
            $sql_verificar = "UPDATE registro_pago SET estado_verificacion = 'verificado', fecha_verificacion = NOW(), verificado_por = ? WHERE codigo_reserva = ?";
            $stmt_verificar = mysqli_prepare($con, $sql_verificar);
            mysqli_stmt_bind_param($stmt_verificar, "is", $_SESSION['user_id'], $codigo_reserva);
            
            if (mysqli_stmt_execute($stmt_verificar)) {
                // Actualizar estado de la reserva
                $sql_reserva = "UPDATE reservas SET estado = 'Anticipo pagado' WHERE codigo_reserva = ?";
                $stmt_reserva = mysqli_prepare($con, $sql_reserva);
                mysqli_stmt_bind_param($stmt_reserva, "s", $codigo_reserva);
                mysqli_stmt_execute($stmt_reserva);
                
                echo json_encode(['success' => true, 'message' => 'Pago verificado y reserva actualizada']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al verificar el pago']);
            }
            exit;
            
        case 'cancelar_reserva':
            $id_reserva = (int)$_POST['id_reserva'];
            
            $sql_cancelar = "UPDATE reservas SET estado = 'Cancelada', fecha_actualizacion = NOW() WHERE id_reserva = ?";
            $stmt_cancelar = mysqli_prepare($con, $sql_cancelar);
            mysqli_stmt_bind_param($stmt_cancelar, "i", $id_reserva);
            
            if (mysqli_stmt_execute($stmt_cancelar)) {
                echo json_encode(['success' => true, 'message' => 'Reserva cancelada correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al cancelar la reserva']);
            }
            exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Calendario de Reservas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand text-white fw-bold" href="#">
                <i class="fas fa-calendar-alt me-2"></i>
                Panel de Administración - La Casona
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text text-white me-3">
                    <i class="fas fa-user me-1"></i>
                    Bienvenido, <?php echo htmlspecialchars($_SESSION["username"]); ?>
                </span>
                <button class="btn btn-outline-light btn-sm me-2" onclick="mostrarFormularioReserva()">
                    <i class="fas fa-plus me-1"></i>
                    Nueva Reserva
                </button>
                <a class="btn btn-outline-light btn-sm" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    Cerrar Sesión
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Gráficos de Gestión Operativa -->
            <div class="col-12 mb-4">
                <div class="row">
                    <!-- Solicitudes pendientes de confirmación -->
                    <div class="col-md-4 mb-3">
                        <div class="stats-card bg-warning">
                            <h6 class="text-white">
                                <i class="fas fa-clock me-2"></i>
                                Pendientes de Confirmación
                            </h6>
                            <div class="stat-number text-white"><?php echo count($pendientes_confirmacion); ?></div>
                            <button class="btn btn-light btn-sm mt-2" onclick="mostrarPendientesConfirmacion()">
                                Ver Detalles
                            </button>
                        </div>
                    </div>
                    
                    <!-- Solicitudes por cancelar -->
                    <div class="col-md-4 mb-3">
                        <div class="stats-card bg-danger">
                            <h6 class="text-white">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Por Cancelar (>2 días)
                            </h6>
                            <div class="stat-number text-white"><?php echo count($por_cancelar); ?></div>
                            <button class="btn btn-light btn-sm mt-2" onclick="mostrarPorCancelar()">
                                Ver Detalles
                            </button>
                        </div>
                    </div>
                    
                    <!-- Solicitudes a completar hoy -->
                    <div class="col-md-4 mb-3">
                        <div class="stats-card bg-success">
                            <h6 class="text-white">
                                <i class="fas fa-check-circle me-2"></i>
                                A Completar Hoy
                            </h6>
                            <div class="stat-number text-white"><?php echo count($completar_hoy); ?></div>
                            <button class="btn btn-light btn-sm mt-2" onclick="mostrarCompletarHoy()">
                                Ver Detalles
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas del mes -->
            <div class="col-12 mb-4">
                <div class="stats-card">
                    <h5 class="mb-3">
                        <i class="fas fa-chart-bar me-2"></i>
                        Estadísticas de <?php echo $nombres_meses[$mes] . ' ' . $año; ?>
                    </h5>
                    <div class="row">
                        <div class="col-md-2 col-6">
                            <div class="stat-item">
                                <div class="stat-number text-primary"><?php echo $stats['total_reservas']; ?></div>
                                <div class="stat-label">Total Reservas</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="stat-item">
                                <div class="stat-number text-warning"><?php echo $stats['solicitadas']; ?></div>
                                <div class="stat-label">Solicitadas</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="stat-item">
                                <div class="stat-number text-info"><?php echo $stats['anticipo_pagado']; ?></div>
                                <div class="stat-label">Con Anticipo</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="stat-item">
                                <div class="stat-number text-success"><?php echo $stats['completadas']; ?></div>
                                <div class="stat-label">Completadas</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="stat-item">
                                <div class="stat-number text-danger"><?php echo $stats['canceladas']; ?></div>
                                <div class="stat-label">Canceladas</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="stat-item">
                                <div class="stat-number text-success">S/. <?php echo number_format($stats['ingresos_total'], 2); ?></div>
                                <div class="stat-label">Ingresos</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendario -->
            <div class="col-12">
                <div class="calendar-container">
                    <div class="calendar-header">
                        <div class="calendar-nav">
                            <a href="?mes=<?php echo $mes_anterior; ?>&año=<?php echo $año_anterior; ?>" class="btn">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </a>
                            <h3 class="mb-0"><?php echo $nombres_meses[$mes] . ' ' . $año; ?></h3>
                            <a href="?mes=<?php echo $mes_siguiente; ?>&año=<?php echo $año_siguiente; ?>" class="btn">
                                Siguiente <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                        
                        <!-- Leyenda de colores -->
                        <div class="color-legend">
                            <span class="legend-item">
                                <span class="color-box" style="background-color: #ffc107;"></span>
                                Solicitada
                            </span>
                            <span class="legend-item">
                                <span class="color-box" style="background-color: #17a2b8;"></span>
                                Anticipo Pagado
                            </span>
                            <span class="legend-item">
                                <span class="color-box" style="background-color: #28a745;"></span>
                                Completada
                            </span>
                            <span class="legend-item">
                                <span class="color-box" style="background-color: #dc3545;"></span>
                                Cancelada
                            </span>
                        </div>
                    </div>
                    
                    <table class="calendar-table">
                        <thead>
                            <tr>
                                <th>Domingo</th>
                                <th>Lunes</th>
                                <th>Martes</th>
                                <th>Miércoles</th>
                                <th>Jueves</th>
                                <th>Viernes</th>
                                <th>Sábado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Calcular el primer día del mes y cuántos días tiene
                            $primer_dia_mes = mktime(0, 0, 0, $mes, 1, $año);
                            $dias_en_mes = date('t', $primer_dia_mes);
                            $dia_semana_inicio = date('w', $primer_dia_mes);
                            
                            // Calcular días del mes anterior para completar la primera semana
                            $mes_anterior_dias = date('t', mktime(0, 0, 0, $mes - 1, 1, $año));
                            
                            $dia_actual = 1;
                            $fecha_hoy = date('Y-m-d');
                            
                            for ($semana = 0; $semana < 6; $semana++) {
                                echo "<tr>";
                                
                                for ($dia_semana = 0; $dia_semana < 7; $dia_semana++) {
                                    if ($semana == 0 && $dia_semana < $dia_semana_inicio) {
                                        // Días del mes anterior
                                        $dia_mostrar = $mes_anterior_dias - ($dia_semana_inicio - $dia_semana - 1);
                                        echo "<td class='other-month'>";
                                        echo "<div class='calendar-day'>$dia_mostrar</div>";
                                        echo "</td>";
                                    } elseif ($dia_actual <= $dias_en_mes) {
                                        // Días del mes actual
                                        $fecha_completa = sprintf("%04d-%02d-%02d", $año, $mes, $dia_actual);
                                        $es_hoy = ($fecha_completa == $fecha_hoy);
                                        
                                        echo "<td>";
                                        echo "<div class='calendar-day" . ($es_hoy ? " today" : "") . "'>$dia_actual</div>";
                                        
                                        // Mostrar reservas del día
                                        if (isset($reservas_por_fecha[$fecha_completa])) {
                                            foreach ($reservas_por_fecha[$fecha_completa] as $reserva) {
                                                $color = getColorEstado($reserva['estado']);
                                                echo "<div class='reserva-item' style='background-color: $color;' 
                                                      onclick='mostrarDetalleReserva(" . json_encode($reserva) . ")'>";
                                                echo date('H:i', strtotime($reserva['hora_reserva'])) . " - " . $reserva['cantidad_personas'] . "p";
                                                echo "</div>";
                                            }
                                        }
                                        
                                        echo "</td>";
                                        $dia_actual++;
                                    } else {
                                        // Días del mes siguiente
                                        $dia_siguiente = $dia_actual - $dias_en_mes;
                                        echo "<td class='other-month'>";
                                        echo "<div class='calendar-day'>$dia_siguiente</div>";
                                        echo "</td>";
                                        $dia_actual++;
                                    }
                                }
                                
                                echo "</tr>";
                                
                                if ($dia_actual > $dias_en_mes && $semana > 3) break;
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales -->
    <?php include 'modales-admin.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
</body>
</html>

<?php
mysqli_close($con);
?>