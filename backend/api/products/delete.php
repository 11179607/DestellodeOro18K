<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/functions.php';

authenticateUser();
isAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $productId = isset($_GET['id']) ? sanitizeInput($_GET['id']) : '';
    
    if (empty($productId)) {
        sendResponse('error', [], 'ID de producto requerido');
    }
    
    try {
        // Verificar si el producto tiene ventas asociadas
        $checkQuery = "SELECT COUNT(*) as count FROM sale_items WHERE product_id = :product_id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':product_id', $productId);
        $checkStmt->execute();
        $result = $checkStmt->fetch();
        
        if ($result['count'] > 0) {
            sendResponse('error', [], 'No se puede eliminar el producto porque tiene ventas asociadas');
        }
        
        // Eliminar producto
        $query = "DELETE FROM products WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $productId);
        
        if ($stmt->execute()) {
            // Registrar actividad
            logActivity($db, $_SESSION['user_id'], 'delete_product', "Producto eliminado: $productId");
            
            sendResponse('success', [], 'Producto eliminado exitosamente');
        } else {
            sendResponse('error', [], 'Error al eliminar producto');
        }
        
    } catch (PDOException $e) {
        error_log("Error al eliminar producto: " . $e->getMessage());
        sendResponse('error', [], 'Error al eliminar producto');
    }
} else {
    sendResponse('error', [], 'Método no permitido');
}
?>