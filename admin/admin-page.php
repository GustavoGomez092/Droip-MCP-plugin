<?php
/**
 * Admin integration for Droip Claude Bridge.
 *
 * Injects a Claude Bridge card into the Droip Integrations tab
 * at /admin.php?page=droip&tab=integrations — matching the native
 * Droip integration card structure.
 *
 * @package DroipBridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue our injection script on the Droip admin page only.
 */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	// Only load on Droip's main dashboard pages.
	if (
		'toplevel_page_droip' !== $hook &&
		'droip_page_droip-settings' !== $hook
	) {
		return;
	}

	wp_enqueue_script(
		'droip-claude-bridge-integration',
		DROIP_BRIDGE_URL . 'admin/integration.js',
		array(),
		DROIP_BRIDGE_VERSION,
		true
	);

	$mcp_config  = droip_bridge_generate_mcp_config();
	$config_json = json_encode( $mcp_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

	wp_localize_script( 'droip-claude-bridge-integration', 'droipBridge', array(
		'configJson' => $config_json,
	) );

} );

// ─── Utility functions ──────────────────────────────────────────────────────

/**
 * Auto-detect the PHP binary path used by Local.
 */
function droip_bridge_detect_php_binary(): string {
	$local_base = getenv( 'HOME' ) . '/Library/Application Support/Local/lightning-services';
	if ( is_dir( $local_base ) ) {
		$dirs = glob( $local_base . '/php-*/bin/darwin-arm64/bin/php' );
		if ( empty( $dirs ) ) {
			$dirs = glob( $local_base . '/php-*/bin/darwin/bin/php' );
		}
		if ( ! empty( $dirs ) ) {
			usort( $dirs, function ( $a, $b ) {
				return version_compare(
					droip_bridge_extract_version( $b ),
					droip_bridge_extract_version( $a )
				);
			} );
			foreach ( $dirs as $php_path ) {
				if ( is_executable( $php_path ) ) {
					return $php_path;
				}
			}
		}
	}

	$system_php = trim( shell_exec( 'which php 2>/dev/null' ) ?: '' );
	if ( $system_php && is_executable( $system_php ) ) {
		return $system_php;
	}

	return '/path/to/php';
}

/**
 * Extract version string from a Local PHP path for sorting.
 */
function droip_bridge_extract_version( string $path ): string {
	if ( preg_match( '/php-(\d+\.\d+\.\d+)/', $path, $m ) ) {
		return $m[1];
	}
	return '0.0.0';
}

/**
 * Auto-detect the MySQL socket used by Local for this site.
 */
function droip_bridge_detect_mysql_socket(): string {
	$local_run = getenv( 'HOME' ) . '/Library/Application Support/Local/run';
	if ( is_dir( $local_run ) ) {
		$sockets = glob( $local_run . '/*/mysql/mysqld.sock' );
		foreach ( $sockets as $sock ) {
			if ( file_exists( $sock ) ) {
				return $sock;
			}
		}
	}
	return '';
}

/**
 * Build the MCP config JSON.
 */
function droip_bridge_generate_mcp_config(): array {
	$php_binary   = droip_bridge_detect_php_binary();
	$mysql_socket = droip_bridge_detect_mysql_socket();
	$server_path  = DROIP_BRIDGE_PATH . 'mcp-server/server.php';
	$wp_root      = rtrim( ABSPATH, '/' );

	$args = array();
	if ( $mysql_socket ) {
		$args[] = '-d';
		$args[] = 'mysqli.default_socket=' . $mysql_socket;
	}
	$args[] = $server_path;

	return array(
		'mcpServers' => array(
			'droip-bridge' => array(
				'command' => $php_binary,
				'args'    => $args,
				'env'     => array(
					'WP_ROOT_PATH' => $wp_root,
				),
			),
		),
	);
}

// ─── Admin Bar Status Light ─────────────────────────────────────────────────

/**
 * Run prerequisite checks for the MCP server.
 *
 * @return array{ok: bool, disabled: bool, checks: array<string, bool>}
 */
function droip_bridge_check_server_status(): array {
	$settings = get_option( DROIP_BRIDGE_OPTION_KEY, array( 'enabled' => false ) );
	$enabled  = ! empty( $settings['enabled'] );

	$php_binary   = droip_bridge_detect_php_binary();
	$mysql_socket = droip_bridge_detect_mysql_socket();

	$checks = array(
		'Server Enabled'         => $enabled,
		'PHP Binary Found'       => $php_binary !== '/path/to/php' && is_executable( $php_binary ),
		'MySQL Socket'           => $mysql_socket !== '' && file_exists( $mysql_socket ),
		'Dependencies Installed' => file_exists( DROIP_BRIDGE_PATH . 'vendor/autoload.php' ),
		'Server File Exists'     => file_exists( DROIP_BRIDGE_PATH . 'mcp-server/server.php' ),
	);

	$all_pass = ! in_array( false, $checks, true );

	return array(
		'ok'       => $all_pass,
		'disabled' => ! $enabled,
		'checks'   => $checks,
	);
}

/**
 * Add MCP server status node to the admin bar.
 */
add_action( 'admin_bar_menu', function ( \WP_Admin_Bar $wp_admin_bar ) {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	$status    = droip_bridge_check_server_status();
	$configure = admin_url( 'admin.php?page=droip&tab=integrations' );

	// Determine dot color.
	if ( $status['disabled'] ) {
		$color = '#9ca3af'; // gray
		$label = 'MCP Disabled';
	} elseif ( $status['ok'] ) {
		$color = '#22c55e'; // green
		$label = 'MCP Server';
	} else {
		$color = '#ef4444'; // red
		$label = 'MCP Server';
	}

	$dot = sprintf(
		'<span style="display:inline-block;width:10px;height:10px;border-radius:50%%;background:%s;margin-right:6px;vertical-align:middle;"></span>',
		$color
	);

	$wp_admin_bar->add_node( array(
		'id'    => 'droip-mcp-status',
		'title' => $dot . $label,
		'href'  => $configure,
		'meta'  => array(
			'title' => $status['ok'] ? 'MCP server ready' : ( $status['disabled'] ? 'MCP server disabled' : 'MCP server has issues' ),
		),
	) );

	// Individual check items.
	foreach ( $status['checks'] as $name => $pass ) {
		$icon = $pass ? '&#x2713;' : '&#x2717;';
		$wp_admin_bar->add_node( array(
			'id'     => 'droip-mcp-' . sanitize_title( $name ),
			'parent' => 'droip-mcp-status',
			'title'  => sprintf(
				'<span style="color:%s;margin-right:4px;">%s</span> %s',
				$pass ? '#22c55e' : '#ef4444',
				$icon,
				esc_html( $name )
			),
		) );
	}

	// Configure link.
	$wp_admin_bar->add_node( array(
		'id'     => 'droip-mcp-configure',
		'parent' => 'droip-mcp-status',
		'title'  => 'Configure&hellip;',
		'href'   => $configure,
	) );
}, 100 );

/**
 * Minimal styles for the admin bar status dot.
 */
add_action( 'admin_head', function () {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	?>
	<style>
		#wp-admin-bar-droip-mcp-status .ab-item { display: flex; align-items: center; }
		#wp-admin-bar-droip-mcp-status .ab-submenu .ab-item { display: flex; align-items: center; }
	</style>
	<?php
} );

