// public/assets/js/plan_evaluacion.js
// JavaScript del modulo de plan de evaluacion por materia
// Usa Fetch API y SweetAlert2 para comunicarse con el servidor

(function () {
    const BASE_URL = window.BASE_URL || '/';

    // Referencias a elementos del DOM
    const btnNuevaActividad = document.getElementById('btnNuevaActividad');
    const formActividad = document.getElementById('formActividad');
    const modalActividadElemento = document.getElementById('modalActividad');
    const tituloModalActividad = document.getElementById('tituloModalActividad');
    const actividadIdInput = document.getElementById('actividadId');

    // Instancia del modal de Bootstrap
    const modalActividad = new bootstrap.Modal(modalActividadElemento);

    // Funcion principal que registra todos los eventos de la pagina
    function init() {
        // Boton para abrir modal de nueva actividad
        if (btnNuevaActividad) {
            btnNuevaActividad.addEventListener('click', abrirModalNuevo);
        }

        // Envio del formulario via AJAX
        if (formActividad) {
            formActividad.addEventListener('submit', guardarActividad);
        }

        // Botones de editar y eliminar con delegacion de eventos
        document.addEventListener('click', function (evento) {
            const botonEditar = evento.target.closest('.js-editar-actividad');
            const botonEliminar = evento.target.closest('.js-eliminar-actividad');

            if (botonEditar) {
                abrirModalEditar(botonEditar);
            }

            if (botonEliminar) {
                confirmarEliminacion(botonEliminar);
            }
        });
    }

    // Abre el modal con campos vacios para crear una nueva actividad
    function abrirModalNuevo() {
        tituloModalActividad.textContent = 'Nueva Actividad';
        formActividad.reset();
        actividadIdInput.value = '0';
        modalActividad.show();
    }

    // Abre el modal con los datos de la actividad para editar
    function abrirModalEditar(boton) {
        tituloModalActividad.textContent = 'Editar Actividad';
        actividadIdInput.value = boton.dataset.id;
        document.getElementById('nombre_actividad').value = boton.dataset.nombre;
        document.getElementById('porcentaje').value = boton.dataset.porcentaje;
        modalActividad.show();
    }

    // Envia los datos del formulario al servidor via AJAX
    function guardarActividad(evento) {
        evento.preventDefault();

        const formData = new FormData(formActividad);

        fetch(BASE_URL + 'planEvaluacion/guardar', {
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
                modalActividad.hide();
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

    // Pide confirmacion y envia la solicitud de eliminacion
    function confirmarEliminacion(boton) {
        const id = boton.dataset.id;
        const nombre = boton.dataset.nombre;

        Swal.fire({
            icon: 'warning',
            title: 'Confirmar eliminacion',
            text: 'Estas seguro de eliminar la actividad "' + nombre + '"?',
            showCancelButton: true,
            confirmButtonText: 'Si, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                eliminarActividad(id);
            }
        });
    }

    // Envia la solicitud de eliminacion al servidor
    function eliminarActividad(id) {
        const formData = new FormData();
        formData.append('id', id);

        fetch(BASE_URL + 'planEvaluacion/eliminar', {
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