<?php
// app/Controllers/PlanEvaluacionController.php
// Controlador para gestionar el plan de evaluacion de las materias
// Opcion B aplicada:
// - Editar nombres y porcentajes es permitido, con aviso si hay notas cargadas
// - Eliminar una actividad con notas cargadas queda bloqueado

namespace App\Controllers;

use App\Config\Database;
use App\Models\PlanEvaluacion;
use App\Models\Materia;
use App\Models\Nota;

class PlanEvaluacionController
{
    private PlanEvaluacion $model;
    private Materia $modelMateria;
    private Nota $modelNota;

    public function __construct()
    {
        $database = new Database();
        $this->model = new PlanEvaluacion($database->getConnection());
        $this->modelMateria = new Materia($database->getConnection());
        $this->modelNota = new Nota($database->getConnection());
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

        // Indicamos a la vista si la materia ya tiene notas cargadas
        $tieneNotas = $this->modelNota->existeNotaEnMateria($materiaId);

        require_once __DIR__ . '/../Views/materia/plan_evaluacion.php';
    }

    // Guarda una actividad del plan (crear o actualizar)
    // Si la materia ya tiene notas, el mensaje de exito incluye un aviso
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

        // Sabemos si la materia tiene notas antes de guardar, para el aviso
        $tieneNotas = $this->modelNota->existeNotaEnMateria($materiaId);

        $datos = [
            'materia_id' => $materiaId,
            'nombre_actividad' => $nombreActividad,
            'porcentaje' => $porcentaje
        ];

        if ($id === 0) {
            $resultado = $this->model->crear($datos);
            if ($resultado) {
                // Si ya habia notas, la nueva actividad deja estudiantes en curso
                $mensaje = 'La actividad se agrego al plan de evaluacion.';
                if ($tieneNotas) {
                    $mensaje .= ' Los estudiantes quedaran En curso hasta que cargues su nota en esta actividad.';
                }
                echo json_encode([
                    'status' => 'success',
                    'title' => 'Actividad creada',
                    'message' => $mensaje
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
                // Si ya habia notas, avisamos que los finales se recalcularon
                $mensaje = 'Los cambios se guardaron correctamente.';
                if ($tieneNotas) {
                    $mensaje .= ' Atencion: las notas finales existentes se recalcularon con los nuevos porcentajes.';
                }
                echo json_encode([
                    'status' => 'success',
                    'title' => 'Actividad actualizada',
                    'message' => $mensaje
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
    // Bloqueado si la actividad ya tiene notas cargadas (proteccion de datos)
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

        // REGLA DE PROTECCION: no eliminar actividades con notas cargadas
        $totalNotas = $this->modelNota->contarNotasPorActividad($id);
        if ($totalNotas > 0) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Accion bloqueada',
                'message' => 'No puedes eliminar esta actividad porque ya tiene ' . $totalNotas . ' nota(s) cargada(s). Elimina esas notas primero desde el modulo de notas.'
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
        }
        exit;
    }
}