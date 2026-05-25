<?php
/**
 * Shared dark-mode header for mcp.sms8.io pages.
 * Pages set:
 *   $page       — slug, e.g. 'home', 'api', 'otp'
 *   $title      — full <title>
 *   $desc       — meta description
 *   $canonical  — canonical URL
 *   $jsonld     — optional JSON-LD blob (raw HTML)
 */
$page      = $page      ?? 'home';
$title     = $title     ?? 'SMS8 MCP Server';
$desc      = $desc      ?? 'SMS gateway MCP for Claude Code, Cursor and Windsurf. SMS, OTP and webhooks from your paired Android phone.';
$canonical = $canonical ?? 'https://mcp.sms8.io/' . ($page === 'home' ? '' : $page);
$jsonld    = $jsonld    ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($title) ?></title>
<meta name="description" content="<?= htmlspecialchars($desc) ?>">
<meta name="robots" content="index, follow, max-image-preview:large">
<meta name="author" content="SMS8.io">
<meta name="theme-color" content="#06060d">
<link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">

<meta property="og:title"       content="<?= htmlspecialchars($title) ?>">
<meta property="og:description" content="<?= htmlspecialchars($desc) ?>">
<meta property="og:type"        content="website">
<meta property="og:url"         content="<?= htmlspecialchars($canonical) ?>">
<meta property="og:site_name"   content="SMS8">
<meta property="og:image"       content="https://sms8.io/wp-content/uploads/2024/07/Add-a-heading2.x51548.png">
<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:title"       content="<?= htmlspecialchars($title) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($desc) ?>">

<!-- Favicons + logos served from the new sms8.io build (no WP CDN) -->
<link rel="icon"           type="image/png" sizes="32x32"   href="https://sms8.io/assets/images/favicon-32.png">
<link rel="icon"           type="image/png" sizes="192x192" href="https://sms8.io/assets/images/favicon-192.png">
<link rel="apple-touch-icon" sizes="180x180" href="https://sms8.io/assets/images/favicon-180.png">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<link rel="stylesheet" href="/css/site.css?v=<?= @filemtime(__DIR__ . '/../css/site.css') ?: time() ?>">

<?= $jsonld ?>
</head>
<body class="mcp-<?= htmlspecialchars($page) ?>">

<header class="site-header" id="site-header">
  <div class="header-inner">
    <a href="/" class="site-logo" aria-label="SMS8 MCP Home">
      <img src="https://sms8.io/assets/images/sms8-logo-white.png" alt="SMS8 SMS gateway MCP for AI agents" width="120" height="29" loading="eager" decoding="async">
      <span class="brand-suffix">MCP</span>
    </a>
    <nav class="site-nav" aria-label="Main navigation">
      <a href="/" class="<?= $page === 'home' ? 'active' : '' ?>">Tools</a>
      <a href="/#install">Install</a>
      <a href="/sms-api-documentation" class="<?= $page === 'api'  ? 'active' : '' ?>">API</a>
      <a href="/sms-otp-verification-api" class="<?= $page === 'otp'  ? 'active' : '' ?>">OTP</a>
      <div class="nav-dropdown<?= in_array($page, ['opencode','openclaw'], true) ? ' is-active' : '' ?>">
        <button type="button" class="nav-dropdown-toggle" aria-haspopup="true" aria-expanded="false">
          AI Agents
          <svg width="10" height="10" viewBox="0 0 12 12" aria-hidden="true" style="margin-left:4px;"><path d="M2 4l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <div class="nav-dropdown-menu" role="menu">
          <a href="/opencode-sms-mcp-server" role="menuitem"<?= $page === 'opencode' ? ' class="active"' : '' ?>>
            <strong>OpenCode</strong>
            <span>AI coding agent (sst) &middot; opencode.json</span>
          </a>
          <a href="/openclaw-sms-mcp-server" role="menuitem"<?= $page === 'openclaw' ? ' class="active"' : '' ?>>
            <strong>OpenClaw</strong>
            <span>Personal AI on WhatsApp / Telegram &middot; openclaw.json</span>
          </a>
        </div>
      </div>
      <a href="/#faq">FAQ</a>
      <a href="https://github.com/1fancy/sms8-sms-gateway" target="_blank" rel="noopener">GitHub</a>
    </nav>
    <div class="header-actions">
      <a class="btn-ghost" href="https://app.sms8.io">Sign in</a>
      <a class="btn-cta"   href="https://app.sms8.io/mcp-setup.php">Get started free</a>
    </div>
    <button class="menu-toggle" id="menu-toggle" aria-label="Toggle menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<nav class="mobile-nav" id="mobile-nav" aria-label="Mobile navigation">
  <a href="/">Tools</a>
  <a href="/#install">Install</a>
  <a href="/sms-api-documentation">API</a>
  <a href="/sms-otp-verification-api">OTP</a>
  <div class="mobile-nav-group">
    <span class="mobile-nav-group-label">AI Agents</span>
    <a href="/opencode-sms-mcp-server">OpenCode</a>
    <a href="/openclaw-sms-mcp-server">OpenClaw</a>
  </div>
  <a href="/#faq">FAQ</a>
  <a href="https://github.com/1fancy/sms8-sms-gateway" target="_blank" rel="noopener">GitHub</a>
  <div class="mobile-nav-actions">
    <a href="https://app.sms8.io" class="btn-ghost">Sign in</a>
    <a href="https://app.sms8.io/mcp-setup.php" class="btn-cta">Get started free</a>
  </div>
</nav>

<main>
