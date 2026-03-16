<?php
// api/csrf.php
// Pequeño helper para proteger endpoints contra CSRF usando double-submit cookie.
// Genera un token por sesión y lo sincroniza en la cookie XSRF-TOKEN (SameSite=Lax).

function ensureCsrfToken(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    // Mantener cookie y sesión alineadas
    $cookie = $_COOKIE['XSRF-TOKEN'] ?? null;
    if (!$cookie || !hash_equals($_SESSION['csrf'], $cookie)) {
        setcookie(
            'XSRF-TOKEN',
            $_SESSION['csrf'],
            [
                'httponly' => false,          // JS la leerá para reenviarla en el header
                'samesite' => 'Lax',
                'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'path'     => '/'
            ]
        );
    }
}

function requireCsrf(): void {
    ensureCsrfToken();
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf'] ?? ($_GET['csrf'] ?? ''));
    if (!$token || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'CSRF token inválido']);
        exit;
    }
}
