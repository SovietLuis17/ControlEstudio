<?php
// app/Controllers/AuthController.php
// Controlador de autenticacion: login, registro y logout
// El registro publico solo crea profesores (rol = 'profesor')

namespace App\Controllers;

use App\Config\Database;
use App\Models\Profesor;

class AuthController
{
    // Modelo de profesores (la tabla profesores incluye admin y profesor)
    private Profesor $model;

    public function __construct()
    {
        $database = new Database();
        $this->model = new Profesor($database->getConnection());
    }

    // Muestra la vista de login
    public function login(): void
    {
        require_once __DIR__ . '/../Views/auth/login.php';
    }

    // Procesa el formulario de login (envio tradicional, no AJAX)
    // Procesa el formulario de login (envio tradicional, no AJAX)
    public function authenticate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        $correo = trim($_POST['correo'] ?? '');
        $password = $_POST['password'] ?? '';

        $_SESSION['old_data'] = [
            'correo' => $correo
        ];

        // Validar campos vacios
        if (empty($correo) || empty($password)) {
            $_SESSION['swal'] = [
                'icon' => 'error',
                'title' => 'Campos incompletos',
                'text' => 'Debe ingresar correo y contrasena.'
            ];
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        // Validar formato de correo antes de consultar la base de datos
        // Si el formato es invalido mostramos el mensaje generico
        // para no revelar informacion a usuarios maliciosos
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['swal'] = [
                'icon' => 'error',
                'title' => 'Error de autenticacion',
                'text' => 'Correo o contrasena incorrectos.'
            ];
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        // Buscar usuario por correo con consulta preparada
        $usuario = $this->model->obtenerPorCorreo($correo);

        // Verificar credenciales con password_verify
        // El mensaje es generico a proposito: no revelamos si el correo existe
        if (!$usuario || !password_verify($password, $usuario['password'])) {
            $_SESSION['swal'] = [
                'icon' => 'error',
                'title' => 'Error de autenticacion',
                'text' => 'Correo o contrasena incorrectos.'
            ];
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }

        // Login exitoso: regenerar ID de sesion por seguridad
        session_regenerate_id(true);

        // Guardar solo datos necesarios en sesion
        // Nunca se guarda la contrasena en sesion
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombres'] . ' ' . $usuario['apellidos'];
        $_SESSION['rol'] = $usuario['rol'];

        // Redirigir al dashboard o a la URL que intentaba visitar
        $destino = $_SESSION['redirect_after_login'] ?? 'dashboard/index';
        unset($_SESSION['redirect_after_login']);

        header('Location: ' . BASE_URL . $destino);
        exit;
    }

    // Muestra la vista de registro (solo para profesores)
    public function register(): void
    {
        require_once __DIR__ . '/../Views/auth/register.php';
    }

    // Procesa el registro de un nuevo profesor
    // Procesa el registro de un nuevo profesor
    public function processRegister(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'auth/register');
            exit;
        }

        // Capturar y limpiar datos del formulario
        $datos = [
            'cedula' => trim($_POST['cedula'] ?? ''),
            'nombres' => trim($_POST['nombres'] ?? ''),
            'apellidos' => trim($_POST['apellidos'] ?? ''),
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? '',
            'genero' => $_POST['genero'] ?? '',
            'direccion' => trim($_POST['direccion'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'correo' => trim($_POST['correo'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? '',
            'especialidad' => trim($_POST['especialidad'] ?? '')
        ];

        // Lista de errores de validacion
        $errores = [];

        // Validar campos obligatorios
        if (empty($datos['cedula']))
            $errores[] = 'La cedula es obligatoria.';
        if (empty($datos['nombres']))
            $errores[] = 'Los nombres son obligatorios.';
        if (empty($datos['apellidos']))
            $errores[] = 'Los apellidos son obligatorios.';
        if (empty($datos['fecha_nacimiento']))
            $errores[] = 'La fecha de nacimiento es obligatoria.';
        if (empty($datos['genero']))
            $errores[] = 'El genero es obligatorio.';
        if (empty($datos['correo']))
            $errores[] = 'El correo es obligatorio.';
        if (empty($datos['password']))
            $errores[] = 'La contrasena es obligatoria.';

        // Validar que la cedula contenga solo numeros (aunque sea varchar)
        if (!empty($datos['cedula']) && !$this->validarSoloNumeros($datos['cedula'])) {
            $errores[] = 'La cedula solo puede contener numeros.';
        }

        // Validar que los nombres contengan solo letras y espacios
        if (!empty($datos['nombres']) && !$this->validarSoloLetras($datos['nombres'])) {
            $errores[] = 'Los nombres solo pueden contener letras y espacios.';
        }

        // Validar que los apellidos contengan solo letras y espacios
        if (!empty($datos['apellidos']) && !$this->validarSoloLetras($datos['apellidos'])) {
            $errores[] = 'Los apellidos solo pueden contener letras y espacios.';
        }

        // Validar que el telefono no contenga letras
        if (!empty($datos['telefono']) && !$this->validarTelefono($datos['telefono'])) {
            $errores[] = 'El telefono solo puede contener numeros, guiones y el signo +.';
        }

        // Validar que la especialidad no contenga simbolos extranos
        if (!empty($datos['especialidad']) && !$this->validarTextoSimple($datos['especialidad'])) {
            $errores[] = 'La especialidad solo puede contener letras, numeros, espacios, puntos y guiones.';
        }

        // Validar formato de correo
        if (!empty($datos['correo']) && !filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo no tiene un formato valido.';
        }

        // Validar contrasena fuerte
        if (!empty($datos['password'])) {
            if (!$this->validarPasswordFuerte($datos['password'])) {
                $errores[] = 'La contrasena debe tener minimo 8 caracteres, una mayuscula, una minuscula, un numero y un caracter especial.';
            }
        }

        // Validar que las contrasenas coincidan
        if (!empty($datos['password']) && $datos['password'] !== $datos['password_confirm']) {
            $errores[] = 'Las contrasenas no coinciden.';
        }

        // Validar cedula unica
        if (!empty($datos['cedula']) && $this->model->existeCedula($datos['cedula'])) {
            $errores[] = 'La cedula ya esta registrada en el sistema.';
        }

        // Validar correo unico
        if (!empty($datos['correo']) && $this->model->existeCorreo($datos['correo'])) {
            $errores[] = 'El correo ya esta registrado en el sistema.';
        }

        // Si hay errores, conservar los datos escritos y redirigir al registro
        if (!empty($errores)) {
            $_SESSION['old_data'] = $datos;
            $_SESSION['old_data']['password'] = '';
            $_SESSION['old_data']['password_confirm'] = '';
            $_SESSION['swal'] = [
                'icon' => 'error',
                'title' => 'Error en el registro',
                'text' => implode(' ', $errores)
            ];
            header('Location: ' . BASE_URL . 'auth/register');
            exit;
        }

        // Hashear contrasena antes de guardar
        $datos['password'] = password_hash($datos['password'], PASSWORD_DEFAULT);
        $datos['rol'] = 'profesor'; // Registro publico siempre crea profesores
        unset($datos['password_confirm']);

        // Crear el profesor en la base de datos
        $resultado = $this->model->crear($datos);

        if ($resultado) {
            $_SESSION['swal'] = [
                'icon' => 'success',
                'title' => 'Registro exitoso',
                'text' => 'Tu cuenta de profesor ha sido creada. Ya puedes iniciar sesion.'
            ];
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        } else {
            $_SESSION['old_data'] = $datos;
            $_SESSION['old_data']['password'] = '';
            $_SESSION['swal'] = [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No se pudo completar el registro. Intenta nuevamente.'
            ];
            header('Location: ' . BASE_URL . 'auth/register');
            exit;
        }
    }
    // Cierra la sesion del usuario y destruye los datos
    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        header('Location: ' . BASE_URL . 'auth/login');
        exit;
    }

    // Valida que la contrasena cumpla con los requisitos de seguridad
    // Minimo 8 caracteres, mayuscula, minuscula, numero y caracter especial
    private function validarPasswordFuerte(string $password): bool
    {
        $regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\-\*\+#\$\&\.\,\!\?\@\%\(\)_\=\[\]\{\}\|:;]).{8,}$/';
        return preg_match($regex, $password) === 1;
    }

    // Valida que un texto contenga solo letras (con o sin acentos) y espacios
    // Se usa para nombres y apellidos
    private function validarSoloLetras(string $valor): bool
    {
        return preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/', $valor) === 1;
    }

    // Valida que un texto contenga solo digitos numericos
    // Se usa para la cedula, aunque la columna sea varchar
    private function validarSoloNumeros(string $valor): bool
    {
        return preg_match('/^[0-9]+$/', $valor) === 1;
    }

    // Valida que un telefono contenga solo digitos, guiones, signo mas y espacios
    private function validarTelefono(string $valor): bool
    {
        return preg_match('/^[0-9\-\+\s]+$/', $valor) === 1;
    }

    // Valida texto simple para especialidad
    // Permite letras, numeros, espacios, puntos y guiones (ej: Matematicas II)
    private function validarTextoSimple(string $valor): bool
    {
        return preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.\-]+$/', $valor) === 1;
    }
}