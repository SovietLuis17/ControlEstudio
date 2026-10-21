<?php
// app/Models/Nota.php
// Modelo para gestionar las calificaciones de los estudiantes
// Las notas se cargan estudiante por estudiante (no de forma masiva)
// Incluye el calculo de nota final solo cuando el plan esta 100% calificado

namespace App\Models;

use PDO;
use PDOException;

class Nota
{
    // Propiedad privada que almacena la conexion PDO
    private PDO $db;

    // Constructor que recibe la conexion desde el controlador
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Obtiene las notas de un estudiante para una materia especifica
    // Retorna las actividades del plan con la nota del estudiante si existe
    public function obtenerPorEstudianteYMateria(int $estudianteId, int $materiaId): array
    {
        $sql = "
            SELECT 
                pe.id as actividad_id,
                pe.nombre_actividad,
                pe.porcentaje,
                n.id as nota_id,
                n.nota
            FROM plan_evaluacion pe
            LEFT JOIN notas n ON pe.id = n.plan_evaluacion_id 
                             AND n.estudiante_id = :estudiante_id
            WHERE pe.materia_id = :materia_id
            ORDER BY pe.id ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':estudiante_id' => $estudianteId,
            ':materia_id' => $materiaId
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtiene el resumen de notas de todos los estudiantes de una seccion
    // Regla de coherencia: la nota final y el estado solo se calculan
    // cuando el estudiante tiene nota en el 100% de las actividades
    // Si le falta alguna, el estado es "en_curso" y no se muestra final parcial
    public function obtenerResumenSeccion(int $seccionId, int $materiaId): array
    {
        // Total de actividades del plan de la materia
        $totalActividades = $this->contarActividadesPorMateria($materiaId);

        // Primero obtenemos los estudiantes de la seccion
        $sqlEstudiantes = "
            SELECT 
                e.id as estudiante_id,
                e.cedula,
                e.nombres,
                e.apellidos
            FROM estudiantes e
            WHERE e.seccion_id = :seccion_id
            ORDER BY e.apellidos ASC, e.nombres ASC
        ";
        $stmtEst = $this->db->prepare($sqlEstudiantes);
        $stmtEst->execute([':seccion_id' => $seccionId]);
        $estudiantes = $stmtEst->fetchAll(PDO::FETCH_ASSOC);

        $resumen = [];

        // Para cada estudiante calculamos su situacion de notas
        foreach ($estudiantes as $estudiante) {
            $sqlNotaFinal = "
                SELECT 
                    COALESCE(SUM(n.nota * pe.porcentaje / 100), 0) as nota_final,
                    COUNT(n.id) as total_notas
                FROM notas n
                INNER JOIN plan_evaluacion pe ON n.plan_evaluacion_id = pe.id
                WHERE n.estudiante_id = :estudiante_id
                  AND pe.materia_id = :materia_id
            ";
            $stmtNota = $this->db->prepare($sqlNotaFinal);
            $stmtNota->execute([
                ':estudiante_id' => $estudiante['estudiante_id'],
                ':materia_id' => $materiaId
            ]);
            $datosNota = $stmtNota->fetch(PDO::FETCH_ASSOC);

            $notaFinal = round(floatval($datosNota['nota_final']), 2);
            $totalNotas = intval($datosNota['total_notas']);

            // Determinamos estado y si mostramos nota final
            // sin_notas: no tiene ninguna nota
            // en_curso: tiene algunas notas pero no todas
            // aprobado / reprobado: tiene el 100% de las notas
            if ($totalNotas === 0) {
                $estado = 'sin_notas';
                $notaFinalMostrar = null;
            } elseif ($totalNotas < $totalActividades) {
                $estado = 'en_curso';
                $notaFinalMostrar = null;
            } elseif ($notaFinal >= 13) {
                $estado = 'aprobado';
                $notaFinalMostrar = $notaFinal;
            } else {
                $estado = 'reprobado';
                $notaFinalMostrar = $notaFinal;
            }

            $resumen[] = [
                'estudiante_id' => $estudiante['estudiante_id'],
                'cedula' => $estudiante['cedula'],
                'nombres' => $estudiante['nombres'],
                'apellidos' => $estudiante['apellidos'],
                'nota_final' => $notaFinalMostrar,
                'total_notas' => $totalNotas,
                'estado' => $estado
            ];
        }

        return $resumen;
    }

    // Guarda o actualiza la nota de un estudiante en una actividad
    public function guardarNota(int $estudianteId, int $planEvaluacionId, float $nota): bool
    {
        try {
            $sql = "
                INSERT INTO notas (estudiante_id, plan_evaluacion_id, nota)
                VALUES (:estudiante_id, :plan_evaluacion_id, :nota)
                ON DUPLICATE KEY UPDATE nota = :nota_update
            ";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':estudiante_id' => $estudianteId,
                ':plan_evaluacion_id' => $planEvaluacionId,
                ':nota' => $nota,
                ':nota_update' => $nota
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Guarda multiples notas de un estudiante usando transaccion
    // Si alguna falla, se revierte todo (rollback)
    public function guardarNotasEstudiante(int $estudianteId, array $notas): bool
    {
        try {
            $this->db->beginTransaction();

            foreach ($notas as $item) {
                $sql = "
                    INSERT INTO notas (estudiante_id, plan_evaluacion_id, nota)
                    VALUES (:estudiante_id, :plan_evaluacion_id, :nota)
                    ON DUPLICATE KEY UPDATE nota = :nota_update
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':estudiante_id' => $estudianteId,
                    ':plan_evaluacion_id' => $item['actividad_id'],
                    ':nota' => $item['nota'],
                    ':nota_update' => $item['nota']
                ]);
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // Elimina un registro de nota especifico por su id
    public function eliminar(int $id): bool
    {
        try {
            $sql = "
                DELETE FROM notas
                WHERE id = :id
            ";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Elimina todas las notas de un estudiante en una materia
    public function eliminarPorEstudianteYMateria(int $estudianteId, int $materiaId): bool
    {
        try {
            $sql = "
                DELETE n FROM notas n
                INNER JOIN plan_evaluacion pe ON n.plan_evaluacion_id = pe.id
                WHERE n.estudiante_id = :estudiante_id
                  AND pe.materia_id = :materia_id
            ";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':estudiante_id' => $estudianteId,
                ':materia_id' => $materiaId
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Cuenta cuantas notas tiene cargadas una actividad del plan
    // Se usa para bloquear la eliminacion de actividades con notas
    public function contarNotasPorActividad(int $planEvaluacionId): int
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM notas
            WHERE plan_evaluacion_id = :plan_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':plan_id' => $planEvaluacionId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return intval($resultado['total']);
    }

    // Verifica si una materia ya tiene notas cargadas en cualquiera de sus actividades
    // Se usa para avisar que un cambio de plan recalculara notas finales
    public function existeNotaEnMateria(int $materiaId): bool
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM notas n
            INNER JOIN plan_evaluacion pe ON n.plan_evaluacion_id = pe.id
            WHERE pe.materia_id = :materia_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':materia_id' => $materiaId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'] > 0;
    }

    // Cuenta cuantas notas se han cargado para una seccion y materia
    public function contarNotasPorSeccionYMateria(int $seccionId, int $materiaId): int
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM notas n
            INNER JOIN estudiantes e ON n.estudiante_id = e.id
            INNER JOIN plan_evaluacion pe ON n.plan_evaluacion_id = pe.id
            WHERE e.seccion_id = :seccion_id
              AND pe.materia_id = :materia_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':seccion_id' => $seccionId,
            ':materia_id' => $materiaId
        ]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return intval($resultado['total']);
    }

    // Cuenta cuantas actividades tiene el plan de una materia
    public function contarActividadesPorMateria(int $materiaId): int
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM plan_evaluacion
            WHERE materia_id = :materia_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':materia_id' => $materiaId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return intval($resultado['total']);
    }

        // Obtiene el reporte completo de notas de una seccion
    // Devuelve cada estudiante con sus notas por actividad, nota final y estado
    // Se usa para los reportes en PDF y Excel
    public function obtenerReporteNotas(int $seccionId, int $materiaId): array
    {
        // Actividades del plan de la materia
        $sqlActividades = "
            SELECT id, nombre_actividad, porcentaje
            FROM plan_evaluacion
            WHERE materia_id = :materia_id
            ORDER BY id ASC
        ";
        $stmtAct = $this->db->prepare($sqlActividades);
        $stmtAct->execute([':materia_id' => $materiaId]);
        $actividades = $stmtAct->fetchAll(PDO::FETCH_ASSOC);

        // Estudiantes inscritos en la seccion
        $sqlEstudiantes = "
            SELECT id as estudiante_id, cedula, nombres, apellidos
            FROM estudiantes
            WHERE seccion_id = :seccion_id
            ORDER BY apellidos ASC, nombres ASC
        ";
        $stmtEst = $this->db->prepare($sqlEstudiantes);
        $stmtEst->execute([':seccion_id' => $seccionId]);
        $estudiantes = $stmtEst->fetchAll(PDO::FETCH_ASSOC);

        // Todas las notas de esa seccion en esa materia, en una sola consulta
        $sqlNotas = "
            SELECT n.estudiante_id, n.plan_evaluacion_id, n.nota
            FROM notas n
            INNER JOIN plan_evaluacion pe ON n.plan_evaluacion_id = pe.id
            INNER JOIN estudiantes e ON n.estudiante_id = e.id
            WHERE e.seccion_id = :seccion_id
              AND pe.materia_id = :materia_id
        ";
        $stmtNotas = $this->db->prepare($sqlNotas);
        $stmtNotas->execute([
            ':seccion_id' => $seccionId,
            ':materia_id' => $materiaId
        ]);
        $notas = $stmtNotas->fetchAll(PDO::FETCH_ASSOC);

        // Armamos un mapa: estudiante -> actividad -> nota
        $mapa = [];
        foreach ($notas as $nota) {
            $mapa[$nota['estudiante_id']][$nota['plan_evaluacion_id']] = $nota['nota'];
        }

        // Combinamos todo en el arreglo final del reporte
        $reporte = [];
        foreach ($estudiantes as $estudiante) {
            $notasDelEstudiante = [];
            $suma = 0;
            $completas = 0;

            foreach ($actividades as $actividad) {
                $valor = $mapa[$estudiante['estudiante_id']][$actividad['id']] ?? null;
                $notasDelEstudiante[$actividad['id']] = $valor;

                if ($valor !== null) {
                    $suma += floatval($valor) * floatval($actividad['porcentaje']) / 100;
                    $completas++;
                }
            }

            // Misma regla de coherencia del sistema:
            // sin notas, en curso, aprobado o reprobado
            if ($completas === 0) {
                $estado = 'sin_notas';
                $notaFinal = null;
            } elseif ($completas < count($actividades)) {
                $estado = 'en_curso';
                $notaFinal = null;
            } elseif (round($suma, 2) >= 13) {
                $estado = 'aprobado';
                $notaFinal = round($suma, 2);
            } else {
                $estado = 'reprobado';
                $notaFinal = round($suma, 2);
            }

            $reporte[] = [
                'estudiante_id' => $estudiante['estudiante_id'],
                'cedula' => $estudiante['cedula'],
                'nombres' => $estudiante['nombres'],
                'apellidos' => $estudiante['apellidos'],
                'notas' => $notasDelEstudiante,
                'nota_final' => $notaFinal,
                'estado' => $estado
            ];
        }

        return $reporte;
    }
}