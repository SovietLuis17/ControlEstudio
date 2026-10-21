<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Control de Estudios</title>
    <!-- Bootstrap 5 desde CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 desde CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
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
                <a href="<?= BASE_URL ?>reporte/index" class="btn btn-outline-light btn-sm me-2">
                    Reportes
                </a>
                <a href="<?= BASE_URL ?>auth/logout" class="btn btn-outline-light btn-sm">
                    Cerrar sesion
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">

        <h3 class="mb-3">Modulo de Reportes</h3>

        <?php if (empty($seccionesPorPeriodo)): ?>
            <div class="alert alert-info">
                No tienes secciones disponibles para generar reportes.
                Contacta al administrador del sistema.
            </div>
        <?php else: ?>

            <!-- Tarjeta con el formulario de seleccion y descargas -->
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    Generar reporte por seccion
                </div>
                <div class="card-body">

                    <!-- Un solo formulario GET con tres botones formaction -->
                    <!-- Cada boton envia el mismo select a una descarga distinta -->
                    <form method="GET" class="row g-3 align-items-end">

                        <!-- Selector de seccion agrupado por periodo -->
                        <div class="col-md-6">
                            <label for="id" class="form-label">Seccion *</label>
                            <select class="form-select" id="id" name="id" required>
                                <option value="">Selecciona una seccion...</option>
                                <?php foreach ($seccionesPorPeriodo as $periodo => $lista): ?>
                                    <optgroup label="Periodo <?= htmlspecialchars($periodo) ?>">
                                        <?php foreach ($lista as $seccion): ?>
                                            <option value="<?= htmlspecialchars($seccion['id']) ?>">
                                                <?= htmlspecialchars(
                                                    $seccion['materia_codigo'] . ' - ' .
                                                    $seccion['materia_nombre'] . ' - Seccion ' .
                                                    $seccion['codigo_seccion']
                                                ) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Botones de descarga, cada uno con su formaction -->
                        <div class="col-md-6 d-flex gap-2 flex-wrap">
                            <button type="submit"
                                    formaction="<?= BASE_URL ?>reporte/notasPdf"
                                    class="btn btn-danger">
                                Notas PDF
                            </button>
                            <button type="submit"
                                    formaction="<?= BASE_URL ?>reporte/notasExcel"
                                    class="btn btn-success">
                                Notas Excel
                            </button>
                            <button type="submit"
                                    formaction="<?= BASE_URL ?>reporte/inscritosExcel"
                                    class="btn btn-success">
                                Inscritos Excel
                            </button>
                        </div>
                    </form>

                </div>
            </div>

            <!-- Tarjetas explicativas de cada reporte -->
            <div class="row mt-4">
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Notas en PDF</h5>
                            <p class="card-text text-muted">
                                Tabla con todas las actividades del plan, nota final ponderada
                                y estado de cada estudiante. Incluye logo institucional.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Notas en Excel</h5>
                            <p class="card-text text-muted">
                                El mismo contenido del PDF en formato .xls nativo,
                                listo para abrir con doble clic en Excel.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Inscritos en Excel</h5>
                            <p class="card-text text-muted">
                                Listado de estudiantes de la seccion con cedula, datos
                                personales, carrera y contacto.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>

    </div>

    <!-- Bootstrap JS desde CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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

</body>
</html>