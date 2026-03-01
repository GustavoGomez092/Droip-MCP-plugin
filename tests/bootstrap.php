<?php
/**
 * PHPUnit bootstrap file for Droip Claude Bridge tests.
 *
 * Loads stubs, defines constants, and sets up autoloading.
 */

// Define WordPress constants before loading stubs
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

if (!defined('DROIP_BRIDGE_VERSION')) {
    define('DROIP_BRIDGE_VERSION', '1.0.0');
}

if (!defined('DROIP_BRIDGE_PATH')) {
    define('DROIP_BRIDGE_PATH', dirname(__DIR__) . '/');
}

if (!defined('DROIP_BRIDGE_URL')) {
    define('DROIP_BRIDGE_URL', 'http://localhost/wp-content/plugins/droip-claude-bridge/');
}

if (!defined('DROIP_BRIDGE_OPTION_KEY')) {
    define('DROIP_BRIDGE_OPTION_KEY', 'droip_claude_bridge_settings');
}

// Load WordPress function stubs
require_once __DIR__ . '/stubs/wordpress.php';

// Load Droip class stubs and constants
require_once __DIR__ . '/stubs/droip.php';

// Load Composer autoloader (MCP SDK + DroipBridge namespace)
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Load admin functions (not autoloaded via PSR-4)
require_once dirname(__DIR__) . '/admin/admin-page.php';
