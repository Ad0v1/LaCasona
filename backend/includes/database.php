<?php
class Database {
    private $host = 'db'; // Docker service name
    private $username = 'root';
    private $password = '123456';
    private $database = 'reservacioneskawai';
    private $connection;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);
            
            if ($this->connection->connect_error) {
                throw new Exception("Error de conexión: " . $this->connection->connect_error);
            }
            
            $this->connection->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            error_log("Error de base de datos: " . $e->getMessage());
            die("Error de conexión a la base de datos");
        }
    }
    
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    public function query($sql) {
        return $this->connection->query($sql);
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
    
    public function __destruct() {
        $this->close();
    }
}
?>
