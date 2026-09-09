<?php
// app/Controllers/PeriodoController.php
// Controlador para gestionar periodos academicos
// Solo el admin puede crear, editar y eliminar periodos

namespace App\Controllers;

use App\Config\Database;
use App\Models\Periodo;

class PeriodoController
{
    private Periodo $model;

    public function __construct()
    {
        $database = new Database();
        $this->model = new Periodo($database->getConnection());
    }

    // Muestra la vista principal con todos los periodos
    public function index(): void
    {
        $periodos = $this->model->obtenerTodos();
        require_once __DIR__ . '/../Views/periodo/index.php';
    }

    // Guarda un periodo (crear o actualizar)
    // Responde JSON para usar con Fetch API
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
        $nombre = trim($_POST['nombre'] ?? '');
        $estado = $_POST['estado'] ?? 'activo';

        // Validar campos obligatorios
        if (empty($nombre)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Campo obligatorio',
                'message' => 'El nombre del periodo es obligatorio.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Validar estado valido
        if (!in_array($estado, ['activo', 'cerrado'])) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Estado invalido',
                'message' => 'El estado debe ser activo o cerrado.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Verificar duplicados
        if ($id === 0) {
            if ($this->model->existeNombre($nombre)) {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Duplicado',
                    'message' => 'Ya existe un periodo con ese nombre.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        } else {
            if ($this->model->existeNombreExceptoId($nombre, $id)) {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Duplicado',
                    'message' => 'Ya existe otro periodo con ese nombre.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        $datos = [
            'nombre' => $nombre,
            'estado' => $estado
        ];

        if ($id === 0) {
            $resultado = $this->model->crear($datos);
            if ($resultado) {
                echo json_encode([
                    'status' => 'success',
                    'title' => 'Periodo creado',
                    'message' => 'El periodo se registro correctamente.'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Error',
                    'message' => 'No se pudo crear el periodo.'
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            $resultado = $this->model->actualizar($id, $datos);
            if ($resultado) {
                echo json_encode([
                    'status' => 'success',
                    'title' => 'Periodo actualizado',
                    'message' => 'Los cambios se guardaron correctamente.'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Error',
                    'message' => 'No se pudo actualizar el periodo.'
                ], JSON_UNESCAPED_UNICODE);
            }
        }
        exit;
    }

    // Elimina un periodo por su id
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
                'title' => 'Periodo eliminado',
                'message' => 'El periodo se elimino correctamente.'
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