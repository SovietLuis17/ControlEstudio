<?php
// app/Controllers/SeccionController.php
// Controlador para gestionar las secciones del sistema
// Admin puede crear, editar y eliminar secciones
// Profesor solo puede ver sus secciones y sus estudiantes

namespace App\Controllers;

use App\Config\Database;
use App\Models\Seccion;
use App\Models\Materia;
use App\Models\Profesor;
use App\Models\Periodo;
use App\Models\Estudiante;

class SeccionController
{
    private Seccion $model;
    private Materia $modelMateria;
    private Profesor $modelProfesor;
    private Periodo $modelPeriodo;
    private Estudiante $modelEstudiante;

    public function __construct()
    {
        $database = new Database();
        $this->model = new Seccion($database->getConnection());
        $this->modelMateria = new Materia($database->getConnection());
        $this->modelProfesor = new Profesor($database->getConnection());
        $this->modelPeriodo = new Periodo($database->getConnection());
        $this->modelEstudiante = new Estudiante($database->getConnection());
    }

    // Muestra la vista principal con las secciones
    // Admin ve todas las secciones del sistema
    // Profesor solo ve las secciones que le pertenecen
    public function index(): void
    {
        $rol = $_SESSION['rol'] ?? '';
        $usuarioId = intval($_SESSION['usuario_id'] ?? 0);

        if ($rol === 'admin') {
            $secciones = $this->model->obtenerTodos();
        } else {
            // El profesor solo ve sus propias secciones
            $secciones = $this->model->obtenerPorProfesor($usuarioId);
        }

        // Datos para los selects del formulario (solo admin los usa)
        $materias = $this->modelMateria->obtenerParaSelect();
        $profesores = $this->modelProfesor->obtenerSoloProfesores();
        $periodos = $this->modelPeriodo->obtenerActivos();

        require_once __DIR__ . '/../Views/seccion/index.php';
    }

    // Guarda una seccion (crear o actualizar)
    // Solo el admin puede ejecutar esta accion
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
        $materiaId = intval($_POST['materia_id'] ?? 0);
        $profesorId = intval($_POST['profesor_id'] ?? 0);
        $periodoId = intval($_POST['periodo_id'] ?? 0);
        $codigoSeccion = trim($_POST['codigo_seccion'] ?? '');
        $aula = trim($_POST['aula'] ?? '');
        $horario = trim($_POST['horario'] ?? '');

        // Validar campos obligatorios
        if ($materiaId <= 0) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Campo obligatorio',
                'message' => 'Debe seleccionar una materia.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($profesorId <= 0) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Campo obligatorio',
                'message' => 'Debe seleccionar un profesor.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($periodoId <= 0) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Campo obligatorio',
                'message' => 'Debe seleccionar un periodo.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (empty($codigoSeccion)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Campo obligatorio',
                'message' => 'El codigo de la seccion es obligatorio.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Verificar combinacion duplicada (materia + periodo + codigo)
        if ($id === 0) {
            if ($this->model->existeCombinacion($materiaId, $periodoId, $codigoSeccion)) {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Seccion duplicada',
                    'message' => 'Ya existe una seccion con ese codigo para esta materia y periodo.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        } else {
            if ($this->model->existeCombinacionExceptoId($materiaId, $periodoId, $codigoSeccion, $id)) {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Seccion duplicada',
                    'message' => 'Ya existe otra seccion con ese codigo para esta materia y periodo.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        $datos = [
            'materia_id' => $materiaId,
            'profesor_id' => $profesorId,
            'periodo_id' => $periodoId,
            'codigo_seccion' => $codigoSeccion,
            'aula' => $aula,
            'horario' => $horario
        ];

        if ($id === 0) {
            $resultado = $this->model->crear($datos);
            if ($resultado) {
                echo json_encode([
                    'status' => 'success',
                    'title' => 'Seccion creada',
                    'message' => 'La seccion se registro correctamente.'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Error',
                    'message' => 'No se pudo crear la seccion.'
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            $resultado = $this->model->actualizar($id, $datos);
            if ($resultado) {
                echo json_encode([
                    'status' => 'success',
                    'title' => 'Seccion actualizada',
                    'message' => 'Los cambios se guardaron correctamente.'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Error',
                    'message' => 'No se pudo actualizar la seccion.'
                ], JSON_UNESCAPED_UNICODE);
            }
        }
        exit;
    }

    // Elimina una seccion por su id
    // Solo el admin puede ejecutar esta accion
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

        // Verificar si la seccion tiene estudiantes asignados
        $totalEstudiantes = $this->modelEstudiante->contarPorSeccion($id);
        if ($totalEstudiantes > 0) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Seccion con estudiantes',
                'message' => 'No se puede eliminar porque tiene ' . $totalEstudiantes . ' estudiante(s) asignado(s).'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $resultado = $this->model->eliminar($id);

        if ($resultado) {
            echo json_encode([
                'status' => 'success',
                'title' => 'Seccion eliminada',
                'message' => 'La seccion se elimino correctamente.'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status' => 'error',
                'title' => 'Error',
                'message' => 'No se pudo eliminar la seccion.'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // Muestra los estudiantes de una seccion especifica
    // Admin puede ver cualquier seccion
    // Profesor solo puede ver sus propias secciones
    public function estudiantes(): void
    {
        $seccionId = intval($_GET['id'] ?? 0);
        $usuarioId = intval($_SESSION['usuario_id'] ?? 0);
        $rol = $_SESSION['rol'] ?? '';

        if ($seccionId <= 0) {
            $_SESSION['swal'] = [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'No se recibio una seccion valida.'
            ];
            header('Location: ' . BASE_URL . 'seccion/index');
            exit;
        }

        // Si es profesor, validar que la seccion le pertenezca
        if ($rol === 'profesor') {
            if (!$this->model->perteneceAProfesor($seccionId, $usuarioId)) {
                $_SESSION['swal'] = [
                    'icon' => 'error',
                    'title' => 'Sin permisos',
                    'text' => 'No puedes ver los estudiantes de una seccion que no es tuya.'
                ];
                header('Location: ' . BASE_URL . 'seccion/index');
                exit;
            }
        }

        $seccion = $this->model->obtenerPorId($seccionId);
        if (!$seccion) {
            $_SESSION['swal'] = [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'La seccion seleccionada no existe.'
            ];
            header('Location: ' . BASE_URL . 'seccion/index');
            exit;
        }

        $estudiantes = $this->modelEstudiante->obtenerPorSeccion($seccionId);
        $estudiantesDisponibles = $this->modelEstudiante->obtenerSinSeccion();

        require_once __DIR__ . '/../Views/seccion/estudiantes.php';
    }
}