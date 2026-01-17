<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/functions.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar datos
    $required = ['admin_username', 'admin_password', 'user_to_reset', 'method'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            sendResponse('error', [], "El campo $field es requerido");
        }
    }
    
    $adminUsername = sanitizeInput($data['admin_username']);
    $adminPassword = sanitizeInput($data['admin_password']);
    $userToReset = sanitizeInput($data['user_to_reset']);
    $method = sanitizeInput($data['method']);
    
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
        
        // Buscar usuario a restablecer
        $query = "SELECT id, username, name, last_name, role FROM users 
                  WHERE username = :username AND is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $userToReset);
        $stmt->execute();
        
        $user = $stmt->fetch();
        
        if (!$user) {
            sendResponse('error', [], 'Usuario no encontrado');
        }
        
        // Generar nueva contraseña aleatoria
        $newPassword = generateRandomPassword(8);
        $hashedPassword = hashPassword($newPassword);
        
        // Actualizar contraseña
        $updateQuery = "UPDATE users SET password = :password, updated_at = NOW() 
                       WHERE id = :id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':password', $hashedPassword);
        $updateStmt->bindParam(':id', $user['id']);
        
        if (!$updateStmt->execute()) {
            sendResponse('error', [], 'Error al restablecer contraseña');
        }
        
        // Registrar actividad
        logActivity($db, $admin['id'], 'reset_password', 
                   "Restableció contraseña de usuario: {$user['username']}");
        
        // Preparar respuesta según método
        if ($method === 'direct') {
            sendResponse('success', [
                'new_password' => $newPassword
            ], 'Nueva contraseña generada');
        } else {
            sendResponse('success', [], 'Contraseña restablecida. Se enviará por WhatsApp.');
        }
        
    } catch (PDOException $e) {
        error_log("Error al restablecer contraseña: " . $e->getMessage());
        sendResponse('error', [], 'Error al restablecer contraseña');
    }
} else {
    sendResponse('error', [], 'Método no permitido');
}

function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}
?>