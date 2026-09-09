// public/assets/js/periodo.js
// JavaScript del modulo de periodos academicos
// Usa Fetch API y SweetAlert2 para comunicarse con el servidor

(function () {
    const BASE_URL = window.BASE_URL || '/';

    // Referencias a elementos del DOM
    const btnNuevoPeriodo = document.getElementById('btnNuevoPeriodo');
    const formPeriodo = document.getElementById('formPeriodo');
    const modalPeriodoElemento = document.getElementById('modalPeriodo');
    const tituloModalPeriodo = document.getElementById('tituloModalPeriodo');
    const periodoIdInput = document.getElementById('periodoId');
    const nombreInput = document.getElementById('nombre');
    const estadoSelect = document.getElementById('estado');

    // Instancia del modal de Bootstrap
    const modalPeriodo = new bootstrap.Modal(modalPeriodoElemento);

    // Funcion principal que registra todos los eventos de la pagina
    function init() {
        // Boton para abrir modal de nuevo periodo
        if (btnNuevoPeriodo) {
            btnNuevoPeriodo.addEventListener('click', abrirModalNuevo);
        }

        // Envio del formulario via AJAX
        if (formPeriodo) {
            formPeriodo.addEventListener('submit', guardarPeriodo);
        }

        // Botones de editar y eliminar con delegacion de eventos
        document.addEventListener('click', function (evento) {
            const botonEditar = evento.target.closest('.js-editar-periodo');
            const botonEliminar = evento.target.closest('.js-eliminar-periodo');

            if (botonEditar) {
                abrirModalEditar(botonEditar);
            }

            if (botonEliminar) {
                confirmarEliminacion(botonEliminar);
            }
        });
    }

    // Abre el modal con campos vacios para crear un nuevo periodo
    function abrirModalNuevo() {
        tituloModalPeriodo.textContent = 'Nuevo Periodo';
        formPeriodo.reset();
        periodoIdInput.value = '0';
        modalPeriodo.show();
    }

    // Abre el modal con los datos del periodo para editar
    function abrirModalEditar(boton) {
        tituloModalPeriodo.textContent = 'Editar Periodo';
        periodoIdInput.value = boton.dataset.id;
        nombreInput.value = boton.dataset.nombre;
        estadoSelect.value = boton.dataset.estado;
        modalPeriodo.show();
    }

    // Envia los datos del formulario al servidor via AJAX
    function guardarPeriodo(evento) {
        evento.preventDefault();

        const formData = new FormData(formPeriodo);

        fetch(BASE_URL + 'periodo/guardar', {
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
                modalPeriodo.hide();
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
            text: 'Estas seguro de eliminar el periodo "' + nombre + '"?',
            showCancelButton: true,
            confirmButtonText: 'Si, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                eliminarPeriodo(id);
            }
        });
    }

    // Envia la solicitud de eliminacion al servidor
    function eliminarPeriodo(id) {
        const formData = new FormData();
        formData.append('id', id);

        fetch(BASE_URL + 'periodo/eliminar', {
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