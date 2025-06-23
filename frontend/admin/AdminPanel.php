<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
        require_once '../includes/database.php';
        require_once '../controllers/AdminControllers.php';
        
        $usuario = $_POST['usuario'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $auth_result = AdminControllers::autenticarAdmin($usuario, $password);
        
        if ($auth_result['success']) {
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_usuario'] = $usuario;
            header('Location: AdminPanel.php');
            exit();
        } else {
            $login_error = $auth_result['message'];
        }
    }
    
    // Mostrar formulario de login si no está autenticado
    include 'Login.php';
    exit();
}

// Procesar acciones del administrador
require_once '../includes/database.php';
require_once '../controllers/AdminControllers.php';
require_once '../controllers/ReservaControllers.php';

$db = new Database();
$conexion = $db->getConnection();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'cambiar_estado':
            $id_reserva = (int)($_POST['id_reserva'] ?? 0);
            $nuevo_estado = $_POST['nuevo_estado'] ?? '';
            
            $result = AdminControllers::cambiarEstadoReserva($conexion, $id_reserva, $nuevo_estado);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            break;
            
        case 'cancelar_vencidas':
            $result = AdminControllers::cancelarReservasVencidas($conexion);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            break;
            
        case 'crear_reserva_manual':
            $nombre = trim($_POST['nombre'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $fecha_reserva = $_POST['fecha_reserva'] ?? '';
            $hora_reserva = $_POST['hora_reserva'] ?? '';
            $cantidad_personas = (int)($_POST['cantidad_personas'] ?? 0);
            $menu_type = $_POST['menu_type'] ?? '';
            $info_adicional = trim($_POST['info_adicional'] ?? '');
            
            // Construir opciones de menú
            $menu_options = [];
            switch ($menu_type) {
                case 'desayuno':
                    $menu_options['bebida'] = $_POST['desayuno_bebida'] ?? '';
                    $menu_options['pan'] = $_POST['desayuno_pan'] ?? '';
                    break;
                case 'almuerzo':
                    $menu_options['entrada'] = $_POST['almuerzo_entrada'] ?? '';
                    $menu_options['plato_fondo'] = $_POST['almuerzo_fondo'] ?? '';
                    $menu_options['postre'] = $_POST['almuerzo_postre'] ?? '';
                    $menu_options['bebida'] = $_POST['almuerzo_bebida'] ?? '';
                    break;
                case 'cena':
                    $menu_options['plato'] = $_POST['cena_plato'] ?? '';
                    $menu_options['postre'] = $_POST['cena_postre'] ?? '';
                    $menu_options['bebida'] = $_POST['cena_bebida'] ?? '';
                    break;
            }
            
            // Calcular precio total
            $menu_prices = [
                'desayuno' => 9.00,
                'almuerzo' => 14.50,
                'cena' => 16.50,
            ];
            $total_price = $menu_prices[$menu_type] * $cantidad_personas;
            
            $result = ReservaControllers::crearReservaCompleta(
                $conexion, $nombre, $telefono, $email, $fecha_reserva, 
                $hora_reserva, $cantidad_personas, $menu_type, $total_price, $info_adicional, $menu_options
            );
            
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
            break;
            
        case 'logout':
            session_destroy();
            header('Location: AdminPanel.php');
            exit();
            break;
    }
}

// Obtener datos para el panel
$reservas_result = AdminControllers::obtenerTodasLasReservas($conexion);
$reservas = $reservas_result['success'] ? $reservas_result['data'] : [];

// Función para obtener reservas por fecha
function obtenerReservasPorFecha($conexion, $fecha) {
    try {
        $stmt = $conexion->prepare("
            SELECT 
                r.id_reserva,
                r.codigo_reserva,
                r.hora_reserva,
                r.cantidad_personas,
                r.total,
                r.estado,
                u.nombre as cliente_nombre,
                u.telefono as cliente_telefono
            FROM reservas r
            INNER JOIN usuarios u ON r.id_usuario = u.id_usuario
            WHERE DATE(r.fecha_reserva) = ?
            ORDER BY r.hora_reserva ASC
        ");
        
        $stmt->bind_param("s", $fecha);
        $stmt->execute();
        $result = $stmt->get_result();
        $reservas = [];
        
        while ($row = $result->fetch_assoc()) {
            $reservas[] = $row;
        }
        
        return $reservas;
    } catch (Exception $e) {
        return [];
    }
}

// Función para generar intervalos de 30 minutos
function generarIntervalosHorarios() {
    $intervalos = [];
    for ($hora = 8; $hora <= 23; $hora++) {
        for ($minuto = 0; $minuto < 60; $minuto += 30) {
            $tiempo = sprintf("%02d:%02d", $hora, $minuto);
            $intervalos[] = $tiempo;
        }
    }
    return $intervalos;
}

// Datos para el formulario manual
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

$horarios_disponibles = [
    '08:00' => ['display' => '8:00 AM', 'tipo' => 'Desayuno'],
    '09:00' => ['display' => '9:00 AM', 'tipo' => 'Desayuno'],
    '12:00' => ['display' => '12:00 PM', 'tipo' => 'Almuerzo'],
    '13:00' => ['display' => '1:00 PM', 'tipo' => 'Almuerzo'],
    '19:00' => ['display' => '7:00 PM', 'tipo' => 'Cena'],
    '20:00' => ['display' => '8:00 PM', 'tipo' => 'Cena'],
];

// Función para obtener estadísticas por rango de fechas
function obtenerEstadisticasPorRango($conexion, $fechaInicio, $fechaFin) {
    try {
        // Pendientes de confirmación
        $stmt = $conexion->prepare("
            SELECT COUNT(*) as count FROM reservas r
            INNER JOIN usuarios u ON r.id_usuario = u.id_usuario
            WHERE r.estado = 'Solicitada' 
            AND DATE(r.fecha_reserva) BETWEEN ? AND ?
        ");
        $stmt->bind_param("ss", $fechaInicio, $fechaFin);
        $stmt->execute();
        $pendientes = $stmt->get_result()->fetch_assoc()['count'];

        // Por cancelar (vencidas)
        $stmt = $conexion->prepare("
            SELECT COUNT(*) as count FROM reservas r
            WHERE r.estado = 'Solicitada' 
            AND DATE(r.fecha_reserva) BETWEEN ? AND ?
            AND TIMESTAMPDIFF(HOUR, r.fecha_creacion, NOW()) > 48
            AND r.id_reserva NOT IN (SELECT id_reserva FROM pagos WHERE id_reserva IS NOT NULL)
        ");
        $stmt->bind_param("ss", $fechaInicio, $fechaFin);
        $stmt->execute();
        $vencidas = $stmt->get_result()->fetch_assoc()['count'];

        // A completar hoy
        $hoy = date('Y-m-d');
        $stmt = $conexion->prepare("
            SELECT COUNT(*) as count FROM reservas r
            WHERE r.estado = 'Anticipo pagado' 
            AND DATE(r.fecha_reserva) = ?
        ");
        $stmt->bind_param("s", $hoy);
        $stmt->execute();
        $completarHoy = $stmt->get_result()->fetch_assoc()['count'];

        // Total en el rango
        $stmt = $conexion->prepare("
            SELECT COUNT(*) as count FROM reservas r
            WHERE DATE(r.fecha_reserva) BETWEEN ? AND ?
        ");
        $stmt->bind_param("ss", $fechaInicio, $fechaFin);
        $stmt->execute();
        $totalRango = $stmt->get_result()->fetch_assoc()['count'];

        return [
            'pendientes' => $pendientes,
            'vencidas' => $vencidas,
            'completar_hoy' => $completarHoy,
            'total_rango' => $totalRango
        ];
    } catch (Exception $e) {
        return [
            'pendientes' => 0,
            'vencidas' => 0,
            'completar_hoy' => 0,
            'total_rango' => 0
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - La Casona Kawai</title>
    
    <!-- Fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/AdminPanel.css">
    
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <!-- Header del Admin -->
    <header class="admin-header">
        <div class="admin-header-container">
            <div class="admin-logo">
                <span class="logo-icon">🏛️</span>
                <h1>Panel de Administración</h1>
                <span class="restaurant-name">La Casona Kawai</span>
            </div>
            <div class="admin-user-info">
                <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['admin_usuario']); ?></span>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="btn-logout">Cerrar Sesión</button>
                </form>
            </div>
        </div>
    </header>

    <!-- Navegación del Admin -->
    <nav class="admin-nav">
        <div class="nav-container">
            <button class="nav-btn" data-section="calendar">📅 Calendario</button>
            <button class="nav-btn" data-section="reservas">📋 Gestión de Reservas</button>
            <button class="nav-btn" data-section="manual">✍️ Registro Manual</button>
        </div>
    </nav>

    <!-- Contenido Principal -->
    <main class="admin-main">
        
        <!-- Mensajes -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <span class="alert-icon"><?php echo $message_type === 'success' ? '✓' : '⚠'; ?></span>
                <span><?php echo htmlspecialchars($message); ?></span>
                <button class="alert-close" onclick="this.parentElement.remove()">×</button>
            </div>
        <?php endif; ?>

        <!-- SECCIÓN: CALENDARIO MEJORADO -->
        <section id="calendar" class="admin-section active">
            <h2 class="section-title">📅 Calendario de Reservas</h2>
            
            <!-- Tarjetas Dinámicas del Calendario -->
            <div class="calendar-stats-grid">
                <div class="calendar-stat-card stat-primary">
                    <div class="stat-icon">📋</div>
                    <div class="stat-content">
                        <h3 id="pendientesCount">0</h3>
                        <p>Pendientes de Confirmación</p>
                    </div>
                </div>
                
                <div class="calendar-stat-card stat-warning">
                    <div class="stat-icon">⏰</div>
                    <div class="stat-content">
                        <h3 id="vencidasCount">0</h3>
                        <p>Por Cancelar (Vencidas)</p>
                    </div>
                </div>
                
                <div class="calendar-stat-card stat-success">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3 id="completarHoyCount">0</h3>
                        <p>A Completar Hoy</p>
                    </div>
                </div>
                
                <div class="calendar-stat-card stat-info">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3 id="mesActualCount">0</h3>
                        <p id="mesActualLabel">Reservas Este Mes</p>
                    </div>
                </div>
            </div>
            
            <div class="calendar-container">
                <!-- Controles del Calendario -->
                <div class="calendar-controls">
                    <div class="calendar-navigation">
                        <button id="prevMonth" class="calendar-nav-btn">‹</button>
                        <h3 id="currentMonthYear" class="calendar-title"></h3>
                        <button id="nextMonth" class="calendar-nav-btn">›</button>
                    </div>
                    
                    <!-- Filtros de Vista -->
                    <div class="calendar-view-filters">
                        <button id="filterMonth" class="view-filter-btn active" data-filter="month">
                            📅 Mes Completo
                        </button>
                        <button id="filterWeek" class="view-filter-btn" data-filter="week">
                            📆 Semana Actual
                        </button>
                        <button id="filterDay" class="view-filter-btn" data-filter="day">
                            📋 Día Actual
                        </button>
                    </div>
                </div>
                
                <!-- Filtros de Estado -->
                <div class="calendar-state-filters">
                    <h4>Filtros por Estado de Reserva:</h4>
                    <div class="state-filter-buttons">
                        <button class="state-filter-btn" data-estado="Solicitada" data-color="#f39c12">
                            <span class="filter-indicator solicitada"></span>
                            <span class="filter-text">Solicitadas</span>
                            <span class="filter-status">Visible</span>
                        </button>
                        <button class="state-filter-btn" data-estado="Anticipo pagado" data-color="#3498db">
                            <span class="filter-indicator anticipo-pagado"></span>
                            <span class="filter-text">Anticipo Pagado</span>
                            <span class="filter-status">Visible</span>
                        </button>
                        <button class="state-filter-btn" data-estado="Completada" data-color="#27ae60">
                            <span class="filter-indicator completada"></span>
                            <span class="filter-text">Completadas</span>
                            <span class="filter-status">Visible</span>
                        </button>
                        <button class="state-filter-btn" data-estado="Cancelada" data-color="#e74c3c">
                            <span class="filter-indicator cancelada"></span>
                            <span class="filter-text">Canceladas</span>
                            <span class="filter-status">Visible</span>
                        </button>
                    </div>
                </div>
                
                <!-- Vista del Calendario -->
                <div id="calendarView" class="calendar-view">
                    <!-- El calendario se generará dinámicamente con JavaScript -->
                </div>
                
                <!-- Vista del Día con Intervalos -->
                <div id="dayView" class="day-view-detailed" style="display: none;">
                    <div class="day-view-header">
                        <h3 id="dayViewTitle"></h3>
                        <p class="day-view-subtitle">Reservas organizadas por intervalos de 30 minutos</p>
                    </div>
                    <div id="dayViewContent" class="day-intervals-container">
                        <!-- Los intervalos se generarán dinámicamente -->
                    </div>
                </div>
            </div>
        </section>

        <!-- SECCIÓN: GESTIÓN DE RESERVAS -->
        <section id="reservas" class="admin-section">
            <h2 class="section-title">Gestión de Reservas</h2>
            
            <!-- Filtros Simplificados -->
            <div class="filters-container">
                <div class="filter-group">
                    <label class="filter-label">Estado de la Reserva</label>
                    <select id="estadoFilter" class="filter-select">
                        <option value="">Todos los estados</option>
                        <option value="Solicitada">Solicitada</option>
                        <option value="Anticipo pagado">Anticipo pagado</option>
                        <option value="Completada">Completada</option>
                        <option value="Cancelada">Cancelada</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Intervalo de Fechas</label>
                    <div class="date-range-inputs">
                        <input type="date" id="fechaInicioFilter" class="filter-input" placeholder="Fecha inicio">
                        <span>hasta</span>
                        <input type="date" id="fechaFinFilter" class="filter-input" placeholder="Fecha fin">
                    </div>
                </div>
                
                <button id="clearFilters" class="btn-secondary">Limpiar Filtros</button>
            </div>

            <!-- Tabla de Reservas -->
            <div class="table-container">
                <table class="reservas-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Personas</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Pago</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="reservasTableBody">
                        <?php foreach ($reservas as $reserva): ?>
                            <tr data-estado="<?php echo strtolower(str_replace(' ', '-', $reserva['estado'])); ?>" 
                                data-fecha="<?php echo $reserva['fecha_reserva']; ?>">
                                <td>
                                    <span class="codigo-reserva"><?php echo htmlspecialchars($reserva['codigo_reserva']); ?></span>
                                </td>
                                <td>
                                    <div class="cliente-info">
                                        <strong><?php echo htmlspecialchars($reserva['cliente_nombre']); ?></strong>
                                        <small><?php echo htmlspecialchars($reserva['cliente_telefono']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($reserva['fecha_reserva'])); ?></td>
                                <td><?php echo htmlspecialchars($reserva['hora_reserva']); ?></td>
                                <td><?php echo $reserva['cantidad_personas']; ?></td>
                                <td>S/<?php echo number_format($reserva['total'], 2); ?></td>
                                <td>
                                    <span class="estado-badge estado-<?php echo strtolower(str_replace(' ', '-', $reserva['estado'])); ?>">
                                        <?php echo htmlspecialchars($reserva['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($reserva['id_pago']): ?>
                                        <span class="pago-badge pago-registrado">
                                            <?php echo htmlspecialchars($reserva['metodo_pago']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="pago-badge pago-pendiente">Sin registro</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-view" onclick="verDetalleReserva(<?php echo $reserva['id_reserva']; ?>)">
                                            👁️
                                        </button>
                                        <button class="btn-action btn-edit" onclick="cambiarEstadoReserva(<?php echo $reserva['id_reserva']; ?>, '<?php echo $reserva['estado']; ?>')">
                                            ✏️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- SECCIÓN: REGISTRO MANUAL -->
        <section id="manual" class="admin-section">
            <h2 class="section-title">Registro Manual de Reservas</h2>
            
            <div class="manual-form-container">
                <form method="POST" class="reserva-form">
                    <input type="hidden" name="action" value="crear_reserva_manual">
                    
                    <div class="form-grid">
                        <!-- Datos del Cliente -->
                        <div class="form-section">
                            <h3>Datos del Cliente</h3>
                            
                            <div class="form-group">
                                <label for="nombre">Nombre Completo *</label>
                                <input type="text" id="nombre" name="nombre" required class="form-control" placeholder="Ingrese el nombre completo">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="telefono">Teléfono *</label>
                                    <input type="tel" id="telefono" name="telefono" pattern="^9\d{8}$" required class="form-control" placeholder="9XXXXXXXX">
                                </div>
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" class="form-control" placeholder="usuario@gmail.com">
                                </div>
                            </div>
                        </div>

                        <!-- Datos de la Reserva -->
                        <div class="form-section">
                            <h3>Datos de la Reserva</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="fecha_reserva">Fecha *</label>
                                    <input type="date" id="fecha_reserva" name="fecha_reserva" required class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="hora_reserva">Hora *</label>
                                    <select id="hora_reserva" name="hora_reserva" required class="form-control">
                                        <option value="">Seleccionar hora</option>
                                        <?php foreach ($horarios_disponibles as $hora => $info): ?>
                                            <option value="<?php echo $hora; ?>">
                                                <?php echo $info['display'] . ' (' . $info['tipo'] . ')'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="cantidad_personas">Cantidad de Personas *</label>
                                <input type="number" id="cantidad_personas" name="cantidad_personas" min="1" max="250" required class="form-control" placeholder="Número de personas">
                            </div>
                            
                            <div class="form-group">
                                <label for="info_adicional">Información Adicional</label>
                                <textarea id="info_adicional" name="info_adicional" class="form-control" rows="3" placeholder="Comentarios, solicitudes especiales, etc."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Selección de Menú -->
                    <div class="form-section">
                        <h3>Selección de Menú</h3>
                        
                        <div class="form-group">
                            <label>Tipo de Menú *</label>
                            <div class="radio-group">
                                <div class="radio-option">
                                    <input type="radio" id="menu_desayuno" name="menu_type" value="desayuno" required>
                                    <label for="menu_desayuno">Desayuno (S/9.00)</label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" id="menu_almuerzo" name="menu_type" value="almuerzo" required>
                                    <label for="menu_almuerzo">Almuerzo (S/14.50)</label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" id="menu_cena" name="menu_type" value="cena" required>
                                    <label for="menu_cena">Cena (S/16.50)</label>
                                </div>
                            </div>
                        </div>

                        <!-- Opciones de Desayuno -->
                        <div id="desayuno_options" class="menu-options-group">
                            <h4>Opciones de Desayuno</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="desayuno_bebida">Bebida *</label>
                                    <select id="desayuno_bebida" name="desayuno_bebida" class="form-control">
                                        <option value="">Seleccionar bebida</option>
                                        <?php foreach ($menu_items['desayuno']['bebidas'] as $item): ?>
                                            <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="desayuno_pan">Pan (con) *</label>
                                    <select id="desayuno_pan" name="desayuno_pan" class="form-control">
                                        <option value="">Seleccionar relleno</option>
                                        <?php foreach ($menu_items['desayuno']['panes'] as $item): ?>
                                            <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Opciones de Almuerzo -->
                        <div id="almuerzo_options" class="menu-options-group">
                            <h4>Opciones de Almuerzo</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="almuerzo_entrada">Entrada *</label>
                                    <select id="almuerzo_entrada" name="almuerzo_entrada" class="form-control">
                                        <option value="">Seleccionar entrada</option>
                                        <?php foreach ($menu_items['almuerzo']['entradas'] as $item): ?>
                                            <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="almuerzo_fondo">Plato de Fondo *</label>
                                    <select id="almuerzo_fondo" name="almuerzo_fondo" class="form-control">
                                        <option value="">Seleccionar plato</option>
                                        <?php foreach ($menu_items['almuerzo']['platos_fondo'] as $item): ?>
                                            <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="almuerzo_postre">Postre *</label>
                                    <select id="almuerzo_postre" name="almuerzo_postre" class="form-control">
                                        <option value="">Seleccionar postre</option>
                                        <?php foreach ($menu_items['almuerzo']['postres'] as $item): ?>
                                            <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="almuerzo_bebida">Bebida *</label>
                                    <select id="almuerzo_bebida" name="almuerzo_bebida" class="form-control">
                                        <option value="">Seleccionar bebida</option>
                                        <?php foreach ($menu_items['almuerzo']['bebidas'] as $item): ?>
                                            <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Opciones de Cena -->
                        <div id="cena_options" class="menu-options-group">
                            <h4>Opciones de Cena</h4>
                            <div class="form-group">
                                <label for="cena_plato">Plato Principal *</label>
                                <select id="cena_plato" name="cena_plato" class="form-control">
                                    <option value="">Seleccionar plato</option>
                                    <?php foreach ($menu_items['cena']['platos_principales'] as $item): ?>
                                        <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="cena_postre">Postre *</label>
                                    <select id="cena_postre" name="cena_postre" class="form-control">
                                        <option value="">Seleccionar postre</option>
                                        <?php foreach ($menu_items['cena']['postres'] as $item): ?>
                                            <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="cena_bebida">Bebida *</label>
                                    <select id="cena_bebida" name="cena_bebida" class="form-control">
                                        <option value="">Seleccionar bebida</option>
                                        <?php foreach ($menu_items['cena']['bebidas'] as $item): ?>
                                            <option value="<?php echo htmlspecialchars($item); ?>"><?php echo htmlspecialchars($item); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Crear Reserva Manual</button>
                        <button type="reset" class="btn-secondary">Limpiar Formulario</button>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <!-- Modal para Detalle de Reserva -->
    <div id="detalleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detalle de Reserva</h3>
                <button class="modal-close" onclick="closeModal('detalleModal')">&times;</button>
            </div>
            <div class="modal-body" id="detalleModalBody">
                <!-- Contenido dinámico -->
            </div>
        </div>
    </div>

    <!-- Modal para Cambiar Estado -->
    <div id="estadoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Cambiar Estado de Reserva</h3>
                <button class="modal-close" onclick="closeModal('estadoModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="estadoForm">
                    <input type="hidden" name="action" value="cambiar_estado">
                    <input type="hidden" name="id_reserva" id="estadoReservaId">
                    
                    <div class="form-group">
                        <label for="nuevo_estado">Nuevo Estado:</label>
                        <select name="nuevo_estado" id="nuevo_estado" class="form-control" required>
                            <option value="">Seleccionar estado</option>
                            <option value="Solicitada">Solicitada</option>
                            <option value="Anticipo pagado">Anticipo pagado</option>
                            <option value="Completada">Completada</option>
                            <option value="Cancelada">Cancelada</option>
                        </select>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="submit" class="btn-primary">Cambiar Estado</button>
                        <button type="button" class="btn-secondary" onclick="closeModal('estadoModal')">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Pasar datos de PHP a JavaScript
        window.reservasData = <?php echo json_encode($reservas); ?>;
    </script>
    <script src="../assets/js/AdminPanel.js"></script>
</body>
</html>
