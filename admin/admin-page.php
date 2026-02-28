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

