<?php
// app/Config/Database.php
// Clase que gestiona la conexion a la base de datos usando PDO
// Devuelve una instancia de PDO lista para usar en los modelos

namespace App\Config;

use PDO;
use PDOException;

class Database
{
    // Atributos privados con la configuracion de conexion
    private $host = '127.0.0.1';
    private $db   = 'controlEstudios';
    private $user = 'root';
    private $pass = '';
    private $charset = 'utf8mb4';
    private $pdo;

    public function __construct()
    {
        $this->connect();
    }

    // Metodo privado que crea la conexion PDO
    private function connect()
    {
        $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";

        // Opciones de PDO: errores como excepciones, fetch asociativo, sin emular prepares
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            // Si falla la conexion, lanzamos excepcion para detener el flujo
            throw new PDOException("Error de conexion: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    // Metodo publico que devuelve la conexion activa
    public function getConnection()
    {
        return $this->pdo;
    }
}