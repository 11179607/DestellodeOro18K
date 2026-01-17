<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    sendResponse('error', [], 'No hay sesión activa');
}

$db = getDB();
$userId = $_SESSION['user_id'];

try {
    // Actualizar sesión en la base de datos
    if (isset($_SESSION['session_token'])) {
        $query = "UPDATE user_sessions 
                  SET logout_time = NOW(), is_active = FALSE 
                  WHERE user_id = :user_id AND session_token = :token";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':token', $_SESSION['session_token']);
        $stmt->execute();
    }
    
    // Registrar actividad
    logActivity($db, $userId, 'logout', 'Cierre de sesión');
    
    // Destruir sesión
    session_destroy();
    
    sendResponse('success', [], 'Sesión cerrada exitosamente');
    
} catch (PDOException $e) {
    error_log("Error al cerrar sesión: " . $e->getMessage());
    session_destroy();
    sendResponse('error', [], 'Error al cerrar sesión');
}
?>