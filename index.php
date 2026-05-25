<?php
/**
 * SMS8 — MCP server (entry point)
 *
 * Speaks the Model Context Protocol over HTTPS so any AI coding tool
 * (Claude Code, Cursor, Windsurf, …) can call SMS8 directly from a
 * developer's IDE.
 *
 * Architecture (Option A — shared code & DB):
 *   This file lives at /www/wwwroot/mcp.sms8.io/ but bootstraps the main
 *   SMS8 codebase at /www/wwwroot/app.sms8.io/. We re-use the existing
 *   models (User, Device, Message, …) and DB connection — no duplicate
 *   business logic, no extra network hop.
 *
 *   The main-app path is configurable via the SMS8_APP_PATH env var; the
 *   default matches our standard deployment.
 *
 * Repo:    https://github.com/1fancy/sms8-sms-gateway
 * Docs:    https://mcp.sms8.io
 * Contact: hello@sms8.io
 */

// ── Bootstrap the main SMS8 app (Option A: share code + DB) ───────────────
$SMS8_APP = getenv('SMS8_APP_PATH') ?: '/www/wwwroot/app.sms8.io';
if (!is_file($SMS8_APP . '/config.php')) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'SMS8 app not found — set SMS8_APP_PATH env var to your install dir.']);
    exit;
}
require_once $SMS8_APP . '/config.php';
require_once $SMS8_APP . '/vendor/autoload.php';
date_default_timezone_set(defined('TIMEZONE') ? TIMEZONE : 'UTC');

// ── MCP-specific helpers ──────────────────────────────────────────────────
require_once __DIR__ . '/lib/Auth.php';
require_once __DIR__ . '/lib/JsonRpc.php';
require_once __DIR__ . '/lib/ToolRegistry.php';

// Auto-load every tool from tools/
foreach (glob(__DIR__ . '/tools/*.php') as $f) require_once $f;

// ── Dispatch ──────────────────────────────────────────────────────────────
header('Content-Type: application/json');

// CORS: MCP is intended to be called from AI tools (Claude Code, Cursor, …)
// running locally — NOT from arbitrary web pages. We allow CORS only for the
// public discovery methods (initialize, tools/list, ping) and for OPTIONS
// preflights; tools/call is intentionally non-CORS so a malicious web page
// can't trigger SMS sends even if the user pasted their API key somewhere.
//
// IDE-side MCP clients send requests server-to-server (no browser preflight
// involved), so this restriction doesn't affect them.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$isOptions = ($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS';

if ($isOptions) {
    // Preflight: only advertise that discovery is allowed.
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Api-Key');
    header('Access-Control-Max-Age: 600');
    exit;
}

// Friendly browser pages on GET — humans hitting mcp.sms8.io.
// Maps the URL path to a PHP page in pages/. Anything unmapped falls back
// to the landing page so legacy bookmarks keep working.
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $path = strtolower(trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/'));

    // 301-redirect old short slugs to the SEO-optimized ones (preserve any
    // links shared before the rename, and keep search engines pointed to one
    // canonical URL).
    $redirects = [
        'api' => '/sms-api-documentation',
        'otp' => '/sms-otp-verification-api',
        'opencode' => '/opencode-sms-mcp-server',
    ];
    if (isset($redirects[$path])) {
        header('Location: ' . $redirects[$path], true, 301);
        exit;
    }

    $routes = [
        ''                            => 'pages/home.php',
        'sms-api-documentation'       => 'pages/api.php',
        'sms-otp-verification-api'    => 'pages/otp.php',
        'opencode-sms-mcp-server'     => 'pages/opencode.php',
    ];
    if (isset($routes[$path])) {
        header('Content-Type: text/html; charset=utf-8');
        require __DIR__ . '/' . $routes[$path];
        exit;
    }
    // Static assets routed through index due to .htaccess catch-all
    if (preg_match('#\.(css|js|png|svg|ico|jpg|jpeg|webp|woff2?|ttf|map)$#i', $path)) {
        $file = __DIR__ . '/' . $path;
        if (is_file($file)) {
            // Let the web server detect content-type
            readfile($file);
            exit;
        }
    }
    // Fallback for /robots.txt, /sitemap.xml, etc
    if (is_file(__DIR__ . '/' . $path)) {
        readfile(__DIR__ . '/' . $path);
        exit;
    }
    // Unknown path — show home
    header('Content-Type: text/html; charset=utf-8');
    require __DIR__ . '/pages/home.php';
    exit;
}

// Peek at the JSON-RPC method to decide whether to set CORS headers for the
// actual response. This runs BEFORE JsonRpc::handle() reads the same body —
// JsonRpc rewinds php://input internally so this is safe.
$raw    = file_get_contents('php://input') ?: '';
$peeked = json_decode($raw, true);
$method = '';
if (is_array($peeked)) {
    if (isset($peeked['method'])) {
        $method = (string)$peeked['method'];
    } elseif (isset($peeked[0]['method'])) {
        // batched — be conservative and require ALL methods to be safe
        $method = '';
        $allSafe = true;
        foreach ($peeked as $sub) {
            $m = $sub['method'] ?? '';
            if (!in_array($m, ['initialize', 'tools/list', 'ping'], true)) { $allSafe = false; break; }
        }
        if ($allSafe) $method = 'initialize'; // any safe value
    }
}
$publicMethod = in_array($method, ['initialize', 'tools/list', 'ping'], true);
if ($publicMethod && $origin !== '') {
    header('Access-Control-Allow-Origin: *');
    header('Vary: Origin');
}
// For tools/call (and anything else) — no CORS headers, browsers will block
// the response from being read by a malicious origin.

JsonRpc::handle($raw);
