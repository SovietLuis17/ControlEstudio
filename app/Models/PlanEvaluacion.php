<?php
// app/Models/PlanEvaluacion.php
// Modelo para gestionar el plan de evaluacion de cada materia
// El plan define las actividades y porcentajes que deben sumar 100
// El plan se define por materia, no por seccion

namespace App\Models;

use PDO;
use PDOException;

class PlanEvaluacion
{
    // Propiedad privada que almacena la conexion PDO
    private PDO $db;

    // Constructor que recibe la conexion desde el controlador
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Obtiene todas las actividades de una materia especifica
    // Se usa para mostrar el plan completo de una materia
    public function obtenerPorMateria(int $materiaId): array
    {
        $sql = "
            SELECT *
            FROM plan_evaluacion
            WHERE materia_id = :materia_id
            ORDER BY id ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':materia_id' => $materiaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtiene una actividad especifica por su id
    // Se usa al editar una actividad existente
    public function obtenerPorId(int $id): array|false
    {
        $sql = "
            SELECT *
            FROM plan_evaluacion
            WHERE id = :id
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Calcula la suma de porcentajes de todas las actividades de una materia
    // Sirve para validar que el plan este completo (debe sumar 100)
    public function sumaPorcentajes(int $materiaId): float
    {
        $sql = "
            SELECT COALESCE(SUM(porcentaje), 0) as total
            FROM plan_evaluacion
            WHERE materia_id = :materia_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':materia_id' => $materiaId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return floatval($resultado['total']);
    }

    // Verifica si el plan de una materia esta completo
    // Un plan esta completo cuando la suma de porcentajes es exactamente 100
    // Usamos un margen minimo por precision de decimales
    public function planCompleto(int $materiaId): bool
    {
        $suma = $this->sumaPorcentajes($materiaId);
        return abs($suma - 100.00) < 0.01;
    }

    // Cuenta cuantas actividades tiene el plan de una materia
    // Se usa para calcular el avance de notas en el dashboard
    public function contarActividades(int $materiaId): int
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

    // Verifica si una actividad con ese nombre ya existe en esa materia
    // Se usa al crear para evitar duplicados
    public function existeActividad(int $materiaId, string $nombre): bool
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM plan_evaluacion
            WHERE materia_id = :materia_id AND nombre_actividad = :nombre
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':materia_id' => $materiaId,
            ':nombre' => $nombre
        ]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'] > 0;
    }

    // Verifica si existe una actividad con ese nombre exceptuando un id
    // Se usa al editar para evitar duplicados con otras actividades
    public function existeActividadExceptoId(int $materiaId, string $nombre, int $idExcluir): bool
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM plan_evaluacion
            WHERE materia_id = :materia_id 
              AND nombre_actividad = :nombre 
              AND id != :id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':materia_id' => $materiaId,
            ':nombre' => $nombre,
            ':id' => $idExcluir
        ]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'] > 0;
    }

    // Crea una nueva actividad en el plan de evaluacion de una materia
    public function crear(array $datos): bool
    {
        try {
            $sql = "
                INSERT INTO plan_evaluacion (materia_id, nombre_actividad, porcentaje)
                VALUES (:materia_id, :nombre_actividad, :porcentaje)
            ";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':materia_id' => $datos['materia_id'],
                ':nombre_actividad' => $datos['nombre_actividad'],
                ':porcentaje' => $datos['porcentaje']
            ]);
        } catch (PDOException $e) {
            // Si falla por duplicado u otro error, retornamos false
            return false;
        }
    }

    // Actualiza una actividad existente del plan de evaluacion
    public function actualizar(int $id, array $datos): bool
    {
        try {
            $sql = "
                UPDATE plan_evaluacion
                SET nombre_actividad = :nombre_actividad,
                    porcentaje = :porcentaje
                WHERE id = :id
            ";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':nombre_actividad' => $datos['nombre_actividad'],
                ':porcentaje' => $datos['porcentaje']
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Elimina una actividad del plan por su id
    // Si la actividad tiene notas asociadas, la FK en cascada las eliminara
    public function eliminar(int $id): bool
    {
        try {
            $sql = "
                DELETE FROM plan_evaluacion
                WHERE id = :id
            ";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Elimina todas las actividades del plan de una materia
    // Se usa cuando se elimina una materia completa
    public function eliminarPorMateria(int $materiaId): bool
    {
        try {
            $sql = "
                DELETE FROM plan_evaluacion
                WHERE materia_id = :materia_id
            ";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':materia_id' => $materiaId]);
        } catch (PDOException $e) {
            return false;
        }
    }
}