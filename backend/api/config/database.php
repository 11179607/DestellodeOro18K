<?php
// Configuración de la base de datos
define('DB_HOST', 'sql311.infinityfree.com');
define('DB_NAME', 'if0_40727550_destellodeoro');
define('DB_USER', 'if0_40727550');
define('DB_PASS', 'R384n6XMKCK4');
define('DB_CHARSET', 'utf8mb4');

// Configuración de la aplicación
define('APP_NAME', 'Destello de Oro 18K');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/destello_oro');
define('ADMIN_WHATSAPP', '3182687488');

// Configuración de seguridad
define('SESSION_LIFETIME', 86400); // 24 horas en segundos
define('PASSWORD_COST', 12); // Costo del hash bcrypt

class Database {
    private $conn;
    
    public function __construct() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
}
?>