<?php
/**
 * send_otp — generate and send a one-time code for phone-number verification.
 *
 * Re-uses the OTP table + send pipeline in ajax/otp-send.php so behaviour
 * stays identical whether the developer calls the MCP tool or the raw HTTP
 * endpoint.
 *
 * Defaults are tuned for safe production use:
 *   length: 6, expires_in: 300s (5 min), max_attempts: 5, resend cooldown: 60s
 */
ToolRegistry::register([
    'name' => 'send_otp',
    'description' => 'Send a one-time verification code to a phone number. Stores the code server-side; verify later with verify_otp. 5-minute expiry, 5 attempts, 60-second resend cooldown by default. Hard per-phone cap of 5 OTPs per 24h.',
    'inputSchema' => [
        'type' => 'object',
        'required' => ['phone'],
        'properties' => [
            'phone'        => ['type' => 'string', 'description' => 'E.164 phone, e.g. +1234567890'],
            'length'       => ['type' => 'integer', 'description' => 'Digits in the code (4-8). Default 6.'],
            'template'     => ['type' => 'string', 'description' => 'SMS body with `{code}` placeholder. Default: "Your verification code is {code}".'],
            'expires_in'   => ['type' => 'integer', 'description' => 'Seconds until expiry (60-900). Default 300.'],
            'max_attempts' => ['type' => 'integer', 'description' => 'Verification attempts allowed (1-10). Default 5.'],
        ],
    ],
    'handler' => function(array $args, User $user): array {
        // Forward to the real endpoint via an internal HTTP call so we keep
        // a single source of truth for OTP logic (rate-limit, cleanup, …).
        return _sms8_internal_post('/ajax/otp-send.php', array_merge($args, [
            'api_key' => $user->getApiKey(),
        ]));
    },
]);

/**
 * verify_otp — confirm a code the user typed.
 */
ToolRegistry::register([
    'name' => 'verify_otp',
    'description' => 'Verify a code the user typed against the most recent OTP issued for that phone. Returns { verified: true|false, reason }.',
    'inputSchema' => [
        'type' => 'object',
        'required' => ['phone', 'code'],
        'properties' => [
            'phone' => ['type' => 'string', 'description' => 'E.164 phone the OTP was sent to.'],
            'code'  => ['type' => 'string', 'description' => 'The code the user typed.'],
        ],
    ],
    'handler' => function(array $args, User $user): array {
        return _sms8_internal_post('/ajax/otp-verify.php', array_merge($args, [
            'api_key' => $user->getApiKey(),
        ]));
    },
]);

/**
 * Internal POST to a relative path on the main app. Uses curl loopback over
 * https so we stay in-process-safe (no shared globals between tool calls).
 */
function _sms8_internal_post(string $path, array $form): array
{
    $url = 'https://app.sms8.io' . $path;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($form),
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    $body = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($body === false) return ['error' => 'Network: ' . $err];
    $j = json_decode($body, true);
    return is_array($j) ? $j : ['error' => 'Bad JSON from ' . $path, 'raw' => $body];
}
