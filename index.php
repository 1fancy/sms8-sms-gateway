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
 * Repo:    https://github.com/1fancy/sms8-mcp
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
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS, GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Api-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

// Friendly landing page on GET — humans hitting mcp.sms8.io in a browser
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: text/html; charset=utf-8');
    readfile(__DIR__ . '/landing.html');
    exit;
}

JsonRpc::handle();
