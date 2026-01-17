<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/functions.php';

authenticateUser();
isAdmin(); // Solo admin puede registrar garantías
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar datos requeridos
    $required = [
        'customer_name', 'customer_phone', 'customer_identification',
        'original_product_id', 'warranty_reason', 'warranty_description',
        'replacement_type'
    ];
    
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            sendResponse('error', [], "El campo $field es requerido");
        }
    }
    
    try {
        $db->beginTransaction();
        
        $userId = $_SESSION['user_id'];
        
        // Verificar producto original
        $query = "SELECT id, name FROM products WHERE id = :product_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':product_id', $data['original_product_id']);
        $stmt->execute();
        
        $originalProduct = $stmt->fetch();
        
        if (!$originalProduct) {
            sendResponse('error', [], 'Producto original no encontrado');
        }
        
        // Verificar producto de reemplazo si es diferente
        $replacementProductId = null;
        if ($data['replacement_type'] === 'different' && !empty($data['replacement_product_id'])) {
            $query = "SELECT id, name FROM products WHERE id = :product_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':product_id', $data['replacement_product_id']);
            $stmt->execute();
            
            $replacementProduct = $stmt->fetch();
            
            if (!$replacementProduct) {
                sendResponse('error', [], 'Producto de reemplazo no encontrado');
            }
            
            $replacementProductId = $data['replacement_product_id'];
        }
        
        // Generar ID de garantía
        $warrantyId = generateWarrantyId($db);
        
        // Insertar garantía
        $query = "INSERT INTO warranties (id, customer_name, customer_phone, customer_identification,
                  original_product_id, warranty_reason, warranty_description, replacement_type,
                  replacement_product_id, price_difference, shipping_cost, shipping_paid_by, user_id)
                  VALUES (:id, :customer_name, :customer_phone, :customer_identification,
                  :original_product_id, :warranty_reason, :warranty_description, :replacement_type,
                  :replacement_product_id, :price_difference, :shipping_cost, :shipping_paid_by, :user_id)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $warrantyId);
        $stmt->bindParam(':customer_name', $data['customer_name']);
        $stmt->bindParam(':customer_phone', $data['customer_phone']);
        $stmt->bindParam(':customer_identification', $data['customer_identification']);
        $stmt->bindParam(':original_product_id', $data['original_product_id']);
        $stmt->bindParam(':warranty_reason', $data['warranty_reason']);
        $stmt->bindParam(':warranty_description', $data['warranty_description']);
        $stmt->bindParam(':replacement_type', $data['replacement_type']);
        $stmt->bindParam(':replacement_product_id', $replacementProductId);
        $stmt->bindParam(':price_difference', $data['price_difference'] ?? 0);
        $stmt->bindParam(':shipping_cost', $data['shipping_cost'] ?? 0);
        $stmt->bindParam(':shipping_paid_by', $data['shipping_paid_by'] ?? 'admin');
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        // Registrar gasto de envío si es asumido por el admin
        $shippingCost = $data['shipping_cost'] ?? 0;
        $shippingPaidBy = $data['shipping_paid_by'] ?? 'admin';
        
        if ($shippingCost > 0 && ($shippingPaidBy === 'admin' || $shippingPaidBy === 'shared')) {
            $adminCost = ($shippingPaidBy === 'admin') ? $shippingCost : $shippingCost / 2;
            
            $expenseQuery = "INSERT INTO expenses (description, expense_date, amount, category, user_id)
                            VALUES (:description, CURDATE(), :amount, 'warranty', :user_id)";
            
            $expenseStmt = $db->prepare($expenseQuery);
            $description = "Envío garantía - {$data['customer_name']} - {$originalProduct['name']}";
            $expenseStmt->bindParam(':description', $description);
            $expenseStmt->bindParam(':amount', $adminCost);
            $expenseStmt->bindParam(':user_id', $userId);
            $expenseStmt->execute();
        }
        
        $db->commit();
        
        // Registrar actividad
        logActivity($db, $userId, 'add_warranty', "Garantía registrada: $warrantyId");
        
        sendResponse('success', ['warranty_id' => $warrantyId], 'Garantía registrada exitosamente');
        
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Error al registrar garantía: " . $e->getMessage());
        sendResponse('error', [], 'Error al registrar garantía');
    }
} else {
    sendResponse('error', [], 'Método no permitido');
}
?>