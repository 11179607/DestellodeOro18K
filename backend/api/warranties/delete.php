<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/functions.php';

authenticateUser();
isAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $warrantyId = isset($_GET['id']) ? sanitizeInput($_GET['id']) : '';
    
    if (empty($warrantyId)) {
        sendResponse('error', [], 'ID de garantía requerido');
    }
    
    try {
        // Verificar si existe la garantía
        $query = "SELECT id FROM warranties WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $warrantyId);
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            sendResponse('error', [], 'Garantía no encontrada');
        }
        
        // Eliminar garantía
        $query = "DELETE FROM warranties WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $warrantyId);
        
        if ($stmt->execute()) {
            // Registrar actividad
            logActivity($db, $_SESSION['user_id'], 'delete_warranty', "Garantía eliminada: $warrantyId");
            
            sendResponse('success', [], 'Garantía eliminada exitosamente');
        } else {
            sendResponse('error', [], 'Error al eliminar garantía');
        }
        
    } catch (PDOException $e) {
        error_log("Error al eliminar garantía: " . $e->getMessage());
        sendResponse('error', [], 'Error al eliminar garantía');
    }
} else {
    sendResponse('error', [], 'Método no permitido');
}
?>