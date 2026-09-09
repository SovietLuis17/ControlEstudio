<?php
$pageTitle = 'Estudiantes - Control Estudios';
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
            <h3 class="mb-0">Estudiantes</h3>
            <!-- Boton para abrir el modal de nuevo estudiante -->
            <!-- Admin y profesor pueden crear estudiantes -->
            <button type="button" class="btn btn-dark" id="btnNuevoEstudiante">
                Nuevo Estudiante
            </button>
        </div>

        <!-- Mensaje informativo segun el rol -->
        <?php if ($rol === 'profesor'): ?>
            <div class="alert alert-info">
                Como profesor puedes registrar y editar estudiantes.
                Para eliminar un estudiante debes contactar al administrador del sistema.
            </div>
        <?php endif; ?>

        <!-- Tarjeta con la tabla de estudiantes -->
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
                                <th>Edad</th>
                                <th>Genero</th>
                                <th>Carrera</th>
                                <th>Seccion</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($estudiantes)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        No hay estudiantes registrados
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($estudiantes as $estudiante): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($estudiante['id']) ?></td>
                                        <td><?= htmlspecialchars($estudiante['cedula']) ?></td>
                                        <td><?= htmlspecialchars($estudiante['nombres']) ?></td>
                                        <td><?= htmlspecialchars($estudiante['apellidos']) ?></td>
                                        <td><?= htmlspecialchars($estudiante['edad'] ?? '') ?></td>
                                        <td>
                                            <?php
                                            // Mostramos el genero en texto legible
                                            $generoTexto = 'Otro';
                                            if ($estudiante['genero'] === 'M') {
                                                $generoTexto = 'Masculino';
                                            } elseif ($estudiante['genero'] === 'F') {
                                                $generoTexto = 'Femenino';
                                            }
                                            ?>
                                            <?= htmlspecialchars($generoTexto) ?>
                                        </td>
                                        <td><?= htmlspecialchars($estudiante['carrera'] ?? 'Sin asignar') ?></td>
                                        <td>
                                            <?php if (!empty($estudiante['seccion_id'])): ?>
                                                <span class="badge bg-info text-dark">
                                                    <?= htmlspecialchars($estudiante['materia_codigo'] ?? '') ?>
                                                    - <?= htmlspecialchars($estudiante['codigo_seccion'] ?? '') ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Sin seccion</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <!-- Boton editar: admin y profesor pueden editar -->
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary js-editar-estudiante"
                                                    data-id="<?= htmlspecialchars($estudiante['id']) ?>"
                                                    data-cedula="<?= htmlspecialchars($estudiante['cedula']) ?>"
                                                    data-nombres="<?= htmlspecialchars($estudiante['nombres']) ?>"
                                                    data-apellidos="<?= htmlspecialchars($estudiante['apellidos']) ?>"
                                                    data-fecha="<?= htmlspecialchars($estudiante['fecha_nacimiento']) ?>"
                                                    data-genero="<?= htmlspecialchars($estudiante['genero']) ?>"
                                                    data-direccion="<?= htmlspecialchars($estudiante['direccion'] ?? '') ?>"
                                                    data-telefono="<?= htmlspecialchars($estudiante['telefono'] ?? '') ?>"
                                                    data-correo="<?= htmlspecialchars($estudiante['correo'] ?? '') ?>"
                                                    data-carrera="<?= htmlspecialchars($estudiante['carrera'] ?? '') ?>">
                                                Editar
                                            </button>

                                            <!-- Boton eliminar: SOLO admin puede eliminar -->
                                            <?php if ($rol === 'admin'): ?>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger js-eliminar-estudiante"
                                                        data-id="<?= htmlspecialchars($estudiante['id']) ?>"
                                                        data-nombre="<?= htmlspecialchars($estudiante['nombres'] . ' ' . $estudiante['apellidos']) ?>">
                                                    Eliminar
                                                </button>
                                            <?php endif; ?>
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

    <!-- Modal para crear o editar estudiante -->
    <!-- Admin y profesor pueden crear y editar estudiantes -->
    <div class="modal fade" id="modalEstudiante" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="tituloModalEstudiante">Nuevo Estudiante</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <!-- Formulario que se envia via AJAX -->
                <form id="formEstudiante">
                    <div class="modal-body">
                        <!-- Campo oculto con el id (0 para nuevo) -->
                        <input type="hidden" name="id" id="estudianteId" value="0">

                        <div class="row">
                            <!-- Campo cedula: solo numeros -->
                            <div class="col-md-6 mb-3">
                                <label for="cedula" class="form-label">Cedula *</label>
                                <input type="text"
                                       class="form-control"
                                       id="cedula"
                                       name="cedula"
                                       placeholder="Ej: 20123456"
                                       pattern="[0-9]{6,20}"
                                       inputmode="numeric"
                                       title="Solo numeros, entre 6 y 20 digitos"
                                       required
                                       maxlength="20">
                                <small class="form-text text-muted">
                                    Solo numeros, entre 6 y 20 digitos.
                                </small>
                            </div>

                            <!-- Campo carrera: texto simple -->
                            <div class="col-md-6 mb-3">
                                <label for="carrera" class="form-label">Carrera</label>
                                <input type="text"
                                       class="form-control"
                                       id="carrera"
                                       name="carrera"
                                       placeholder="Ej: Informatica"
                                       pattern="[A-Za-z0-9ÁÉÍÓÚáéíóúÑñÜü\s\.\-]+"
                                       title="Solo letras, numeros, espacios, puntos y guiones"
                                       maxlength="100">
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
                                       placeholder="Nombres del estudiante"
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
                                       placeholder="Apellidos del estudiante"
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
                                <label for="correo" class="form-label">Correo electronico</label>
                                <input type="email"
                                       class="form-control"
                                       id="correo"
                                       name="correo"
                                       placeholder="correo@ejemplo.com"
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
                                   placeholder="Direccion del estudiante"
                                   maxlength="200">
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
    <!-- JavaScript del modulo estudiante -->
    <script src="<?= BASE_URL ?>assets/js/estudiante.js"></script>

</body>
</html>