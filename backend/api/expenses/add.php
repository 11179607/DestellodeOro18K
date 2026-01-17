<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/functions.php';

authenticateUser();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar datos requeridos
    $required = ['description', 'expense_date', 'amount', 'category'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            sendResponse('error', [], "El campo $field es requerido");
        }
    }
    
    $description = sanitizeInput($data['description']);
    $expenseDate = sanitizeInput($data['expense_date']);
    $amount = (float)$data['amount'];
    $category = sanitizeInput($data['category']);
    $userId = $_SESSION['user_id'];
    
    try {
        $query = "INSERT INTO expenses (description, expense_date, amount, category, user_id)
                  VALUES (:description, :expense_date, :amount, :category, :user_id)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':expense_date', $expenseDate);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':user_id', $userId);
        
        if ($stmt->execute()) {
            $expenseId = $db->lastInsertId();
            
            // Registrar actividad
            logActivity($db, $userId, 'add_expense', "Gasto registrado: $description - $amount");
            
            sendResponse('success', ['id' => $expenseId], 'Gasto registrado exitosamente');
        } else {
            sendResponse('error', [], 'Error al registrar gasto');
        }
        
    } catch (PDOException $e) {
        error_log("Error al registrar gasto: " . $e->getMessage());
        sendResponse('error', [], 'Error al registrar gasto');
    }
} else {
    sendResponse('error', [], 'Método no permitido');
}
?>