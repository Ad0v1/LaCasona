// ===== VARIABLES GLOBALES =====
let reservaActual = null;
let modalReserva = null;
let modalGestion = null;
let modalFormulario = null;
let bootstrap = window.bootstrap; // Declare the bootstrap variable

// ===== INICIALIZACIÓN =====
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar modales
    modalReserva = new bootstrap.Modal(document.getElementById('modalReserva'));
    modalGestion = new bootstrap.Modal(document.getElementById('modalGestion'));
    modalFormulario = new bootstrap.Modal(document.getElementById('modalFormulario'));
    
    // Configurar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Actualizar página cada 5 minutos
    setInterval(() => {
        location.reload();
    }, 300000);
    
    console.log('🎯 Panel de Administración cargado correctamente');
});

// ===== FUNCIONES PRINCIPALES =====

/**
 * Mostrar detalle completo de una reserva
 */
function mostrarDetalleReserva(reserva) {
    reservaActual = reserva;
    
    const estadoColors = {
        'Solicitada': 'warning',
        'Anticipo pagado': 'info', 
        'Completada': 'success',
        'Cancelada': 'danger'
    };
    
    const badgeClass = estadoColors[reserva.estado] || 'secondary';
    
    // Construir información del menú
    let menuInfo = '';
    if (reserva.id_desayuno) {
        menuInfo = `
            <div class="menu-details">
                <h6><i class="fas fa-coffee me-2"></i>Desayuno</h6>
                <div class="menu-item">
                    <span>Bebida:</span>
                    <span>${reserva.desayuno_bebida || 'No especificado'}</span>
                </div>
                <div class="menu-item">
                    <span>Pan:</span>
                    <span>${reserva.desayuno_pan || 'No especificado'}</span>
                </div>
            </div>
        `;
    } else if (reserva.id_almuerzo) {
        menuInfo = `
            <div class="menu-details">
                <h6><i class="fas fa-utensils me-2"></i>Almuerzo</h6>
                <div class="menu-item">
                    <span>Entrada:</span>
                    <span>${reserva.almuerzo_entrada || 'No especificado'}</span>
                </div>
                <div class="menu-item">
                    <span>Plato de Fondo:</span>
                    <span>${reserva.almuerzo_fondo || 'No especificado'}</span>
                </div>
                <div class="menu-item">
                    <span>Postre:</span>
                    <span>${reserva.almuerzo_postre || 'No especificado'}</span>
                </div>
                <div class="menu-item">
                    <span>Bebida:</span>
                    <span>${reserva.almuerzo_bebida || 'No especificado'}</span>
                </div>
            </div>
        `;
    } else if (reserva.id_cena) {
        menuInfo = `
            <div class="menu-details">
                <h6><i class="fas fa-moon me-2"></i>Cena</h6>
                <div class="menu-item">
                    <span>Plato Principal:</span>
                    <span>${reserva.cena_plato || 'No especificado'}</span>
                </div>
                <div class="menu-item">
                    <span>Postre:</span>
                    <span>${reserva.cena_postre || 'No especificado'}</span>
                </div>
                <div class="menu-item">
                    <span>Bebida:</span>
                    <span>${reserva.cena_bebida || 'No especificado'}</span>
                </div>
            </div>
        `;
    }
    
    // Construir información de pago
    let pagoInfo = '';
    if (reserva.metodo_pago) {
        const estadoPago = reserva.estado_verificacion || 'pendiente';
        const clasePago = estadoPago === 'verificado' ? '' : (estadoPago === 'pendiente' ? 'pendiente' : 'sin-pago');
        
        pagoInfo = `
            <div class="pago-info ${clasePago}">
                <h6><i class="fas fa-credit-card me-2"></i>Información de Pago</h6>
                <div class="menu-item">
                    <span>Método:</span>
                    <span>${reserva.metodo_pago.toUpperCase()}</span>
                </div>
                <div class="menu-item">
                    <span>Titular:</span>
                    <span>${reserva.nombre_titular}</span>
                </div>
                <div class="menu-item">
                    <span>N° Operación:</span>
                    <span>${reserva.numero_operacion}</span>
                </div>
                ${reserva.banco ? `
                <div class="menu-item">
                    <span>Banco:</span>
                    <span>${reserva.banco}</span>
                </div>
                ` : ''}
                <div class="menu-item">
                    <span>Monto:</span>
                    <span>S/. ${parseFloat(reserva.monto_pagado || 0).toFixed(2)}</span>
                </div>
                <div class="menu-item">
                    <span>Estado:</span>
                    <span class="badge bg-${estadoPago === 'verificado' ? 'success' : 'warning'}">${estadoPago.toUpperCase()}</span>
                </div>
                <div class="menu-item">
                    <span>Fecha Pago:</span>
                    <span>${reserva.fecha_pago ? new Date(reserva.fecha_pago).toLocaleString('es-ES') : 'No registrada'}</span>
                </div>
                ${estadoPago === 'pendiente' ? `
                <div class="mt-2">
                    <button class="btn btn-success btn-sm" onclick="verificarPago('${reserva.codigo_reserva}')">
                        <i class="fas fa-check me-1"></i>Verificar Pago
                    </button>
                </div>
                ` : ''}
            </div>
        `;
    } else {
        pagoInfo = `
            <div class="pago-info sin-pago">
                <h6><i class="fas fa-exclamation-triangle me-2"></i>Sin Registro de Pago</h6>
                <p class="mb-0">El usuario aún no ha subido su registro de pago de anticipo.</p>
            </div>
        `;
    }
    
    const content = `
        <div class="row">
            <div class="col-md-6">
                <div class="detalle-row">
                    <span class="detalle-label">
                        <i class="fas fa-hashtag"></i>Código
                    </span>
                    <span class="detalle-value fw-bold text-primary">${reserva.codigo_reserva}</span>
                </div>
                
                <div class="detalle-row">
                    <span class="detalle-label">
                        <i class="fas fa-user"></i>Cliente
                    </span>
                    <span class="detalle-value">${reserva.nombre_usuario || 'No especificado'}</span>
                </div>
                
                <div class="detalle-row">
                    <span class="detalle-label">
                        <i class="fas fa-phone"></i>Teléfono
                    </span>
                    <span class="detalle-value">${reserva.telefono || 'No especificado'}</span>
                </div>
                
                <div class="detalle-row">
                    <span class="detalle-label">
                        <i class="fas fa-envelope"></i>Email
                    </span>
                    <span class="detalle-value">${reserva.email || 'No especificado'}</span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detalle-row">
                    <span class="detalle-label">
                        <i class="fas fa-calendar"></i>Fecha y Hora
                    </span>
                    <span class="detalle-value">${new Date(reserva.fecha_reserva).toLocaleDateString('es-ES')} - ${reserva.hora_reserva}</span>
                </div>
                
                <div class="detalle-row">
                    <span class="detalle-label">
                        <i class="fas fa-users"></i>Personas
                    </span>
                    <span class="detalle-value">${reserva.cantidad_personas} personas</span>
                </div>
                
                <div class="detalle-row">
                    <span class="detalle-label">
                        <i class="fas fa-dollar-sign"></i>Total
                    </span>
                    <span class="detalle-value fw-bold">S/. ${parseFloat(reserva.total).toFixed(2)}</span>
                </div>
                
                <div class="detalle-row">
                    <span class="detalle-label">
                        <i class="fas fa-info-circle"></i>Estado
                    </span>
                    <span class="detalle-value">
                        <span class="estado-badge bg-${badgeClass} text-white">${reserva.estado}</span>
                    </span>
                </div>
            </div>
        </div>
        
        ${menuInfo}
        ${pagoInfo}
        
        ${reserva.info_adicional ? `
            <div class="mt-3">
                <h6><i class="fas fa-sticky-note me-2"></i>Información Adicional</h6>
                <p class="text-muted">${reserva.info_adicional}</p>
            </div>
        ` : ''}
        
        <div class="mt-3">
            <h6><i class="fas fa-cog me-2"></i>Acciones</h6>
            <div class="d-flex gap-2 flex-wrap">
                ${reserva.estado === 'Solicitada' ? `
                    <button class="btn btn-info btn-sm" onclick="cambiarEstado(${reserva.id_reserva}, 'Anticipo pagado')">
                        <i class="fas fa-credit-card me-1"></i>Marcar como Pagado
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="cambiarEstado(${reserva.id_reserva}, 'Cancelada')">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                ` : ''}
                ${reserva.estado === 'Anticipo pagado' ? `
                    <button class="btn btn-success btn-sm" onclick="cambiarEstado(${reserva.id_reserva}, 'Completada')">
                        <i class="fas fa-check me-1"></i>Completar
                    </button>
                ` : ''}
                <button class="btn btn-secondary btn-sm" onclick="editarReserva(${reserva.id_reserva})">
                    <i class="fas fa-edit me-1"></i>Editar
                </button>
            </div>
        </div>
    `;
    
    document.getElementById('modalReservaContent').innerHTML = content;
    modalReserva.show();
}

/**
 * Cambiar estado de una reserva
 */
function cambiarEstado(idReserva, nuevoEstado) {
    if (!confirm(`¿Estás seguro de cambiar el estado a "${nuevoEstado}"?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'cambiar_estado');
    formData.append('id_reserva', idReserva);
    formData.append('nuevo_estado', nuevoEstado);
    
    fetch('IndexAdmin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacion('success', data.message);
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarNotificacion('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('error', 'Error al procesar la solicitud');
    });
}

/**
 * Verificar pago de una reserva
 */
function verificarPago(codigoReserva) {
    if (!confirm('¿Confirmas que el pago ha sido verificado correctamente?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'verificar_pago');
    formData.append('codigo_reserva', codigoReserva);
    
    fetch('IndexAdmin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacion('success', data.message);
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarNotificacion('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('error', 'Error al verificar el pago');
    });
}

/**
 * Mostrar solicitudes pendientes de confirmación
 */
function mostrarPendientesConfirmacion() {
    // Esta función se implementará con datos del servidor
    fetch('get-pendientes-confirmacion.php')
    .then(response => response.json())
    .then(data => {
        mostrarModalGestion('Pendientes de Confirmación', data, 'confirmacion');
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('error', 'Error al cargar los datos');
    });
}

/**
 * Mostrar solicitudes por cancelar
 */
function mostrarPorCancelar() {
    fetch('get-por-cancelar.php')
    .then(response => response.json())
    .then(data => {
        mostrarModalGestion('Solicitudes por Cancelar', data, 'cancelar');
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('error', 'Error al cargar los datos');
    });
}

/**
 * Mostrar solicitudes a completar hoy
 */
function mostrarCompletarHoy() {
    fetch('get-completar-hoy.php')
    .then(response => response.json())
    .then(data => {
        mostrarModalGestion('A Completar Hoy', data, 'completar');
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('error', 'Error al cargar los datos');
    });
}

/**
 * Mostrar modal de gestión con lista de reservas
 */
function mostrarModalGestion(titulo, reservas, tipo) {
    document.getElementById('modalGestionTitle').textContent = titulo;
    
    let content = '';
    if (reservas.length === 0) {
        content = '<p class="text-center text-muted">No hay reservas en esta categoría.</p>';
    } else {
        content = '<div class="gestion-list">';
        reservas.forEach(reserva => {
            content += `
                <div class="gestion-item border-start-${getColorClass(tipo)}">
                    <div class="gestion-item-header">
                        <span class="gestion-item-title">${reserva.codigo_reserva}</span>
                        <span class="gestion-item-meta">${reserva.fecha_reserva} - ${reserva.hora_reserva}</span>
                    </div>
                    <div class="gestion-item-meta">
                        <i class="fas fa-user me-1"></i>${reserva.nombre || 'Sin nombre'}
                        <i class="fas fa-users ms-2 me-1"></i>${reserva.cantidad_personas} personas
                        <i class="fas fa-dollar-sign ms-2 me-1"></i>S/. ${parseFloat(reserva.total).toFixed(2)}
                    </div>
                    <div class="gestion-actions">
                        ${getAccionesPorTipo(tipo, reserva)}
                    </div>
                </div>
            `;
        });
        content += '</div>';
    }
    
    document.getElementById('modalGestionContent').innerHTML = content;
    modalGestion.show();
}

/**
 * Obtener clase de color según el tipo
 */
function getColorClass(tipo) {
    switch (tipo) {
        case 'confirmacion': return 'warning';
        case 'cancelar': return 'danger';
        case 'completar': return 'success';
        default: return 'primary';
    }
}

/**
 * Obtener acciones según el tipo
 */
function getAccionesPorTipo(tipo, reserva) {
    switch (tipo) {
        case 'confirmacion':
            return `
                <button class="btn btn-success btn-sm" onclick="verificarPago('${reserva.codigo_reserva}')">
                    <i class="fas fa-check me-1"></i>Verificar
                </button>
                <button class="btn btn-info btn-sm" onclick="mostrarDetalleReserva(${JSON.stringify(reserva).replace(/"/g, '&quot;')})">
                    <i class="fas fa-eye me-1"></i>Ver
                </button>
            `;
        case 'cancelar':
            return `
                <button class="btn btn-danger btn-sm" onclick="cambiarEstado(${reserva.id_reserva}, 'Cancelada')">
                    <i class="fas fa-times me-1"></i>Cancelar
                </button>
                <button class="btn btn-info btn-sm" onclick="mostrarDetalleReserva(${JSON.stringify(reserva).replace(/"/g, '&quot;')})">
                    <i class="fas fa-eye me-1"></i>Ver
                </button>
            `;
        case 'completar':
            return `
                <button class="btn btn-success btn-sm" onclick="cambiarEstado(${reserva.id_reserva}, 'Completada')">
                    <i class="fas fa-check me-1"></i>Completar
                </button>
                <button class="btn btn-info btn-sm" onclick="mostrarDetalleReserva(${JSON.stringify(reserva).replace(/"/g, '&quot;')})">
                    <i class="fas fa-eye me-1"></i>Ver
                </button>
            `;
        default:
            return '';
    }
}

/**
 * Mostrar formulario para nueva reserva
 */
function mostrarFormularioReserva() {
    document.getElementById('modalFormularioTitle').textContent = 'Nueva Reserva Manual';
    document.getElementById('modalFormularioContent').innerHTML = `
        <form id="formNuevaReserva" onsubmit="crearReservaManual(event)">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="nombreCliente" name="nombre" required>
                        <label for="nombreCliente">Nombre del Cliente</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="tel" class="form-control" id="telefonoCliente" name="telefono" required>
                        <label for="telefonoCliente">Teléfono</label>
                    </div>
                </div>
            </div>
            
            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="emailCliente" name="email">
                <label for="emailCliente">Email (opcional)</label>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="date" class="form-control" id="fechaReserva" name="fecha_reserva" required>
                        <label for="fechaReserva">Fecha de Reserva</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="time" class="form-control" id="horaReserva" name="hora_reserva" required>
                        <label for="horaReserva">Hora de Reserva</label>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="number" class="form-control" id="cantidadPersonas" name="cantidad_personas" min="1" max="250" required>
                        <label for="cantidadPersonas">Cantidad de Personas</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <select class="form-control" id="tipoMenu" name="tipo_menu" required>
                            <option value="">Seleccionar...</option>
                            <option value="desayuno">Desayuno (S/9.00)</option>
                            <option value="almuerzo">Almuerzo (S/14.50)</option>
                            <option value="cena">Cena (S/16.50)</option>
                        </select>
                        <label for="tipoMenu">Tipo de Menú</label>
                    </div>
                </div>
            </div>
            
            <div class="form-floating mb-3">
                <select class="form-control" id="metodoPago" name="metodo_pago">
                    <option value="">Sin método de pago</option>
                    <option value="yape">Yape</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="efectivo">Efectivo</option>
                </select>
                <label for="metodoPago">Método de Pago</label>
            </div>
            
            <div class="form-floating mb-3">
                <textarea class="form-control" id="infoAdicional" name="info_adicional" style="height: 100px"></textarea>
                <label for="infoAdicional">Información Adicional (opcional)</label>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Crear Reserva
                </button>
            </div>
        </form>
    `;
    
    // Establecer fecha mínima (mañana)
    const mañana = new Date();
    mañana.setDate(mañana.getDate() + 1);
    document.getElementById('fechaReserva').min = mañana.toISOString().split('T')[0];
    
    modalFormulario.show();
}

/**
 * Crear reserva manual
 */
function crearReservaManual(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    formData.append('action', 'crear_reserva_manual');
    
    fetch('crear-reserva-manual.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacion('success', `Reserva creada exitosamente. Código: ${data.codigo_reserva}`);
            modalFormulario.hide();
            setTimeout(() => location.reload(), 2000);
        } else {
            mostrarNotificacion('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('error', 'Error al crear la reserva');
    });
}

/**
 * Mostrar notificaciones
 */
function mostrarNotificacion(tipo, mensaje) {
    const alertClass = tipo === 'success' ? 'alert-success' : 'alert-danger';
    const iconClass = tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="fas ${iconClass} me-2"></i>
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

/**
 * Editar reserva existente
 */
function editarReserva(idReserva) {
    // Implementar funcionalidad de edición
    mostrarNotificacion('info', 'Funcionalidad de edición en desarrollo');
}

// ===== UTILIDADES =====

/**
 * Formatear fecha para mostrar
 */
function formatearFecha(fecha) {
    return new Date(fecha).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

/**
 * Formatear hora para mostrar
 */
function formatearHora(hora) {
    return hora.substring(0, 5);
}

/**
 * Calcular días transcurridos
 */
function diasTranscurridos(fecha) {
    const hoy = new Date();
    const fechaReserva = new Date(fecha);
    const diferencia = hoy - fechaReserva;
    return Math.floor(diferencia / (1000 * 60 * 60 * 24));
}

// ===== EVENTOS GLOBALES =====

// Cerrar modales con ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modales = [modalReserva, modalGestion, modalFormulario];
        modales.forEach(modal => {
            if (modal && modal._isShown) {
                modal.hide();
            }
        });
    }
});

// Actualizar automáticamente cada 5 minutos
setInterval(() => {
    console.log('🔄 Actualizando datos automáticamente...');
    // Solo recargar si no hay modales abiertos
    const modalAbierto = document.querySelector('.modal.show');
    if (!modalAbierto) {
        location.reload();
    }
}, 300000);

console.log('✅ Sistema de administración cargado completamente');