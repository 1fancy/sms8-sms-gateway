<?php
/**
 * SMS8 — complete OTP / phone-verification flow.
 *
 * Two endpoints on your server:
 *   POST /signup/start  { phone } → triggers SMS code
 *   POST /signup/verify { phone, code } → confirms and logs in
 *
 * Uses the Sms8Sender class from php-send.php.
 */

require_once __DIR__ . '/php-send.php';

$sms = new Sms8Sender(getenv('SMS8_API_KEY'));

// ── POST /signup/start ────────────────────────────────────────────────────
function startSignup(Sms8Sender $sms, string $phone): array {
    // Validation: E.164 only, reject obviously-bad input
    if (!preg_match('/^\+[1-9]\d{6,14}$/', $phone)) {
        http_response_code(400);
        return ['error' => 'Use international format, e.g. +1234567890'];
    }
    try {
        $res = $sms->sendOtp($phone, length: 6, expiresIn: 300);
        // Tip: track $res['otp_id'] in your session if you want to throttle
        return ['sent' => true, 'expires_in' => $res['expires_in'] ?? 300];
    } catch (Throwable $e) {
        http_response_code(500);
        return ['error' => $e->getMessage()];
    }
}

// ── POST /signup/verify ───────────────────────────────────────────────────
function verifySignup(Sms8Sender $sms, string $phone, string $code): array {
    $verified = $sms->verifyOtp($phone, $code);
    if (!$verified) {
        http_response_code(400);
        return ['verified' => false, 'error' => 'Invalid or expired code'];
    }
    // ✅ Phone proven — create / log in the user in YOUR database here.
    //    Tip: store the verified flag against the user row so they don't
    //    have to re-verify on every login.
    return ['verified' => true];
}

// Example wiring (replace with your framework's router)
$input = json_decode(file_get_contents('php://input'), true) ?: [];
switch ($_SERVER['REQUEST_URI'] ?? '') {
    case '/signup/start':
        echo json_encode(startSignup($sms, $input['phone'] ?? ''));
        break;
    case '/signup/verify':
        echo json_encode(verifySignup($sms, $input['phone'] ?? '', $input['code'] ?? ''));
        break;
}
