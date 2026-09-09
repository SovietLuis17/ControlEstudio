<?php
// app/Models/Seccion.php
// Modelo para gestionar las secciones del sistema
// Una seccion es una instancia de una materia en un periodo
// dictada por un profesor con aula y horario

namespace App\Models;

use PDO;
use PDOException;

class Seccion
{
    // Propiedad privada que almacena la conexion PDO
    private PDO $db;

    // Constructor que recibe la conexion desde el controlador
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Obtiene todas las secciones con datos relacionados
    // Usamos INNER JOIN porque toda seccion debe tener materia, profesor y periodo
    public function obtenerTodos(): array
    {
        $stmt = $this->db->query("
            SELECT 
                s.id,
                s.codigo_seccion,
                s.aula,
                s.horario,
                m.id as materia_id,
                m.codigo as materia_codigo,
                m.nombre as materia_nombre,
                p.id as profesor_id,
                CONCAT(p.nombres, ' ', p.apellidos) as profesor_nombre,
                pe.id as periodo_id,
                pe.nombre as periodo_nombre
            FROM secciones s
            INNER JOIN materias m ON s.materia_id = m.id
            INNER JOIN profesores p ON s.profesor_id = p.id
            INNER JOIN periodos pe ON s.periodo_id = pe.id
            ORDER BY s.id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtiene las secciones asignadas a un profesor especifico
    // Se usa en el dashboard del profesor
    public function obtenerPorProfesor(int $profesorId): array
    {
        $sql = "
            SELECT 
                s.id,
                s.codigo_seccion,
                s.aula,
                s.horario,
                m.id as materia_id,
                m.codigo as materia_codigo,
                m.nombre as materia_nombre,
                pe.id as periodo_id,
                pe.nombre as periodo_nombre,
                (SELECT COUNT(*) FROM estudiantes e WHERE e.seccion_id = s.id) as total_estudiantes
            FROM secciones s
            INNER JOIN materias m ON s.materia_id = m.id
            INNER JOIN periodos pe ON s.periodo_id = pe.id
            WHERE s.profesor_id = :profesor_id
            ORDER BY pe.nombre DESC, m.codigo ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':profesor_id' => $profesorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtiene una seccion especifica por su id con datos relacionados
    public function obtenerPorId(int $id): array|false
    {
        $sql = "
            SELECT 
                s.*,
                m.codigo as materia_codigo,
                m.nombre as materia_nombre,
                CONCAT(p.nombres, ' ', p.apellidos) as profesor_nombre,
                pe.nombre as periodo_nombre
            FROM secciones s
            INNER JOIN materias m ON s.materia_id = m.id
            INNER JOIN profesores p ON s.profesor_id = p.id
            INNER JOIN periodos pe ON s.periodo_id = pe.id
            WHERE s.id = :id
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obtiene secciones para usar en un select (solo datos basicos)
    public function obtenerParaSelect(): array
    {
        $stmt = $this->db->query("
            SELECT 
                s.id,
                CONCAT(m.codigo, ' - Sec ', s.codigo_seccion, ' - ', pe.nombre) as etiqueta
            FROM secciones s
            INNER JOIN materias m ON s.materia_id = m.id
            INNER JOIN periodos pe ON s.periodo_id = pe.id
            ORDER BY m.codigo ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Verifica si una combinacion materia + periodo + seccion ya existe
    public function existeCombinacion(int $materiaId, int $periodoId, string $codigo): bool
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM secciones
            WHERE materia_id = :materia_id 
              AND periodo_id = :periodo_id 
              AND codigo_seccion = :codigo
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':materia_id' => $materiaId,
            ':periodo_id' => $periodoId,
            ':codigo' => $codigo
        ]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'] > 0;
    }

    // Verifica si una combinacion existe exceptuando un id (para ediciones)
    public function existeCombinacionExceptoId(int $materiaId, int $periodoId, string $codigo, int $idExcluir): bool
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM secciones
            WHERE materia_id = :materia_id 
              AND periodo_id = :periodo_id 
              AND codigo_seccion = :codigo
              AND id != :id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':materia_id' => $materiaId,
            ':periodo_id' => $periodoId,
            ':codigo' => $codigo,
            ':id' => $idExcluir
        ]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'] > 0;
    }

    // Crea una nueva seccion
    public function crear(array $datos): bool
    {
        try {
            $sql = "
                INSERT INTO secciones (materia_id, profesor_id, periodo_id, codigo_seccion, aula, horario)
                VALUES (:materia_id, :profesor_id, :periodo_id, :codigo_seccion, :aula, :horario)
            ";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':materia_id' => $datos['materia_id'],
                ':profesor_id' => $datos['profesor_id'],
                ':periodo_id' => $datos['periodo_id'],
                ':codigo_seccion' => $datos['codigo_seccion'],
                ':aula' => $datos['aula'],
                ':horario' => $datos['horario']
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Actualiza una seccion existente
    public function actualizar(int $id, array $datos): bool
    {
        try {
            $sql = "
                UPDATE secciones
                SET materia_id = :materia_id,
                    profesor_id = :profesor_id,
                    periodo_id = :periodo_id,
                    codigo_seccion = :codigo_seccion,
                    aula = :aula,
                    horario = :horario
                WHERE id = :id
            ";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':materia_id' => $datos['materia_id'],
                ':profesor_id' => $datos['profesor_id'],
                ':periodo_id' => $datos['periodo_id'],
                ':codigo_seccion' => $datos['codigo_seccion'],
                ':aula' => $datos['aula'],
                ':horario' => $datos['horario']
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Elimina una seccion por su id
    public function eliminar(int $id): bool
    {
        try {
            $sql = "
                DELETE FROM secciones
                WHERE id = :id
            ";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            // Si tiene estudiantes asignados, la FK SET NULL los dejara sin seccion
            return false;
        }
    }

    // Verifica si una seccion pertenece a un profesor especifico
    // Se usa para validar permisos del profesor
    public function perteneceAProfesor(int $seccionId, int $profesorId): bool
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM secciones
            WHERE id = :seccion_id AND profesor_id = :profesor_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':seccion_id' => $seccionId,
            ':profesor_id' => $profesorId
        ]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'] > 0;
    }

    // Cuenta cuantas notas se han cargado en una seccion
    // Se usa en el dashboard del profesor para mostrar avance
    public function contarNotasCargadas(int $seccionId, int $materiaId): int
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
}