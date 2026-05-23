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
            $err = _sms8_validate_webhook_url($url);
            if ($err !== null) return ['error' => $err];
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
                'See examples/webhook-handler.php in https://github.com/1fancy/sms8-sms-gateway for a drop-in handler.',
            ],
        ];
    },
]);

/**
 * Validate a user-supplied webhook URL.
 *
 * Returns null if OK, or an error string. Rejections cover:
 *   - non-HTTPS schemes
 *   - URL user-info (http://user:pass@host)
 *   - hostnames that resolve to loopback / RFC1918 / link-local / CGNAT /
 *     IPv4-mapped IPv6 / numeric-encoded loopback / well-known metadata IPs
 *   - DNS rebinding: we resolve every A/AAAA at validation time and reject
 *     if *any* record falls in a blocked range. (Final defence at fire-time
 *     should also re-resolve and use CURLOPT_RESOLVE to pin.)
 */
function _sms8_validate_webhook_url(string $url): ?string
{
    if ($url === '') return 'url must be a valid HTTPS URL';

    $parsed = parse_url($url);
    if ($parsed === false || empty($parsed['host']) || empty($parsed['scheme'])) {
        return 'url is malformed';
    }
    if (strtolower($parsed['scheme']) !== 'https') {
        return 'url must use the https scheme';
    }
    if (!empty($parsed['user']) || !empty($parsed['pass'])) {
        return 'url must not contain credentials (user:pass@)';
    }

    $host = strtolower($parsed['host']);

    // Strip IPv6 brackets if present
    if ($host !== '' && $host[0] === '[') {
        $host = trim($host, '[]');
    }

    // Block hostname-based loopback / *.localtest.me / .local
    static $forbiddenHosts = [
        'localhost', 'localhost.localdomain', 'broadcasthost', 'ip6-localhost',
        'ip6-loopback', 'metadata.google.internal',
    ];
    if (in_array($host, $forbiddenHosts, true)) {
        return 'webhook URL points to a private/loopback host — use a public HTTPS endpoint';
    }
    if (preg_match('/\.local(host)?$/', $host) || preg_match('/\.localtest\.me$/', $host)) {
        return 'webhook URL points to a private/loopback host — use a public HTTPS endpoint';
    }

    // Resolve and inspect every A / AAAA record. If any one falls in a
    // blocked range, reject the whole URL.
    $records = @dns_get_record($host, DNS_A + DNS_AAAA);
    $ips = [];
    if (is_array($records)) {
        foreach ($records as $r) {
            if (!empty($r['ip']))    $ips[] = $r['ip'];
            if (!empty($r['ipv6']))  $ips[] = $r['ipv6'];
        }
    }
    // If the host is a literal IP, validate it directly
    if (filter_var($host, FILTER_VALIDATE_IP)) $ips[] = $host;

    if (empty($ips)) {
        // Couldn't resolve — refuse (we can't prove it's safe)
        return 'webhook host could not be resolved';
    }

    foreach ($ips as $ip) {
        if (_sms8_ip_is_blocked($ip)) {
            return 'webhook URL resolves to a private/loopback address — use a public HTTPS endpoint';
        }
    }

    return null;
}

/**
 * True if the given IP literal is in a range we refuse to call into.
 * Covers IPv4 loopback (127.0.0.0/8), 0.0.0.0/8, link-local (169.254.0.0/16
 * incl. cloud metadata), RFC1918 (10/8, 172.16/12, 192.168/16), CGNAT
 * (100.64.0.0/10), IPv6 ::1, fc00::/7 (ULA), fe80::/10 (link-local), and
 * IPv4-mapped IPv6 forms of any of the above.
 */
function _sms8_ip_is_blocked(string $ip): bool
{
    // PHP's FILTER_FLAG_NO_PRIV_RANGE | NO_RES_RANGE rejects most of what
    // we want, but it misses CGNAT and some IPv6 cases — so we also do
    // explicit checks below.
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        // Either invalid or in a private/reserved range — block.
        // (filter_var returns false for both cases.)
        if (!filter_var($ip, FILTER_VALIDATE_IP)) return true;  // garbage
        return true;
    }

    // Extra: CGNAT 100.64.0.0/10 — used by Tailscale, ISPs, sometimes routes
    // back into private networks.
    if (preg_match('/^100\.(6[4-9]|[7-9]\d|1[01]\d|12[0-7])\./', $ip)) return true;

    // IPv4-mapped IPv6 (::ffff:1.2.3.4 etc.)
    if (strpos($ip, '::ffff:') === 0) {
        $v4 = substr($ip, 7);
        if (filter_var($v4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return _sms8_ip_is_blocked($v4);
        }
    }

    return false;
}
