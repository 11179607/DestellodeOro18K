<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/functions.php';

authenticateUser();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $saleId = isset($_GET['id']) ? sanitizeInput($_GET['id']) : '';
    
    if (empty($saleId)) {
        sendResponse('error', [], 'ID de venta requerido');
    }
    
    try {
        // Obtener datos de la venta
        $query = "SELECT s.*, c.name as customer_name, c.identification as customer_id, 
                         c.phone as customer_phone, c.email as customer_email,
                         c.address as customer_address, c.city as customer_city,
                         u.name as user_name
                  FROM sales s
                  LEFT JOIN customers c ON s.customer_id = c.id
                  LEFT JOIN users u ON s.user_id = u.id
                  WHERE s.id = :sale_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':sale_id', $saleId);
        $stmt->execute();
        
        $sale = $stmt->fetch();
        
        if (!$sale) {
            sendResponse('error', [], 'Venta no encontrada');
        }
        
        // Obtener items de la venta
        $itemsQuery = "SELECT si.*, p.name as product_name 
                      FROM sale_items si
                      JOIN products p ON si.product_id = p.id
                      WHERE si.sale_id = :sale_id";
        
        $itemsStmt = $db->prepare($itemsQuery);
        $itemsStmt->bindParam(':sale_id', $saleId);
        $itemsStmt->execute();
        $sale['items'] = $itemsStmt->fetchAll();
        
        // Formatear campos
        $sale['subtotal_formatted'] = formatCurrency($sale['subtotal']);
        $sale['discount_formatted'] = formatCurrency($sale['discount']);
        $sale['delivery_cost_formatted'] = formatCurrency($sale['delivery_cost']);
        $sale['total_formatted'] = formatCurrency($sale['total']);
        $sale['sale_date_formatted'] = date('d/m/Y H:i', strtotime($sale['sale_date']));
        
        sendResponse('success', $sale);
        
    } catch (PDOException $e) {
        error_log("Error al obtener venta: " . $e->getMessage());
        sendResponse('error', [], 'Error al obtener venta');
    }
} else {
    sendResponse('error', [], 'Método no permitido');
}
?>