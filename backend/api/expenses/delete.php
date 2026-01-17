<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/functions.php';

authenticateUser();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $expenseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($expenseId <= 0) {
        sendResponse('error', [], 'ID de gasto inválido');
    }
    
    // Solo admin puede eliminar gastos, o el usuario que lo creó
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['user_role'];
    
    try {
        // Verificar permisos
        $query = "SELECT user_id FROM expenses WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $expenseId);
        $stmt->execute();
        
        $expense = $stmt->fetch();
        
        if (!$expense) {
            sendResponse('error', [], 'Gasto no encontrado');
        }
        
        if ($userRole !== 'admin' && $expense['user_id'] != $userId) {
            sendResponse('error', [], 'No tiene permisos para eliminar este gasto');
        }
        
        // Eliminar gasto
        $query = "DELETE FROM expenses WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $expenseId);
        
        if ($stmt->execute()) {
            // Registrar actividad
            logActivity($db, $userId, 'delete_expense', "Gasto eliminado: ID $expenseId");
            
            sendResponse('success', [], 'Gasto eliminado exitosamente');
        } else {
            sendResponse('error', [], 'Error al eliminar gasto');
        }
        
    } catch (PDOException $e) {
        error_log("Error al eliminar gasto: " . $e->getMessage());
        sendResponse('error', [], 'Error al eliminar gasto');
    }
} else {
    sendResponse('error', [], 'Método no permitido');
}
?>