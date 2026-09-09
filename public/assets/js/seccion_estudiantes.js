// public/assets/js/seccion_estudiantes.js
// JavaScript para gestionar la inscripcion de estudiantes en una seccion
// Permite inscribir estudiantes disponibles y quitarlos de la seccion

(function () {
    const BASE_URL = window.BASE_URL || '/';

    // Referencias a elementos del DOM
    const btnInscribirEstudiante = document.getElementById('btnInscribirEstudiante');
    const formInscripcion = document.getElementById('formInscripcion');
    const modalInscripcionElemento = document.getElementById('modalInscripcion');

    // Instancia del modal de Bootstrap
    const modalInscripcion = new bootstrap.Modal(modalInscripcionElemento);

    // Funcion principal que registra todos los eventos de la pagina
    function init() {
        // Boton para abrir modal de inscripcion
        if (btnInscribirEstudiante) {
            btnInscribirEstudiante.addEventListener('click', abrirModalInscripcion);
        }

        // Envio del formulario de inscripcion via AJAX
        if (formInscripcion) {
            formInscripcion.addEventListener('submit', inscribirEstudiante);
        }

        // Botones de quitar estudiante con delegacion de eventos
        document.addEventListener('click', function (evento) {
            const botonQuitar = evento.target.closest('.js-quitar-estudiante');

            if (botonQuitar) {
                confirmarQuitar(botonQuitar);
            }
        });
    }

    // Abre el modal para inscribir un estudiante en la seccion
    function abrirModalInscripcion() {
        formInscripcion.reset();
        modalInscripcion.show();
    }

    // Envia la solicitud de inscripcion al servidor via AJAX
    function inscribirEstudiante(evento) {
        evento.preventDefault();

        const formData = new FormData(formInscripcion);

        fetch(BASE_URL + 'estudiante/asignarSeccion', {
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
                modalInscripcion.hide();
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

    // Pide confirmacion y envia la solicitud de quitar estudiante
    function confirmarQuitar(boton) {
        const id = boton.dataset.id;
        const nombre = boton.dataset.nombre;

        Swal.fire({
            icon: 'warning',
            title: 'Confirmar accion',
            text: 'Estas seguro de quitar a "' + nombre + '" de esta seccion? Se eliminaran sus notas de esta materia.',
            showCancelButton: true,
            confirmButtonText: 'Si, quitar',
            cancelButtonText: 'Cancelar'
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                quitarEstudiante(id);
            }
        });
    }

    // Envia la solicitud de quitar estudiante al servidor
    function quitarEstudiante(id) {
        const formData = new FormData();
        formData.append('estudiante_id', id);

        fetch(BASE_URL + 'estudiante/quitarSeccion', {
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