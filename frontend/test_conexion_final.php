<?php
// test_conexion_final.php
$config = [
    'host' => 'sql311.infinityfree.com',
    'dbname' => 'if0_40727550_destello_oro',
    'user' => 'if0_40727550',
    'pass' => 'TU_CONTRASEÑA_DEL_PANEL'  // La misma del login
];

echo "<h2>🔧 Test Conexión con TUS DATOS</h2>";
echo "<pre>";
print_r($config);
echo "</pre>";

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass']);
    
    echo "<div style='background:green;color:white;padding:20px;'>";
    echo "✅ ¡CONEXIÓN EXITOSA!";
    echo "</div>";
    
    // Ver tablas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>📊 Tablas en la BD (" . count($tables) . "):</p>";
    echo "<ul>";
    foreach($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "<div style='background:red;color:white;padding:20px;'>";
    echo "❌ ERROR: " . $e->getMessage();
    echo "<br><br><strong>Posible problema:</strong>";
    echo "<br>1. Contraseña incorrecta (usa la del panel)";
    echo "<br>2. La BD no existe o usuario no tiene acceso";
    echo "<br>3. Host incorrecto";
    echo "</div>";
}
?>