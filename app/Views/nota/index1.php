<?php
$pageTitle = 'Notas - Control de Estudios';
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
                    Seccion <?= htmlspecialchars($seccion['codigo_seccion'] ?? '') ?> -
                    <?= htmlspecialchars($seccion['periodo_nombre'] ?? '') ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Materia:</strong> <?= htmlspecialchars($seccion['materia_nombre'] ?? '') ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Profesor:</strong> <?= htmlspecialchars($seccion['profesor_nombre'] ?? '') ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Plan de evaluacion:</strong>
                        <?php foreach ($actividades as $actividad): ?>
                            <?= htmlspecialchars($actividad['nombre_actividad']) ?>
                            (<?= number_format($actividad['porcentaje'], 0) ?>%)
                            <?php if ($actividad !== end($actividades)): ?>, <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Resumen de Notas</h4>
            <!-- Boton para ir a los estudiantes de la seccion -->
            <a href="<?= BASE_URL ?>seccion/estudiantes/<?= $seccion['id'] ?>"
               class="btn btn-outline-primary btn-sm">
                Ver Estudiantes
            </a>
        </div>

        <!-- Leyenda de estados -->
        <div class="mb-3">
            <span class="badge bg-success">Aprobado (>= 13)</span>
            <span class="badge bg-danger">Reprobado (< 13)</span>
            <span class="badge bg-secondary">Sin notas</span>
        </div>

        <!-- Tarjeta con la tabla de resumen de notas -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Cedula</th>
                                <th>Estudiante</th>
                                <th class="text-center">Nota Final</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($resumen)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No hay estudiantes inscritos en esta seccion
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($resumen as $fila): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($fila['estudiante_id']) ?></td>
                                        <td><?= htmlspecialchars($fila['cedula']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($fila['nombres'] . ' ' . $fila['apellidos']) ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($fila['estado'] === 'sin_notas'): ?>
                                                <span class="text-muted">---</span>
                                            <?php else: ?>
                                                <strong><?= number_format($fila['nota_final'], 2) ?></strong>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($fila['estado'] === 'aprobado'): ?>
                                                <span class="badge bg-success">Aprobado</span>
                                            <?php elseif ($fila['estado'] === 'reprobado'): ?>
                                                <span class="badge bg-danger">Reprobado</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Sin notas</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <!-- Boton para ver y editar notas del estudiante -->
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary js-ver-notas"
                                                    data-estudiante-id="<?= htmlspecialchars($fila['estudiante_id']) ?>"
                                                    data-seccion-id="<?= htmlspecialchars($seccion['id']) ?>"
                                                    data-nombre="<?= htmlspecialchars($fila['nombres'] . ' ' . $fila['apellidos']) ?>">
                                                Ver / Editar
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

    <!-- Modal para ver y editar notas de un estudiante especifico -->
    <!-- Este modal se llena dinamicamente con JavaScript -->
    <div class="modal fade" id="modalNotas" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="tituloModalNotas">Notas del Estudiante</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <!-- Formulario que se envia via AJAX -->
                <form id="formNotas">
                    <div class="modal-body">
                        <!-- Campos ocultos -->
                        <input type="hidden" name="estudiante_id" id="notaEstudianteId" value="0">
                        <input type="hidden" name="seccion_id" id="notaSeccionId" value="0">

                        <!-- Informacion del estudiante -->
                        <div class="alert alert-info" id="infoEstudiante">
                            Cargando datos del estudiante...
                        </div>

                        <!-- Tabla de actividades con inputs de nota -->
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Actividad</th>
                                        <th class="text-center">Porcentaje</th>
                                        <th class="text-center">Nota (0-20)</th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpoActividades">
                                    <!-- Se llena dinamicamente con JavaScript -->
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>NOTA FINAL</th>
                                        <th class="text-center">100%</th>
                                        <th class="text-center" id="notaFinalCalculada">---</th>
                                    </tr>
                                    <tr>
                                        <th>ESTADO</th>
                                        <th colspan="2" class="text-center" id="estadoEstudiante">---</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-dark">
                            Guardar Notas
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
    <!-- JavaScript del modulo nota -->
    <script src="<?= BASE_URL ?>assets/js/nota.js"></script>

</body>
</html>