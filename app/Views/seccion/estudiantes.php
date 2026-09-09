<?php
$pageTitle = 'Estudiantes de la Seccion - Control de Estudios';
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

        <!-- Boton para volver a la lista de secciones -->
        <a href="<?= BASE_URL ?>seccion/index" class="btn btn-outline-secondary btn-sm mb-3">
            Volver a secciones
        </a>

        <!-- Informacion de la seccion -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">
                    <?= htmlspecialchars($seccion['materia_codigo'] ?? '') ?> -
                    Seccion <?= htmlspecialchars($seccion['codigo_seccion'] ?? '') ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Materia:</strong> <?= htmlspecialchars($seccion['materia_nombre'] ?? '') ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Profesor:</strong> <?= htmlspecialchars($seccion['profesor_nombre'] ?? '') ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Periodo:</strong> <?= htmlspecialchars($seccion['periodo_nombre'] ?? '') ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Aula:</strong> <?= htmlspecialchars($seccion['aula'] ?? 'Sin asignar') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Estudiantes Inscritos</h4>
            <div>
                <!-- Boton para ir a las notas de la seccion -->
                <a href="<?= BASE_URL ?>nota/index/<?= $seccion['id'] ?>"
                   class="btn btn-success btn-sm me-2">
                    Ver Notas
                </a>
                <!-- Boton para abrir el modal de inscripcion -->
                <button type="button" class="btn btn-dark btn-sm" id="btnInscribirEstudiante">
                    Inscribir Estudiante
                </button>
            </div>
        </div>

        <!-- Tarjeta con la tabla de estudiantes inscritos -->
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
                                <th>Carrera</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($estudiantes)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No hay estudiantes inscritos en esta seccion
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
                                        <td><?= htmlspecialchars($estudiante['carrera'] ?? 'Sin asignar') ?></td>
                                        <td class="text-center">
                                            <!-- Boton para quitar estudiante de la seccion -->
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger js-quitar-estudiante"
                                                    data-id="<?= htmlspecialchars($estudiante['id']) ?>"
                                                    data-nombre="<?= htmlspecialchars($estudiante['nombres'] . ' ' . $estudiante['apellidos']) ?>">
                                                Quitar
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

    <!-- Modal para inscribir un estudiante en la seccion -->
    <div class="modal fade" id="modalInscripcion" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Inscribir Estudiante</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <!-- Formulario que se envia via AJAX -->
                <form id="formInscripcion">
                    <div class="modal-body">
                        <!-- Campo oculto con el id de la seccion -->
                        <input type="hidden" name="seccion_id" value="<?= htmlspecialchars($seccion['id']) ?>">

                        <!-- Select con estudiantes disponibles (sin seccion) -->
                        <div class="mb-3">
                            <label for="estudiante_id" class="form-label">Estudiante *</label>
                            <?php if (empty($estudiantesDisponibles)): ?>
                                <div class="alert alert-warning mb-0">
                                    No hay estudiantes disponibles para inscribir.
                                    Todos los estudiantes ya pertenecen a una seccion.
                                </div>
                            <?php else: ?>
                                <select class="form-select" id="estudiante_id" name="estudiante_id" required>
                                    <option value="">Selecciona un estudiante...</option>
                                    <?php foreach ($estudiantesDisponibles as $disponible): ?>
                                        <option value="<?= htmlspecialchars($disponible['id']) ?>">
                                            <?= htmlspecialchars($disponible['cedula']) ?> -
                                            <?= htmlspecialchars($disponible['nombres'] . ' ' . $disponible['apellidos']) ?>
                                            (<?= htmlspecialchars($disponible['carrera'] ?? 'Sin carrera') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <?php if (!empty($estudiantesDisponibles)): ?>
                            <button type="submit" class="btn btn-dark">
                                Inscribir
                            </button>
                        <?php endif; ?>
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
    
    <!-- Definimos BASE_URL antes de cargar el JavaScript del modulo -->
    <script>
        window.BASE_URL = '<?= BASE_URL ?>';
    </script>
    <!-- JavaScript del modulo seccion estudiantes -->
    <script src="<?= BASE_URL ?>assets/js/seccion_estudiantes.js"></script>

</body>
</html>