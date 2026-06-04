<?php
/**
 * Ecrece CRM Webhook Receiver
 * Recibe eventos de Meta (WhatsApp Business) y Calendly en tiempo real y los almacena localmente
 * para que el CRM del navegador los lea e incorpore automáticamente.
 */

// Cabeceras de seguridad y CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Manejo de peticiones pre-vuelo OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$events_file = 'webhook_events.json';
$verify_token = 'ecrece_verify_token_2026'; // Token para validar la conexión en Meta Developer Portal

// 1. VERIFICACIÓN DEL WEBHOOK (Meta solicita GET con challenge)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';

    if ($mode === 'subscribe' && $token === $verify_token) {
        http_response_code(200);
        echo $challenge;
        exit;
    } else {
        http_response_code(403);
        echo json_encode(["error" => "Token de verificación incorrecto"]);
        exit;
    }
}

// 2. RECEPCIÓN DE DATOS (POST de Meta o Calendly)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_payload = file_get_contents('php://input');
    $payload = json_decode($raw_payload, true);

    if ($payload) {
        // Cargar eventos existentes
        $existing_events = [];
        if (file_exists($events_file)) {
            $existing_events = json_decode(file_get_contents($events_file), true) ?: [];
        }

        // Estructurar el nuevo evento
        $new_event = [
            "id" => uniqid("event_", true),
            "timestamp" => date("Y-m-d H:i:s"),
            "data" => $payload
        ];

        // Añadir al inicio y limitar el historial a los últimos 150 eventos
        array_unshift($existing_events, $new_event);
        if (count($existing_events) > 150) {
            array_pop($existing_events);
        }

        // Guardar archivo JSON localmente
        if (file_put_contents($events_file, json_encode($existing_events, JSON_PRETTY_PRINT))) {
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Evento guardado correctamente"]);
            exit;
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "No se pudo guardar el evento en el disco"]);
            exit;
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Payload JSON inválido"]);
        exit;
    }
}

// Responder por defecto si no es GET ni POST
http_response_code(405);
echo json_encode(["error" => "Método no permitido"]);
exit;
