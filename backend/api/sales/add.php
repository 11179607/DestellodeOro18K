<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/functions.php';

authenticateUser();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar datos requeridos
    if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
        sendResponse('error', [], 'Debe agregar al menos un producto a la venta');
    }
    
    if (!isset($data['customer_info']) || !isset($data['total'])) {
        sendResponse('error', [], 'Datos de cliente y total son requeridos');
    }
    
    try {
        $db->beginTransaction();
        
        $userId = $_SESSION['user_id'];
        $customerInfo = $data['customer_info'];
        $saleType = $data['sale_type'] ?? 'retail';
        $paymentMethod = $data['payment_method'] ?? 'cash';
        $deliveryType = $data['delivery_type'] ?? 'store';
        $deliveryCost = $data['delivery_cost'] ?? 0;
        $discount = $data['discount'] ?? 0;
        $subtotal = $data['subtotal'] ?? $data['total'];
        $total = $data['total'];
        
        // Guardar cliente si no existe
        $customerId = null;
        if (!empty($customerInfo['identification'])) {
            $query = "SELECT id FROM customers WHERE identification = :identification";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':identification', $customerInfo['identification']);
            $stmt->execute();
            
            $existingCustomer = $stmt->fetch();
            
            if ($existingCustomer) {
                $customerId = $existingCustomer['id'];
            } else {
                // Insertar nuevo cliente
                $query = "INSERT INTO customers (name, identification, phone, email, address, city) 
                         VALUES (:name, :identification, :phone, :email, :address, :city)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':name', $customerInfo['name']);
                $stmt->bindParam(':identification', $customerInfo['identification']);
                $stmt->bindParam(':phone', $customerInfo['phone']);
                $stmt->bindParam(':email', $customerInfo['email']);
                $stmt->bindParam(':address', $customerInfo['address']);
                $stmt->bindParam(':city', $customerInfo['city']);
                $stmt->execute();
                
                $customerId = $db->lastInsertId();
            }
        }
        
        // Generar ID de factura
        $invoiceId = generateInvoiceId($db);
        
        // Determinar estado según método de pago
        $status = ($paymentMethod === 'cash') ? 'completed' : 'pending';
        $confirmed = ($paymentMethod === 'cash') ? 1 : 0;
        
        // Insertar venta
        $query = "INSERT INTO sales (id, customer_id, subtotal, discount, delivery_cost, total, 
                  sale_type, payment_method, delivery_type, status, confirmed, user_id) 
                  VALUES (:id, :customer_id, :subtotal, :discount, :delivery_cost, :total, 
                  :sale_type, :payment_method, :delivery_type, :status, :confirmed, :user_id)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $invoiceId);
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->bindParam(':subtotal', $subtotal);
        $stmt->bindParam(':discount', $discount);
        $stmt->bindParam(':delivery_cost', $deliveryCost);
        $stmt->bindParam(':total', $total);
        $stmt->bindParam(':sale_type', $saleType);
        $stmt->bindParam(':payment_method', $paymentMethod);
        $stmt->bindParam(':delivery_type', $deliveryType);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':confirmed', $confirmed);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        // Insertar items de venta y actualizar inventario
        foreach ($data['items'] as $item) {
            // Insertar item
            $query = "INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, sale_type, discount) 
                     VALUES (:sale_id, :product_id, :quantity, :unit_price, :sale_type, :discount)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':sale_id', $invoiceId);
            $stmt->bindParam(':product_id', $item['product_id']);
            $stmt->bindParam(':quantity', $item['quantity']);
            $stmt->bindParam(':unit_price', $item['unit_price']);
            $stmt->bindParam(':sale_type', $item['sale_type']);
            $stmt->bindParam(':discount', $item['discount'] ?? 0);
            $stmt->execute();
            
            // Actualizar inventario (solo si la venta está confirmada)
            if ($status === 'completed') {
                $query = "UPDATE products SET quantity = quantity - :quantity 
                         WHERE id = :product_id AND quantity >= :quantity";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':product_id', $item['product_id']);
                $stmt->bindParam(':quantity', $item['quantity']);
                $stmt->execute();
                
                if ($stmt->rowCount() === 0) {
                    throw new Exception("Stock insuficiente para el producto: {$item['product_id']}");
                }
            }
        }
        
        $db->commit();
        
        // Registrar actividad
        logActivity($db, $userId, 'add_sale', "Venta registrada: $invoiceId - Total: $total");
        
        sendResponse('success', [
            'invoice_id' => $invoiceId,
            'status' => $status,
            'message' => $status === 'completed' ? 
                'Venta procesada exitosamente' : 
                'Venta registrada como pendiente de pago'
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error al registrar venta: " . $e->getMessage());
        sendResponse('error', [], 'Error al registrar venta: ' . $e->getMessage());
    }
} else {
    sendResponse('error', [], 'Método no permitido');
}
?>