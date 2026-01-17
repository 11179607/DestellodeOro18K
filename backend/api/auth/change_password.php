<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/functions.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar datos
    $required = ['admin_username', 'admin_password', 'user_to_change', 'new_password'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            sendResponse('error', [], "El campo $field es requerido");
        }
    }
    
    $adminUsername = sanitizeInput($data['admin_username']);
    $adminPassword = sanitizeInput($data['admin_password']);
    $userToChange = sanitizeInput($data['user_to_change']);
    $newPassword = sanitizeInput($data['new_password']);
    
    try {
        // Verificar credenciales de administrador
        $query = "SELECT id, password FROM users 
                  WHERE username = :username AND role = 'admin' AND is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $adminUsername);
        $stmt->execute();
        
        $admin = $stmt->fetch();
        
        if (!$admin || !verifyPassword($adminPassword, $admin['password'])) {
            sendResponse('error', [], 'Credenciales de administrador incorrectas');
        }
        
        // Buscar usuario a modificar
        $query = "SELECT id FROM users WHERE username = :username AND is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $userToChange);
        $stmt->execute();
        
        $user = $stmt->fetch();
        
        if (!$user) {
            sendResponse('error', [], 'Usuario no encontrado');
        }
        
        // Actualizar contraseña con hash
        $hashedPassword = hashPassword($newPassword);
        
        $updateQuery = "UPDATE users SET password = :password, updated_at = NOW() 
                       WHERE id = :id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':password', $hashedPassword);
        $updateStmt->bindParam(':id', $user['id']);
        
        if ($updateStmt->execute()) {
            // Registrar actividad
            logActivity($db, $admin['id'], 'change_password', 
                       "Cambió contraseña de usuario: $userToChange");
            
            sendResponse('success', [], 'Contraseña cambiada exitosamente');
        } else {
            sendResponse('error', [], 'Error al cambiar contraseña');
        }
        
    } catch (PDOException $e) {
        error_log("Error al cambiar contraseña: " . $e->getMessage());
        sendResponse('error', [], 'Error al cambiar contraseña');
    }
} else {
    sendResponse('error', [], 'Método no permitido');
}
?>