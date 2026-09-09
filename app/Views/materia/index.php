<?php
$pageTitle = 'Materias - Control Estudios';
require __DIR__ . '/../partials/header.php';
?>

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
            <h3 class="mb-0">Materias</h3>
            <?php if ($rol === 'admin'): ?>
                <!-- Boton para abrir el modal de nueva materia (solo admin) -->
                <button type="button" class="btn btn-dark" id="btnNuevaMateria">
                    Nueva Materia
                </button>
            <?php endif; ?>
        </div>

        <!-- Aviso para el profesor sobre el alcance de su listado -->
        <?php if ($rol === 'profesor'): ?>
            <div class="alert alert-info">
                Estas viendo unicamente las materias que dictas.
                Solo puedes gestionar el plan de evaluacion de estas materias.
            </div>
        <?php endif; ?>

        <!-- Tarjeta con la tabla de materias -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Codigo</th>
                                <th>Nombre</th>
                                <th>Creditos</th>
                                <th>Plan de Evaluacion</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($materias)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <?php if ($rol === 'admin'): ?>
                                            No hay materias registradas
                                        <?php else: ?>
                                            No tienes materias asignadas. Contacta al administrador.
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($materias as $materia): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($materia['id']) ?></td>
                                        <td><?= htmlspecialchars($materia['codigo']) ?></td>
                                        <td><?= htmlspecialchars($materia['nombre']) ?></td>
                                        <td><?= htmlspecialchars($materia['creditos']) ?></td>
                                        <td>
                                            <?php if ($materia['plan_completo']): ?>
                                                <span class="badge bg-success">
                                                    Completo (<?= number_format($materia['suma_porcentajes'], 0) ?>%)
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">
                                                    Incompleto (<?= number_format($materia['suma_porcentajes'], 0) ?>%)
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <!-- Boton para ver el plan de evaluacion -->
                                            <a href="<?= BASE_URL ?>planEvaluacion/index/<?= $materia['id'] ?>"
                                               class="btn btn-sm btn-outline-info">
                                                Plan
                                            </a>
                                            <?php if ($rol === 'admin'): ?>
                                                <!-- Boton editar con data attributes para JS -->
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-primary js-editar-materia"
                                                        data-id="<?= htmlspecialchars($materia['id']) ?>"
                                                        data-codigo="<?= htmlspecialchars($materia['codigo']) ?>"
                                                        data-nombre="<?= htmlspecialchars($materia['nombre']) ?>"
                                                        data-creditos="<?= htmlspecialchars($materia['creditos']) ?>"
                                                        data-descripcion="<?= htmlspecialchars($materia['descripcion'] ?? '') ?>">
                                                    Editar
                                                </button>
                                                <!-- Boton eliminar con data attributes para JS -->
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger js-eliminar-materia"
                                                        data-id="<?= htmlspecialchars($materia['id']) ?>"
                                                        data-nombre="<?= htmlspecialchars($materia['nombre']) ?>">
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
    <!-- Modal para crear o editar materia (solo admin) -->
    <div class="modal fade" id="modalMateria" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="tituloModalMateria">Nueva Materia</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <!-- Formulario que se envia via AJAX -->
                <form id="formMateria">
                    <div class="modal-body">
                        <!-- Campo oculto con el id (0 para nuevo) -->
                        <input type="hidden" name="id" id="materiaId" value="0">

                        <!-- Campo codigo de la materia -->
                        <div class="mb-3">
                            <label for="codigo" class="form-label">Codigo *</label>
                            <input type="text"
                                   class="form-control"
                                   id="codigo"
                                   name="codigo"
                                   placeholder="Ej: PROGII-01"
                                   required
                                   maxlength="20">
                        </div>

                        <!-- Campo nombre de la materia -->
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre *</label>
                            <input type="text"
                                   class="form-control"
                                   id="nombre"
                                   name="nombre"
                                   placeholder="Ej: Programacion II"
                                   required
                                   maxlength="100">
                        </div>

                        <!-- Campo creditos -->
                        <div class="mb-3">
                            <label for="creditos" class="form-label">Creditos *</label>
                            <input type="number"
                                   class="form-control"
                                   id="creditos"
                                   name="creditos"
                                   min="1"
                                   max="10"
                                   value="3"
                                   required>
                        </div>

                        <!-- Campo descripcion -->
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripcion</label>
                            <textarea class="form-control"
                                      id="descripcion"
                                      name="descripcion"
                                      rows="3"
                                      maxlength="255"></textarea>
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
    <!-- JavaScript del modulo materia -->
    <script src="<?= BASE_URL ?>assets/js/materia.js"></script>

</body>
</html>