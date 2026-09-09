<?php
// app/Models/Materia.php
// Modelo para gestionar el catalogo de materias del sistema
// Incluye metodos para restringir las materias que ve un profesor

namespace App\Models;

use PDO;
use PDOException;

class Materia
{
    // Propiedad privada que almacena la conexion PDO
    private PDO $db;

    // Constructor que recibe la conexion desde el controlador
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Obtiene todas las materias ordenadas por id descendente
    // Se usa solo para el rol admin
    public function obtenerTodos(): array
    {
        $stmt = $this->db->query("
            SELECT *
            FROM materias
            ORDER BY id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtiene solo las materias que dicta un profesor
    // Una materia es dictada por un profesor si tiene al menos una seccion asignada a el
    public function obtenerPorProfesor(int $profesorId): array
    {
        $sql = "
            SELECT DISTINCT m.*
            FROM materias m
            INNER JOIN secciones s ON s.materia_id = m.id
            WHERE s.profesor_id = :profesor_id
            ORDER BY m.codigo ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':profesor_id' => $profesorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Verifica si una materia es dictada por un profesor especifico
    // Se usa para restringir el acceso del profesor a sus propias materias y planes
    public function esDictadaPorProfesor(int $materiaId, int $profesorId): bool
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM secciones
            WHERE materia_id = :materia_id AND profesor_id = :profesor_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':materia_id' => $materiaId,
            ':profesor_id' => $profesorId
        ]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'] > 0;
    }

    // Obtiene una materia especifica por su id
    public function obtenerPorId(int $id): array|false
    {
        $sql = "
            SELECT *
            FROM materias
            WHERE id = :id
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obtiene materias para usar en un select (solo id y nombre con codigo)
    public function obtenerParaSelect(): array
    {
        $stmt = $this->db->query("
            SELECT id, codigo, nombre
            FROM materias
            ORDER BY codigo ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Verifica si una materia con ese codigo ya existe
    public function existeCodigo(string $codigo): bool
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM materias
            WHERE codigo = :codigo
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':codigo' => $codigo]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'] > 0;
    }

    // Verifica si existe una materia con ese codigo exceptuando un id (para ediciones)
    public function existeCodigoExceptoId(string $codigo, int $idExcluir): bool
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM materias
            WHERE codigo = :codigo AND id != :id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':codigo' => $codigo,
            ':id' => $idExcluir
        ]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'] > 0;
    }

    // Crea una nueva materia
    public function crear(array $datos): bool
    {
        try {
            $sql = "
                INSERT INTO materias (codigo, nombre, creditos, descripcion)
                VALUES (:codigo, :nombre, :creditos, :descripcion)
            ";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':codigo' => $datos['codigo'],
                ':nombre' => $datos['nombre'],
                ':creditos' => $datos['creditos'],
                ':descripcion' => $datos['descripcion']
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Actualiza una materia existente
    public function actualizar(int $id, array $datos): bool
    {
        try {
            $sql = "
                UPDATE materias
                SET codigo = :codigo,
                    nombre = :nombre,
                    creditos = :creditos,
                    descripcion = :descripcion
                WHERE id = :id
            ";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':codigo' => $datos['codigo'],
                ':nombre' => $datos['nombre'],
                ':creditos' => $datos['creditos'],
                ':descripcion' => $datos['descripcion']
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Elimina una materia por su id
    public function eliminar(int $id): bool
    {
        try {
            $sql = "
                DELETE FROM materias
                WHERE id = :id
            ";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            // Si tiene secciones asociadas, la FK impedira la eliminacion
            return false;
        }
    }
}