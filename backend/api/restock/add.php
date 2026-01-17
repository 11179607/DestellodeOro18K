<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/functions.php';

authenticateUser();
isAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['product_id']) || !isset($data['quantity'])) {
        sendResponse('error', [], 'Producto y cantidad son requeridos');
    }
    
    $productId = sanitizeInput($data['product_id']);
    $quantity = (int)$data['quantity'];
    $userId = $_SESSION['user_id'];
    
    if ($quantity <= 0) {
        sendResponse('error', [], 'La cantidad debe ser mayor a 0');
    }
    
    try {
        $db->beginTransaction();
        
        // Obtener información del producto
        $query = "SELECT id, name, purchase_price FROM products WHERE id = :product_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':product_id', $productId);
        $stmt->execute();
        
        $product = $stmt->fetch();
        
        if (!$product) {
            sendResponse('error', [], 'Producto no encontrado');
        }
        
        // Actualizar cantidad en inventario
        $updateQuery = "UPDATE products SET quantity = quantity + :quantity WHERE id = :product_id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':quantity', $quantity);
        $updateStmt->bindParam(':product_id', $productId);
        $updateStmt->execute();
        
        // Registrar surtido
        $totalValue = $product['purchase_price'] * $quantity;
        
        $restockQuery = "INSERT INTO restocks (product_id, product_name, quantity, total_value, user_id)
                        VALUES (:product_id, :product_name, :quantity, :total_value, :user_id)";
        
        $restockStmt = $db->prepare($restockQuery);
        $restockStmt->bindParam(':product_id', $productId);
        $restockStmt->bindParam(':product_name', $product['name']);
        $restockStmt->bindParam(':quantity', $quantity);
        $restockStmt->bindParam(':total_value', $totalValue);
        $restockStmt->bindParam(':user_id', $userId);
        $restockStmt->execute();
        
        $db->commit();
        
        // Registrar actividad
        logActivity($db, $userId, 'restock_product', 
                   "Surtido: {$product['name']} - Cantidad: $quantity - Valor: $totalValue");
        
        sendResponse('success', [
            'new_quantity' => $product['quantity'] + $quantity,
            'total_value' => $totalValue
        ], 'Inventario surtido exitosamente');
        
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Error al surtir inventario: " . $e->getMessage());
        sendResponse('error', [], 'Error al surtir inventario');
    }
} else {
    sendResponse('error', [], 'Método no permitido');
}
?>