<?php
/**
 * create_webhook — register a callback URL that SMS8 will POST inbound SMS
 * (and delivery events) to.
 *
 * Behind the scenes it just updates the User.webHook column — the same
 * field the webhook.php page in the main app writes. Token generation is
 * left to that page; here we only set the URL.
 */
ToolRegistry::register([
    'name' => 'create_webhook',
    'description' => 'Register a webhook URL that SMS8 will POST inbound messages and delivery events to. Returns the configured webhook + a sample payload schema the AI can use to scaffold the receiving handler. Pass `enabled: false` to remove the webhook.',
    'inputSchema' => [
        'type' => 'object',
        'required' => ['url'],
        'properties' => [
            'url'     => ['type' => 'string', 'description' => 'HTTPS URL on YOUR server. SMS8 will POST JSON here on every inbound SMS and delivery event.'],
            'enabled' => ['type' => 'boolean', 'description' => 'Default true. Pass false to clear the webhook.'],
        ],
    ],
    'handler' => function(array $args, User $user): array {
        $url     = trim($args['url'] ?? '');
        $enabled = !isset($args['enabled']) ? true : (bool)$args['enabled'];

        if ($enabled) {
            if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL) || stripos($url, 'https://') !== 0) {
                return ['error' => 'url must be a valid HTTPS URL'];
            }
            // Block obviously dangerous SSRF targets (loopback / RFC1918 / metadata)
            $host = parse_url($url, PHP_URL_HOST) ?: '';
            $forbidden = ['localhost', '127.0.0.1', '0.0.0.0', '169.254.169.254', '::1'];
            if (in_array($host, $forbidden, true) || preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[01])\.)/', $host)) {
                return ['error' => 'webhook URL points to a private/loopback address — use a public HTTPS endpoint'];
            }
        }

        $user->setWebHook($enabled ? $url : null);
        $user->save(false);

        return [
            'success' => true,
            'webhook' => $user->getWebHook(),
            'enabled' => $enabled && $user->getWebHook(),
            'sample_payload' => [
                'event'       => 'message.received',
                'data'        => [
                    'id'          => 1234,
                    'from'        => '+15550142',
                    'message'     => 'Hey, can I reschedule?',
                    'device_id'   => 42,
                    'received_at' => date('c'),
                ],
            ],
            'next_steps' => [
                'Your server should respond 2xx within 10 seconds; SMS8 retries on failure.',
                'Use the webhook secret on https://app.sms8.io/webhook.php to sign-verify incoming requests.',
                'See examples/webhook-handler.php in https://github.com/1fancy/sms8-mcp for a drop-in handler.',
            ],
        ];
    },
]);
