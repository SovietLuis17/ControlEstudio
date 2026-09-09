<?php
// app/Controllers/EstudianteController.php
// Controlador para gestionar los estudiantes del sistema
// Admin puede crear, editar y eliminar estudiantes
// Profesor puede crear y editar, pero no eliminar
// Incluye busqueda por cedula y paginacion en el listado

namespace App\Controllers;

use App\Config\Database;
use App\Models\Estudiante;
use App\Models\Seccion;
use App\Models\Nota;

class EstudianteController
{
    private Estudiante $model;
    private Seccion $modelSeccion;
    private Nota $modelNota;

    public function __construct()
    {
        $database = new Database();
        $this->model = new Estudiante($database->getConnection());
        $this->modelSeccion = new Seccion($database->getConnection());
        $this->modelNota = new Nota($database->getConnection());
    }

    // Muestra la vista principal con busqueda por cedula y paginacion
    // La busqueda y la pagina viajan por la URL como query string
    public function index(): void
    {
        $rol = $_SESSION['rol'] ?? '';

        // Lectura y limpieza del termino de busqueda
        $busqueda = trim($_GET['busqueda'] ?? '');

        // La cedula solo acepta numeros, coherente con las validaciones del modulo
        if ($busqueda !== '' && !preg_match('/^[0-9]+$/', $busqueda)) {
            $_SESSION['swal'] = [
                'icon' => 'warning',
                'title' => 'Busqueda invalida',
                'text' => 'La busqueda por cedula solo acepta numeros.'
            ];
            header('Location: ' . BASE_URL . 'estudiante/index');
            exit;
        }

        // Configuracion de paginacion
        $porPagina = 10;
        $totalEstudiantes = $this->model->contarTotal($busqueda);
        $totalPaginas = max(1, (int) ceil($totalEstudiantes / $porPagina));

        // Pagina solicitada, siempre dentro del rango valido
        $pagina = intval($_GET['pagina'] ?? 1);
        if ($pagina < 1) {
            $pagina = 1;
        }
        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
        }

        // Cuantos registros saltar segun la pagina actual
        $offset = ($pagina - 1) * $porPagina;

        // Listado paginado y filtrado
        $estudiantes = $this->model->obtenerPaginados($porPagina, $offset, $busqueda);

        require_once __DIR__ . '/../Views/estudiante/index.php';
    }

    // Guarda un estudiante (crear o actualizar)
    // Incluye validaciones fuertes de formato antes de tocar la base de datos
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
        $carrera = trim($_POST['carrera'] ?? '');

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

        // Validar que la carrera no contenga simbolos extranos
        if (!empty($carrera) && !$this->validarTextoSimple($carrera)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Carrera invalida',
                'message' => 'La carrera solo puede contener letras, numeros, espacios, puntos y guiones.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Validar formato de correo si se proporciono
        if (!empty($correo) && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Correo invalido',
                'message' => 'El correo no tiene un formato valido.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Verificar cedula duplicada
        if ($id === 0) {
            if ($this->model->existeCedula($cedula)) {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Cedula duplicada',
                    'message' => 'Ya existe un estudiante con esa cedula.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        } else {
            if ($this->model->existeCedulaExceptoId($cedula, $id)) {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Cedula duplicada',
                    'message' => 'Ya existe otro estudiante con esa cedula.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        // Verificar correo duplicado si se proporciono
        if (!empty($correo)) {
            if ($id === 0) {
                if ($this->model->existeCorreo($correo)) {
                    echo json_encode([
                        'status' => 'error',
                        'title' => 'Correo duplicado',
                        'message' => 'Ya existe un estudiante con ese correo.'
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            } else {
                if ($this->model->existeCorreoExceptoId($correo, $id)) {
                    echo json_encode([
                        'status' => 'error',
                        'title' => 'Correo duplicado',
                        'message' => 'Ya existe otro estudiante con ese correo.'
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }
        }

        // Datos listos para el modelo
        $datos = [
            'cedula' => $cedula,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'fecha_nacimiento' => $fechaNacimiento,
            'genero' => $genero,
            'direccion' => $direccion,
            'telefono' => $telefono,
            'correo' => $correo,
            'carrera' => $carrera
        ];

        if ($id === 0) {
            $resultado = $this->model->crear($datos);
            if ($resultado) {
                echo json_encode([
                    'status' => 'success',
                    'title' => 'Estudiante creado',
                    'message' => 'El estudiante se registro correctamente.'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Error',
                    'message' => 'No se pudo crear el estudiante.'
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            $resultado = $this->model->actualizar($id, $datos);
            if ($resultado) {
                echo json_encode([
                    'status' => 'success',
                    'title' => 'Estudiante actualizado',
                    'message' => 'Los cambios se guardaron correctamente.'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Error',
                    'message' => 'No se pudo actualizar el estudiante.'
                ], JSON_UNESCAPED_UNICODE);
            }
        }
        exit;
    }

    // Elimina un estudiante por su id
    // La matriz de rutas garantiza que solo el admin llegue aqui
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

        if ($id <= 0) {
            echo json_encode([
                'status' => 'error',
                'title' => 'ID invalido',
                'message' => 'No se recibio un identificador valido.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $resultado = $this->model->eliminar($id);

        if ($resultado) {
            echo json_encode([
                'status' => 'success',
                'title' => 'Estudiante eliminado',
                'message' => 'El estudiante se elimino correctamente.'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status' => 'error',
                'title' => 'Error',
                'message' => 'No se pudo eliminar el estudiante.'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // Asigna un estudiante a una seccion
    // Admin puede asignar a cualquier seccion
    // Profesor solo puede asignar a sus propias secciones
    public function asignarSeccion(): void
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

        $estudianteId = intval($_POST['estudiante_id'] ?? 0);
        $seccionId = intval($_POST['seccion_id'] ?? 0);
        $usuarioId = intval($_SESSION['usuario_id'] ?? 0);
        $rol = $_SESSION['rol'] ?? '';

        // Validar ids recibidos
        if ($estudianteId <= 0 || $seccionId <= 0) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Datos invalidos',
                'message' => 'Se requieren estudiante y seccion validos.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Si es profesor, validar que la seccion le pertenezca
        if ($rol === 'profesor') {
            if (!$this->modelSeccion->perteneceAProfesor($seccionId, $usuarioId)) {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Sin permisos',
                    'message' => 'No puedes asignar estudiantes a una seccion que no es tuya.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        // Verificar que el estudiante exista
        $estudiante = $this->model->obtenerPorId($estudianteId);
        if (!$estudiante) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Estudiante no encontrado',
                'message' => 'El estudiante que intentas asignar no existe.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Verificar que el estudiante no tenga ya una seccion asignada
        if (!empty($estudiante['seccion_id'])) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Estudiante ya asignado',
                'message' => 'Este estudiante ya pertenece a una seccion.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $resultado = $this->model->asignarSeccion($estudianteId, $seccionId);

        if ($resultado) {
            echo json_encode([
                'status' => 'success',
                'title' => 'Estudiante asignado',
                'message' => 'El estudiante se asigno a la seccion correctamente.'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status' => 'error',
                'title' => 'Error',
                'message' => 'No se pudo asignar el estudiante.'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // Quita un estudiante de su seccion actual
    // Admin puede quitar de cualquier seccion
    // Profesor solo puede quitar de sus propias secciones
    public function quitarSeccion(): void
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

        $estudianteId = intval($_POST['estudiante_id'] ?? 0);
        $usuarioId = intval($_SESSION['usuario_id'] ?? 0);
        $rol = $_SESSION['rol'] ?? '';

        if ($estudianteId <= 0) {
            echo json_encode([
                'status' => 'error',
                'title' => 'ID invalido',
                'message' => 'No se recibio un identificador valido.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Obtener datos del estudiante para saber su seccion actual
        $estudiante = $this->model->obtenerPorId($estudianteId);
        if (!$estudiante) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Estudiante no encontrado',
                'message' => 'El estudiante que intentas quitar no existe.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Verificar que el estudiante tenga una seccion asignada
        if (empty($estudiante['seccion_id'])) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Sin seccion',
                'message' => 'Este estudiante no pertenece a ninguna seccion.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $seccionId = intval($estudiante['seccion_id']);

        // Si es profesor, validar que la seccion le pertenezca
        if ($rol === 'profesor') {
            if (!$this->modelSeccion->perteneceAProfesor($seccionId, $usuarioId)) {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Sin permisos',
                    'message' => 'No puedes quitar estudiantes de una seccion que no es tuya.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        // Obtener la seccion para saber la materia y eliminar las notas asociadas
        $seccion = $this->modelSeccion->obtenerPorId($seccionId);
        if ($seccion) {
            $this->modelNota->eliminarPorEstudianteYMateria($estudianteId, intval($seccion['materia_id']));
        }

        $resultado = $this->model->quitarSeccion($estudianteId);

        if ($resultado) {
            echo json_encode([
                'status' => 'success',
                'title' => 'Estudiante retirado',
                'message' => 'El estudiante se quito de la seccion y sus notas fueron eliminadas.'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status' => 'error',
                'title' => 'Error',
                'message' => 'No se pudo quitar el estudiante de la seccion.'
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

    // Valida texto simple para carrera: letras, numeros, espacios, puntos y guiones
    private function validarTextoSimple(string $valor): bool
    {
        return preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.\-]+$/', $valor) === 1;
    }
}