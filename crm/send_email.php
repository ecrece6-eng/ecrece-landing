<?php
/**
 * Ecrece CRM Email Sender
 * Envía correos electrónicos reales utilizando la función nativa de correo de PHP en HostGator.
 * No requiere contraseñas de Gmail ni configuraciones complejas en el frontend.
 */

// Cabeceras de seguridad y CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Manejo de peticiones pre-vuelo OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_payload = file_get_contents('php://input');
    $payload = json_decode($raw_payload, true);

    $to = $payload['to'] ?? '';
    $subject = $payload['subject'] ?? '';
    $message_body = $payload['message'] ?? '';

    // Validar parámetros obligatorios
    if (!$to || !$subject || !$message_body) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Faltan parámetros requeridos: 'to', 'subject' o 'message'"]);
        exit;
    }

    // Filtrar/Validar formato de correo electrónico
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "El correo electrónico del destinatario no es válido"]);
        exit;
    }

    // Configuración del correo
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    
    // Obtener el dominio actual para construir la cabecera From de forma dinámica
    $host = $_SERVER['HTTP_HOST'] ?? 'ecrece.com';
    $headers .= "From: Ecrece IA <no-reply@" . $host . ">" . "\r\n";
    $headers .= "Reply-To: no-reply@" . $host . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    // Formatear el cuerpo del mensaje en HTML elegante
    $html_message = "
    <html>
    <head>
      <title>" . htmlspecialchars($subject) . "</title>
      <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f6f9fc; color: #333333; margin: 0; padding: 20px; }
        .container { max-width: 600px; background-color: #ffffff; padding: 30px; border-radius: 12px; margin: 0 auto; border: 1px solid #e3e8ee; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        .header { border-bottom: 2px solid #3b424c; padding-bottom: 15px; margin-bottom: 25px; text-align: center; }
        .logo { font-size: 24px; font-weight: 800; color: #1e252e; letter-spacing: 1px; }
        .content { font-size: 16px; line-height: 1.6; color: #4f566b; }
        .footer { font-size: 12px; color: #a5acb8; border-top: 1px solid #e3e8ee; padding-top: 15px; margin-top: 25px; text-align: center; }
        .btn { display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #1e252e 0%, #3b424c 100%); color: #ffffff !important; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 15px; }
      </style>
    </head>
    <body>
      <div class='container'>
        <div class='header'>
          <div class='logo'>ECRECE</div>
        </div>
        <div class='content'>
          " . nl2br(htmlspecialchars($message_body)) . "
        </div>
        <div class='footer'>
          Este correo fue enviado de forma automática por el asistente comercial inteligente de Ecrece.<br>
          &copy; " . date("Y") . " Ecrece. Todos los derechos reservados.
        </div>
      </div>
    </body>
    </html>
    ";

    // Enviar correo
    if (mail($to, $subject, $html_message, $headers)) {
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Correo electrónico enviado correctamente"]);
        exit;
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Error interno del servidor de correo de HostGator al procesar el envío"]);
        exit;
    }
}

// Denegar métodos no autorizados
http_response_code(405);
echo json_encode(["error" => "Método no permitido"]);
exit;
