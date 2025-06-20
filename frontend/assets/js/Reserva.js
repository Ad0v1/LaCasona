document.addEventListener("DOMContentLoaded", () => {
  // LIMPIAR localStorage al cargar la página para evitar campos completados
  const persistentFields = [
    "menu_type",
    "fecha_reserva",
    "hora_reserva",
    "cantidad_personas",
    "nombre",
    "apellidos",
    "telefono",
    "email",
    "codigo_reserva",
    "metodo_pago",
    "nombre_titular",
    "numero_operacion",
  ]

  // Limpiar localStorage al cargar la página
  persistentFields.forEach((fieldName) => {
    localStorage.removeItem(`reserva_${fieldName}`)
  })

  // Elementos del DOM
  const menuTypeRadios = document.querySelectorAll('input[name="menu_type"]')
  const menuOptionsGroups = {
    desayuno: document.getElementById("desayuno_options"),
    almuerzo: document.getElementById("almuerzo_options"),
    cena: document.getElementById("cena_options"),
  }

  const fechaReservaInput = document.getElementById("fecha_reserva")
  const horaReservaSelect = document.getElementById("hora_reserva")
  const telefonoInput = document.getElementById("telefono")
  const emailInput = document.getElementById("email")
  const cantidadPersonasInput = document.getElementById("cantidad_personas")

  // Elementos de pago
  const metodoPagoRadios = document.querySelectorAll('input[name="metodo_pago"]')
  const codigoSeguridadGroup = document.getElementById("codigo_seguridad_group")
  const bancoGroup = document.getElementById("banco_group")
  const tipoComprobanteRadios = document.querySelectorAll('input[name="tipo_comprobante"]')
  const rucFacturaGroup = document.getElementById("ruc_factura_group")

  // Configurar fecha mínima (2 días después de hoy)
  if (fechaReservaInput) {
    const today = new Date()
    const minDate = new Date(today)
    minDate.setDate(today.getDate() + 2)

    const yyyy = minDate.getFullYear()
    const mm = String(minDate.getMonth() + 1).padStart(2, "0")
    const dd = String(minDate.getDate()).padStart(2, "0")

    fechaReservaInput.min = `${yyyy}-${mm}-${dd}`
  }

  // Función para mostrar notificación toast
  function showNotification(message, type = "success", duration = 5000) {
    const toast = document.createElement("div")
    toast.className = `notification-toast ${type}`

    const icon = type === "success" ? "✓" : type === "error" ? "⚠" : "ℹ"

    toast.innerHTML = `
      <div class="toast-icon">${icon}</div>
      <div class="toast-content">
        <p>${message}</p>
      </div>
      <button class="toast-close" onclick="this.parentElement.remove()">×</button>
    `

    // Estilos para la notificación
    toast.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: ${
        type === "success"
          ? "linear-gradient(135deg, #d4edda 0%, #a8e6cf 100%)"
          : type === "error"
            ? "linear-gradient(135deg, #fde8e8 0%, #ffcccb 100%)"
            : "linear-gradient(135deg, #d1ecf1 0%, #a8e6cf 100%)"
      };
      color: ${type === "success" ? "#155724" : type === "error" ? "#721c24" : "#0c5460"};
      padding: 20px 25px;
      border-radius: 16px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
      border: 3px solid ${type === "success" ? "#27ae60" : type === "error" ? "#e74c3c" : "#3498db"};
      display: flex;
      align-items: center;
      gap: 15px;
      z-index: 10000;
      transform: translateX(400px);
      opacity: 0;
      transition: all 0.3s ease-out;
      max-width: 400px;
      font-family: 'EB Garamond', serif;
      font-size: 1.6rem;
      font-weight: 600;
    `

    document.body.appendChild(toast)

    // Mostrar toast con animación
    setTimeout(() => {
      toast.style.transform = "translateX(0)"
      toast.style.opacity = "1"
    }, 100)

    // Ocultar toast después del tiempo especificado
    setTimeout(() => {
      toast.style.transform = "translateX(400px)"
      toast.style.opacity = "0"
      setTimeout(() => toast.remove(), 300)
    }, duration)
  }

  // Función para filtrar horarios según tipo de menú - MEJORADA
  function filterHorariosByMenuType() {
    const selectedMenuType = document.querySelector('input[name="menu_type"]:checked')
    const horaReservaSelect = document.getElementById("hora_reserva")

    if (!horaReservaSelect || !selectedMenuType) return

    // Guardar todas las opciones originales si no están guardadas
    if (!horaReservaSelect.dataset.originalOptions) {
      horaReservaSelect.dataset.originalOptions = horaReservaSelect.innerHTML
    }

    // Restaurar todas las opciones
    horaReservaSelect.innerHTML = horaReservaSelect.dataset.originalOptions

    const menuType = selectedMenuType.value
    const allOptions = horaReservaSelect.querySelectorAll("option")

    // Filtrar opciones según el tipo de menú
    allOptions.forEach((option) => {
      if (option.value === "") return // Mantener opción vacía

      const optionText = option.textContent.toLowerCase()
      let shouldShow = false

      switch (menuType) {
        case "desayuno":
          // Mostrar solo horarios de 7:00 AM a 12:00 PM
          shouldShow =
            optionText.includes("desayuno") ||
            (optionText.includes("am") && !optionText.includes("12:")) ||
            (optionText.includes("pm") && (optionText.includes("12:00") || optionText.includes("12:30")))
          break
        case "almuerzo":
          // Mostrar solo horarios de 12:00 PM a 6:00 PM
          shouldShow =
            optionText.includes("almuerzo") ||
            (optionText.includes("pm") &&
              (optionText.includes("12:") ||
                optionText.includes("1:") ||
                optionText.includes("2:") ||
                optionText.includes("3:") ||
                optionText.includes("4:") ||
                optionText.includes("5:") ||
                optionText.includes("6:00") ||
                optionText.includes("6:30")))
          break
        case "cena":
          // Mostrar solo horarios de 6:00 PM a 12:00 AM
          shouldShow =
            optionText.includes("cena") ||
            (optionText.includes("pm") &&
              (optionText.includes("6:") ||
                optionText.includes("7:") ||
                optionText.includes("8:") ||
                optionText.includes("9:") ||
                optionText.includes("10:") ||
                optionText.includes("11:"))) ||
            (optionText.includes("am") && (optionText.includes("12:00") || optionText.includes("12:30")))
          break
      }

      if (!shouldShow) {
        option.style.display = "none"
        option.disabled = true
      } else {
        option.style.display = "block"
        option.disabled = false
      }
    })

    // Limpiar selección si la opción actual no es válida para el nuevo tipo
    const currentValue = horaReservaSelect.value
    if (currentValue) {
      const currentOption = horaReservaSelect.querySelector(`option[value="${currentValue}"]`)
      if (currentOption && currentOption.style.display === "none") {
        horaReservaSelect.value = ""
      }
    }
  }

  // Función para mostrar/ocultar opciones de menú
  function showMenuOptions() {
    // Ocultar todas las opciones
    Object.values(menuOptionsGroups).forEach((group) => {
      if (group) group.classList.remove("active")
    })

    // Mostrar la opción seleccionada
    const selectedMenuType = document.querySelector('input[name="menu_type"]:checked')
    if (selectedMenuType && menuOptionsGroups[selectedMenuType.value]) {
      menuOptionsGroups[selectedMenuType.value].classList.add("active")
    }

    // Filtrar horarios según el tipo de menú seleccionado
    filterHorariosByMenuType()
  }

  // Función para manejar métodos de pago
  function togglePaymentFields() {
    const selectedMethod = document.querySelector('input[name="metodo_pago"]:checked')

    if (codigoSeguridadGroup && bancoGroup) {
      // Ocultar ambos grupos inicialmente
      codigoSeguridadGroup.style.display = "none"
      codigoSeguridadGroup.classList.remove("show")
      bancoGroup.style.display = "none"
      bancoGroup.classList.remove("show")

      if (selectedMethod) {
        if (selectedMethod.value === "yape") {
          setTimeout(() => {
            codigoSeguridadGroup.style.display = "block"
            codigoSeguridadGroup.classList.add("show")
          }, 100)
        } else if (selectedMethod.value === "transferencia") {
          setTimeout(() => {
            bancoGroup.style.display = "block"
            bancoGroup.classList.add("show")
          }, 100)
        }
      }
    }
  }

  // Función para manejar tipo de comprobante
  function toggleRucField() {
    const selectedComprobante = document.querySelector('input[name="tipo_comprobante"]:checked')
    if (rucFacturaGroup) {
      if (selectedComprobante && selectedComprobante.value === "factura") {
        setTimeout(() => {
          rucFacturaGroup.style.display = "block"
          rucFacturaGroup.classList.add("show")
          const rucInput = rucFacturaGroup.querySelector("input")
          if (rucInput) rucInput.setAttribute("required", "required")
        }, 100)
      } else {
        rucFacturaGroup.style.display = "none"
        rucFacturaGroup.classList.remove("show")
        const rucInput = rucFacturaGroup.querySelector("input")
        if (rucInput) {
          rucInput.value = ""
          rucInput.removeAttribute("required")
        }
      }
    }
  }

  // Event listeners
  menuTypeRadios.forEach((radio) => {
    radio.addEventListener("change", showMenuOptions)
  })

  metodoPagoRadios.forEach((radio) => {
    radio.addEventListener("change", togglePaymentFields)
  })

  tipoComprobanteRadios.forEach((radio) => {
    radio.addEventListener("change", toggleRucField)
  })

  // Función para mostrar errores individuales por campo
  function showFieldError(field, message) {
    // Remover mensaje de error previo
    const existingError = field.parentElement.querySelector(".field-error-message")
    if (existingError) {
      existingError.remove()
    }

    // Agregar clase de error al campo
    field.classList.add("field-error")

    // Crear mensaje de error
    const errorDiv = document.createElement("div")
    errorDiv.className = "field-error-message"
    errorDiv.textContent = message

    // Insertar después del campo
    field.parentElement.appendChild(errorDiv)

    // Remover el error después de 5 segundos
    setTimeout(() => {
      if (errorDiv && errorDiv.parentElement) {
        errorDiv.remove()
      }
      field.classList.remove("field-error")
    }, 5000)
  }

  // Función para limpiar errores de un campo
  function clearFieldError(field) {
    field.classList.remove("field-error")
    const existingError = field.parentElement.querySelector(".field-error-message")
    if (existingError) {
      existingError.remove()
    }
  }

  // VALIDACIONES MEJORADAS EN TIEMPO REAL
  if (telefonoInput) {
    telefonoInput.addEventListener("input", function () {
      const phonePattern = /^9\d{8}$/
      if (this.value && !phonePattern.test(this.value)) {
        this.setCustomValidity("Debe empezar con 9 y tener exactamente 9 dígitos.")
        showFieldError(this, "Debe empezar con 9 y tener exactamente 9 dígitos.")
      } else {
        this.setCustomValidity("")
        clearFieldError(this)
      }
    })
  }

  if (emailInput) {
    emailInput.addEventListener("input", function () {
      const emailPattern = /^[^\s@]+@gmail\.com$/
      if (this.value && !emailPattern.test(this.value)) {
        this.setCustomValidity("El correo debe terminar en @gmail.com")
        showFieldError(this, "El correo debe terminar en @gmail.com")
      } else {
        this.setCustomValidity("")
        clearFieldError(this)
      }
    })
  }

  if (fechaReservaInput) {
    fechaReservaInput.addEventListener("change", function () {
      const selectedDate = new Date(this.value)
      const minDate = new Date()
      minDate.setDate(minDate.getDate() + 2)
      minDate.setHours(0, 0, 0, 0)

      if (selectedDate < minDate) {
        this.setCustomValidity("La fecha debe ser mínimo 2 días después de hoy.")
        showFieldError(this, "La fecha debe ser mínimo 2 días después de hoy.")
      } else {
        this.setCustomValidity("")
        clearFieldError(this)
      }
    })
  }

  // Validación mejorada de números de operación
  const numeroOperacionInput = document.getElementById("numero_operacion")
  if (numeroOperacionInput) {
    numeroOperacionInput.addEventListener("input", function () {
      const selectedMethod = document.querySelector('input[name="metodo_pago"]:checked')
      if (selectedMethod) {
        let isValid = false
        let errorMessage = ""

        if (selectedMethod.value === "yape") {
          isValid = /^\d{8}$/.test(this.value)
          errorMessage = "El número de operación de Yape debe tener exactamente 8 dígitos."
        } else if (selectedMethod.value === "transferencia") {
          isValid = /^\d{8,11}$/.test(this.value)
          errorMessage = "El número de operación de transferencia debe tener entre 8 y 11 dígitos."
        }

        if (this.value && !isValid) {
          this.setCustomValidity(errorMessage)
          showFieldError(this, errorMessage)
        } else {
          this.setCustomValidity("")
          clearFieldError(this)
        }
      }
    })
  }

  // Validación mejorada de RUC
  const rucInput = document.getElementById("ruc_factura")
  if (rucInput) {
    rucInput.addEventListener("input", function () {
      const rucPattern = /^\d{11}$/
      if (this.value && !rucPattern.test(this.value)) {
        this.setCustomValidity("El RUC debe tener exactamente 11 dígitos.")
        showFieldError(this, "El RUC debe tener exactamente 11 dígitos.")
      } else {
        this.setCustomValidity("")
        clearFieldError(this)
      }
    })
  }

  // Formatear código de reserva en mayúsculas
  const codigoReservaInput = document.getElementById("codigo_reserva")
  if (codigoReservaInput) {
    codigoReservaInput.addEventListener("input", function () {
      this.value = this.value.toUpperCase()
    })
  }

  // ===== VALIDACIÓN COMPLETAMENTE CORREGIDA =====
  // SOLO validar en acciones de AVANCE, NUNCA en navegación/cambio/plegado
  const forms = document.querySelectorAll("form")
  forms.forEach((form) => {
    form.addEventListener("submit", (e) => {
      // Obtener el botón que se presionó
      const submitButton = e.submitter || form.querySelector('button[type="submit"]')
      const currentAction = submitButton ? submitButton.value : null

      console.log("Acción detectada:", currentAction)

      // ===== ACCIONES QUE NUNCA DEBEN VALIDAR =====
      const NO_VALIDATION_ACTIONS = [
        // Cambio de actividad / Plegado
        "show_solicitud",
        "show_pago",

        // Navegación hacia atrás
        "solicitud_back",
        "pago_back",

        // Reinicio
        "reset",

        // Cualquier acción que no sea avance
        null,
        undefined,
      ]

      // Si es una acción sin validación, permitir INMEDIATAMENTE
      if (NO_VALIDATION_ACTIONS.includes(currentAction)) {
        console.log("Acción sin validación - Permitiendo inmediatamente")
        return // NO HACER NADA - Dejar que el formulario se envíe
      }

      // ===== SOLO VALIDAR ACCIONES DE AVANCE =====
      const ADVANCE_ACTIONS = [
        "solicitud_next_step1",
        "solicitud_next_step2",
        "solicitud_confirm",
        "pago_next_step1",
        "pago_next_step2",
        "pago_confirm",
      ]

      // Solo validar si es una acción de avance
      if (!ADVANCE_ACTIONS.includes(currentAction)) {
        console.log("No es acción de avance - Permitiendo")
        return // Permitir cualquier otra acción
      }

      console.log("Validando acción de avance:", currentAction)

      // Encontrar el paso activo
      const activeStep = document.querySelector(".form-step.active")
      if (!activeStep) {
        console.log("No se encontró paso activo")
        return
      }

      // Validar campos requeridos del paso activo
      const requiredFields = activeStep.querySelectorAll("input[required], select[required]")
      let isValid = true
      let firstErrorField = null

      requiredFields.forEach((field) => {
        // Verificar si el campo está visible (no está en un grupo oculto)
        const isVisible = field.offsetParent !== null

        if (isVisible) {
          if (field.type === "radio") {
            // Para radio buttons, verificar si al menos uno del grupo está seleccionado
            const radioGroup = document.querySelectorAll(`input[name="${field.name}"]`)
            const isChecked = Array.from(radioGroup).some((radio) => radio.checked)
            if (!isChecked) {
              field.classList.add("field-error")
              if (!firstErrorField) firstErrorField = field
              isValid = false
            } else {
              radioGroup.forEach((radio) => radio.classList.remove("field-error"))
            }
          } else {
            // Para otros tipos de campos
            if (!field.value.trim()) {
              field.classList.add("field-error")
              if (!firstErrorField) firstErrorField = field
              isValid = false
            } else {
              field.classList.remove("field-error")
            }
          }
        }
      })

      // Validaciones específicas adicionales solo para avance
      if (currentAction === "solicitud_next_step1") {
        // Validar que se haya seleccionado un tipo de menú
        const menuType = document.querySelector('input[name="menu_type"]:checked')
        if (!menuType) {
          isValid = false
          const menuRadios = document.querySelectorAll('input[name="menu_type"]')
          menuRadios.forEach((radio) => radio.classList.add("field-error"))
          if (!firstErrorField) firstErrorField = menuRadios[0]
        }

        // Validar opciones de menú según el tipo seleccionado
        if (menuType) {
          const menuValue = menuType.value
          const menuGroup = document.getElementById(`${menuValue}_options`)

          if (menuGroup && menuGroup.classList.contains("active")) {
            const menuRequiredFields = menuGroup.querySelectorAll("select[required]")
            menuRequiredFields.forEach((field) => {
              if (!field.value.trim()) {
                field.classList.add("field-error")
                if (!firstErrorField) firstErrorField = field
                isValid = false
              }
            })
          }
        }
      }

      // Si hay errores, prevenir el envío y mostrar mensaje
      if (!isValid) {
        console.log("Validación falló - Previniendo envío")
        e.preventDefault()

        // Mostrar notificación de error
        showNotification("Por favor, completa todos los campos obligatorios antes de continuar.", "error")

        // Scroll al primer campo con error
        if (firstErrorField) {
          firstErrorField.scrollIntoView({ behavior: "smooth", block: "center" })
          setTimeout(() => firstErrorField.focus(), 500)
        }
      } else {
        console.log("Validación exitosa - Permitiendo envío")

        // Mostrar notificación de éxito para confirmaciones
        if (currentAction === "pago_confirm") {
          showNotification("Procesando registro de pago...", "info", 2000)
        } else if (currentAction === "solicitud_confirm") {
          showNotification("Procesando solicitud de reserva...", "info", 2000)
        }
      }
    })
  })

  // Inicializar estados
  showMenuOptions()
  togglePaymentFields()
  toggleRucField()

  // Animaciones y efectos visuales
  const formSteps = document.querySelectorAll(".form-step")
  formSteps.forEach((step) => {
    if (step.classList.contains("active")) {
      step.style.opacity = "1"
      step.style.transform = "translateY(0)"
    }
  })

  // Efecto ripple para botones
  const buttons = document.querySelectorAll(".btn")
  buttons.forEach((button) => {
    button.addEventListener("click", function (e) {
      const ripple = document.createElement("span")
      const rect = this.getBoundingClientRect()
      const size = Math.max(rect.width, rect.height)
      const x = e.clientX - rect.left - size / 2
      const y = e.clientY - rect.top - size / 2

      ripple.style.width = ripple.style.height = size + "px"
      ripple.style.left = x + "px"
      ripple.style.top = y + "px"
      ripple.classList.add("ripple")

      this.appendChild(ripple)

      setTimeout(() => {
        ripple.remove()
      }, 600)
    })
  })

  // Limpiar localStorage cuando se completa exitosamente
  const urlParams = new URLSearchParams(window.location.search)
  const successMessage = urlParams.get("success")

  if (successMessage) {
    persistentFields.forEach((fieldName) => {
      localStorage.removeItem(`reserva_${fieldName}`)
    })
  }

  // Función para inicializar animaciones
  function initializeAnimations() {
    // Animar elementos al cargar la página
    const animate = (elements, delay = 0) => {
      elements.forEach((element, index) => {
        setTimeout(
          () => {
            element.classList.add("animate")
          },
          delay + index * 50,
        )
      })
    }

    // Animar diferentes grupos de elementos
    setTimeout(() => {
      animate(document.querySelectorAll(".animate-fade-in"), 100)
      animate(document.querySelectorAll(".animate-scale-in"), 200)
      animate(document.querySelectorAll(".animate-button"), 300)
      animate(document.querySelectorAll(".step-animate"), 400)
    }, 100)
  }

  // Inicializar animaciones
  initializeAnimations()

  // Función para animar elementos al hacer scroll
  function animateOnScroll() {
    const elements = document.querySelectorAll(
      ".animate-fade-in:not(.animate), .animate-fade-in-left:not(.animate), .animate-fade-in-right:not(.animate), .animate-scale-in:not(.animate), .animate-slide-up:not(.animate), .animate-button:not(.animate), .step-animate:not(.animate)",
    )

    elements.forEach((element) => {
      const elementTop = element.getBoundingClientRect().top
      const elementVisible = 100

      if (elementTop < window.innerHeight - elementVisible) {
        element.classList.add("animate")
      }
    })
  }

  // Ejecutar animaciones al hacer scroll
  window.addEventListener("scroll", animateOnScroll)
  window.addEventListener("load", () => {
    setTimeout(animateOnScroll, 50)
  })

  console.log("Sistema de Reservas Kawai - JavaScript cargado correctamente")
})

// Estilos CSS adicionales para efectos ripple
const rippleStyles = `
.ripple {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.6);
  transform: scale(0);
  animation: ripple-animation 0.6s linear;
  pointer-events: none;
}

@keyframes ripple-animation {
  to {
    transform: scale(4);
    opacity: 0;
  }
}

.btn {
  position: relative;
  overflow: hidden;
}
`

// Agregar estilos al documento
const styleSheet = document.createElement("style")
styleSheet.textContent = rippleStyles
document.head.appendChild(styleSheet)
