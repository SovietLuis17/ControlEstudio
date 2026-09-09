// public/assets/js/nota.js
// JavaScript del modulo de notas
// Las notas se cargan y editan estudiante por estudiante (no de forma masiva)
// Al abrir el modal se obtienen las actividades y notas del estudiante via AJAX

(function () {
    const BASE_URL = window.BASE_URL || '/';

    // Referencias a elementos del DOM
    const formNotas = document.getElementById('formNotas');
    const modalNotasElemento = document.getElementById('modalNotas');
    const tituloModalNotas = document.getElementById('tituloModalNotas');
    const notaEstudianteId = document.getElementById('notaEstudianteId');
    const notaSeccionId = document.getElementById('notaSeccionId');
    const infoEstudiante = document.getElementById('infoEstudiante');
    const cuerpoActividades = document.getElementById('cuerpoActividades');
    const notaFinalCalculada = document.getElementById('notaFinalCalculada');
    const estadoEstudiante = document.getElementById('estadoEstudiante');

    // Instancia del modal de Bootstrap
    const modalNotas = new bootstrap.Modal(modalNotasElemento);

    // Funcion principal que registra todos los eventos de la pagina
    function init() {
        // Envio del formulario de notas via AJAX
        if (formNotas) {
            formNotas.addEventListener('submit', guardarNotas);
        }

        // Botones de ver/editar notas con delegacion de eventos
        document.addEventListener('click', function (evento) {
            const botonVer = evento.target.closest('.js-ver-notas');

            if (botonVer) {
                cargarNotasEstudiante(botonVer);
            }
        });

        // Evento para recalcular la nota final cuando cambian los inputs
        if (cuerpoActividades) {
            cuerpoActividades.addEventListener('input', calcularNotaFinal);
        }
    }

    // Abre el modal y carga las notas del estudiante via AJAX
    function cargarNotasEstudiante(boton) {
        const estudianteId = boton.dataset.estudianteId;
        const seccionId = boton.dataset.seccionId;
        const nombre = boton.dataset.nombre;

        // Colocamos los datos en los campos ocultos del formulario
        notaEstudianteId.value = estudianteId;
        notaSeccionId.value = seccionId;
        tituloModalNotas.textContent = 'Notas de ' + nombre;
        infoEstudiante.textContent = 'Cargando datos...';
        cuerpoActividades.innerHTML = '';
        notaFinalCalculada.textContent = '---';
        estadoEstudiante.textContent = '---';

        const formData = new FormData();
        formData.append('estudiante_id', estudianteId);
        formData.append('seccion_id', seccionId);

        fetch(BASE_URL + 'nota/ver', {
            method: 'POST',
            body: formData
        })
        .then(function (respuesta) {
            return respuesta.text();
        })
        .then(function (texto) {
            let datos;
            try {
                datos = JSON.parse(texto);
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error del servidor',
                    text: 'La respuesta del servidor no es JSON valido.'
                });
                return;
            }

            if (datos.status === 'success') {
                renderizarModal(datos.data);
                modalNotas.show();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: datos.title,
                    text: datos.message
                });
            }
        })
        .catch(function (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error de conexion',
                text: 'No se pudo comunicar con el servidor.'
            });
        });
    }

    // Construye el contenido del modal con las actividades y notas
    function renderizarModal(data) {
        const estudiante = data.estudiante;
        const actividades = data.actividades;

        // Mostramos info del estudiante
        infoEstudiante.innerHTML =
            '<strong>Estudiante:</strong> ' + estudiante.nombres + ' ' + estudiante.apellidos +
            ' | <strong>Cedula:</strong> ' + estudiante.cedula;

        // Limpiamos la tabla y llenamos con las actividades
        cuerpoActividades.innerHTML = '';

        actividades.forEach(function (actividad) {
            const fila = document.createElement('tr');

            // Columna de nombre de actividad
            const celdaNombre = document.createElement('td');
            celdaNombre.textContent = actividad.nombre_actividad;
            fila.appendChild(celdaNombre);

            // Columna de porcentaje
            const celdaPorcentaje = document.createElement('td');
            celdaPorcentaje.className = 'text-center';
            celdaPorcentaje.textContent = parseFloat(actividad.porcentaje).toFixed(2) + '%';
            fila.appendChild(celdaPorcentaje);

            // Columna de input de nota
            const celdaNota = document.createElement('td');
            celdaNota.className = 'text-center';

            const inputNota = document.createElement('input');
            inputNota.type = 'number';
            inputNota.className = 'form-control js-input-nota';
            inputNota.name = 'notas[' + actividad.actividad_id + ']';
            inputNota.min = '0';
            inputNota.max = '20';
            inputNota.step = '0.01';
            inputNota.placeholder = 'Sin nota';
            // Si ya tiene nota, colocamos el valor
            if (actividad.nota !== null) {
                inputNota.value = actividad.nota;
            }
            celdaNota.appendChild(inputNota);
            fila.appendChild(celdaNota);

            cuerpoActividades.appendChild(fila);
        });

        // Calculamos la nota final inicial con los datos cargados
        calcularNotaFinal();
    }

    // Calcula la nota final ponderada segun los inputs del modal
    // Se ejecuta cada vez que el usuario cambia un valor
    function calcularNotaFinal() {
        const inputs = document.querySelectorAll('.js-input-nota');
        let notaFinal = 0;
        let totalActividadesConNota = 0;

        inputs.forEach(function (input) {
            const valor = parseFloat(input.value);
            if (!isNaN(valor) && input.value !== '') {
                // Obtenemos el porcentaje del input de porcentaje
                // Buscamos la fila padre y la celda de porcentaje
                const fila = input.closest('tr');
                const celdaPorcentaje = fila.children[1];
                const porcentajeTexto = celdaPorcentaje.textContent.replace('%', '');
                const porcentaje = parseFloat(porcentajeTexto);

                if (!isNaN(porcentaje)) {
                    notaFinal += valor * porcentaje / 100;
                    totalActividadesConNota++;
                }
            }
        });

        // Redondeamos a 2 decimales
        notaFinal = Math.round(notaFinal * 100) / 100;

        // Mostramos la nota final calculada
        notaFinalCalculada.textContent = notaFinal.toFixed(2);

        // Determinamos el estado segun la nota final
        // >= 13 es aprobado, < 13 es reprobado
        if (totalActividadesConNota === 0) {
            estadoEstudiante.textContent = 'Sin notas';
            estadoEstudiante.className = 'text-center text-muted';
        } else if (notaFinal >= 13) {
            estadoEstudiante.textContent = 'Aprobado';
            estadoEstudiante.className = 'text-center text-success fw-bold';
        } else {
            estadoEstudiante.textContent = 'Reprobado';
            estadoEstudiante.className = 'text-center text-danger fw-bold';
        }
    }

    // Envia las notas del estudiante al servidor via AJAX
    function guardarNotas(evento) {
        evento.preventDefault();

        const formData = new FormData(formNotas);

        fetch(BASE_URL + 'nota/guardar', {
            method: 'POST',
            body: formData
        })
        .then(function (respuesta) {
            return respuesta.text();
        })
        .then(function (texto) {
            let datos;
            try {
                datos = JSON.parse(texto);
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error del servidor',
                    text: 'La respuesta del servidor no es JSON valido.'
                });
                return;
            }

            if (datos.status === 'success') {
                modalNotas.hide();
                Swal.fire({
                    icon: 'success',
                    title: datos.title,
                    text: datos.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(function () {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: datos.title,
                    text: datos.message
                });
            }
        })
        .catch(function (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error de conexion',
                text: 'No se pudo comunicar con el servidor.'
            });
        });
    }

    // Inicializacion segura cuando el DOM este listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();