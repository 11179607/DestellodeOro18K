<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/functions.php';

authenticateUser();
isAdmin(); // Solo admin puede agregar productos
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar datos requeridos
    $required = ['id', 'name', 'quantity', 'purchase_price', 'wholesale_price', 'retail_price', 'supplier'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            sendResponse('error', [], "El campo $field es requerido");
        }
    }
    
    // Sanitizar datos
    $product = [];
    foreach ($data as $key => $value) {
        $product[$key] = sanitizeInput($value);
    }
    
    $userId = $_SESSION['user_id'];
    
    try {
        // Verificar si el producto ya existe
        $checkQuery = "SELECT id FROM products WHERE id = :id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':id', $product['id']);
        $checkStmt->execute();
        
        if ($checkStmt->fetch()) {
            sendResponse('error', [], 'Ya existe un producto con esa referencia');
        }
        
        // Insertar producto
        $query = "INSERT INTO products (id, name, quantity, purchase_price, wholesale_price, 
                  retail_price, supplier, added_by) 
                  VALUES (:id, :name, :quantity, :purchase_price, :wholesale_price, 
                  :retail_price, :supplier, :added_by)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $product['id']);
        $stmt->bindParam(':name', $product['name']);
        $stmt->bindParam(':quantity', $product['quantity']);
        $stmt->bindParam(':purchase_price', $product['purchase_price']);
        $stmt->bindParam(':wholesale_price', $product['wholesale_price']);
        $stmt->bindParam(':retail_price', $product['retail_price']);
        $stmt->bindParam(':supplier', $product['supplier']);
        $stmt->bindParam(':added_by', $userId);
        
        if ($stmt->execute()) {
            // Registrar actividad
            logActivity($db, $userId, 'add_product', "Producto agregado: {$product['id']} - {$product['name']}");
            
            sendResponse('success', ['id' => $product['id']], 'Producto agregado exitosamente');
        } else {
            sendResponse('error', [], 'Error al agregar producto');
        }
        
    } catch (PDOException $e) {
        error_log("Error al agregar producto: " . $e->getMessage());
        sendResponse('error', [], 'Error al agregar producto');
    }
} else {
    sendResponse('error', [], 'Método no permitido');
}
?>