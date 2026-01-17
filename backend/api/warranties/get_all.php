<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/functions.php';

authenticateUser();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        
        $query = "SELECT w.*, 
                         p1.name as original_product_name,
                         p2.name as replacement_product_name,
                         u.name as user_name
                  FROM warranties w
                  LEFT JOIN products p1 ON w.original_product_id = p1.id
                  LEFT JOIN products p2 ON w.replacement_product_id = p2.id
                  LEFT JOIN users u ON w.user_id = u.id
                  ORDER BY w.warranty_date DESC 
                  LIMIT :limit";
        
        $stmt = $db->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $warranties = $stmt->fetchAll();
        
        // Formatear campos
        foreach ($warranties as &$warranty) {
            $warranty['shipping_cost_formatted'] = formatCurrency($warranty['shipping_cost']);
            $warranty['price_difference_formatted'] = formatCurrency($warranty['price_difference']);
            $warranty['total_formatted'] = formatCurrency(
                $warranty['shipping_cost'] + $warranty['price_difference']
            );
            $warranty['warranty_date_formatted'] = date('d/m/Y H:i', strtotime($warranty['warranty_date']));
            
            // Determinar producto nuevo
            if ($warranty['replacement_type'] === 'different' && $warranty['replacement_product_name']) {
                $warranty['new_product'] = $warranty['replacement_product_name'];
            } else {
                $warranty['new_product'] = $warranty['original_product_name'];
            }
        }
        
        sendResponse('success', $warranties);
        
    } catch (PDOException $e) {
        error_log("Error al obtener garantías: " . $e->getMessage());
        sendResponse('error', [], 'Error al obtener garantías');
    }
} else {
    sendResponse('error', [], 'Método no permitido');
}
?>