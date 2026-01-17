<?php
session_start();
header('Content-Type: application/json');

require_once '../../includes/functions.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['username']) || !isset($data['password']) || !isset($data['role'])) {
        sendResponse('error', [], 'Credenciales incompletas');
    }
    
    $username = sanitizeInput($data['username']);
    $password = sanitizeInput($data['password']);
    $role = sanitizeInput($data['role']);
    
    // Buscar usuario
    $query = "SELECT id, username, password, role, name, last_name, phone, email, is_active 
              FROM users 
              WHERE username = :username AND role = :role AND is_active = 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':role', $role);
    $stmt->execute();
    
    $user = $stmt->fetch();
    
    if ($user && verifyPassword($password, $user['password'])) {
        // Crear sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'] . ($user['last_name'] ? ' ' . $user['last_name'] : '');
        
        // Actualizar último login
        $updateQuery = "UPDATE users SET last_login = NOW() WHERE id = :id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':id', $user['id']);
        $updateStmt->execute();
        
        // Registrar sesión
        $token = bin2hex(random_bytes(32));
        $sessionQuery = "INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent) 
                        VALUES (:user_id, :token, :ip_address, :user_agent)";
        $sessionStmt = $db->prepare($sessionQuery);
        $sessionStmt->bindParam(':user_id', $user['id']);
        $sessionStmt->bindParam(':token', $token);
        $sessionStmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);
        $sessionStmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT']);
        $sessionStmt->execute();
        
        $_SESSION['session_token'] = $token;
        
        // Registrar actividad
        logActivity($db, $user['id'], 'login', 'Inicio de sesión exitoso');
        
        sendResponse('success', [
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'name' => $user['name'] . ($user['last_name'] ? ' ' . $user['last_name'] : ''),
                'phone' => $user['phone']
            ]
        ], 'Login exitoso');
    } else {
        sendResponse('error', [], 'Credenciales incorrectas');
    }
} else {
    sendResponse('error', [], 'Método no permitido');
}
?>