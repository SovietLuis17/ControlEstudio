<?php
$pageTitle = 'Plan de Evaluacion - Control de Estudios';
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

        <!-- Boton para volver a la lista de materias -->
        <a href="<?= BASE_URL ?>materia/index" class="btn btn-outline-secondary btn-sm mb-3">
            Volver a materias
        </a>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Plan de Evaluacion</h3>
                <h6 class="text-muted">
                    <?= htmlspecialchars($materia['codigo']) ?> - <?= htmlspecialchars($materia['nombre']) ?>
                </h6>
            </div>
            <button type="button" class="btn btn-dark" id="btnNuevaActividad">
                Agregar Actividad
            </button>
        </div>

        <!-- Alerta sobre el estado del plan -->
        <?php if ($planCompleto): ?>
            <div class="alert alert-success">
                El plan de evaluacion esta completo. Suma total: <?= number_format($sumaPorcentajes, 2) ?>%
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                El plan de evaluacion esta incompleto. Suma actual: <?= number_format($sumaPorcentajes, 2) ?>%.
                Debe sumar exactamente 100% para poder cargar notas.
            </div>
        <?php endif; ?>

        <!-- Tarjeta con la tabla de actividades -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Actividad</th>
                                <th>Porcentaje</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($actividades)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        No hay actividades registradas en el plan
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($actividades as $actividad): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($actividad['id']) ?></td>
                                        <td><?= htmlspecialchars($actividad['nombre_actividad']) ?></td>
                                        <td><?= number_format($actividad['porcentaje'], 2) ?>%</td>
                                        <td class="text-center">
                                            <!-- Boton editar con data attributes para JS -->
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary js-editar-actividad"
                                                    data-id="<?= htmlspecialchars($actividad['id']) ?>"
                                                    data-nombre="<?= htmlspecialchars($actividad['nombre_actividad']) ?>"
                                                    data-porcentaje="<?= htmlspecialchars($actividad['porcentaje']) ?>">
                                                Editar
                                            </button>
                                            <!-- Boton eliminar con data attributes para JS -->
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger js-eliminar-actividad"
                                                    data-id="<?= htmlspecialchars($actividad['id']) ?>"
                                                    data-nombre="<?= htmlspecialchars($actividad['nombre_actividad']) ?>">
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <!-- Fila con el total de porcentajes -->
                                <tr class="table-dark">
                                    <td colspan="2" class="text-end"><strong>TOTAL</strong></td>
                                    <td colspan="2"><strong><?= number_format($sumaPorcentajes, 2) ?>%</strong></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal para crear o editar actividad -->
    <div class="modal fade" id="modalActividad" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="tituloModalActividad">Nueva Actividad</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <!-- Formulario que se envia via AJAX -->
                <form id="formActividad">
                    <div class="modal-body">
                        <!-- Campos ocultos con el id y la materia -->
                        <input type="hidden" name="id" id="actividadId" value="0">
                        <input type="hidden" name="materia_id" value="<?= htmlspecialchars($materia['id']) ?>">

                        <!-- Campo nombre de la actividad -->
                        <div class="mb-3">
                            <label for="nombre_actividad" class="form-label">Nombre de la actividad *</label>
                            <input type="text"
                                   class="form-control"
                                   id="nombre_actividad"
                                   name="nombre_actividad"
                                   placeholder="Ej: Parcial 1"
                                   required
                                   maxlength="100">
                        </div>

                        <!-- Campo porcentaje -->
                        <div class="mb-3">
                            <label for="porcentaje" class="form-label">Porcentaje (%) *</label>
                            <input type="number"
                                   class="form-control"
                                   id="porcentaje"
                                   name="porcentaje"
                                   min="1"
                                   max="100"
                                   step="0.01"
                                   placeholder="Ej: 25"
                                   required>
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
    
    <!-- Definimos BASE_URL antes de cargar el JavaScript del modulo -->
    <script>
        window.BASE_URL = '<?= BASE_URL ?>';
    </script>
    <!-- JavaScript del modulo plan de evaluacion -->
    <script src="<?= BASE_URL ?>assets/js/plan_evaluacion.js"></script>

</body>
</html>