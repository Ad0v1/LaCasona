<?php
class Database {
    private $host = 'db'; // Nombre del servicio Docker
    private $username = 'root';
    private $password = '123456';
    private $database = 'reservacioneskawai';
    private $connection;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            // Configuración para Docker
            $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);
            
            if ($this->connection->connect_error) {
                throw new Exception("Error de conexión: " . $this->connection->connect_error);
            }
            
            // Configurar charset
            $this->connection->set_charset("utf8mb4");
            
            // Log de conexión exitosa (solo en desarrollo)
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("✅ Conexión a base de datos exitosa - Host: {$this->host}, DB: {$this->database}");
            }
            
        } catch (Exception $e) {
            error_log("❌ Error de base de datos: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }
    
    public function prepare($sql) {
        if (!$this->connection) {
            throw new Exception("No hay conexión a la base de datos");
        }
        
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $this->connection->error);
        }
        
        return $stmt;
    }
    
    public function query($sql) {
        if (!$this->connection) {
            throw new Exception("No hay conexión a la base de datos");
        }
        
        $result = $this->connection->query($sql);
        if (!$result) {
            throw new Exception("Error ejecutando consulta: " . $this->connection->error);
        }
        
        return $result;
    }
    
    public function insert_id() {
        return $this->connection->insert_id;
    }
    
    public function beginTransaction() {
        return $this->connection->begin_transaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
    
    public function close() {
        if ($this->connection && $this->connection->ping()) {
            $this->connection->close();
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function testConnection() {
        try {
            $result = $this->query("SELECT 1 as test");
            return $result->fetch_assoc()['test'] === 1;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function __destruct() {
        $this->close();
    }
}
?>
