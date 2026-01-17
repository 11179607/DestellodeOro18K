<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/functions.php';

authenticateUser();
isAdmin(); // Solo admin puede confirmar pagos
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['sale_id'])) {
        sendResponse('error', [], 'ID de venta requerido');
    }
    
    $saleId = sanitizeInput($data['sale_id']);
    $userId = $_SESSION['user_id'];
    
    try {
        $db->beginTransaction();
        
        // Obtener venta y sus items
        $query = "SELECT s.*, si.product_id, si.quantity 
                  FROM sales s
                  JOIN sale_items si ON s.id = si.sale_id
                  WHERE s.id = :sale_id AND s.status = 'pending'";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':sale_id', $saleId);
        $stmt->execute();
        
        $saleData = $stmt->fetchAll();
        
        if (empty($saleData)) {
            sendResponse('error', [], 'Venta no encontrada o ya confirmada');
        }
        
        // Actualizar inventario para cada item
        foreach ($saleData as $item) {
            $updateQuery = "UPDATE products 
                           SET quantity = quantity - :quantity 
                           WHERE id = :product_id AND quantity >= :quantity";
            
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':product_id', $item['product_id']);
            $updateStmt->bindParam(':quantity', $item['quantity']);
            $updateStmt->execute();
            
            if ($updateStmt->rowCount() === 0) {
                throw new Exception("Stock insuficiente para el producto: {$item['product_id']}");
            }
        }
        
        // Actualizar estado de la venta
        $updateSaleQuery = "UPDATE sales 
                           SET status = 'completed', confirmed = 1 
                           WHERE id = :sale_id";
        
        $updateSaleStmt = $db->prepare($updateSaleQuery);
        $updateSaleStmt->bindParam(':sale_id', $saleId);
        $updateSaleStmt->execute();
        
        $db->commit();
        
        // Registrar actividad
        logActivity($db, $userId, 'confirm_sale', "Pago confirmado para venta: $saleId");
        
        sendResponse('success', [], 'Pago confirmado y venta procesada exitosamente');
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error al confirmar pago: " . $e->getMessage());
        sendResponse('error', [], 'Error al confirmar pago: ' . $e->getMessage());
    }
} else {
    sendResponse('error', [], 'Método no permitido');
}
?>