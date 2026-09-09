<?php
// app/Controllers/ProfesorController.php
// Controlador para gestionar profesores desde el panel de admin
// Solo el admin puede crear, editar y eliminar profesores
// El registro publico de profesores se maneja en AuthController
// Incluye validaciones fuertes de formato antes de tocar la base de datos

namespace App\Controllers;

use App\Config\Database;
use App\Models\Profesor;

class ProfesorController
{
    private Profesor $model;

    public function __construct()
    {
        $database = new Database();
        $this->model = new Profesor($database->getConnection());
    }

    // Muestra la vista principal con todos los profesores
    public function index(): void
    {
        $profesores = $this->model->obtenerTodos();
        require_once __DIR__ . '/../Views/profesor/index.php';
    }

    // Guarda un profesor (crear o actualizar)
    // Incluye validaciones fuertes de formato y reglas de seguridad de rol
    public function guardar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'status' => 'error',
                'title' => 'Metodo no permitido',
                'message' => 'La solicitud debe ser POST.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $id = intval($_POST['id'] ?? 0);
        $cedula = trim($_POST['cedula'] ?? '');
        $nombres = trim($_POST['nombres'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $fechaNacimiento = $_POST['fecha_nacimiento'] ?? '';
        $genero = $_POST['genero'] ?? '';
        $direccion = trim($_POST['direccion'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $password = $_POST['password'] ?? '';
        $especialidad = trim($_POST['especialidad'] ?? '');
        $rol = $_POST['rol'] ?? 'profesor';

        // Validar campos obligatorios
        if (empty($cedula)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Campo obligatorio',
                'message' => 'La cedula es obligatoria.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (empty($nombres)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Campo obligatorio',
                'message' => 'Los nombres son obligatorios.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (empty($apellidos)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Campo obligatorio',
                'message' => 'Los apellidos son obligatorios.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (empty($fechaNacimiento)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Campo obligatorio',
                'message' => 'La fecha de nacimiento es obligatoria.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (empty($genero)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Campo obligatorio',
                'message' => 'El genero es obligatorio.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (empty($correo)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Campo obligatorio',
                'message' => 'El correo es obligatorio.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Validar que la cedula contenga solo numeros (aunque sea varchar)
        if (!$this->validarSoloNumeros($cedula)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Cedula invalida',
                'message' => 'La cedula solo puede contener numeros.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Validar que los nombres contengan solo letras y espacios
        if (!$this->validarSoloLetras($nombres)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Nombres invalidos',
                'message' => 'Los nombres solo pueden contener letras y espacios.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Validar que los apellidos contengan solo letras y espacios
        if (!$this->validarSoloLetras($apellidos)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Apellidos invalidos',
                'message' => 'Los apellidos solo pueden contener letras y espacios.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Validar que el telefono no contenga letras
        if (!empty($telefono) && !$this->validarTelefono($telefono)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Telefono invalido',
                'message' => 'El telefono solo puede contener numeros, guiones y el signo +.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Validar que la especialidad no contenga simbolos extranos
        if (!empty($especialidad) && !$this->validarTextoSimple($especialidad)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Especialidad invalida',
                'message' => 'La especialidad solo puede contener letras, numeros, espacios, puntos y guiones.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Validar formato de correo
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Correo invalido',
                'message' => 'El correo no tiene un formato valido.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Validar rol permitido
        if (!in_array($rol, ['admin', 'profesor'])) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Rol invalido',
                'message' => 'El rol debe ser admin o profesor.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Si es creacion, la contrasena es obligatoria
        if ($id === 0 && empty($password)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Campo obligatorio',
                'message' => 'La contrasena es obligatoria para un nuevo profesor.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Validar contrasena fuerte solo si se proporciona
        if (!empty($password) && !$this->validarPasswordFuerte($password)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Contrasena debil',
                'message' => 'Minimo 8 caracteres, una mayuscula, una minuscula, un numero y un caracter especial.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Verificar cedula duplicada
        if ($id === 0) {
            if ($this->model->existeCedula($cedula)) {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Cedula duplicada',
                    'message' => 'Ya existe un profesor con esa cedula.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        } else {
            if ($this->model->existeCedulaExceptoId($cedula, $id)) {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Cedula duplicada',
                    'message' => 'Ya existe otro profesor con esa cedula.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        // Verificar correo duplicado
        if ($id === 0) {
            if ($this->model->existeCorreo($correo)) {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Correo duplicado',
                    'message' => 'Ya existe un profesor con ese correo.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        } else {
            if ($this->model->existeCorreoExceptoId($correo, $id)) {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Correo duplicado',
                    'message' => 'Ya existe otro profesor con ese correo.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        // Impedir que un usuario cambie su propio rol desde este formulario
        $usuarioActualId = intval($_SESSION['usuario_id'] ?? 0);
        if ($id === $usuarioActualId && $rol !== $_SESSION['rol']) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Accion no permitida',
                'message' => 'No puedes cambiar tu propio rol.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Preparar datos para guardar
        // Si password viene vacio en edicion, el modelo no tocara la contrasena
        $datos = [
            'cedula' => $cedula,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'fecha_nacimiento' => $fechaNacimiento,
            'genero' => $genero,
            'direccion' => $direccion,
            'telefono' => $telefono,
            'correo' => $correo,
            'password' => !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : '',
            'especialidad' => $especialidad,
            'rol' => $rol
        ];

        if ($id === 0) {
            $resultado = $this->model->crear($datos);
            if ($resultado) {
                echo json_encode([
                    'status' => 'success',
                    'title' => 'Profesor creado',
                    'message' => 'El profesor se registro correctamente.'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Error',
                    'message' => 'No se pudo crear el profesor.'
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            $resultado = $this->model->actualizar($id, $datos);
            if ($resultado) {
                echo json_encode([
                    'status' => 'success',
                    'title' => 'Profesor actualizado',
                    'message' => 'Los cambios se guardaron correctamente.'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Error',
                    'message' => 'No se pudo actualizar el profesor.'
                ], JSON_UNESCAPED_UNICODE);
            }
        }
        exit;
    }

    // Elimina un profesor por su id
    // Incluye reglas de seguridad: no eliminarse a si mismo ni al ultimo admin
    public function eliminar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'status' => 'error',
                'title' => 'Metodo no permitido',
                'message' => 'La solicitud debe ser POST.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $id = intval($_POST['id'] ?? 0);
        $usuarioActualId = intval($_SESSION['usuario_id'] ?? 0);

        if ($id <= 0) {
            echo json_encode([
                'status' => 'error',
                'title' => 'ID invalido',
                'message' => 'No se recibio un identificador valido.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // No permitir que el admin se elimine a si mismo
        if ($id === $usuarioActualId) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Accion no permitida',
                'message' => 'No puedes eliminar tu propia cuenta.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Verificar si es el ultimo admin del sistema
        $profesor = $this->model->obtenerPorId($id);
        if ($profesor && $profesor['rol'] === 'admin') {
            $totalAdmins = $this->model->contarAdmins();
            if ($totalAdmins <= 1) {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Accion no permitida',
                    'message' => 'No puedes eliminar al ultimo administrador del sistema.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        $resultado = $this->model->eliminar($id);

        if ($resultado) {
            echo json_encode([
                'status' => 'success',
                'title' => 'Profesor eliminado',
                'message' => 'El profesor se elimino correctamente.'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status' => 'error',
                'title' => 'Error',
                'message' => 'No se pudo eliminar. Verifica que no tenga secciones asignadas.'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // ============================================================
    // METODOS PRIVADOS DE VALIDACION FUERTE
    // ============================================================

    // Valida que un texto contenga solo letras (con o sin acentos) y espacios
    private function validarSoloLetras(string $valor): bool
    {
        return preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/', $valor) === 1;
    }

    // Valida que un texto contenga solo digitos numericos
    private function validarSoloNumeros(string $valor): bool
    {
        return preg_match('/^[0-9]+$/', $valor) === 1;
    }

    // Valida que un telefono contenga solo digitos, guiones, signo mas y espacios
    private function validarTelefono(string $valor): bool
    {
        return preg_match('/^[0-9\-\+\s]+$/', $valor) === 1;
    }

    // Valida texto simple para especialidad: letras, numeros, espacios, puntos y guiones
    private function validarTextoSimple(string $valor): bool
    {
        return preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.\-]+$/', $valor) === 1;
    }

    // Valida que la contrasena cumpla con los requisitos de seguridad
    // Minimo 8 caracteres, mayuscula, minuscula, numero y caracter especial
    private function validarPasswordFuerte(string $password): bool
    {
        $regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\-\*\+#\$\&\.\,\!\?\@\%\(\)_\=\[\]\{\}\|:;]).{8,}$/';
        return preg_match($regex, $password) === 1;
    }
}