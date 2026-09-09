<?php
// app/Controllers/NotaController.php
// Controlador para gestionar las calificaciones de los estudiantes
// Las notas se cargan estudiante por estudiante (no de forma masiva)
// Admin puede ver, editar y eliminar
// Profesor puede ver y editar solo de sus secciones

namespace App\Controllers;

use App\Config\Database;
use App\Models\Nota;
use App\Models\Seccion;
use App\Models\PlanEvaluacion;
use App\Models\Estudiante;

class NotaController
{
    private Nota $model;
    private Seccion $modelSeccion;
    private PlanEvaluacion $modelPlan;
    private Estudiante $modelEstudiante;

    public function __construct()
    {
        $database = new Database();
        $this->model = new Nota($database->getConnection());
        $this->modelSeccion = new Seccion($database->getConnection());
        $this->modelPlan = new PlanEvaluacion($database->getConnection());
        $this->modelEstudiante = new Estudiante($database->getConnection());
    }

    // Muestra la vista principal con el resumen de notas de una seccion
    // Recibe el id de la seccion por GET
    public function index(): void
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
            if (!$this->modelSeccion->perteneceAProfesor($seccionId, $usuarioId)) {
                $_SESSION['swal'] = [
                    'icon' => 'error',
                    'title' => 'Sin permisos',
                    'text' => 'No puedes ver las notas de una seccion que no es tuya.'
                ];
                header('Location: ' . BASE_URL . 'seccion/index');
                exit;
            }
        }

        // Obtener datos de la seccion
        $seccion = $this->modelSeccion->obtenerPorId($seccionId);
        if (!$seccion) {
            $_SESSION['swal'] = [
                'icon' => 'error',
                'title' => 'Error',
                'text' => 'La seccion seleccionada no existe.'
            ];
            header('Location: ' . BASE_URL . 'seccion/index');
            exit;
        }

        $materiaId = intval($seccion['materia_id']);

        // Verificar que el plan de evaluacion este completo (suma 100)
        $planCompleto = $this->modelPlan->planCompleto($materiaId);
        if (!$planCompleto) {
            $_SESSION['swal'] = [
                'icon' => 'warning',
                'title' => 'Plan incompleto',
                'text' => 'El plan de evaluacion de esta materia no suma 100%. Complete el plan antes de cargar notas.'
            ];
            header('Location: ' . BASE_URL . 'seccion/index');
            exit;
        }

        // Obtener resumen de notas de la seccion
        $resumen = $this->model->obtenerResumenSeccion($seccionId, $materiaId);
        $actividades = $this->modelPlan->obtenerPorMateria($materiaId);

        require_once __DIR__ . '/../Views/nota/index.php';
    }

    // Devuelve JSON con las notas de un estudiante para una materia
    // Se usa para cargar el modal de edicion estudiante por estudiante
    public function ver(): void
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
                    'message' => 'No puedes ver las notas de una seccion que no es tuya.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        // Obtener la seccion para saber la materia
        $seccion = $this->modelSeccion->obtenerPorId($seccionId);
        if (!$seccion) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Seccion no encontrada',
                'message' => 'La seccion seleccionada no existe.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $materiaId = intval($seccion['materia_id']);

        // Obtener datos del estudiante
        $estudiante = $this->modelEstudiante->obtenerPorId($estudianteId);
        if (!$estudiante) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Estudiante no encontrado',
                'message' => 'El estudiante seleccionado no existe.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Verificar que el estudiante pertenezca a la seccion
        if (intval($estudiante['seccion_id']) !== $seccionId) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Estudiante no inscrito',
                'message' => 'Este estudiante no pertenece a la seccion seleccionada.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Obtener las actividades del plan con las notas del estudiante
        $actividades = $this->model->obtenerPorEstudianteYMateria($estudianteId, $materiaId);

        // Calcular nota final ponderada
        $notaFinal = 0;
        $totalNotas = 0;
        foreach ($actividades as $actividad) {
            if ($actividad['nota'] !== null) {
                $notaFinal += floatval($actividad['nota']) * floatval($actividad['porcentaje']) / 100;
                $totalNotas++;
            }
        }
        $notaFinal = round($notaFinal, 2);

        // Determinar estado segun nota final (>= 13 aprobado)
        if ($totalNotas === 0) {
            $estado = 'sin_notas';
        } elseif ($notaFinal >= 13) {
            $estado = 'aprobado';
        } else {
            $estado = 'reprobado';
        }

        echo json_encode([
            'status' => 'success',
            'data' => [
                'estudiante' => [
                    'id' => $estudiante['id'],
                    'nombres' => $estudiante['nombres'],
                    'apellidos' => $estudiante['apellidos'],
                    'cedula' => $estudiante['cedula']
                ],
                'actividades' => $actividades,
                'nota_final' => $notaFinal,
                'estado' => $estado
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Guarda las notas de un estudiante para todas las actividades
    // Recibe un array de notas: [{actividad_id, nota}, ...]
    // Usa transaccion para garantizar integridad de datos
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
                    'message' => 'No puedes guardar notas en una seccion que no es tuya.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        // Obtener las notas del formulario
        // Se espera un array en $_POST['notas'] con formato:
        // notas[actividad_id] = valor_nota
        $notasRecibidas = $_POST['notas'] ?? [];

        if (empty($notasRecibidas) || !is_array($notasRecibidas)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Sin datos',
                'message' => 'No se recibieron notas para guardar.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Obtener la seccion para saber la materia y validar plan completo
        $seccion = $this->modelSeccion->obtenerPorId($seccionId);
        if (!$seccion) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Seccion no encontrada',
                'message' => 'La seccion seleccionada no existe.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $materiaId = intval($seccion['materia_id']);

        // Verificar que el plan de evaluacion este completo
        if (!$this->modelPlan->planCompleto($materiaId)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Plan incompleto',
                'message' => 'El plan de evaluacion de esta materia no suma 100%.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Preparar array de notas para el modelo
        $notasParaGuardar = [];
        foreach ($notasRecibidas as $actividadId => $notaValor) {
            $actividadId = intval($actividadId);
            $notaValor = trim($notaValor);

            // Si el campo esta vacio, lo saltamos (no se guarda nota)
            if ($notaValor === '') {
                continue;
            }

            $notaFloat = floatval($notaValor);

            // Validar que la nota este entre 0 y 20
            if ($notaFloat < 0 || $notaFloat > 20) {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Nota invalida',
                    'message' => 'Las notas deben estar entre 0 y 20.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $notasParaGuardar[] = [
                'actividad_id' => $actividadId,
                'nota' => $notaFloat
            ];
        }

        if (empty($notasParaGuardar)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Sin notas validas',
                'message' => 'Debe ingresar al menos una nota.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Guardar todas las notas usando transaccion
        $resultado = $this->model->guardarNotasEstudiante($estudianteId, $notasParaGuardar);

        if ($resultado) {
            echo json_encode([
                'status' => 'success',
                'title' => 'Notas guardadas',
                'message' => 'Las calificaciones se guardaron correctamente.'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status' => 'error',
                'title' => 'Error',
                'message' => 'No se pudieron guardar las notas.'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    // Elimina un registro de nota especifico
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

        $notaId = intval($_POST['nota_id'] ?? 0);

        if ($notaId <= 0) {
            echo json_encode([
                'status' => 'error',
                'title' => 'ID invalido',
                'message' => 'No se recibio un identificador valido.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $resultado = $this->model->eliminar($notaId);

        if ($resultado) {
            echo json_encode([
                'status' => 'success',
                'title' => 'Nota eliminada',
                'message' => 'La calificacion se elimino correctamente.'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status' => 'error',
                'title' => 'Error',
                'message' => 'No se pudo eliminar la calificacion.'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}