<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Handles database option cleanup and path rewrites flush securely.
 *
 * @link       https://yukdigitalz.com/yukdigitalz-knowledge-base
 * @since      1.0.0
 *
 * @package    YukdigitalzKnowledgeBase
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete registered settings options
$yukdigitalz_kb_options = array(
	'yukdigitalz_kb_slug',
	'yukdigitalz_kb_cat_slug',
	'yukdigitalz_kb_tag_slug',
	'yukdigitalz_kb_primary_color',
	'yukdigitalz_kb_secondary_color',
	'yukdigitalz_kb_accent_color',
	'yukdigitalz_kb_enable_feedback',
	'yukdigitalz_kb_enable_toc',
	'yukdigitalz_kb_enable_breadcrumbs',
	'yukdigitalz_kb_enable_reading_time',
	'yukdigitalz_kb_enable_ai_chat',
	'yukdigitalz_kb_enable_comments',
	'yukdigitalz_kb_gemini_api_key',
	'yukdigitalz_kb_enable_rate_limit',
	'yukdigitalz_kb_rate_limit_count',
);

foreach ( $yukdigitalz_kb_options as $yukdigitalz_kb_option ) {
	delete_option( $yukdigitalz_kb_option );
}

// Clean up rewrite paths
flush_rewrite_rules();
