<?php
// app/Controllers/PlanEvaluacionController.php
// Controlador para gestionar el plan de evaluacion de las materias
// El plan se define por materia (no por seccion)
// Admin gestiona cualquier plan, profesor solo los planes de sus materias

namespace App\Controllers;

use App\Config\Database;
use App\Models\PlanEvaluacion;
use App\Models\Materia;

class PlanEvaluacionController
{
    private PlanEvaluacion $model;
    private Materia $modelMateria;

    public function __construct()
    {
        $database = new Database();
        $this->model = new PlanEvaluacion($database->getConnection());
        $this->modelMateria = new Materia($database->getConnection());
    }

    // Muestra la vista del plan de evaluacion de una materia
    // Recibe el id de la materia por URL (se convierte en $_GET['id'])
    public function index(): void
    {
        $materiaId = intval($_GET['id'] ?? 0);
        $rol = $_SESSION['rol'] ?? '';
        $usuarioId = intval($_SESSION['usuario_id'] ?? 0);

        if ($materiaId <= 0) {
            $_SESSION['swal'] = [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'Debe seleccionar una materia valida.'
            ];
            header('Location: ' . BASE_URL . 'materia/index');
            exit;
        }

        // Si es profesor, validar que la materia sea dictada por el
        if ($rol === 'profesor' && !$this->modelMateria->esDictadaPorProfesor($materiaId, $usuarioId)) {
            $_SESSION['swal'] = [
                'icon' => 'error',
                'title' => 'Sin permisos',
                'text' => 'Solo puedes ver el plan de evaluacion de las materias que dictas.'
            ];
            header('Location: ' . BASE_URL . 'materia/index');
            exit;
        }

        $materia = $this->modelMateria->obtenerPorId($materiaId);

        if (!$materia) {
            $_SESSION['swal'] = [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'La materia seleccionada no existe.'
            ];
            header('Location: ' . BASE_URL . 'materia/index');
            exit;
        }

        $actividades = $this->model->obtenerPorMateria($materiaId);
        $sumaPorcentajes = $this->model->sumaPorcentajes($materiaId);
        $planCompleto = $this->model->planCompleto($materiaId);

        require_once __DIR__ . '/../Views/materia/plan_evaluacion.php';
    }

    // Guarda una actividad del plan (crear o actualizar)
    // Valida pertenencia de la materia cuando el usuario es profesor
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

        $rol = $_SESSION['rol'] ?? '';
        $usuarioId = intval($_SESSION['usuario_id'] ?? 0);

        $id = intval($_POST['id'] ?? 0);
        $materiaId = intval($_POST['materia_id'] ?? 0);
        $nombreActividad = trim($_POST['nombre_actividad'] ?? '');
        $porcentaje = floatval($_POST['porcentaje'] ?? 0);

        // Validar campos obligatorios
        if ($materiaId <= 0) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Dato invalido',
                'message' => 'No se recibio la materia correctamente.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Si es profesor, validar que la materia sea dictada por el
        if ($rol === 'profesor' && !$this->modelMateria->esDictadaPorProfesor($materiaId, $usuarioId)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Sin permisos',
                'message' => 'No puedes modificar el plan de una materia que no dictas.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (empty($nombreActividad)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Campo obligatorio',
                'message' => 'El nombre de la actividad es obligatorio.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Validar porcentaje entre 0 y 100
        if ($porcentaje <= 0 || $porcentaje > 100) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Dato invalido',
                'message' => 'El porcentaje debe ser mayor a 0 y menor o igual a 100.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Verificar nombre de actividad duplicado en la misma materia
        if ($id === 0) {
            if ($this->model->existeActividad($materiaId, $nombreActividad)) {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Duplicado',
                    'message' => 'Ya existe una actividad con ese nombre en esta materia.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        } else {
            if ($this->model->existeActividadExceptoId($materiaId, $nombreActividad, $id)) {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Duplicado',
                    'message' => 'Ya existe otra actividad con ese nombre en esta materia.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        // Validar que la suma total no exceda 100
        $sumaActual = $this->model->sumaPorcentajes($materiaId);
        $sumaConNueva = $sumaActual + $porcentaje;

        // Si estamos editando, restamos el porcentaje anterior
        if ($id > 0) {
            $actividadActual = $this->model->obtenerPorId($id);
            if ($actividadActual) {
                $sumaConNueva = $sumaActual - floatval($actividadActual['porcentaje']) + $porcentaje;
            }
        }

        if ($sumaConNueva > 100) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Porcentaje excedido',
                'message' => 'La suma de porcentajes no puede superar 100%. Suma actual: ' . $sumaConNueva . '%.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $datos = [
            'materia_id' => $materiaId,
            'nombre_actividad' => $nombreActividad,
            'porcentaje' => $porcentaje
        ];

        if ($id === 0) {
            $resultado = $this->model->crear($datos);
            if ($resultado) {
                echo json_encode([
                    'status' => 'success',
                    'title' => 'Actividad creada',
                    'message' => 'La actividad se agrego al plan de evaluacion.'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Error',
                    'message' => 'No se pudo agregar la actividad.'
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            $resultado = $this->model->actualizar($id, $datos);
            if ($resultado) {
                echo json_encode([
                    'status' => 'success',
                    'title' => 'Actividad actualizada',
                    'message' => 'Los cambios se guardaron correctamente.'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Error',
                    'message' => 'No se pudo actualizar la actividad.'
                ], JSON_UNESCAPED_UNICODE);
            }
        }
        exit;
    }

    // Elimina una actividad del plan por su id
    // Valida pertenencia de la materia cuando el usuario es profesor
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

        $rol = $_SESSION['rol'] ?? '';
        $usuarioId = intval($_SESSION['usuario_id'] ?? 0);

        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode([
                'status' => 'error',
                'title' => 'ID invalido',
                'message' => 'No se recibio un identificador valido.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Obtener la actividad para saber a que materia pertenece
        $actividad = $this->model->obtenerPorId($id);
        if (!$actividad) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Actividad no encontrada',
                'message' => 'La actividad que intentas eliminar no existe.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $materiaId = intval($actividad['materia_id']);

        // Si es profesor, validar que la materia sea dictada por el
        if ($rol === 'profesor' && !$this->modelMateria->esDictadaPorProfesor($materiaId, $usuarioId)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Sin permisos',
                'message' => 'No puedes eliminar actividades de una materia que no dictas.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $resultado = $this->model->eliminar($id);

        if ($resultado) {
            echo json_encode([
                'status' => 'success',
                'title' => 'Actividad eliminada',
                'message' => 'La actividad se elimino del plan de evaluacion.'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status' => 'error',
                'title' => 'Error',
                'message' => 'No se pudo eliminar la actividad.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        exit;
    }
}