<?php
// app/Models/Periodo.php
// Modelo para gestionar los periodos academicos del sistema
// Ejemplo: 2026-1, 2026-2, etc.

namespace App\Models;

use PDO;
use PDOException;

class Periodo
{
    // Propiedad privada que almacena la conexion PDO
    private PDO $db;

    // Constructor que recibe la conexion desde el controlador
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Obtiene todos los periodos ordenados por id descendente
    public function obtenerTodos(): array
    {
        $stmt = $this->db->query("
            SELECT *
            FROM periodos
            ORDER BY id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtiene solo los periodos activos (para selects y filtros)
    public function obtenerActivos(): array
    {
        $stmt = $this->db->query("
            SELECT *
            FROM periodos
            WHERE estado = 'activo'
            ORDER BY id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtiene un periodo especifico por su id
    public function obtenerPorId(int $id): array|false
    {
        $sql = "
            SELECT *
            FROM periodos
            WHERE id = :id
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Verifica si un periodo con ese nombre ya existe
    public function existeNombre(string $nombre): bool
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM periodos
            WHERE nombre = :nombre
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':nombre' => $nombre]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'] > 0;
    }

    // Verifica si existe un periodo con ese nombre exceptuando un id (para ediciones)
    public function existeNombreExceptoId(string $nombre, int $idExcluir): bool
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM periodos
            WHERE nombre = :nombre AND id != :id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':id' => $idExcluir
        ]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'] > 0;
    }

    // Crea un nuevo periodo academico
    public function crear(array $datos): bool
    {
        try {
            $sql = "
                INSERT INTO periodos (nombre, estado)
                VALUES (:nombre, :estado)
            ";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':nombre' => $datos['nombre'],
                ':estado' => $datos['estado']
            ]);
        } catch (PDOException $e) {
            // Si falla por duplicado u otro error, retornamos false
            return false;
        }
    }

    // Actualiza un periodo existente
    public function actualizar(int $id, array $datos): bool
    {
        try {
            $sql = "
                UPDATE periodos
                SET nombre = :nombre,
                    estado = :estado
                WHERE id = :id
            ";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':nombre' => $datos['nombre'],
                ':estado' => $datos['estado']
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Elimina un periodo por su id
    public function eliminar(int $id): bool
    {
        try {
            $sql = "
                DELETE FROM periodos
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