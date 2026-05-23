<?php
/**
 * setup_sms8 — onboarding handshake.
 *
 * Given an api_key, returns everything an AI coding assistant needs to
 * generate production-ready SMS8 integration code:
 *   - validated user identity + plan/credits snapshot
 *   - list of paired devices
 *   - canonical base URL + endpoint reference
 *   - inline code samples (PHP, JS, cURL, webhook)
 *   - quick action links into the SMS8 dashboard
 *
 * Designed so an AI calls this ONCE at the start of a coding session and
 * gets a self-contained block of context — no further documentation lookups
 * required for the common SMS / OTP / webhook use cases.
 */
ToolRegistry::register([
    'name' => 'setup_sms8',
    'description' => 'Start here. Validate the user\'s SMS8 API key and return everything needed to integrate SMS, OTP, and webhooks into their app — including code samples and dashboard links. Pass api_key once via this tool to set up the session.',
    'inputSchema' => [
        'type' => 'object',
        'properties' => [
            'api_key' => [
                'type' => 'string',
                'description' => 'The user\'s SMS8 API key (from app.sms8.io → Profile → API). Can also be passed via Authorization: Bearer header.',
            ],
        ],
    ],
    'handler' => function(array $args, ?User $user): array {
        // setup_sms8 is a public tool — it does its own auth so we can
        // return a helpful error pointing to where the key lives.
        $key = $args['api_key'] ?? $_REQUEST['api_key'] ?? null;
        $hdrs = function_exists('getallheaders') ? getallheaders() : [];
        foreach ($hdrs as $k => $v) {
            if (!$key && strtolower($k) === 'authorization' && stripos($v, 'bearer ') === 0) {
                $key = trim(substr($v, 7));
            }
        }
        if (!$key) {
            return [
                'success' => false,
                'error'   => 'No API key provided.',
                'how_to_get_one' => [
                    'step_1' => 'Sign in at https://app.sms8.io',
                    'step_2' => 'Profile → API → copy your key',
                    'no_account' => 'Sign up free at https://sms8.io (5-day trial, no credit card)',
                ],
            ];
        }

        $u = User::where('apiKey', trim($key))->read();
        if (!$u) {
            return ['success' => false, 'error' => 'Invalid API key. Verify it in https://app.sms8.io → Profile → API.'];
        }

        // Devices snapshot
        $devices = [];
        foreach ($u->getDevices() as $d) {
            $devices[] = [
                'id'      => $d->getID(),
                'model'   => $d->getModel(),
                'enabled' => (bool)$d->getEnabled(),
                'primary' => $u->getPrimaryDeviceID() == $d->getID(),
            ];
        }

        // Plan / credits / expiry
        $expiry = $u->getExpiryDate();
        $daysLeft = $expiry ? (int)ceil((strtotime($expiry) - time()) / 86400) : null;

        $base = 'https://app.sms8.io';

        // Never echo the full api_key back. AI tools often log entire tool
        // responses; the raw key would leak into Cursor/Claude history and any
        // shared transcripts. Return a fingerprint instead.
        $tail = substr($key, -4);
        $masked = str_repeat('•', max(0, strlen($key) - 4)) . $tail;

        return [
            'success' => true,
            'message' => 'SMS8 ready. Read the user\'s API key from their SMS8_API_KEY environment variable when generating code — do not paste it into source files or logs.',
            'account' => [
                'user_id'    => $u->getID(),
                'name'       => $u->getName(),
                'email'      => $u->getEmail(),
                'credits'    => $u->getCredits(),
                'expires_in' => $daysLeft !== null ? max(0, $daysLeft) . ' days' : 'never',
                'devices'    => count($devices),
            ],
            'devices' => $devices,
            'config'  => [
                'base_url'        => $base,
                'api_key_masked'  => $masked,
                'api_key_source'  => 'Read from the SMS8_API_KEY environment variable. Get the raw key at ' . $base . '/api.php',
                'env_hints' => [
                    'SMS8_BASE_URL' => $base,
                    'SMS8_API_KEY'  => 'set this in your shell env or .env file; do not paste the literal key into code',
                ],
                'note' => 'For security, this response only includes the last 4 chars of your key. The AI assistant should reference the env var, never embed the key in source files.',
            ],
            'endpoints' => [
                'send_sms'   => ['method' => 'POST', 'url' => "$base/api.php?action=send"],
                'otp_send'   => ['method' => 'POST', 'url' => "$base/ajax/otp-send.php"],
                'otp_verify' => ['method' => 'POST', 'url' => "$base/ajax/otp-verify.php"],
                'inbox'      => ['method' => 'GET',  'url' => "$base/api.php?action=inbox"],
                'devices'    => ['method' => 'GET',  'url' => "$base/api.php?action=devices"],
            ],
            'quick_actions' => [
                'add_device'       => "$base/devices.php",
                'get_api_key'      => "$base/profile.php",
                'sms_test_page'    => "$base/sender.php",
                'devices_dashboard'=> "$base/devices.php",
                'otp_settings'     => "$base/profile.php#otp",
                'webhook_settings' => "$base/webhook.php",
                'api_docs'         => "$base/api.php",
                'mcp_docs'         => 'https://mcp.sms8.io',
            ],
            'next_steps' => [
                count($devices) === 0
                    ? 'No paired devices yet — install the SMS8 Android app and pair via QR before sending. ' . $base . '/devices.php'
                    : 'You have ' . count($devices) . ' paired device(s). Call `send_sms` or `send_otp` to start sending.',
            ],
            'integration_examples' => [
                'php_send' => 'See examples/php-send.php on https://github.com/1fancy/sms8-sms-gateway',
                'js_fetch' => 'See examples/js-fetch.js on https://github.com/1fancy/sms8-sms-gateway',
                'otp_flow' => 'See examples/otp-flow.php on https://github.com/1fancy/sms8-sms-gateway',
                'webhook'  => 'See examples/webhook-handler.php on https://github.com/1fancy/sms8-sms-gateway',
            ],
        ];
    },
]);
