<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/functions.php';

authenticateUser();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $query = "SELECT p.*, u.name as added_by_name 
                  FROM products p 
                  LEFT JOIN users u ON p.added_by = u.id 
                  ORDER BY p.date_added DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $products = $stmt->fetchAll();
        
        // Calcular ganancias para cada producto
        foreach ($products as &$product) {
            $product['profit'] = $product['retail_price'] - $product['purchase_price'];
            $product['profit_percentage'] = $product['purchase_price'] > 0 ? 
                round(($product['profit'] / $product['purchase_price']) * 100, 2) : 0;
            
            // Formatear precios
            $product['purchase_price_formatted'] = formatCurrency($product['purchase_price']);
            $product['wholesale_price_formatted'] = formatCurrency($product['wholesale_price']);
            $product['retail_price_formatted'] = formatCurrency($product['retail_price']);
            $product['profit_formatted'] = formatCurrency($product['profit']);
        }
        
        sendResponse('success', $products);
        
    } catch (PDOException $e) {
        error_log("Error al obtener productos: " . $e->getMessage());
        sendResponse('error', [], 'Error al obtener productos');
    }
} else {
    sendResponse('error', [], 'Método no permitido');
}
?>