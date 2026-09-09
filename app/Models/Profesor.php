<?php
// app/Models/Profesor.php
// Modelo para gestionar profesores y admins del sistema
// La tabla profesores incluye ambos roles: admin y profesor

namespace App\Models;

use PDO;
use PDOException;

class Profesor
{
    // Propiedad privada que almacena la conexion PDO
    private PDO $db;

    // Constructor que recibe la conexion desde el controlador
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Obtiene todos los profesores ordenados por id descendente
    public function obtenerTodos(): array
    {
        $stmt = $this->db->query("
            SELECT id, cedula, nombres, apellidos, fecha_nacimiento, genero, 
                   direccion, telefono, correo, especialidad, rol,
                   TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) as edad
            FROM profesores
            ORDER BY id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtiene solo profesores con rol profesor (para selects)
    public function obtenerSoloProfesores(): array
    {
        $stmt = $this->db->query("
            SELECT id, cedula, nombres, apellidos, correo, especialidad
            FROM profesores
            WHERE rol = 'profesor'
            ORDER BY nombres ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtiene un profesor especifico por su id
    public function obtenerPorId(int $id): array|false
    {
        $sql = "
            SELECT id, cedula, nombres, apellidos, fecha_nacimiento, genero,
                   direccion, telefono, correo, especialidad, rol,
                   TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) as edad
            FROM profesores
            WHERE id = :id
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obtiene un profesor por su correo (para login)
    public function obtenerPorCorreo(string $correo): array|false
    {
        $sql = "
            SELECT *
            FROM profesores
            WHERE correo = :correo
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':correo' => $correo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Verifica si una cedula ya existe
    public function existeCedula(string $cedula): bool
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM profesores
            WHERE cedula = :cedula
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cedula' => $cedula]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'] > 0;
    }

    // Verifica si una cedula existe exceptuando un id (para ediciones)
    public function existeCedulaExceptoId(string $cedula, int $idExcluir): bool
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM profesores
            WHERE cedula = :cedula AND id != :id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cedula' => $cedula,
            ':id' => $idExcluir
        ]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'] > 0;
    }

    // Verifica si un correo ya existe
    public function existeCorreo(string $correo): bool
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM profesores
            WHERE correo = :correo
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':correo' => $correo]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'] > 0;
    }

    // Verifica si un correo existe exceptuando un id (para ediciones)
    public function existeCorreoExceptoId(string $correo, int $idExcluir): bool
    {
        $sql = "
            SELECT COUNT(*) as total
            FROM profesores
            WHERE correo = :correo AND id != :id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':correo' => $correo,
            ':id' => $idExcluir
        ]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'] > 0;
    }

    // Crea un nuevo profesor con password hasheado
    public function crear(array $datos): bool
    {
        try {
            $sql = "
                INSERT INTO profesores (cedula, nombres, apellidos, fecha_nacimiento, genero, 
                                       direccion, telefono, correo, password, especialidad, rol)
                VALUES (:cedula, :nombres, :apellidos, :fecha_nacimiento, :genero,
                        :direccion, :telefono, :correo, :password, :especialidad, :rol)
            ";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':cedula' => $datos['cedula'],
                ':nombres' => $datos['nombres'],
                ':apellidos' => $datos['apellidos'],
                ':fecha_nacimiento' => $datos['fecha_nacimiento'],
                ':genero' => $datos['genero'],
                ':direccion' => $datos['direccion'],
                ':telefono' => $datos['telefono'],
                ':correo' => $datos['correo'],
                ':password' => $datos['password'],
                ':especialidad' => $datos['especialidad'],
                ':rol' => $datos['rol']
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Actualiza un profesor existente
    // Si password esta vacio, no se modifica la contrasena
    public function actualizar(int $id, array $datos): bool
    {
        try {
            // Si se proporciona nueva contrasena, la actualizamos
            if (!empty($datos['password'])) {
                $sql = "
                    UPDATE profesores
                    SET cedula = :cedula,
                        nombres = :nombres,
                        apellidos = :apellidos,
                        fecha_nacimiento = :fecha_nacimiento,
                        genero = :genero,
                        direccion = :direccion,
                        telefono = :telefono,
                        correo = :correo,
                        password = :password,
                        especialidad = :especialidad,
                        rol = :rol
                    WHERE id = :id
                ";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([
                    ':id' => $id,
                    ':cedula' => $datos['cedula'],
                    ':nombres' => $datos['nombres'],
                    ':apellidos' => $datos['apellidos'],
                    ':fecha_nacimiento' => $datos['fecha_nacimiento'],
                    ':genero' => $datos['genero'],
                    ':direccion' => $datos['direccion'],
                    ':telefono' => $datos['telefono'],
                    ':correo' => $datos['correo'],
                    ':password' => $datos['password'],
                    ':especialidad' => $datos['especialidad'],
                    ':rol' => $datos['rol']
                ]);
            } else {
                // Si no hay nueva contrasena, no tocamos el campo password
                $sql = "
                    UPDATE profesores
                    SET cedula = :cedula,
                        nombres = :nombres,
                        apellidos = :apellidos,
                        fecha_nacimiento = :fecha_nacimiento,
                        genero = :genero,
                        direccion = :direccion,
                        telefono = :telefono,
                        correo = :correo,
                        especialidad = :especialidad,
                        rol = :rol
                    WHERE id = :id
                ";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([
                    ':id' => $id,
                    ':cedula' => $datos['cedula'],
                    ':nombres' => $datos['nombres'],
                    ':apellidos' => $datos['apellidos'],
                    ':fecha_nacimiento' => $datos['fecha_nacimiento'],
                    ':genero' => $datos['genero'],
                    ':direccion' => $datos['direccion'],
                    ':telefono' => $datos['telefono'],
                    ':correo' => $datos['correo'],
                    ':especialidad' => $datos['especialidad'],
                    ':rol' => $datos['rol']
                ]);
            }
        } catch (PDOException $e) {
            return false;
        }
    }

    // Elimina un profesor por su id
    public function eliminar(int $id): bool
    {
        try {
            $sql = "
                DELETE FROM profesores
                WHERE id = :id
            ";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            // Si tiene secciones asociadas, la FK impedira la eliminacion
            return false;
        }
    }

    // Cuenta cuantos admins existen en el sistema
    // Sirve para evitar eliminar al ultimo admin
    public function contarAdmins(): int
    {
        $stmt = $this->db->query("
            SELECT COUNT(*) as total
            FROM profesores
            WHERE rol = 'admin'
        ");
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return intval($resultado['total']);
    }
}