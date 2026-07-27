<?php
namespace Shihela\YukdigitalzKnowledgeBase;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles enqueuing styles and scripts for both frontend and backend.
 */
class Assets {
	/**
	 * Hooks into WordPress actions.
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueues scripts and styles for frontend viewports conditionally.
	 */
	public function enqueue_public_assets() {
		global $post;

		// Always register styles and scripts first
		wp_register_style(
			'yukdigitalz-kb-public',
			YUKDIGITALZ_KB_URL . 'assets/css/public.css',
			array(),
			YUKDIGITALZ_KB_VERSION,
			'all'
		);

		wp_register_script(
			'yukdigitalz-kb-public',
			YUKDIGITALZ_KB_URL . 'assets/js/public.js',
			array(),
			YUKDIGITALZ_KB_VERSION,
			true
		);

		// Inject customized style configurations defined in Admin Panel
		$primary_color   = sanitize_hex_color( get_option( 'yukdigitalz_kb_primary_color', '#2563eb' ) );
		$secondary_color = sanitize_hex_color( get_option( 'yukdigitalz_kb_secondary_color', '#1d4ed8' ) );
		$accent_color    = sanitize_hex_color( get_option( 'yukdigitalz_kb_accent_color', '#f59e0b' ) );
		
		if ( ! $primary_color ) {
			$primary_color = '#2563eb';
		}
		if ( ! $secondary_color ) {
			$secondary_color = '#1d4ed8';
		}
		if ( ! $accent_color ) {
			$accent_color = '#f59e0b';
		}

		$custom_css = "
			:root {
				--yukdigitalz-kb-primary: " . esc_attr( $primary_color ) . ";
				--yukdigitalz-kb-primary-hover: " . esc_attr( $secondary_color ) . ";
				--yukdigitalz-kb-accent: " . esc_attr( $accent_color ) . ";
			}
		";
		wp_add_inline_style( 'yukdigitalz-kb-public', $custom_css );

		// Pass settings, AJAX URL, and security tokens to frontend JS
		wp_localize_script(
			'yukdigitalz-kb-public',
			'yukdigitalz_kb_vars',
			array(
				'ajax_url'       => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'yukdigitalz_kb_ajax_nonce' ),
				'public_css_url' => esc_url( YUKDIGITALZ_KB_URL . 'assets/css/public.css' ),
				'colors'         => array(
					'primary'   => esc_attr( $primary_color ),
					'secondary' => esc_attr( $secondary_color ),
					'accent'    => esc_attr( $accent_color ),
				),
				'strings'        => array(
					'voting_thanks' => esc_html__( 'Thank you for your feedback!', 'yukdigitalz-knowledge-base' ),
					'voting_error'  => esc_html__( 'Could not register feedback. Please try again.', 'yukdigitalz-knowledge-base' ),
					'search_no_res' => esc_html__( 'No documentation found matching your query.', 'yukdigitalz-knowledge-base' ),
				)
			)
		);

		// Check if the page contains the plugin's shortcode
		$has_shortcode = is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'yukdigitalz_kb' );

		// Optimize asset delivery by only loading on Yukdigitalz KB templates or pages with the shortcode
		if ( is_singular( 'yukdigitalz_kb_doc' ) || 
			 is_post_type_archive( 'yukdigitalz_kb_doc' ) || 
			 is_tax( 'yukdigitalz_kb_cat' ) || 
			 is_tax( 'yukdigitalz_kb_tag' ) || 
			 $has_shortcode 
		) {
			wp_enqueue_style( 'yukdigitalz-kb-public' );
			wp_enqueue_script( 'yukdigitalz-kb-public' );
		}
	}

	/**
	 * Enqueues styles and scripts for admin settings dashboard.
	 * 
	 * @param string $hook The current admin page screen hook name.
	 */
	public function enqueue_admin_assets( $hook ) {
		$screen = get_current_screen();
		
		if ( $hook === 'settings_page_yukdigitalz-kb' ) {
			wp_enqueue_style(
				'yukdigitalz-kb-admin',
				YUKDIGITALZ_KB_URL . 'assets/css/admin.css',
				array(),
				YUKDIGITALZ_KB_VERSION,
				'all'
			);

			wp_enqueue_script(
				'yukdigitalz-kb-admin',
				YUKDIGITALZ_KB_URL . 'assets/js/admin.js',
				array(),
				YUKDIGITALZ_KB_VERSION,
				true
			);

			wp_localize_script(
				'yukdigitalz-kb-admin',
				'yukdigitalz_kb_admin_vars',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'yukdigitalz_kb_ajax_nonce' ),
				)
			);
		}
	}
}
