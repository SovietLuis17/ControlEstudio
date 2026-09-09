// public/assets/js/estudiante.js
// JavaScript del modulo de estudiantes
// Usa Fetch API y SweetAlert2 para comunicarse con el servidor

(function () {
    const BASE_URL = window.BASE_URL || '/';

    // Referencias a elementos del DOM
    const btnNuevoEstudiante = document.getElementById('btnNuevoEstudiante');
    const formEstudiante = document.getElementById('formEstudiante');
    const modalEstudianteElemento = document.getElementById('modalEstudiante');
    const tituloModalEstudiante = document.getElementById('tituloModalEstudiante');
    const estudianteIdInput = document.getElementById('estudianteId');

    // Instancia del modal de Bootstrap si el elemento existe
    // El modal solo existe para el rol admin
    let modalEstudiante = null;
    if (modalEstudianteElemento) {
        modalEstudiante = new bootstrap.Modal(modalEstudianteElemento);
    }

    // Funcion principal que registra todos los eventos de la pagina
    function init() {
        // Boton para abrir modal de nuevo estudiante
        if (btnNuevoEstudiante) {
            btnNuevoEstudiante.addEventListener('click', abrirModalNuevo);
        }

        // Envio del formulario via AJAX
        if (formEstudiante) {
            formEstudiante.addEventListener('submit', guardarEstudiante);
        }

        // Botones de editar y eliminar con delegacion de eventos
        document.addEventListener('click', function (evento) {
            const botonEditar = evento.target.closest('.js-editar-estudiante');
            const botonEliminar = evento.target.closest('.js-eliminar-estudiante');

            if (botonEditar) {
                abrirModalEditar(botonEditar);
            }

            if (botonEliminar) {
                confirmarEliminacion(botonEliminar);
            }
        });
    }

    // Abre el modal con campos vacios para crear un nuevo estudiante
    function abrirModalNuevo() {
        tituloModalEstudiante.textContent = 'Nuevo Estudiante';
        formEstudiante.reset();
        estudianteIdInput.value = '0';
        modalEstudiante.show();
    }

    // Abre el modal con los datos del estudiante para editar
    function abrirModalEditar(boton) {
        tituloModalEstudiante.textContent = 'Editar Estudiante';
        formEstudiante.reset();
        estudianteIdInput.value = boton.dataset.id;
        document.getElementById('cedula').value = boton.dataset.cedula;
        document.getElementById('nombres').value = boton.dataset.nombres;
        document.getElementById('apellidos').value = boton.dataset.apellidos;
        document.getElementById('fecha_nacimiento').value = boton.dataset.fecha;
        document.getElementById('genero').value = boton.dataset.genero;
        document.getElementById('direccion').value = boton.dataset.direccion;
        document.getElementById('telefono').value = boton.dataset.telefono;
        document.getElementById('correo').value = boton.dataset.correo;
        document.getElementById('carrera').value = boton.dataset.carrera;
        modalEstudiante.show();
    }

    // Envia los datos del formulario al servidor via AJAX
    function guardarEstudiante(evento) {
        evento.preventDefault();

        const formData = new FormData(formEstudiante);

        fetch(BASE_URL + 'estudiante/guardar', {
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
                modalEstudiante.hide();
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
            text: 'Estas seguro de eliminar al estudiante "' + nombre + '"?',
            showCancelButton: true,
            confirmButtonText: 'Si, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                eliminarEstudiante(id);
            }
        });
    }

    // Envia la solicitud de eliminacion al servidor
    function eliminarEstudiante(id) {
        const formData = new FormData();
        formData.append('id', id);

        fetch(BASE_URL + 'estudiante/eliminar', {
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