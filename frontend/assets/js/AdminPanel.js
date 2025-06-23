document.addEventListener("DOMContentLoaded", () => {
  console.log("🚀 Iniciando Panel de Administración - La Casona Kawai")

  // Elementos del DOM
  const navButtons = document.querySelectorAll(".nav-btn")
  const sections = document.querySelectorAll(".admin-section")

  // Elementos de filtros simplificados
  const estadoFilter = document.getElementById("estadoFilter")
  const fechaInicioFilter = document.getElementById("fechaInicioFilter")
  const fechaFinFilter = document.getElementById("fechaFinFilter")
  const clearFiltersBtn = document.getElementById("clearFilters")
  const reservasTableBody = document.getElementById("reservasTableBody")

  // Elementos del calendario mejorado
  const calendarView = document.getElementById("calendarView")
  const dayView = document.getElementById("dayView")
  const currentMonthYear = document.getElementById("currentMonthYear")
  const prevMonthBtn = document.getElementById("prevMonth")
  const nextMonthBtn = document.getElementById("nextMonth")

  // Filtros de vista
  const filterMonthBtn = document.getElementById("filterMonth")
  const filterWeekBtn = document.getElementById("filterWeek")
  const filterDayBtn = document.getElementById("filterDay")

  // Filtros de estado
  const stateFilterButtons = document.querySelectorAll(".state-filter-btn")

  // Elementos del formulario de reserva
  const menuTypeRadios = document.querySelectorAll('input[name="menu_type"]')
  const menuOptionsGroups = {
    desayuno: document.getElementById("desayuno_options"),
    almuerzo: document.getElementById("almuerzo_options"),
    cena: document.getElementById("cena_options"),
  }

  // Estado del calendario
  const currentDate = new Date()
  let currentView = "month"
  const hiddenStates = new Set()

  // Navegación entre secciones
  navButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const targetSection = button.dataset.section
      showSection(targetSection)

      // Actualizar navegación activa
      navButtons.forEach((btn) => btn.classList.remove("active"))
      button.classList.add("active")
    })
  })

  // Función para mostrar sección
  function showSection(sectionId) {
    console.log("📍 Mostrando sección:", sectionId)

    sections.forEach((section) => {
      section.classList.remove("active")
    })

    const targetSection = document.getElementById(sectionId)
    if (targetSection) {
      targetSection.classList.add("active")

      // Inicializar funcionalidades específicas de cada sección
      if (sectionId === "calendar") {
        console.log("📅 Inicializando calendario...")
        initCalendar()
      } else if (sectionId === "manual") {
        console.log("✍️ Inicializando formulario manual...")
        initReservaForm()
      } else if (sectionId === "reservas") {
        console.log("📋 Inicializando gestión de reservas...")
        applyFilters()
      }
    } else {
      console.error("❌ Sección no encontrada:", sectionId)
    }
  }

  // Filtros simplificados de tabla
  function applyFilters() {
    console.log("🔍 Aplicando filtros...")

    if (!reservasTableBody) {
      console.error("❌ Tabla de reservas no encontrada")
      return
    }

    const estadoValue = estadoFilter?.value.toLowerCase() || ""
    const fechaInicio = fechaInicioFilter?.value || ""
    const fechaFin = fechaFinFilter?.value || ""

    const rows = reservasTableBody.querySelectorAll("tr")

    rows.forEach((row) => {
      const estado = row.dataset.estado || ""
      const fecha = row.dataset.fecha || ""

      const estadoMatch = !estadoValue || estado.includes(estadoValue.replace(" ", "-"))

      let fechaMatch = true
      if (fechaInicio || fechaFin) {
        const fechaReserva = new Date(fecha)
        const inicio = fechaInicio ? new Date(fechaInicio) : new Date("1900-01-01")
        const fin = fechaFin ? new Date(fechaFin) : new Date("2100-12-31")
        fechaMatch = fechaReserva >= inicio && fechaReserva <= fin
      }

      if (estadoMatch && fechaMatch) {
        row.style.display = ""
      } else {
        row.style.display = "none"
      }
    })
  }

  // Event listeners para filtros
  if (estadoFilter) estadoFilter.addEventListener("change", applyFilters)
  if (fechaInicioFilter) fechaInicioFilter.addEventListener("change", applyFilters)
  if (fechaFinFilter) fechaFinFilter.addEventListener("change", applyFilters)

  if (clearFiltersBtn) {
    clearFiltersBtn.addEventListener("click", () => {
      if (estadoFilter) estadoFilter.value = ""
      if (fechaInicioFilter) fechaInicioFilter.value = ""
      if (fechaFinFilter) fechaFinFilter.value = ""
      applyFilters()
    })
  }

  // ===== CALENDARIO MEJORADO =====

  function initCalendar() {
    console.log("📅 Inicializando calendario mejorado...")

    // Event listeners para navegación de mes
    if (prevMonthBtn) {
      prevMonthBtn.addEventListener("click", () => {
        if (currentView === "month") {
          currentDate.setMonth(currentDate.getMonth() - 1)
        } else if (currentView === "week") {
          currentDate.setDate(currentDate.getDate() - 7)
        } else if (currentView === "day") {
          currentDate.setDate(currentDate.getDate() - 1)
        }
        renderCalendar()
      })
    }

    if (nextMonthBtn) {
      nextMonthBtn.addEventListener("click", () => {
        if (currentView === "month") {
          currentDate.setMonth(currentDate.getMonth() + 1)
        } else if (currentView === "week") {
          currentDate.setDate(currentDate.getDate() + 7)
        } else if (currentView === "day") {
          currentDate.setDate(currentDate.getDate() + 1)
        }
        renderCalendar()
      })
    }

    // Event listeners para filtros de vista
    if (filterMonthBtn) {
      filterMonthBtn.addEventListener("click", () => {
        setCalendarView("month")
      })
    }

    if (filterWeekBtn) {
      filterWeekBtn.addEventListener("click", () => {
        setCalendarView("week")
      })
    }

    if (filterDayBtn) {
      filterDayBtn.addEventListener("click", () => {
        setCalendarView("day")
      })
    }

    // Event listeners para filtros de estado
    stateFilterButtons.forEach((button) => {
      button.addEventListener("click", () => {
        const estado = button.dataset.estado
        const statusSpan = button.querySelector(".filter-status")

        if (hiddenStates.has(estado)) {
          hiddenStates.delete(estado)
          button.classList.remove("active")
          statusSpan.textContent = "Visible"
        } else {
          hiddenStates.add(estado)
          button.classList.add("active")
          statusSpan.textContent = "Oculto"
        }

        renderCalendar()
      })
    })

    // Renderizar calendario inicial
    renderCalendar()

    // Actualizar estadísticas iniciales
    updateCalendarStats()
  }

  function setCalendarView(view) {
    currentView = view

    // Actualizar botones activos
    document.querySelectorAll(".view-filter-btn").forEach((btn) => btn.classList.remove("active"))

    if (view === "month") {
      filterMonthBtn?.classList.add("active")
    } else if (view === "week") {
      filterWeekBtn?.classList.add("active")
    } else if (view === "day") {
      filterDayBtn?.classList.add("active")
    }

    renderCalendar()
    updateCalendarStats()
  }

  function renderCalendar() {
    if (currentView === "day") {
      renderDayView()
    } else {
      renderMonthOrWeekView()
    }
  }

  function renderMonthOrWeekView() {
    if (!calendarView || !currentMonthYear) return

    // Mostrar vista de calendario y ocultar vista de día
    calendarView.style.display = "block"
    if (dayView) dayView.style.display = "none"

    const year = currentDate.getFullYear()
    const month = currentDate.getMonth()

    // Actualizar título
    const monthNames = [
      "Enero",
      "Febrero",
      "Marzo",
      "Abril",
      "Mayo",
      "Junio",
      "Julio",
      "Agosto",
      "Septiembre",
      "Octubre",
      "Noviembre",
      "Diciembre",
    ]

    if (currentView === "month") {
      currentMonthYear.textContent = `${monthNames[month]} ${year}`
    } else if (currentView === "week") {
      const startOfWeek = getStartOfWeek(currentDate)
      const endOfWeek = new Date(startOfWeek)
      endOfWeek.setDate(startOfWeek.getDate() + 6)
      currentMonthYear.textContent = `Semana del ${startOfWeek.getDate()}/${startOfWeek.getMonth() + 1} al ${endOfWeek.getDate()}/${endOfWeek.getMonth() + 1}`
    }

    // Limpiar calendario
    calendarView.innerHTML = ""

    // Crear cuadrícula del calendario
    const calendarGrid = document.createElement("div")
    calendarGrid.className = "calendar-grid"

    // Días de la semana
    const daysOfWeek = ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb"]
    daysOfWeek.forEach((day) => {
      const dayElement = document.createElement("div")
      dayElement.className = "calendar-day-header"
      dayElement.textContent = day
      calendarGrid.appendChild(dayElement)
    })

    const daysToRender = []

    if (currentView === "month") {
      // Vista mensual: todos los días del mes
      const firstDay = new Date(year, month, 1).getDay()
      const daysInMonth = new Date(year, month + 1, 0).getDate()

      // Espacios vacíos para el primer día
      for (let i = 0; i < firstDay; i++) {
        daysToRender.push(null)
      }

      // Días del mes
      for (let day = 1; day <= daysInMonth; day++) {
        daysToRender.push(new Date(year, month, day))
      }
    } else if (currentView === "week") {
      // Vista semanal: 7 días de la semana actual
      const startOfWeek = getStartOfWeek(currentDate)
      for (let i = 0; i < 7; i++) {
        const day = new Date(startOfWeek)
        day.setDate(startOfWeek.getDate() + i)
        daysToRender.push(day)
      }
    }

    // Renderizar días
    daysToRender.forEach((date) => {
      const dayElement = document.createElement("div")

      if (date === null) {
        // Día vacío
        dayElement.className = "calendar-day empty"
      } else {
        dayElement.className = "calendar-day"

        // Número del día
        const dayNumber = document.createElement("div")
        dayNumber.className = "day-number"
        dayNumber.textContent = date.getDate()
        dayElement.appendChild(dayNumber)

        // Verificar si es hoy
        const today = new Date()
        if (isSameDay(date, today)) {
          dayElement.classList.add("today")
        }

        // Verificar si está en la semana actual (solo en vista mensual)
        if (currentView === "month" && isInCurrentWeek(date)) {
          dayElement.classList.add("current-week")
        }

        // Buscar reservas para este día
        const dateString = formatDateForDB(date)
        const dayReservas = getReservasForDate(dateString)

        if (dayReservas.length > 0) {
          // Contenedor de reservas
          const reservasContainer = document.createElement("div")
          reservasContainer.className = "day-reservas-container"

          // Mostrar hasta 3 reservas detalladas
          const reservasToShow = dayReservas.slice(0, 3)
          reservasToShow.forEach((reserva) => {
            if (!hiddenStates.has(reserva.estado)) {
              const reservaElement = document.createElement("div")
              reservaElement.className = `day-reserva-item estado-${reserva.estado.toLowerCase().replace(" ", "-")}`
              reservaElement.innerHTML = `
                <div class="reserva-cliente">${reserva.cliente_nombre}</div>
                <div class="reserva-info">
                  <span class="reserva-hora">${reserva.hora_reserva}</span>
                  <span class="reserva-personas">${reserva.cantidad_personas}p</span>
                </div>
              `

              // Click para ver detalles
              reservaElement.addEventListener("click", (e) => {
                e.stopPropagation()
                window.verDetalleReserva(reserva.id_reserva)
              })

              reservasContainer.appendChild(reservaElement)
            }
          })

          // Si hay más reservas, mostrar contador
          const visibleReservas = dayReservas.filter((r) => !hiddenStates.has(r.estado))
          if (visibleReservas.length > 3) {
            const moreElement = document.createElement("div")
            moreElement.className = "more-reservas"
            moreElement.textContent = `+${visibleReservas.length - 3} más`
            reservasContainer.appendChild(moreElement)
          }

          dayElement.appendChild(reservasContainer)
        }

        // Click en el día para ver todas las reservas
        dayElement.addEventListener("click", () => {
          showDayReservas(dateString, dayReservas)
        })
      }

      calendarGrid.appendChild(dayElement)
    })

    calendarView.appendChild(calendarGrid)
    updateCalendarStats()
  }

  function renderDayView() {
    if (!dayView || !currentMonthYear) return

    // Ocultar vista de calendario y mostrar vista de día
    if (calendarView) calendarView.style.display = "none"
    dayView.style.display = "block"

    const today = new Date()
    const dateString = formatDateForDB(today)
    const dayReservas = getReservasForDate(dateString)

    // Actualizar título
    const dayNames = ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"]
    const monthNames = [
      "Enero",
      "Febrero",
      "Marzo",
      "Abril",
      "Mayo",
      "Junio",
      "Julio",
      "Agosto",
      "Septiembre",
      "Octubre",
      "Noviembre",
      "Diciembre",
    ]

    currentMonthYear.textContent = `Hoy - ${dayNames[today.getDay()]}, ${today.getDate()} de ${monthNames[today.getMonth()]} ${today.getFullYear()}`

    const dayViewTitle = document.getElementById("dayViewTitle")
    const dayViewContent = document.getElementById("dayViewContent")

    if (dayViewTitle) {
      dayViewTitle.textContent = `${dayNames[today.getDay()]}, ${today.getDate()} de ${monthNames[today.getMonth()]} ${today.getFullYear()}`
    }

    if (dayViewContent) {
      // Filtrar reservas visibles
      const visibleReservas = dayReservas.filter((reserva) => !hiddenStates.has(reserva.estado))

      if (visibleReservas.length === 0) {
        dayViewContent.innerHTML = '<div class="empty-interval">No hay reservas visibles para hoy</div>'
        return
      }

      // Generar intervalos de 30 minutos
      const intervalos = generarIntervalosHorarios()

      // Agrupar reservas por hora
      const reservasPorHora = {}
      visibleReservas.forEach((reserva) => {
        const hora = reserva.hora_reserva.substring(0, 5) // HH:MM
        if (!reservasPorHora[hora]) {
          reservasPorHora[hora] = []
        }
        reservasPorHora[hora].push(reserva)
      })

      let content = ""

      intervalos.forEach((intervalo) => {
        const reservasEnIntervalo = reservasPorHora[intervalo] || []

        content += `
          <div class="time-interval">
            <div class="interval-time">${intervalo}</div>
            <div class="interval-reservas">
        `

        if (reservasEnIntervalo.length === 0) {
          content += '<div class="empty-interval">Sin reservas</div>'
        } else {
          reservasEnIntervalo.forEach((reserva) => {
            content += `
              <div class="interval-reserva-item estado-${reserva.estado.toLowerCase().replace(" ", "-")}" onclick="verDetalleReserva(${reserva.id_reserva})">
                <div class="reserva-header">
                  <span class="reserva-codigo">${reserva.codigo_reserva}</span>
                  <span class="estado-badge estado-${reserva.estado.toLowerCase().replace(" ", "-")}">${reserva.estado}</span>
                </div>
                <div class="reserva-details">
                  <strong>${reserva.cliente_nombre}</strong><br>
                  ${reserva.cantidad_personas} personas - S/${Number.parseFloat(reserva.total).toFixed(2)}<br>
                  Teléfono: ${reserva.cliente_telefono}
                </div>
              </div>
            `
          })
        }

        content += `
            </div>
          </div>
        `
      })

      dayViewContent.innerHTML = content
    }
    updateCalendarStats()
  }

  // Funciones auxiliares para el calendario
  function getStartOfWeek(date) {
    const start = new Date(date)
    const day = start.getDay()
    const diff = start.getDate() - day
    return new Date(start.setDate(diff))
  }

  function isSameDay(date1, date2) {
    return (
      date1.getDate() === date2.getDate() &&
      date1.getMonth() === date2.getMonth() &&
      date1.getFullYear() === date2.getFullYear()
    )
  }

  function isInCurrentWeek(date) {
    const today = new Date()
    const startOfWeek = getStartOfWeek(today)
    const endOfWeek = new Date(startOfWeek)
    endOfWeek.setDate(startOfWeek.getDate() + 6)

    return date >= startOfWeek && date <= endOfWeek
  }

  function formatDateForDB(date) {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, "0")
    const day = String(date.getDate()).padStart(2, "0")
    return `${year}-${month}-${day}`
  }

  function getReservasForDate(dateString) {
    if (!window.reservasData) return []
    return window.reservasData.filter((reserva) => reserva.fecha_reserva === dateString)
  }

  function generarIntervalosHorarios() {
    const intervalos = []
    for (let hora = 8; hora <= 23; hora++) {
      for (let minuto = 0; minuto < 60; minuto += 30) {
        const tiempo = `${String(hora).padStart(2, "0")}:${String(minuto).padStart(2, "0")}`
        intervalos.push(tiempo)
      }
    }
    return intervalos
  }

  function showDayReservas(date, reservas) {
    const modal = document.getElementById("detalleModal")
    const modalBody = document.getElementById("detalleModalBody")

    if (!modal || !modalBody) return

    let content = `<h4>Reservas para ${date}</h4>`
    content += '<div class="day-reservas-list">'

    const visibleReservas = reservas.filter((reserva) => !hiddenStates.has(reserva.estado))

    if (visibleReservas.length === 0) {
      content += '<div class="empty-state">No hay reservas visibles para este día</div>'
    } else {
      visibleReservas.forEach((reserva) => {
        content += `
          <div class="day-reserva-item">
            <div class="reserva-header">
              <span class="reserva-codigo">${reserva.codigo_reserva}</span>
              <span class="estado-badge estado-${reserva.estado.toLowerCase().replace(" ", "-")}">${reserva.estado}</span>
            </div>
            <div class="reserva-details">
              <strong>${reserva.cliente_nombre}</strong> - ${reserva.hora_reserva}<br>
              ${reserva.cantidad_personas} personas - S/${Number.parseFloat(reserva.total).toFixed(2)}<br>
              Teléfono: ${reserva.cliente_telefono}
            </div>
          </div>
        `
      })
    }

    content += "</div>"
    modalBody.innerHTML = content
    modal.style.display = "block"
  }

  // Formulario de reserva mejorado
  function initReservaForm() {
    console.log("✍️ Inicializando formulario de reserva...")

    // Configurar fecha mínima (2 días después de hoy)
    const fechaReservaInput = document.getElementById("fecha_reserva")
    if (fechaReservaInput) {
      const today = new Date()
      const minDate = new Date(today)
      minDate.setDate(today.getDate() + 2)

      const yyyy = minDate.getFullYear()
      const mm = String(minDate.getMonth() + 1).padStart(2, "0")
      const dd = String(minDate.getDate()).padStart(2, "0")

      fechaReservaInput.min = `${yyyy}-${mm}-${dd}`
    }

    // Validación de teléfono
    const telefonoInput = document.getElementById("telefono")
    if (telefonoInput) {
      telefonoInput.addEventListener("input", function () {
        const value = this.value
        if (value && !value.match(/^9\d{8}$/)) {
          this.classList.add("error")
          showFieldError(this, "El teléfono debe empezar con 9 y tener 9 dígitos")
        } else {
          this.classList.remove("error")
          this.classList.add("success")
          hideFieldError(this)
        }
      })
    }

    // Validación de email
    const emailInput = document.getElementById("email")
    if (emailInput) {
      emailInput.addEventListener("input", function () {
        const value = this.value
        if (value && !value.match(/^[^\s@]+@gmail\.com$/)) {
          this.classList.add("error")
          showFieldError(this, "El email debe terminar en @gmail.com")
        } else {
          this.classList.remove("error")
          if (value) this.classList.add("success")
          hideFieldError(this)
        }
      })
    }

    // Validación de cantidad de personas
    const cantidadInput = document.getElementById("cantidad_personas")
    if (cantidadInput) {
      cantidadInput.addEventListener("input", function () {
        const value = Number.parseInt(this.value)
        if (value < 1 || value > 250) {
          this.classList.add("error")
          showFieldError(this, "La cantidad debe estar entre 1 y 250 personas")
        } else {
          this.classList.remove("error")
          this.classList.add("success")
          hideFieldError(this)
        }
      })
    }

    // Mostrar opciones de menú
    menuTypeRadios.forEach((radio) => {
      radio.addEventListener("change", showMenuOptions)
    })

    // Validación del formulario completo
    const reservaForm = document.querySelector(".reserva-form")
    if (reservaForm) {
      reservaForm.addEventListener("submit", validateReservaForm)
    }
  }

  function showMenuOptions() {
    // Ocultar todas las opciones
    Object.values(menuOptionsGroups).forEach((group) => {
      if (group) group.classList.remove("active")
    })

    // Mostrar la opción seleccionada
    const selectedMenuType = document.querySelector('input[name="menu_type"]:checked')
    if (selectedMenuType && menuOptionsGroups[selectedMenuType.value]) {
      menuOptionsGroups[selectedMenuType.value].classList.add("active")

      // Hacer requeridos los campos del menú seleccionado
      const activeGroup = menuOptionsGroups[selectedMenuType.value]
      const selects = activeGroup.querySelectorAll("select")
      selects.forEach((select) => {
        select.required = true
      })

      // Quitar requerido de otros grupos
      Object.values(menuOptionsGroups).forEach((group) => {
        if (group && group !== activeGroup) {
          const selects = group.querySelectorAll("select")
          selects.forEach((select) => {
            select.required = false
          })
        }
      })
    }
  }

  function validateReservaForm(e) {
    let hasErrors = false
    const form = e.target

    // Validar campos requeridos
    const requiredFields = form.querySelectorAll("[required]")
    requiredFields.forEach((field) => {
      if (!field.value.trim()) {
        field.classList.add("error")
        showFieldError(field, "Este campo es obligatorio")
        hasErrors = true
      }
    })

    // Validar tipo de menú
    const selectedMenuType = document.querySelector('input[name="menu_type"]:checked')
    if (!selectedMenuType) {
      showNotification("Por favor, selecciona un tipo de menú", "error")
      hasErrors = true
    }

    // Validar opciones de menú
    if (selectedMenuType) {
      const menuGroup = menuOptionsGroups[selectedMenuType.value]
      if (menuGroup && menuGroup.classList.contains("active")) {
        const requiredSelects = menuGroup.querySelectorAll("select[required]")
        requiredSelects.forEach((select) => {
          if (!select.value.trim()) {
            select.classList.add("error")
            showFieldError(select, "Selecciona una opción")
            hasErrors = true
          }
        })
      }
    }

    if (hasErrors) {
      e.preventDefault()
      showNotification("Por favor, corrige los errores en el formulario", "error")
      return false
    }

    showNotification("Creando reserva manual...", "info", 2000)
    return true
  }

  function showFieldError(field, message) {
    hideFieldError(field)
    const errorDiv = document.createElement("div")
    errorDiv.className = "error-message"
    errorDiv.textContent = message
    field.parentNode.appendChild(errorDiv)
  }

  function hideFieldError(field) {
    const existingError = field.parentNode.querySelector(".error-message")
    if (existingError) {
      existingError.remove()
    }
  }

  // Funciones globales para modales y acciones
  window.verDetalleReserva = (idReserva) => {
    const reserva = window.reservasData ? window.reservasData.find((r) => r.id_reserva == idReserva) : null
    if (reserva) {
      showReservaDetail(reserva)
    }
  }

  function showReservaDetail(reserva) {
    const modal = document.getElementById("detalleModal")
    const modalBody = document.getElementById("detalleModalBody")

    if (!modal || !modalBody) return

    let menuInfo = ""

    // Determinar tipo de menú y opciones
    if (reserva.desayuno_bebida) {
      menuInfo = `
        <strong>Desayuno:</strong><br>
        Bebida: ${reserva.desayuno_bebida}<br>
        Pan con: ${reserva.desayuno_pan}
      `
    } else if (reserva.almuerzo_entrada) {
      menuInfo = `
        <strong>Almuerzo:</strong><br>
        Entrada: ${reserva.almuerzo_entrada}<br>
        Plato de fondo: ${reserva.almuerzo_fondo}<br>
        Postre: ${reserva.almuerzo_postre}<br>
        Bebida: ${reserva.almuerzo_bebida}
      `
    } else if (reserva.cena_plato) {
      menuInfo = `
        <strong>Cena:</strong><br>
        Plato principal: ${reserva.cena_plato}<br>
        Postre: ${reserva.cena_postre}<br>
        Bebida: ${reserva.cena_bebida}
      `
    }

    let pagoInfo = ""
    if (reserva.id_pago) {
      pagoInfo = `
        <div class="pago-info">
          <h4>Información de Pago</h4>
          <p><strong>Método:</strong> ${reserva.metodo_pago}</p>
          <p><strong>Titular:</strong> ${reserva.nombre_titular}</p>
          <p><strong>Número de operación:</strong> ${reserva.numero_operacion}</p>
          ${reserva.banco ? `<p><strong>Banco:</strong> ${reserva.banco}</p>` : ""}
          <p><strong>Monto:</strong> S/${Number.parseFloat(reserva.monto_pagado || 0).toFixed(2)}</p>
          <p><strong>Tipo de comprobante:</strong> ${reserva.tipo_comprobante}</p>
          ${reserva.ruc_factura ? `<p><strong>RUC:</strong> ${reserva.ruc_factura}</p>` : ""}
          <p><strong>Fecha de pago:</strong> ${new Date(reserva.fecha_pago).toLocaleString()}</p>
        </div>
      `
    } else {
      pagoInfo = `
        <div class="pago-info">
          <p class="no-pago">⚠️ El usuario aún no ha subido su registro de pago de anticipo.</p>
        </div>
      `
    }

    modalBody.innerHTML = `
      <div class="reserva-detalle">
        <div class="detalle-header">
          <h4>Reserva ${reserva.codigo_reserva}</h4>
          <span class="estado-badge estado-${reserva.estado.toLowerCase().replace(" ", "-")}">${reserva.estado}</span>
        </div>
        
        <div class="detalle-grid">
          <div class="detalle-section">
            <h4>Información del Cliente</h4>
            <p><strong>Nombre:</strong> ${reserva.cliente_nombre}</p>
            <p><strong>Teléfono:</strong> ${reserva.cliente_telefono}</p>
            <p><strong>Email:</strong> ${reserva.cliente_email || "No proporcionado"}</p>
          </div>
          
          <div class="detalle-section">
            <h4>Detalles de la Reserva</h4>
            <p><strong>Fecha:</strong> ${new Date(reserva.fecha_reserva).toLocaleDateString()}</p>
            <p><strong>Hora:</strong> ${reserva.hora_reserva}</p>
            <p><strong>Cantidad de personas:</strong> ${reserva.cantidad_personas}</p>
            <p><strong>Total:</strong> S/${Number.parseFloat(reserva.total).toFixed(2)}</p>
            <p><strong>Fecha de creación:</strong> ${new Date(reserva.fecha_creacion).toLocaleString()}</p>
          </div>
          
          <div class="detalle-section">
            <h4>Menú Seleccionado</h4>
            ${menuInfo}
          </div>
          
          <div class="detalle-section">
            ${pagoInfo}
          </div>
        </div>
        
        <div class="detalle-actions">
          <button class="btn-primary" onclick="cambiarEstadoReserva(${reserva.id_reserva}, '${reserva.estado}')">
            Cambiar Estado
          </button>
          ${
            reserva.id_pago && reserva.estado === "Solicitada"
              ? `
              <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="cambiar_estado">
                <input type="hidden" name="id_reserva" value="${reserva.id_reserva}">
                <input type="hidden" name="nuevo_estado" value="Anticipo pagado">
                <button type="submit" class="btn-success" onclick="return confirm('¿Confirmar pago y cambiar a Anticipo pagado?')">
                  ✓ Confirmar Pago
                </button>
              </form>
          `
              : ""
          }
        </div>
      </div>
    `

    modal.style.display = "block"
  }

  window.cambiarEstadoReserva = (idReserva, estadoActual) => {
    document.getElementById("estadoReservaId").value = idReserva
    document.getElementById("nuevo_estado").value = estadoActual
    document.getElementById("estadoModal").style.display = "block"
  }

  window.closeModal = (modalId) => {
    document.getElementById(modalId).style.display = "none"
  }

  // Cerrar modales al hacer clic fuera
  window.addEventListener("click", (event) => {
    const modals = document.querySelectorAll(".modal")
    modals.forEach((modal) => {
      if (event.target === modal) {
        modal.style.display = "none"
      }
    })
  })

  // Función para mostrar notificaciones
  function showNotification(message, type = "info", duration = 5000) {
    const notification = document.createElement("div")
    notification.className = `notification ${type}`
    notification.innerHTML = `
      <span class="notification-icon">${type === "success" ? "✓" : type === "error" ? "⚠" : "ℹ"}</span>
      <span class="notification-message">${message}</span>
      <button class="notification-close" onclick="this.parentElement.remove()">×</button>
    `

    // Estilos para la notificación
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: ${type === "success" ? "#d4edda" : type === "error" ? "#f8d7da" : "#d1ecf1"};
      color: ${type === "success" ? "#155724" : type === "error" ? "#721c24" : "#0c5460"};
      padding: 15px 20px;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      border-left: 4px solid ${type === "success" ? "#27ae60" : type === "error" ? "#e74c3c" : "#3498db"};
      display: flex;
      align-items: center;
      gap: 10px;
      z-index: 10000;
      transform: translateX(400px);
      opacity: 0;
      transition: all 0.3s ease;
      max-width: 400px;
      font-family: 'EB Garamond', serif;
      font-size: 14px;
      font-weight: 600;
    `

    document.body.appendChild(notification)

    // Mostrar notificación
    setTimeout(() => {
      notification.style.transform = "translateX(0)"
      notification.style.opacity = "1"
    }, 100)

    // Ocultar notificación
    setTimeout(() => {
      notification.style.transform = "translateX(400px)"
      notification.style.opacity = "0"
      setTimeout(() => notification.remove(), 300)
    }, duration)
  }

  // Efectos visuales adicionales
  function addHoverEffects() {
    // Efecto hover para tarjetas de estadísticas
    const statCards = document.querySelectorAll(".stat-card")
    statCards.forEach((card) => {
      card.addEventListener("mouseenter", function () {
        this.style.transform = "translateY(-8px) scale(1.02)"
      })

      card.addEventListener("mouseleave", function () {
        this.style.transform = "translateY(0) scale(1)"
      })
    })

    // Efecto hover para botones de filtro
    const filterButtons = document.querySelectorAll(".view-filter-btn, .state-filter-btn")
    filterButtons.forEach((button) => {
      button.addEventListener("mouseenter", function () {
        this.style.transform = "translateY(-2px)"
      })

      button.addEventListener("mouseleave", function () {
        this.style.transform = "translateY(0)"
      })
    })
  }

  // Función para animar elementos al cargar
  function animateOnLoad() {
    const animatedElements = document.querySelectorAll(
      ".stat-card, .chart-container, .calendar-container, .manual-form-container, .report-section",
    )

    animatedElements.forEach((element, index) => {
      element.style.opacity = "0"
      element.style.transform = "translateY(30px)"

      setTimeout(() => {
        element.style.transition = "all 0.6s ease-out"
        element.style.opacity = "1"
        element.style.transform = "translateY(0)"
      }, index * 100)
    })
  }

  // Función para actualizar las tarjetas estadísticas del calendario
  function updateCalendarStats() {
    if (!window.reservasData) return

    const stats = calculateStatsForCurrentView()

    // Actualizar los números con animación
    updateStatCard("pendientesCount", stats.pendientes)
    updateStatCard("vencidasCount", stats.vencidas)
    updateStatCard("completarHoyCount", stats.completarHoy)
    updateStatCard("mesActualCount", stats.totalRango)

    // Actualizar el label del período
    updatePeriodLabel()
  }

  function calculateStatsForCurrentView() {
    const reservas = window.reservasData
    let filteredReservas = []

    if (currentView === "month") {
      // Filtrar por mes actual
      const year = currentDate.getFullYear()
      const month = currentDate.getMonth()
      filteredReservas = reservas.filter((reserva) => {
        const fechaReserva = new Date(reserva.fecha_reserva)
        return fechaReserva.getFullYear() === year && fechaReserva.getMonth() === month
      })
    } else if (currentView === "week") {
      // Filtrar por semana actual
      const startOfWeek = getStartOfWeek(currentDate)
      const endOfWeek = new Date(startOfWeek)
      endOfWeek.setDate(startOfWeek.getDate() + 6)

      filteredReservas = reservas.filter((reserva) => {
        const fechaReserva = new Date(reserva.fecha_reserva)
        return fechaReserva >= startOfWeek && fechaReserva <= endOfWeek
      })
    } else if (currentView === "day") {
      // Filtrar por día actual
      const today = new Date()
      const todayString = formatDateForDB(today)
      filteredReservas = reservas.filter((reserva) => reserva.fecha_reserva === todayString)
    }

    // Calcular estadísticas
    const pendientes = filteredReservas.filter((r) => r.estado === "Solicitada").length

    const vencidas = filteredReservas.filter((r) => {
      if (r.estado !== "Solicitada") return false
      const fechaCreacion = new Date(r.fecha_creacion)
      const horasTranscurridas = (new Date() - fechaCreacion) / (1000 * 60 * 60)
      return horasTranscurridas > 48 && !r.id_pago
    }).length

    const hoy = new Date()
    const hoyString = formatDateForDB(hoy)
    const completarHoy = reservas.filter((r) => r.estado === "Anticipo pagado" && r.fecha_reserva === hoyString).length

    const totalRango = filteredReservas.length

    return {
      pendientes,
      vencidas,
      completarHoy,
      totalRango,
    }
  }

  function updateStatCard(elementId, newValue) {
    const element = document.getElementById(elementId)
    if (!element) return

    const currentValue = Number.parseInt(element.textContent) || 0

    if (currentValue !== newValue) {
      element.classList.add("updating")

      // Animar el cambio de número
      animateNumber(element, currentValue, newValue, 300)

      setTimeout(() => {
        element.classList.remove("updating")
      }, 300)
    }
  }

  function animateNumber(element, start, end, duration) {
    const startTime = performance.now()
    const difference = end - start

    function updateNumber(currentTime) {
      const elapsed = currentTime - startTime
      const progress = Math.min(elapsed / duration, 1)

      const current = Math.round(start + difference * progress)
      element.textContent = current

      if (progress < 1) {
        requestAnimationFrame(updateNumber)
      }
    }

    requestAnimationFrame(updateNumber)
  }

  function updatePeriodLabel() {
    const labelElement = document.getElementById("mesActualLabel")
    if (!labelElement) return

    if (currentView === "month") {
      const monthNames = [
        "Enero",
        "Febrero",
        "Marzo",
        "Abril",
        "Mayo",
        "Junio",
        "Julio",
        "Agosto",
        "Septiembre",
        "Octubre",
        "Noviembre",
        "Diciembre",
      ]
      const monthName = monthNames[currentDate.getMonth()]
      labelElement.textContent = `Reservas de ${monthName}`
    } else if (currentView === "week") {
      labelElement.textContent = "Reservas de la Semana"
    } else if (currentView === "day") {
      labelElement.textContent = "Reservas de Hoy"
    }
  }

  // Inicializar efectos y animaciones
  addHoverEffects()
  animateOnLoad()

  // Mostrar opciones de menú iniciales si hay una selección
  showMenuOptions()

  // Al inicio del DOMContentLoaded, cambiar:
  // Mostrar calendario por defecto en lugar de dashboard
  showSection("calendar")

  // Exponer funciones globales
  window.showNotification = showNotification

  console.log("✅ Panel de Administración - JavaScript cargado correctamente")
})
