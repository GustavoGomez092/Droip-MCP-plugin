<?php
/**
 * Plugin Name: Droip Claude Bridge
 * Description: MCP server bridge enabling Claude Code to create and manage Droip symbols programmatically.
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Author: Droip
 * License: GPL-2.0-or-later
 *
 * @package DroipBridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DROIP_BRIDGE_VERSION', '1.0.0' );
define( 'DROIP_BRIDGE_PATH', plugin_dir_path( __FILE__ ) );
define( 'DROIP_BRIDGE_URL', plugin_dir_url( __FILE__ ) );
define( 'DROIP_BRIDGE_OPTION_KEY', 'droip_claude_bridge_settings' );

/**
 * Block activation if Droip is not active.
 */
register_activation_hook( __FILE__, function () {
	// is_plugin_active may not be available yet during activation
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( ! is_plugin_active( 'droip/droip.php' ) ) {
		wp_die(
			'<strong>Droip Claude Bridge</strong> requires the <strong>Droip</strong> plugin to be installed and active.',
			'Plugin Activation Error',
			array(
				'back_link' => true,
				'response'  => 403,
			)
		);
	}

	// Set defaults on activation
	if ( false === get_option( DROIP_BRIDGE_OPTION_KEY ) ) {
		add_option( DROIP_BRIDGE_OPTION_KEY, array( 'enabled' => false ) );
	}
} );

/**
 * Deactivate ourselves if Droip gets deactivated.
 */
add_action( 'admin_init', function () {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( ! is_plugin_active( 'droip/droip.php' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p>';
			echo '<strong>Droip Claude Bridge</strong> has been deactivated because <strong>Droip</strong> is no longer active.';
			echo '</p></div>';
		} );
	}
} );

// Load the admin page (dashboard integration + AJAX handlers)
if ( is_admin() ) {
	require_once DROIP_BRIDGE_PATH . 'admin/admin-page.php';
}
