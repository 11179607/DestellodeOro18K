<?php
// Incluir configuración de base de datos
require_once __DIR__ . '/../api/config/database.php';

/**
 * Obtener conexión a la base de datos
 */
function getDB() {
    $database = new Database();
    return $database->getConnection();
}

/**
 * Verificar autenticación del usuario
 */
function authenticateUser() {
    if (!isset($_SESSION['user_id'])) {
        header('HTTP/1.1 401 Unauthorized');
        die(json_encode([
            'status' => 'error',
            'message' => 'No autorizado. Inicie sesión primero.'
        ]));
    }
    return $_SESSION['user_id'];
}

/**
 * Verificar si el usuario es administrador
 */
function isAdmin() {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('HTTP/1.1 403 Forbidden');
        die(json_encode([
            'status' => 'error',
            'message' => 'Acceso denegado. Se requiere rol de administrador.'
        ]));
    }
}

/**
 * Formatear moneda
 */
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.');
}

/**
 * Generar hash seguro para contraseñas
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verificar contraseña
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generar ID único para facturas
 */
function generateInvoiceId($db) {
    $query = "SELECT MAX(CAST(SUBSTRING(id, 4) AS UNSIGNED)) as last_id FROM sales WHERE id LIKE 'FAC%'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();
    
    $lastId = $result['last_id'] ? $result['last_id'] : 0;
    $nextId = $lastId + 1;
    
    return 'FAC' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
}

/**
 * Generar ID único para garantías
 */
function generateWarrantyId($db) {
    $query = "SELECT MAX(CAST(SUBSTRING(id, 4) AS UNSIGNED)) as last_id FROM warranties WHERE id LIKE 'GAR%'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();
    
    $lastId = $result['last_id'] ? $result['last_id'] : 0;
    $nextId = $lastId + 1;
    
    return 'GAR' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
}

/**
 * Validar y sanitizar entrada de datos
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitizeInput($value);
        }
        return $data;
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Enviar respuesta JSON
 */
function sendResponse($status, $data = [], $message = '') {
    header('Content-Type: application/json');
    $response = ['status' => $status];
    
    if ($message) {
        $response['message'] = $message;
    }
    
    if (!empty($data)) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Registrar actividad en el sistema
 */
function logActivity($db, $userId, $action, $details = '') {
    $query = "INSERT INTO user_logs (user_id, action, details, ip_address, user_agent) 
              VALUES (:user_id, :action, :details, :ip_address, :user_agent)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':action', $action);
    $stmt->bindParam(':details', $details);
    $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);
    $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT']);
    
    $stmt->execute();
}

/**
 * Obtener estadísticas mensuales
 */
function getMonthlyStats($db, $month, $year) {
    $startDate = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
    $endDate = date('Y-m-t', strtotime($startDate));
    
    $stats = [];
    
    // Ventas totales
    $query = "SELECT COALESCE(SUM(total), 0) as total_sales, COUNT(*) as sales_count 
              FROM sales 
              WHERE sale_date BETWEEN :start_date AND :end_date 
              AND status = 'completed'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $stats['sales'] = $stmt->fetch();
    
    // Gastos totales
    $query = "SELECT COALESCE(SUM(amount), 0) as total_expenses, COUNT(*) as expenses_count 
              FROM expenses 
              WHERE expense_date BETWEEN :start_date AND :end_date";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $stats['expenses'] = $stmt->fetch();
    
    // Surtidos totales
    $query = "SELECT COALESCE(SUM(total_value), 0) as total_restocks, COUNT(*) as restocks_count 
              FROM restocks 
              WHERE restock_date BETWEEN :start_date AND :end_date";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $stats['restocks'] = $stmt->fetch();
    
    // Garantías totales
    $query = "SELECT COALESCE(SUM(shipping_cost + price_difference), 0) as total_warranties, 
              COUNT(*) as warranties_count 
              FROM warranties 
              WHERE warranty_date BETWEEN :start_date AND :end_date";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
    $stmt->execute();
    $stats['warranties'] = $stmt->fetch();
    
    return $stats;
}
?>