// public/assets/js/seccion.js
// JavaScript del modulo de secciones
// Usa Fetch API y SweetAlert2 para comunicarse con el servidor

(function () {
    const BASE_URL = window.BASE_URL || '/';

    // Referencias a elementos del DOM
    const btnNuevaSeccion = document.getElementById('btnNuevaSeccion');
    const formSeccion = document.getElementById('formSeccion');
    const modalSeccionElemento = document.getElementById('modalSeccion');
    const tituloModalSeccion = document.getElementById('tituloModalSeccion');
    const seccionIdInput = document.getElementById('seccionId');

    // Instancia del modal de Bootstrap si el elemento existe
    // El modal solo existe para el rol admin
    let modalSeccion = null;
    if (modalSeccionElemento) {
        modalSeccion = new bootstrap.Modal(modalSeccionElemento);
    }

    // Funcion principal que registra todos los eventos de la pagina
    function init() {
        // Boton para abrir modal de nueva seccion
        if (btnNuevaSeccion) {
            btnNuevaSeccion.addEventListener('click', abrirModalNuevo);
        }

        // Envio del formulario via AJAX
        if (formSeccion) {
            formSeccion.addEventListener('submit', guardarSeccion);
        }

        // Botones de editar y eliminar con delegacion de eventos
        document.addEventListener('click', function (evento) {
            const botonEditar = evento.target.closest('.js-editar-seccion');
            const botonEliminar = evento.target.closest('.js-eliminar-seccion');

            if (botonEditar) {
                abrirModalEditar(botonEditar);
            }

            if (botonEliminar) {
                confirmarEliminacion(botonEliminar);
            }
        });
    }

    // Abre el modal con campos vacios para crear una nueva seccion
    function abrirModalNuevo() {
        tituloModalSeccion.textContent = 'Nueva Seccion';
        formSeccion.reset();
        seccionIdInput.value = '0';
        modalSeccion.show();
    }

    // Abre el modal con los datos de la seccion para editar
    function abrirModalEditar(boton) {
        tituloModalSeccion.textContent = 'Editar Seccion';
        formSeccion.reset();
        seccionIdInput.value = boton.dataset.id;
        document.getElementById('materia_id').value = boton.dataset.materia;
        document.getElementById('profesor_id').value = boton.dataset.profesor;
        document.getElementById('periodo_id').value = boton.dataset.periodo;
        document.getElementById('codigo_seccion').value = boton.dataset.codigo;
        document.getElementById('aula').value = boton.dataset.aula;
        document.getElementById('horario').value = boton.dataset.horario;
        modalSeccion.show();
    }

    // Envia los datos del formulario al servidor via AJAX
    function guardarSeccion(evento) {
        evento.preventDefault();

        const formData = new FormData(formSeccion);

        fetch(BASE_URL + 'seccion/guardar', {
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
                modalSeccion.hide();
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
        const codigo = boton.dataset.codigo;

        Swal.fire({
            icon: 'warning',
            title: 'Confirmar eliminacion',
            text: 'Estas seguro de eliminar la seccion "' + codigo + '"?',
            showCancelButton: true,
            confirmButtonText: 'Si, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                eliminarSeccion(id);
            }
        });
    }

    // Envia la solicitud de eliminacion al servidor
    function eliminarSeccion(id) {
        const formData = new FormData();
        formData.append('id', id);

        fetch(BASE_URL + 'seccion/eliminar', {
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