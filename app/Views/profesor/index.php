<?php
$pageTitle = 'Registro de Profesor - Control de Estudios';
require __DIR__ . '/../partials/header.php';
?>
<body class="bg-light">

    <!-- Barra de navegacion principal -->
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="<?= BASE_URL ?>dashboard/index">
                Control de Estudios
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">
                    <?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?>
                    <span class="badge bg-secondary"><?= htmlspecialchars($_SESSION['rol'] ?? '') ?></span>
                </span>
                <a href="<?= BASE_URL ?>dashboard/index" class="btn btn-outline-light btn-sm me-2">
                    Dashboard
                </a>
                <a href="<?= BASE_URL ?>auth/logout" class="btn btn-outline-light btn-sm">
                    Cerrar sesion
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Profesores</h3>
            <!-- Boton para abrir el modal de nuevo profesor -->
            <button type="button" class="btn btn-dark" id="btnNuevoProfesor">
                Nuevo Profesor
            </button>
        </div>

        <!-- Tarjeta con la tabla de profesores -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Cedula</th>
                                <th>Nombres</th>
                                <th>Apellidos</th>
                                <th>Correo</th>
                                <th>Especialidad</th>
                                <th>Rol</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($profesores)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        No hay profesores registrados
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($profesores as $profesor): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($profesor['id']) ?></td>
                                        <td><?= htmlspecialchars($profesor['cedula']) ?></td>
                                        <td><?= htmlspecialchars($profesor['nombres']) ?></td>
                                        <td><?= htmlspecialchars($profesor['apellidos']) ?></td>
                                        <td><?= htmlspecialchars($profesor['correo']) ?></td>
                                        <td><?= htmlspecialchars($profesor['especialidad'] ?? 'Sin asignar') ?></td>
                                        <td>
                                            <?php if ($profesor['rol'] === 'admin'): ?>
                                                <span class="badge bg-danger">Admin</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">Profesor</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <!-- Boton editar con data attributes para JS -->
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary js-editar-profesor"
                                                    data-id="<?= htmlspecialchars($profesor['id']) ?>"
                                                    data-cedula="<?= htmlspecialchars($profesor['cedula']) ?>"
                                                    data-nombres="<?= htmlspecialchars($profesor['nombres']) ?>"
                                                    data-apellidos="<?= htmlspecialchars($profesor['apellidos']) ?>"
                                                    data-fecha="<?= htmlspecialchars($profesor['fecha_nacimiento']) ?>"
                                                    data-genero="<?= htmlspecialchars($profesor['genero']) ?>"
                                                    data-direccion="<?= htmlspecialchars($profesor['direccion'] ?? '') ?>"
                                                    data-telefono="<?= htmlspecialchars($profesor['telefono'] ?? '') ?>"
                                                    data-correo="<?= htmlspecialchars($profesor['correo']) ?>"
                                                    data-especialidad="<?= htmlspecialchars($profesor['especialidad'] ?? '') ?>"
                                                    data-rol="<?= htmlspecialchars($profesor['rol']) ?>">
                                                Editar
                                            </button>
                                            <!-- Boton eliminar con data attributes para JS -->
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger js-eliminar-profesor"
                                                    data-id="<?= htmlspecialchars($profesor['id']) ?>"
                                                    data-nombre="<?= htmlspecialchars($profesor['nombres'] . ' ' . $profesor['apellidos']) ?>">
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal para crear o editar profesor -->
    <div class="modal fade" id="modalProfesor" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="tituloModalProfesor">Nuevo Profesor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <!-- Formulario que se envia via AJAX -->
                <form id="formProfesor">
                    <div class="modal-body">
                        <!-- Campo oculto con el id (0 para nuevo) -->
                        <input type="hidden" name="id" id="profesorId" value="0">

                        <div class="row">
                            <!-- Campo cedula: solo numeros -->
                            <div class="col-md-6 mb-3">
                                <label for="cedula" class="form-label">Cedula *</label>
                                <input type="text"
                                       class="form-control"
                                       id="cedula"
                                       name="cedula"
                                       placeholder="Ej: 12345678"
                                       pattern="[0-9]{6,20}"
                                       inputmode="numeric"
                                       title="Solo numeros, entre 6 y 20 digitos"
                                       required
                                       maxlength="20">
                                <small class="form-text text-muted">
                                    Solo numeros, entre 6 y 20 digitos.
                                </small>
                            </div>

                            <!-- Campo rol -->
                            <div class="col-md-6 mb-3">
                                <label for="rol" class="form-label">Rol *</label>
                                <select class="form-select" id="rol" name="rol" required>
                                    <option value="profesor">Profesor</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Campo nombres: solo letras -->
                            <div class="col-md-6 mb-3">
                                <label for="nombres" class="form-label">Nombres *</label>
                                <input type="text"
                                       class="form-control"
                                       id="nombres"
                                       name="nombres"
                                       placeholder="Nombres del profesor"
                                       pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+"
                                       title="Solo letras y espacios, sin numeros"
                                       required
                                       maxlength="100">
                                <small class="form-text text-muted">
                                    Solo letras y espacios.
                                </small>
                            </div>

                            <!-- Campo apellidos: solo letras -->
                            <div class="col-md-6 mb-3">
                                <label for="apellidos" class="form-label">Apellidos *</label>
                                <input type="text"
                                       class="form-control"
                                       id="apellidos"
                                       name="apellidos"
                                       placeholder="Apellidos del profesor"
                                       pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+"
                                       title="Solo letras y espacios, sin numeros"
                                       required
                                       maxlength="100">
                                <small class="form-text text-muted">
                                    Solo letras y espacios.
                                </small>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Campo fecha de nacimiento -->
                            <div class="col-md-6 mb-3">
                                <label for="fecha_nacimiento" class="form-label">Fecha de nacimiento *</label>
                                <input type="date"
                                       class="form-control"
                                       id="fecha_nacimiento"
                                       name="fecha_nacimiento"
                                       required>
                            </div>

                            <!-- Campo genero -->
                            <div class="col-md-6 mb-3">
                                <label for="genero" class="form-label">Genero *</label>
                                <select class="form-select" id="genero" name="genero" required>
                                    <option value="">Selecciona...</option>
                                    <option value="M">Masculino</option>
                                    <option value="F">Femenino</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Campo telefono: solo numeros y simbolos permitidos -->
                            <div class="col-md-6 mb-3">
                                <label for="telefono" class="form-label">Telefono</label>
                                <input type="text"
                                       class="form-control"
                                       id="telefono"
                                       name="telefono"
                                       placeholder="Ej: 04141234567"
                                       pattern="[0-9+\-\s]{7,20}"
                                       inputmode="tel"
                                       title="Solo numeros, guiones y el signo +"
                                       maxlength="20">
                                <small class="form-text text-muted">
                                    Solo numeros, guiones y el signo +.
                                </small>
                            </div>

                            <!-- Campo correo electronico -->
                            <div class="col-md-6 mb-3">
                                <label for="correo" class="form-label">Correo electronico *</label>
                                <input type="email"
                                       class="form-control"
                                       id="correo"
                                       name="correo"
                                       placeholder="correo@ejemplo.com"
                                       required
                                       maxlength="150">
                            </div>
                        </div>

                        <!-- Campo direccion: libre porque puede llevar numeros -->
                        <div class="mb-3">
                            <label for="direccion" class="form-label">Direccion</label>
                            <input type="text"
                                   class="form-control"
                                   id="direccion"
                                   name="direccion"
                                   placeholder="Direccion del profesor"
                                   maxlength="200">
                        </div>

                        <div class="row">
                            <!-- Campo especialidad: texto simple -->
                            <div class="col-md-6 mb-3">
                                <label for="especialidad" class="form-label">Especialidad</label>
                                <input type="text"
                                       class="form-control"
                                       id="especialidad"
                                       name="especialidad"
                                       placeholder="Ej: Programacion"
                                       pattern="[A-Za-z0-9ÁÉÍÓÚáéíóúÑñÜü\s\.\-]+"
                                       title="Solo letras, numeros, espacios, puntos y guiones"
                                       maxlength="100">
                            </div>

                            <!-- Campo contrasena: obligatoria al crear, opcional al editar -->
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label" id="labelPassword">Contrasena *</label>
                                <input type="password"
                                       class="form-control"
                                       id="password"
                                       name="password"
                                       placeholder="Minimo 8 caracteres"
                                       minlength="8">
                                <!-- Texto de ayuda para la contrasena -->
                                <small class="form-text text-muted">
                                    Minimo 8 caracteres. Debe incluir mayuscula, minuscula, numero y caracter especial (- * + # $ &amp; etc).
                                    Al editar, dejala vacia para no cambiarla.
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-dark">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS desde CDN 
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
-->
    <!-- Boostrap JS nativo-->
    <script src="<?= BASE_URL ?>assets/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script para mostrar mensajes SweetAlert guardados en sesion -->
    <script>
        <?php if (isset($_SESSION['swal'])): ?>
            Swal.fire({
                icon: '<?= htmlspecialchars($_SESSION['swal']['icon'] ?? 'info') ?>',
                title: '<?= htmlspecialchars($_SESSION['swal']['title'] ?? '') ?>',
                text: '<?= htmlspecialchars($_SESSION['swal']['text'] ?? '') ?>',
                confirmButtonText: 'Aceptar'
            });
            <?php unset($_SESSION['swal']); ?>
        <?php endif; ?>
    </script>

    <!-- Definimos BASE_URL antes de cargar el JavaScript del modulo -->
    <script>
        window.BASE_URL = '<?= BASE_URL ?>';
    </script>
    <!-- JavaScript del modulo profesor -->
    <script src="<?= BASE_URL ?>assets/js/profesor.js"></script>

</body>
</html>