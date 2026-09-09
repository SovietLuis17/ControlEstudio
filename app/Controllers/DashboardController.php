<?php
// app/Controllers/DashboardController.php
// Controlador del panel principal que muestra resumen segun el rol
// Admin ve totales globales, Profesor ve sus secciones y avance

namespace App\Controllers;

use App\Config\Database;
use App\Models\Periodo;
use App\Models\Materia;
use App\Models\Profesor;
use App\Models\Estudiante;
use App\Models\Seccion;
use App\Models\Nota;

class DashboardController
{
    private Periodo $modelPeriodo;
    private Materia $modelMateria;
    private Profesor $modelProfesor;
    private Estudiante $modelEstudiante;
    private Seccion $modelSeccion;
    private Nota $modelNota;

    public function __construct()
    {
        $database = new Database();
        $this->modelPeriodo = new Periodo($database->getConnection());
        $this->modelMateria = new Materia($database->getConnection());
        $this->modelProfesor = new Profesor($database->getConnection());
        $this->modelEstudiante = new Estudiante($database->getConnection());
        $this->modelSeccion = new Seccion($database->getConnection());
        $this->modelNota = new Nota($database->getConnection());
    }

    // Muestra el dashboard adaptativo segun el rol del usuario
    public function index(): void
    {
        $rol = $_SESSION['rol'] ?? '';
        $usuarioId = intval($_SESSION['usuario_id'] ?? 0);

        if ($rol === 'admin') {
            // Datos para dashboard de admin: totales globales
            $periodos = $this->modelPeriodo->obtenerTodos();
            $materias = $this->modelMateria->obtenerTodos();
            $profesores = $this->modelProfesor->obtenerTodos();
            $estudiantes = $this->modelEstudiante->obtenerTodos();
            $secciones = $this->modelSeccion->obtenerTodos();

            $datos = [
                'rol' => $rol,
                'totalPeriodos' => count($periodos),
                'totalMaterias' => count($materias),
                'totalProfesores' => count($profesores),
                'totalEstudiantes' => count($estudiantes),
                'totalSecciones' => count($secciones),
                'periodos' => $periodos,
                'materias' => $materias,
                'profesores' => $profesores,
                'estudiantes' => $estudiantes,
                'secciones' => $secciones
            ];
        } else {
            // Datos para dashboard de profesor: sus secciones y avance
            $seccionesProfesor = $this->modelSeccion->obtenerPorProfesor($usuarioId);

            // Calcular avance de notas para cada seccion
            foreach ($seccionesProfesor as &$seccion) {
                $totalActividades = $this->modelNota->contarActividadesPorMateria($seccion['materia_id']);
                $totalEstudiantes = intval($seccion['total_estudiantes']);
                $totalNotas = $this->modelNota->contarNotasPorSeccionYMateria($seccion['id'], $seccion['materia_id']);

                $totalEsperado = $totalActividades * $totalEstudiantes;
                $seccion['avance'] = $totalEsperado > 0 ? round(($totalNotas / $totalEsperado) * 100) : 0;
                $seccion['total_notas'] = $totalNotas;
                $seccion['total_esperado'] = $totalEsperado;
            }
            unset($seccion); // Liberar referencia del foreach

            $datos = [
                'rol' => $rol,
                'secciones' => $seccionesProfesor
            ];
        }

        require_once __DIR__ . '/../Views/dashboard/index.php';
    }
}