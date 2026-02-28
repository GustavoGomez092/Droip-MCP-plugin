<?php
/**
 * WordPress + Droip bootstrap for the MCP server.
 *
 * Loads wp-load.php so all WordPress and Droip APIs are available.
 * Must be called before any tool/resource registration.
 *
 * @package DroipBridge
 */

declare(strict_types=1);

namespace DroipBridge;

// Prevent any output from corrupting the stdio JSON-RPC stream
ob_start();

// Suppress theme loading — we only need the DB and plugin APIs
define('WP_USE_THEMES', false);

// Note: Do NOT define WP_INSTALLING here — it prevents plugin loading.
// We need Droip's plugin to be fully loaded so we can use its classes.

// Determine WordPress root path
$wpRootPath = getenv('WP_ROOT_PATH');

if (!$wpRootPath) {
    // Try relative path from plugin dir (plugin is in wp-content/plugins/droip-claude-bridge/)
    $wpRootPath = dirname(__DIR__, 4);
}

$wpLoadFile = rtrim($wpRootPath, '/') . '/wp-load.php';

if (!file_exists($wpLoadFile)) {
    fwrite(STDERR, "ERROR: Cannot find wp-load.php at {$wpLoadFile}\n");
    fwrite(STDERR, "Set the WP_ROOT_PATH environment variable to your WordPress root directory.\n");
    exit(1);
}

// Suppress any output during WP bootstrap
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../mcp-server-errors.log');

require_once $wpLoadFile;

// Discard any buffered output from WP bootstrap
ob_end_clean();

// Verify Droip is active
if (!defined('DROIP_VERSION')) {
    fwrite(STDERR, "ERROR: Droip plugin is not active. Please activate Droip first.\n");
    exit(1);
}

if (!defined('DROIP_SYMBOL_TYPE')) {
    fwrite(STDERR, "ERROR: Droip constants not loaded. Droip may not be fully initialized.\n");
    exit(1);
}

fwrite(STDERR, "Bootstrap complete: WordPress " . get_bloginfo('version') . " + Droip " . DROIP_VERSION . "\n");
