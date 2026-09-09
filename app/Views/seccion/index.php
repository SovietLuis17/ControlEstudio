<?php
$pageTitle = 'Secciones - Control de Estudios';
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

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Secciones</h3>
            <?php if ($rol === 'admin'): ?>
                <!-- Boton para abrir el modal de nueva seccion (solo admin) -->
                <button type="button" class="btn btn-dark" id="btnNuevaSeccion">
                    Nueva Seccion
                </button>
            <?php endif; ?>
        </div>

        <!-- Tarjeta con la tabla de secciones -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Materia</th>
                                <th>Seccion</th>
                                <th>Periodo</th>
                                <th>Aula</th>
                                <th>Horario</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($secciones)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        No hay secciones registradas
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($secciones as $seccion): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($seccion['id']) ?></td>
                                        <td><?= htmlspecialchars($seccion['materia_codigo'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($seccion['codigo_seccion'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($seccion['periodo_nombre'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($seccion['aula'] ?? 'Sin asignar') ?></td>
                                        <td><?= htmlspecialchars($seccion['horario'] ?? 'Sin asignar') ?></td>
                                        <td class="text-center">
                                            <!-- Boton para ver los estudiantes de la seccion -->
                                            <a href="<?= BASE_URL ?>seccion/estudiantes/<?= $seccion['id'] ?>"
                                               class="btn btn-sm btn-outline-info">
                                                Estudiantes
                                            </a>
                                            <!-- Boton para ver las notas de la seccion -->
                                            <a href="<?= BASE_URL ?>nota/index/<?= $seccion['id'] ?>"
                                               class="btn btn-sm btn-outline-success">
                                                Notas
                                            </a>
                                            <?php if ($rol === 'admin'): ?>
                                                <!-- Boton editar con data attributes para JS -->
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-primary js-editar-seccion"
                                                        data-id="<?= htmlspecialchars($seccion['id']) ?>"
                                                        data-materia="<?= htmlspecialchars($seccion['materia_id'] ?? '') ?>"
                                                        data-profesor="<?= htmlspecialchars($seccion['profesor_id'] ?? '') ?>"
                                                        data-periodo="<?= htmlspecialchars($seccion['periodo_id'] ?? '') ?>"
                                                        data-codigo="<?= htmlspecialchars($seccion['codigo_seccion'] ?? '') ?>"
                                                        data-aula="<?= htmlspecialchars($seccion['aula'] ?? '') ?>"
                                                        data-horario="<?= htmlspecialchars($seccion['horario'] ?? '') ?>">
                                                    Editar
                                                </button>
                                                <!-- Boton eliminar con data attributes para JS -->
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger js-eliminar-seccion"
                                                        data-id="<?= htmlspecialchars($seccion['id']) ?>"
                                                        data-codigo="<?= htmlspecialchars($seccion['materia_codigo'] ?? '') ?> - <?= htmlspecialchars($seccion['codigo_seccion'] ?? '') ?>">
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

    <?php if ($rol === 'admin'): ?>
    <!-- Modal para crear o editar seccion (solo admin) -->
    <div class="modal fade" id="modalSeccion" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="tituloModalSeccion">Nueva Seccion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <!-- Formulario que se envia via AJAX -->
                <form id="formSeccion">
                    <div class="modal-body">
                        <!-- Campo oculto con el id (0 para nuevo) -->
                        <input type="hidden" name="id" id="seccionId" value="0">

                        <div class="row">
                            <!-- Select de materia -->
                            <div class="col-md-6 mb-3">
                                <label for="materia_id" class="form-label">Materia *</label>
                                <select class="form-select" id="materia_id" name="materia_id" required>
                                    <option value="">Selecciona una materia...</option>
                                    <?php foreach ($materias as $materia): ?>
                                        <option value="<?= htmlspecialchars($materia['id']) ?>">
                                            <?= htmlspecialchars($materia['codigo']) ?> - <?= htmlspecialchars($materia['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Select de profesor -->
                            <div class="col-md-6 mb-3">
                                <label for="profesor_id" class="form-label">Profesor *</label>
                                <select class="form-select" id="profesor_id" name="profesor_id" required>
                                    <option value="">Selecciona un profesor...</option>
                                    <?php foreach ($profesores as $profesor): ?>
                                        <option value="<?= htmlspecialchars($profesor['id']) ?>">
                                            <?= htmlspecialchars($profesor['nombres'] . ' ' . $profesor['apellidos']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Select de periodo -->
                            <div class="col-md-6 mb-3">
                                <label for="periodo_id" class="form-label">Periodo *</label>
                                <select class="form-select" id="periodo_id" name="periodo_id" required>
                                    <option value="">Selecciona un periodo...</option>
                                    <?php foreach ($periodos as $periodo): ?>
                                        <option value="<?= htmlspecialchars($periodo['id']) ?>">
                                            <?= htmlspecialchars($periodo['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Campo codigo de seccion -->
                            <div class="col-md-6 mb-3">
                                <label for="codigo_seccion" class="form-label">Codigo de seccion *</label>
                                <input type="text"
                                       class="form-control"
                                       id="codigo_seccion"
                                       name="codigo_seccion"
                                       placeholder="Ej: A, B, C"
                                       required
                                       maxlength="10">
                            </div>
                        </div>

                        <div class="row">
                            <!-- Campo aula -->
                            <div class="col-md-6 mb-3">
                                <label for="aula" class="form-label">Aula</label>
                                <input type="text"
                                       class="form-control"
                                       id="aula"
                                       name="aula"
                                       placeholder="Ej: Aula 301"
                                       maxlength="30">
                            </div>

                            <!-- Campo horario -->
                            <div class="col-md-6 mb-3">
                                <label for="horario" class="form-label">Horario</label>
                                <input type="text"
                                       class="form-control"
                                       id="horario"
                                       name="horario"
                                       placeholder="Ej: Lunes-Miercoles 8:00-10:00"
                                       maxlength="100">
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
    <?php endif; ?>

    <!-- Bootstrap JS desde CDN 
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
-->
    
    <!-- Boostrap JS nativo-->
    <script src="<?= BASE_URL ?>assets/js/bootstrap.bundle.min.js"></script>
    <!-- Definimos BASE_URL antes de cargar el JavaScript del modulo -->
    <script>
        window.BASE_URL = '<?= BASE_URL ?>';
    </script>
    <!-- JavaScript del modulo seccion -->
    <script src="<?= BASE_URL ?>assets/js/seccion.js"></script>

</body>
</html>