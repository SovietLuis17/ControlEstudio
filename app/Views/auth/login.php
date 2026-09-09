<?php

require __DIR__ . '/../partials/header.php';
?>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-header bg-dark text-white text-center py-3">
                        <h4 class="mb-0">Control de Estudios</h4>
                        <small>Iniciar Sesion</small>
                    </div>
                    <div class="card-body p-4">
                          <?php
                        // Recuperamos los datos guardados en sesion si hubo un error previo
                        // Esto evita que el usuario pierda lo que ya escribio
                        $old = $_SESSION['old_data'] ?? [];
                        unset($_SESSION['old_data']);
                        ?>


                        <!-- Formulario de login con envio tradicional -->
                        <form action="<?= BASE_URL ?>auth/authenticate" method="POST">

                            <!-- Campo correo electronico -->
                            <div class="mb-3">
                                <label for="correo" class="form-label">Correo electronico</label>
                                <input type="email"
                                       class="form-control"
                                       id="correo"
                                       name="correo"
                                       placeholder="correo@ejemplo.com"
                                       required
                                       value="<?= htmlspecialchars($old['correo'] ?? '') ?>"
                                       maxlength="150">
                            </div>

                            <!-- Campo contrasena -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Contrasena</label>
                                <input type="password"
                                       class="form-control"
                                       id="password"
                                       name="password"
                                       placeholder="Ingresa tu contrasena"
                                       required>
                            </div>

                            <!-- Boton de envio -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-dark">
                                    Ingresar
                                </button>
                            </div>
                        </form>

                        <hr>

                        <!-- Enlace al registro publico de profesores -->
                        <div class="text-center">
                            <small>
                                Eres profesor y no tienes cuenta?
                                <a href="<?= BASE_URL ?>auth/register">Registrate aqui</a>
                            </small>
                        </div>

                    </div>
                </div>
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
        // Leemos el mensaje que el controlador guardo en sesion
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