<?php
$pageTitle = 'Periodos - Control de Estudios';
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
            <h3 class="mb-0">Periodos Academicos</h3>
            <!-- Boton para abrir el modal de nuevo periodo -->
            <button type="button" class="btn btn-dark" id="btnNuevoPeriodo">
                Nuevo Periodo
            </button>
        </div>

        <!-- Tarjeta con la tabla de periodos -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Nombre</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($periodos)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        No hay periodos registrados
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($periodos as $periodo): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($periodo['id']) ?></td>
                                        <td><?= htmlspecialchars($periodo['nombre']) ?></td>
                                        <td>
                                            <?php if ($periodo['estado'] === 'activo'): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Cerrado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <!-- Boton editar con data attributes para JS -->
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary js-editar-periodo"
                                                    data-id="<?= htmlspecialchars($periodo['id']) ?>"
                                                    data-nombre="<?= htmlspecialchars($periodo['nombre']) ?>"
                                                    data-estado="<?= htmlspecialchars($periodo['estado']) ?>">
                                                Editar
                                            </button>
                                            <!-- Boton eliminar con data attributes para JS -->
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger js-eliminar-periodo"
                                                    data-id="<?= htmlspecialchars($periodo['id']) ?>"
                                                    data-nombre="<?= htmlspecialchars($periodo['nombre']) ?>">
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

    <!-- Modal para crear o editar periodo -->
    <div class="modal fade" id="modalPeriodo" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="tituloModalPeriodo">Nuevo Periodo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <!-- Formulario que se envia via AJAX -->
                <form id="formPeriodo">
                    <div class="modal-body">
                        <!-- Campo oculto con el id (0 para nuevo) -->
                        <input type="hidden" name="id" id="periodoId" value="0">

                        <!-- Campo nombre del periodo -->
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del periodo *</label>
                            <input type="text"
                                   class="form-control"
                                   id="nombre"
                                   name="nombre"
                                   placeholder="Ej: 2026-1"
                                   required
                                   maxlength="20">
                        </div>

                        <!-- Campo estado del periodo -->
                        <div class="mb-3">
                            <label for="estado" class="form-label">Estado *</label>
                            <select class="form-select" id="estado" name="estado" required>
                                <option value="activo">Activo</option>
                                <option value="cerrado">Cerrado</option>
                            </select>
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
    <!-- JavaScript del modulo periodo -->
    <script src="<?= BASE_URL ?>assets/js/periodo.js"></script>

</body>
</html>