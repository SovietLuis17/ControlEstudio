<?php
$pageTitle = 'Registro de Usuario/Profesor - Control de Estudios';
require __DIR__ . '/../partials/header.php';
?>
<body class="bg-light py-4">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-dark text-white text-center py-3">
                        <h4 class="mb-0">Registro de Profesor</h4>
                        <a href="<?= BASE_URL ?>auth/login" class="btn btn-outline-light btn-sm">
                        Atras
                        </a>
                        <small>Crea tu cuenta para acceder al sistema</small>
                    </div>
                     
                    <div class="card-body p-4">

                        <?php
                        // Recuperamos los datos guardados en sesion si hubo un error previo
                        // Esto evita que el usuario pierda lo que ya escribio
                        $old = $_SESSION['old_data'] ?? [];
                        unset($_SESSION['old_data']);
                        ?>

                        <!-- Formulario de registro con envio tradicional -->
                        <form action="<?= BASE_URL ?>auth/processRegister" method="POST">

                            <div class="row">
                                <!-- Campo cedula: solo numeros -->
                                <div class="col-md-6 mb-3">
                                    <label for="cedula" class="form-label">Cedula *</label>
                                    <input type="text"
                                           class="form-control"
                                           id="cedula"
                                           name="cedula"
                                           value="<?= htmlspecialchars($old['cedula'] ?? '') ?>"
                                           placeholder="Ej: 12345678"
                                           pattern="[0-9]{6,20}"
                                           inputmode="numeric"
                                           title="Solo numeros, entre 6 y 20 digitos"
                                           required
                                           maxlength="20">
                                    <small class="form-text text-muted">
                                        Solo numeros, entre 6 y 20 digitos.
                                    </small>
                                </div>

                                <!-- Campo especialidad -->
                                <div class="col-md-6 mb-3">
                                    <label for="especialidad" class="form-label">Especialidad</label>
                                    <input type="text"
                                           class="form-control"
                                           id="especialidad"
                                           name="especialidad"
                                           value="<?= htmlspecialchars($old['especialidad'] ?? '') ?>"
                                           placeholder="Ej: Programacion"
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
                                           value="<?= htmlspecialchars($old['nombres'] ?? '') ?>"
                                           placeholder="Tus nombres"
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
                                           value="<?= htmlspecialchars($old['apellidos'] ?? '') ?>"
                                           placeholder="Tus apellidos"
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
                                           value="<?= htmlspecialchars($old['fecha_nacimiento'] ?? '') ?>"
                                           required>
                                </div>

                                <!-- Campo genero -->
                                <div class="col-md-6 mb-3">
                                    <label for="genero" class="form-label">Genero *</label>
                                    <select class="form-select"
                                            id="genero"
                                            name="genero"
                                            required>
                                        <option value="">Selecciona...</option>
                                        <option value="M" <?= ($old['genero'] ?? '') === 'M' ? 'selected' : '' ?>>Masculino</option>
                                        <option value="F" <?= ($old['genero'] ?? '') === 'F' ? 'selected' : '' ?>>Femenino</option>
                                        <option value="Otro" <?= ($old['genero'] ?? '') === 'Otro' ? 'selected' : '' ?>>Otro</option>
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
                                           value="<?= htmlspecialchars($old['telefono'] ?? '') ?>"
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
                                    <label for="correo" class="form-label">Correo electronico *</label>
                                    <input type="email"
                                           class="form-control"
                                           id="correo"
                                           name="correo"
                                           value="<?= htmlspecialchars($old['correo'] ?? '') ?>"
                                           placeholder="correo@ejemplo.com"
                                           required
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
                                       value="<?= htmlspecialchars($old['direccion'] ?? '') ?>"
                                       placeholder="Tu direccion"
                                       maxlength="200">
                            </div>

                            <div class="row">
                                <!-- Campo contrasena -->
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Contrasena *</label>
                                    <input type="password"
                                           class="form-control"
                                           id="password"
                                           name="password"
                                           placeholder="Minimo 8 caracteres"
                                           required
                                           minlength="8">
                                    <!-- Texto de ayuda para la contrasena -->
                                    <small class="form-text text-muted">
                                        Minimo 8 caracteres. Debe incluir mayuscula, minuscula, numero y caracter especial (- * + # $ &amp; etc).
                                    </small>
                                </div>

                                <!-- Campo confirmar contrasena -->
                                <div class="col-md-6 mb-3">
                                    <label for="password_confirm" class="form-label">Confirmar contrasena *</label>
                                    <input type="password"
                                           class="form-control"
                                           id="password_confirm"
                                           name="password_confirm"
                                           placeholder="Repite la contrasena"
                                           required
                                           minlength="8">
                                </div>
                            </div>

                            <!-- Boton de envio -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-dark">
                                    Registrarme
                                </button>
                            </div>
                        </form>

                        <hr>

                        <!-- Enlace al login -->
                        <div class="text-center">
                            <small>
                                Ya tienes cuenta?
                                <a href="<?= BASE_URL ?>auth/login">Inicia sesion aqui</a>
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