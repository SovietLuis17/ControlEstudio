<?php
// app/Controllers/MateriaController.php
// Controlador para gestionar el catalogo de materias
// Admin puede crear, editar y eliminar todas las materias
// Profesor solo ve las materias que dicta y gestiona sus planes

namespace App\Controllers;

use App\Config\Database;
use App\Models\Materia;
use App\Models\PlanEvaluacion;

class MateriaController
{
    private Materia $model;
    private PlanEvaluacion $modelPlan;

    public function __construct()
    {
        $database = new Database();
        $this->model = new Materia($database->getConnection());
        $this->modelPlan = new PlanEvaluacion($database->getConnection());
    }

    // Muestra la vista principal con las materias
    // Admin ve todas las materias del sistema
    // Profesor ve solo las materias que dicta
    public function index(): void
    {
        $rol = $_SESSION['rol'] ?? '';
        $usuarioId = intval($_SESSION['usuario_id'] ?? 0);

        if ($rol === 'admin') {
            $materias = $this->model->obtenerTodos();
        } else {
            // El profesor solo recibe las materias donde tiene secciones
            $materias = $this->model->obtenerPorProfesor($usuarioId);
        }

        // Para cada materia, obtenemos si su plan de evaluacion esta completo
        foreach ($materias as &$materia) {
            $materia['plan_completo'] = $this->modelPlan->planCompleto($materia['id']);
            $materia['suma_porcentajes'] = $this->modelPlan->sumaPorcentajes($materia['id']);
        }
        unset($materia);

        require_once __DIR__ . '/../Views/materia/index.php';
    }

    // Guarda una materia (crear o actualizar)
    // Solo el admin puede ejecutar esta accion (matriz de rutas)
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
        $codigo = trim($_POST['codigo'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $creditos = intval($_POST['creditos'] ?? 3);
        $descripcion = trim($_POST['descripcion'] ?? '');

        // Validar campos obligatorios
        if (empty($codigo)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Campo obligatorio',
                'message' => 'El codigo de la materia es obligatorio.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (empty($nombre)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Campo obligatorio',
                'message' => 'El nombre de la materia es obligatorio.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Validar que creditos sea un numero positivo
        if ($creditos <= 0) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Dato invalido',
                'message' => 'Los creditos deben ser un numero positivo.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Verificar codigo duplicado
        if ($id === 0) {
            if ($this->model->existeCodigo($codigo)) {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Duplicado',
                    'message' => 'Ya existe una materia con ese codigo.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        } else {
            if ($this->model->existeCodigoExceptoId($codigo, $id)) {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Duplicado',
                    'message' => 'Ya existe otra materia con ese codigo.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        $datos = [
            'codigo' => $codigo,
            'nombre' => $nombre,
            'creditos' => $creditos,
            'descripcion' => $descripcion
        ];

        if ($id === 0) {
            $resultado = $this->model->crear($datos);
            if ($resultado) {
                echo json_encode([
                    'status' => 'success',
                    'title' => 'Materia creada',
                    'message' => 'La materia se registro correctamente.'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Error',
                    'message' => 'No se pudo crear la materia.'
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            $resultado = $this->model->actualizar($id, $datos);
            if ($resultado) {
                echo json_encode([
                    'status' => 'success',
                    'title' => 'Materia actualizada',
                    'message' => 'Los cambios se guardaron correctamente.'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Error',
                    'message' => 'No se pudo actualizar la materia.'
                ], JSON_UNESCAPED_UNICODE);
            }
        }
        exit;
    }

    // Elimina una materia por su id
    // Solo el admin puede ejecutar esta accion (matriz de rutas)
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
                'title' => 'Materia eliminada',
                'message' => 'La materia se elimino correctamente.'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status' => 'error',
                'title' => 'Error',
                'message' => 'No se pudo eliminar. Verifica que no tenga secciones asociadas.'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}