<?php
/**
 * SMS8 — webhook handler.
 *
 * Drop this file at the URL you registered with create_webhook (or via
 * https://app.sms8.io/webhook.php). SMS8 will POST a JSON body on every
 * inbound SMS and on delivery / failure events.
 *
 * Sample payload (inbound SMS):
 *   {
 *     "event": "message.received",
 *     "data": {
 *       "id": 1234,
 *       "from": "+15550142",
 *       "message": "Yes, that works",
 *       "device_id": 42,
 *       "received_at": "2026-05-23T14:30:00Z"
 *     }
 *   }
 *
 * Other events you may see: message.sent, message.delivered, message.failed
 */

header('Content-Type: application/json');

// Read raw body (NOT $_POST — SMS8 sends application/json)
$raw     = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'bad payload']);
    exit;
}

// ── Optional: verify signature ─────────────────────────────────────────────
// SMS8 sends an X-SMS8-Signature header (HMAC-SHA256 of the raw body using
// your webhook secret). Get yours from app.sms8.io/webhook.php.
$secret = getenv('SMS8_WEBHOOK_SECRET') ?: '';
if ($secret !== '') {
    $expected = hash_hmac('sha256', $raw, $secret);
    $got      = $_SERVER['HTTP_X_SMS8_SIGNATURE'] ?? '';
    if (!hash_equals($expected, $got)) {
        http_response_code(403);
        echo json_encode(['error' => 'signature mismatch']);
        exit;
    }
}

// ── Dispatch ───────────────────────────────────────────────────────────────
$event = $payload['event'] ?? '';
$data  = $payload['data']  ?? [];

switch ($event) {
    case 'message.received':
        // Reply to a STOP keyword automatically? Insert into your inbox?
        handleInbound($data['from'], $data['message'], (int)$data['id']);
        break;

    case 'message.delivered':
        markDelivered((int)$data['id']);
        break;

    case 'message.failed':
        markFailed((int)$data['id'], $data['error'] ?? 'unknown');
        break;
}

// Acknowledge fast — SMS8 retries if you respond slowly or non-2xx
echo json_encode(['ok' => true]);

// ── Your business logic ────────────────────────────────────────────────────
function handleInbound(string $from, string $message, int $id): void {
    // TODO: insert into your DB, trigger an auto-reply, ping Slack, …
    error_log("SMS8 inbound from $from: $message");
}
function markDelivered(int $id): void { /* update your DB */ }
function markFailed(int $id, string $err): void { /* update your DB */ }
