// public/assets/js/profesor.js
// JavaScript del modulo de profesores
// Usa Fetch API y SweetAlert2 para comunicarse con el servidor

(function () {
    const BASE_URL = window.BASE_URL || '/';

    // Referencias a elementos del DOM
    const btnNuevoProfesor = document.getElementById('btnNuevoProfesor');
    const formProfesor = document.getElementById('formProfesor');
    const modalProfesorElemento = document.getElementById('modalProfesor');
    const tituloModalProfesor = document.getElementById('tituloModalProfesor');
    const profesorIdInput = document.getElementById('profesorId');
    const labelPassword = document.getElementById('labelPassword');
    const passwordInput = document.getElementById('password');

    // Instancia del modal de Bootstrap
    const modalProfesor = new bootstrap.Modal(modalProfesorElemento);

    // Funcion principal que registra todos los eventos de la pagina
    function init() {
        // Boton para abrir modal de nuevo profesor
        if (btnNuevoProfesor) {
            btnNuevoProfesor.addEventListener('click', abrirModalNuevo);
        }

        // Envio del formulario via AJAX
        if (formProfesor) {
            formProfesor.addEventListener('submit', guardarProfesor);
        }

        // Botones de editar y eliminar con delegacion de eventos
        document.addEventListener('click', function (evento) {
            const botonEditar = evento.target.closest('.js-editar-profesor');
            const botonEliminar = evento.target.closest('.js-eliminar-profesor');

            if (botonEditar) {
                abrirModalEditar(botonEditar);
            }

            if (botonEliminar) {
                confirmarEliminacion(botonEliminar);
            }
        });
    }

    // Abre el modal con campos vacios para crear un nuevo profesor
    function abrirModalNuevo() {
        tituloModalProfesor.textContent = 'Nuevo Profesor';
        formProfesor.reset();
        profesorIdInput.value = '0';
        // Al crear, la contrasena es obligatoria
        labelPassword.textContent = 'Contrasena *';
        passwordInput.setAttribute('required', 'required');
        modalProfesor.show();
    }

    // Abre el modal con los datos del profesor para editar
    function abrirModalEditar(boton) {
        tituloModalProfesor.textContent = 'Editar Profesor';
        formProfesor.reset();
        profesorIdInput.value = boton.dataset.id;
        document.getElementById('cedula').value = boton.dataset.cedula;
        document.getElementById('nombres').value = boton.dataset.nombres;
        document.getElementById('apellidos').value = boton.dataset.apellidos;
        document.getElementById('fecha_nacimiento').value = boton.dataset.fecha;
        document.getElementById('genero').value = boton.dataset.genero;
        document.getElementById('direccion').value = boton.dataset.direccion;
        document.getElementById('telefono').value = boton.dataset.telefono;
        document.getElementById('correo').value = boton.dataset.correo;
        document.getElementById('especialidad').value = boton.dataset.especialidad;
        document.getElementById('rol').value = boton.dataset.rol;
        // Al editar, la contrasena es opcional (si se deja vacia no se cambia)
        labelPassword.textContent = 'Contrasena (opcional para editar)';
        passwordInput.removeAttribute('required');
        modalProfesor.show();
    }

    // Envia los datos del formulario al servidor via AJAX
    function guardarProfesor(evento) {
        evento.preventDefault();

        const formData = new FormData(formProfesor);

        fetch(BASE_URL + 'profesor/guardar', {
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
                modalProfesor.hide();
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
            text: 'Estas seguro de eliminar al profesor "' + nombre + '"?',
            showCancelButton: true,
            confirmButtonText: 'Si, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                eliminarProfesor(id);
            }
        });
    }

    // Envia la solicitud de eliminacion al servidor
    function eliminarProfesor(id) {
        const formData = new FormData();
        formData.append('id', id);

        fetch(BASE_URL + 'profesor/eliminar', {
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