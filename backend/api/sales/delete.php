<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/functions.php';

authenticateUser();
isAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $saleId = isset($_GET['id']) ? sanitizeInput($_GET['id']) : '';
    
    if (empty($saleId)) {
        sendResponse('error', [], 'ID de venta requerido');
    }
    
    try {
        // Verificar que la venta esté pendiente
        $query = "SELECT status FROM sales WHERE id = :sale_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':sale_id', $saleId);
        $stmt->execute();
        
        $sale = $stmt->fetch();
        
        if (!$sale) {
            sendResponse('error', [], 'Venta no encontrada');
        }
        
        if ($sale['status'] !== 'pending') {
            sendResponse('error', [], 'Solo se pueden eliminar ventas pendientes');
        }
        
        // Eliminar venta (se eliminarán automáticamente los items por CASCADE)
        $deleteQuery = "DELETE FROM sales WHERE id = :sale_id";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->bindParam(':sale_id', $saleId);
        
        if ($deleteStmt->execute()) {
            // Registrar actividad
            logActivity($db, $_SESSION['user_id'], 'delete_sale', "Venta eliminada: $saleId");
            
            sendResponse('success', [], 'Venta eliminada exitosamente');
        } else {
            sendResponse('error', [], 'Error al eliminar venta');
        }
        
    } catch (PDOException $e) {
        error_log("Error al eliminar venta: " . $e->getMessage());
        sendResponse('error', [], 'Error al eliminar venta');
    }
} else {
    sendResponse('error', [], 'Método no permitido');
}
?>