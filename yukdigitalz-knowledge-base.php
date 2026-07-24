<?php
/**
 * Plugin Name:       Yukdigitalz Knowledge Base
 * Plugin URI:        https://yukdigitalz.com/yukdigitalz-knowledge-base
 * Description:       An enterprise-grade, high-performance Knowledge Base and Documentation plugin for WordPress.
 * Version:           1.0.0
 * Author:            Shihela
 * Author URI:        https://yukdigitalz.com/shihela
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       yukdigitalz-knowledge-base
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define plugin constants
define( 'YUKDIGITALZ_KB_VERSION', '1.0.0' );
define( 'YUKDIGITALZ_KB_PATH', plugin_dir_path( __FILE__ ) );
define( 'YUKDIGITALZ_KB_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoloader for Yukdigitalz Knowledge Base classes under the 'YukdigitalzKnowledgeBase' namespace.
 * Maps 'YukdigitalzKnowledgeBase\ClassName' to 'includes/class-classname.php' (lowercased, underscore replaced with hyphen).
 */
spl_autoload_register( function ( $class ) {
	$prefix = 'YukdigitalzKnowledgeBase\\';
	$base_dir = YUKDIGITALZ_KB_PATH . 'includes/';

	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, $len );
	// Convert camelCase or PascalCase to lowercase hyphenated
	$file_class = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $relative_class ) );
	$file_class = str_replace( '_', '-', $file_class );
	$file = $base_dir . 'class-' . $file_class . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

/**
 * Activation hooks
 */
register_activation_hook( __FILE__, function() {
	// Trigger CPT registration to flush rewrite rules
	if ( class_exists( 'YukdigitalzKnowledgeBase\CPT' ) ) {
		$cpt = new YukdigitalzKnowledgeBase\CPT();
		$cpt->register_post_type();
		$cpt->register_taxonomy();
	}
	flush_rewrite_rules();
} );

/**
 * Deactivation hook
 */
register_deactivation_hook( __FILE__, function() {
	flush_rewrite_rules();
} );

/**
 * Initialize and run the plugin
 */
function yukdigitalz_kb_run() {
	if ( class_exists( 'YukdigitalzKnowledgeBase\Plugin' ) ) {
		$plugin = new YukdigitalzKnowledgeBase\Plugin();
		$plugin->run();
	}
}
add_action( 'plugins_loaded', 'yukdigitalz_kb_run' );
