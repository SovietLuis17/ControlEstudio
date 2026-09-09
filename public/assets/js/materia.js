// public/assets/js/materia.js
// JavaScript del modulo de materias
// Usa Fetch API y SweetAlert2 para comunicarse con el servidor

(function () {
    const BASE_URL = window.BASE_URL || '/';

    // Referencias a elementos del DOM
    const btnNuevaMateria = document.getElementById('btnNuevaMateria');
    const formMateria = document.getElementById('formMateria');
    const modalMateriaElemento = document.getElementById('modalMateria');
    const tituloModalMateria = document.getElementById('tituloModalMateria');
    const materiaIdInput = document.getElementById('materiaId');

    // Instancia del modal de Bootstrap si el elemento existe
    let modalMateria = null;
    if (modalMateriaElemento) {
        modalMateria = new bootstrap.Modal(modalMateriaElemento);
    }

    // Funcion principal que registra todos los eventos de la pagina
    function init() {
        // Boton para abrir modal de nueva materia
        if (btnNuevaMateria) {
            btnNuevaMateria.addEventListener('click', abrirModalNuevo);
        }

        // Envio del formulario via AJAX
        if (formMateria) {
            formMateria.addEventListener('submit', guardarMateria);
        }

        // Botones de editar y eliminar con delegacion de eventos
        document.addEventListener('click', function (evento) {
            const botonEditar = evento.target.closest('.js-editar-materia');
            const botonEliminar = evento.target.closest('.js-eliminar-materia');

            if (botonEditar) {
                abrirModalEditar(botonEditar);
            }

            if (botonEliminar) {
                confirmarEliminacion(botonEliminar);
            }
        });
    }

    // Abre el modal con campos vacios para crear una nueva materia
    function abrirModalNuevo() {
        tituloModalMateria.textContent = 'Nueva Materia';
        formMateria.reset();
        materiaIdInput.value = '0';
        modalMateria.show();
    }

    // Abre el modal con los datos de la materia para editar
    function abrirModalEditar(boton) {
        tituloModalMateria.textContent = 'Editar Materia';
        materiaIdInput.value = boton.dataset.id;
        document.getElementById('codigo').value = boton.dataset.codigo;
        document.getElementById('nombre').value = boton.dataset.nombre;
        document.getElementById('creditos').value = boton.dataset.creditos;
        document.getElementById('descripcion').value = boton.dataset.descripcion;
        modalMateria.show();
    }

    // Envia los datos del formulario al servidor via AJAX
    function guardarMateria(evento) {
        evento.preventDefault();

        const formData = new FormData(formMateria);

        fetch(BASE_URL + 'materia/guardar', {
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
                modalMateria.hide();
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
            text: 'Estas seguro de eliminar la materia "' + nombre + '"?',
            showCancelButton: true,
            confirmButtonText: 'Si, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                eliminarMateria(id);
            }
        });
    }

    // Envia la solicitud de eliminacion al servidor
    function eliminarMateria(id) {
        const formData = new FormData();
        formData.append('id', id);

        fetch(BASE_URL + 'materia/eliminar', {
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