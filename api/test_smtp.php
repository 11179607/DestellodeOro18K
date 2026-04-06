<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Intentar cargar PHPMailer - Probá con la ruta correcta
$rutas = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/PHPMailer/src/PHPMailer.php',
    __DIR__ . '/../PHPMailer/src/PHPMailer.php'
];

$cargado = false;
foreach ($rutas as $ruta) {
    if (file_exists($ruta)) {
        if (strpos($ruta, 'autoload.php') !== false) {
            require_once $ruta;
            $cargado = true;
            echo "✅ Cargado: Composer autoload<br>";
            break;
        } else {
            require_once dirname($ruta) . '/PHPMailer.php';
            require_once dirname($ruta) . '/SMTP.php';
            require_once dirname($ruta) . '/Exception.php';
            $cargado = true;
            echo "✅ Cargado: PHPMailer manual desde " . htmlspecialchars($ruta) . "<br>";
            break;
        }
    }
}

if (!$cargado) {
    die("❌ No se pudo cargar PHPMailer. Verificá la ruta.");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Tus datos SMTP (cambialos)
$smtp_host = 'smtp.hostinger.com';
$smtp_port = 587;
$smtp_user = 'destellodeorojoyeria@sistemadegestion18k.com';
$smtp_pass = 'LA_CONTRASEÑA_QUE_CREASTE'; // CAMBIALA
$smtp_from = 'destellodeorojoyeria@sistemadegestion18k.com';
$smtp_from_name = 'Sistema Destello de Oro';
$test_email = 'tu-email@gmail.com'; // tu correo personal

echo "<h3>📧 Probando envío SMTP</h3>";
echo "Host: $smtp_host<br>";
echo "Puerto: $smtp_port<br>";
echo "Usuario: $smtp_user<br>";
echo "Destino: $test_email<br><br>";

$mail = new PHPMailer(true);

try {
    // Configuración SMTP
    $mail->isSMTP();
    $mail->Host       = $smtp_host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_user;
    $mail->Password   = $smtp_pass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $smtp_port;
    
    // Timeout más largo (por si acaso)
    $mail->Timeout = 30;

    $mail->setFrom($smtp_from, $smtp_from_name);
    $mail->addAddress($test_email);
    $mail->addReplyTo($smtp_from, 'No Responder');

    $mail->isHTML(true);
    $mail->Subject = 'Prueba SMTP Hostinger';
    $mail->Body    = '<h2>¡Funciona!</h2><p>Si ves esto, la configuración SMTP de Hostinger está correcta.</p>';
    $mail->AltBody = '¡Funciona! La configuración SMTP de Hostinger está correcta.';

    if ($mail->send()) {
        echo '✅ CORREO ENVIADO CORRECTAMENTE. Revisá tu bandeja (y spam).';
    } else {
        echo '❌ Error inesperado';
    }
} catch (Exception $e) {
    echo "❌ ERROR DE PHPMailer: " . $mail->ErrorInfo . "<br>";
    echo "Detalle: " . $e->getMessage();
} catch (Throwable $t) {
    echo "❌ ERROR GENERAL: " . $t->getMessage();
}
?>