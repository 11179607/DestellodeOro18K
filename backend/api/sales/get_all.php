<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/functions.php';

authenticateUser();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'all';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        
        $query = "SELECT s.*, c.name as customer_name, c.phone as customer_phone, 
                         u.name as user_name
                  FROM sales s
                  LEFT JOIN customers c ON s.customer_id = c.id
                  LEFT JOIN users u ON s.user_id = u.id
                  WHERE 1=1";
        
        $params = [];
        
        if ($status !== 'all') {
            $query .= " AND s.status = :status";
            $params[':status'] = $status;
        }
        
        $query .= " ORDER BY s.sale_date DESC LIMIT :limit";
        
        $stmt = $db->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        
        $stmt->execute();
        $sales = $stmt->fetchAll();
        
        // Obtener items para cada venta
        foreach ($sales as &$sale) {
            $itemsQuery = "SELECT si.*, p.name as product_name 
                          FROM sale_items si
                          JOIN products p ON si.product_id = p.id
                          WHERE si.sale_id = :sale_id";
            
            $itemsStmt = $db->prepare($itemsQuery);
            $itemsStmt->bindParam(':sale_id', $sale['id']);
            $itemsStmt->execute();
            $sale['items'] = $itemsStmt->fetchAll();
            
            // Formatear campos
            $sale['subtotal_formatted'] = formatCurrency($sale['subtotal']);
            $sale['discount_formatted'] = formatCurrency($sale['discount']);
            $sale['delivery_cost_formatted'] = formatCurrency($sale['delivery_cost']);
            $sale['total_formatted'] = formatCurrency($sale['total']);
            $sale['sale_date_formatted'] = date('d/m/Y H:i', strtotime($sale['sale_date']));
        }
        
        sendResponse('success', $sales);
        
    } catch (PDOException $e) {
        error_log("Error al obtener ventas: " . $e->getMessage());
        sendResponse('error', [], 'Error al obtener ventas');
    }
} else {
    sendResponse('error', [], 'Método no permitido');
}
?>