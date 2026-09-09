<?php
$pageTitle = 'Dashboard del Sistema';
require __DIR__ . '/../partials/header.php';
?>
<body class="bg-light">

    <!-- Barra de navegacion principal con menu segun el rol -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="<?= BASE_URL ?>dashboard/index">
                Control de Estudios
            </a>
            <!-- Boton para menu en moviles -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="menuPrincipal">
                <!-- Menu de modulos segun el rol del usuario -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if ($datos['rol'] === 'admin'): ?>
                        <!-- Menu completo para el admin -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>periodo/index">Periodos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>materia/index">Materias</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>profesor/index">Profesores</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>estudiante/index">Estudiantes</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>seccion/index">Secciones</a>
                        </li>
                    <?php else: ?>
                        <!-- Menu reducido para el profesor -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>estudiante/index">Estudiantes</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>seccion/index">Mis Secciones</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>materia/index">Materias</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <!-- Datos del usuario y cierre de sesion -->
                <div class="d-flex align-items-center">
                    <span class="text-white me-3">
                        <?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?>
                        <span class="badge bg-secondary"><?= htmlspecialchars($_SESSION['rol'] ?? '') ?></span>
                    </span>
                    <a href="<?= BASE_URL ?>auth/logout" class="btn btn-outline-light btn-sm">
                        Cerrar sesion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">

        <?php if ($datos['rol'] === 'admin'): ?>
            <!-- ============================================ -->
            <!-- DASHBOARD DEL ADMIN                          -->
            <!-- ============================================ -->

            <h3 class="mb-4">Panel de Administracion</h3>

            <!-- Tarjetas con totales del sistema -->
            <div class="row mb-4">
                <div class="col-md-2 mb-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body text-center">
                            <h5 class="card-title"><?= $datos['totalPeriodos'] ?></h5>
                            <p class="card-text mb-0">Periodos</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="card text-white bg-success">
                        <div class="card-body text-center">
                            <h5 class="card-title"><?= $datos['totalMaterias'] ?></h5>
                            <p class="card-text mb-0">Materias</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="card text-white bg-info">
                        <div class="card-body text-center">
                            <h5 class="card-title"><?= $datos['totalProfesores'] ?></h5>
                            <p class="card-text mb-0">Profesores</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body text-center">
                            <h5 class="card-title"><?= $datos['totalEstudiantes'] ?></h5>
                            <p class="card-text mb-0">Estudiantes</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-danger">
                        <div class="card-body text-center">
                            <h5 class="card-title"><?= $datos['totalSecciones'] ?></h5>
                            <p class="card-text mb-0">Secciones</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accesos rapidos a los modulos -->
            <h5 class="mb-3">Modulos del sistema</h5>
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <a href="<?= BASE_URL ?>periodo/index" class="btn btn-primary w-100">
                        Periodos
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="<?= BASE_URL ?>materia/index" class="btn btn-success w-100">
                        Materias
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="<?= BASE_URL ?>profesor/index" class="btn btn-info w-100">
                        Profesores
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="<?= BASE_URL ?>estudiante/index" class="btn btn-warning w-100">
                        Estudiantes
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="<?= BASE_URL ?>seccion/index" class="btn btn-danger w-100">
                        Secciones
                    </a>
                </div>
            </div>

            <!-- Tabla de ultimas secciones registradas -->
            <div class="card">
                <div class="card-header bg-dark text-white">
                    Ultimas secciones registradas
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Materia</th>
                                    <th>Seccion</th>
                                    <th>Profesor</th>
                                    <th>Periodo</th>
                                    <th>Aula</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($datos['secciones'])): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            No hay secciones registradas
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach (array_slice($datos['secciones'], 0, 5) as $seccion): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($seccion['materia_codigo'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($seccion['codigo_seccion'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($seccion['profesor_nombre'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($seccion['periodo_nombre'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($aula = $seccion['aula'] ?? 'Sin asignar') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- ============================================ -->
            <!-- DASHBOARD DEL PROFESOR                       -->
            <!-- ============================================ -->

            <h3 class="mb-4">Panel del Profesor</h3>

            <!-- Accesos rapidos para el profesor -->
            <div class="row mb-4">
                <!-- Tarjeta para gestionar estudiantes -->
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title">Gestionar Estudiantes</h5>
                            <p class="card-text text-muted">
                                Registra y edita estudiantes del sistema.
                            </p>
                            <a href="<?= BASE_URL ?>estudiante/index" class="btn btn-primary">
                                Ir a Estudiantes
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Tarjeta para ver sus secciones -->
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title">Mis Secciones</h5>
                            <p class="card-text text-muted">
                                Consulta las secciones que dictas y inscribe estudiantes.
                            </p>
                            <a href="<?= BASE_URL ?>seccion/index" class="btn btn-success">
                                Ver Secciones
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Tarjeta para ver materias -->
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title">Materias</h5>
                            <p class="card-text text-muted">
                                Consulta el catalogo de materias y sus planes.
                            </p>
                            <a href="<?= BASE_URL ?>materia/index" class="btn btn-info">
                                Ver Materias
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <h4 class="mb-3">Mis Secciones Asignadas</h4>

            <?php if (empty($datos['secciones'])): ?>
                <div class="alert alert-info">
                    No tienes secciones asignadas. Contacta al administrador del sistema.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($datos['secciones'] as $seccion): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header bg-dark text-white">
                                    <strong><?= htmlspecialchars($seccion['materia_codigo'] ?? '') ?></strong>
                                    - Seccion <?= htmlspecialchars($seccion['codigo_seccion'] ?? '') ?>
                                </div>
                                <div class="card-body">
                                    <p class="mb-1">
                                        <strong>Materia:</strong>
                                        <?= htmlspecialchars($seccion['materia_nombre'] ?? '') ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Periodo:</strong>
                                        <?= htmlspecialchars($seccion['periodo_nombre'] ?? '') ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Aula:</strong>
                                        <?= htmlspecialchars($seccion['aula'] ?? 'Sin asignar') ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Horario:</strong>
                                        <?= htmlspecialchars($seccion['horario'] ?? 'Sin asignar') ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Estudiantes:</strong>
                                        <?= intval($seccion['total_estudiantes'] ?? 0) ?>
                                    </p>
                                    <p class="mb-2">
                                        <strong>Avance de notas:</strong>
                                        <?= intval($seccion['avance'] ?? 0) ?>%
                                        (<?= intval($seccion['total_notas'] ?? 0) ?> de <?= intval($seccion['total_esperado'] ?? 0) ?>)
                                    </p>

                                    <!-- Barra de progreso del avance de notas -->
                                    <div class="progress mb-3">
                                        <div class="progress-bar bg-success"
                                             style="width: <?= intval($seccion['avance'] ?? 0) ?>%">
                                        </div>
                                    </div>

                                    <!-- Botones de accion de la seccion -->
                                    <div class="d-flex gap-2">
                                        <a href="<?= BASE_URL ?>seccion/estudiantes/<?= $seccion['id'] ?>"
                                           class="btn btn-outline-primary btn-sm">
                                            Ver estudiantes
                                        </a>
                                        <a href="<?= BASE_URL ?>nota/index/<?= $seccion['id'] ?>"
                                           class="btn btn-outline-success btn-sm">
                                            Ver notas
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>

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

</body>
</html>