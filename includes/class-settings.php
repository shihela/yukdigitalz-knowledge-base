<?php
namespace YukdigitalzKnowledgeBase;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the backend admin settings panel.
 */
class Settings {
	/**
	 * Hooks settings to WordPress.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Adds settings menu link under Settings > Yukdigitalz KB.
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'Yukdigitalz KB Settings', 'yukdigitalz-knowledge-base' ),
			__( 'Yukdigitalz KB', 'yukdigitalz-knowledge-base' ),
			'manage_options',
			'yukdigitalz-kb',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings inputs with the WordPress Settings API.
	 */
	public function register_settings() {
		// General Settings Section
		register_setting( 'yukdigitalz_kb_settings_group', 'yukdigitalz_kb_slug', array(
			'sanitize_callback' => 'sanitize_title',
			'default'           => 'docs',
		) );
		register_setting( 'yukdigitalz_kb_settings_group', 'yukdigitalz_kb_cat_slug', array(
			'sanitize_callback' => 'sanitize_title',
			'default'           => 'doc-category',
		) );
		register_setting( 'yukdigitalz_kb_settings_group', 'yukdigitalz_kb_tag_slug', array(
			'sanitize_callback' => 'sanitize_title',
			'default'           => 'doc-tag',
		) );
		register_setting( 'yukdigitalz_kb_settings_group', 'yukdigitalz_kb_archive_posts_per_page', array(
			'sanitize_callback' => 'absint',
			'default'           => 6,
		) );

		// Styling Settings Section
		register_setting( 'yukdigitalz_kb_settings_group', 'yukdigitalz_kb_primary_color', array(
			'sanitize_callback' => 'sanitize_hex_color',
			'default'           => '#2563eb',
		) );
		register_setting( 'yukdigitalz_kb_settings_group', 'yukdigitalz_kb_secondary_color', array(
			'sanitize_callback' => 'sanitize_hex_color',
			'default'           => '#1d4ed8',
		) );
		register_setting( 'yukdigitalz_kb_settings_group', 'yukdigitalz_kb_accent_color', array(
			'sanitize_callback' => 'sanitize_hex_color',
			'default'           => '#f59e0b',
		) );

		// Features Settings Section
		register_setting( 'yukdigitalz_kb_settings_group', 'yukdigitalz_kb_enable_feedback', array(
			'sanitize_callback' => 'absint',
			'default'           => 1,
		) );
		register_setting( 'yukdigitalz_kb_settings_group', 'yukdigitalz_kb_enable_toc', array(
			'sanitize_callback' => 'absint',
			'default'           => 1,
		) );
		register_setting( 'yukdigitalz_kb_settings_group', 'yukdigitalz_kb_enable_breadcrumbs', array(
			'sanitize_callback' => 'absint',
			'default'           => 1,
		) );
		register_setting( 'yukdigitalz_kb_settings_group', 'yukdigitalz_kb_enable_reading_time', array(
			'sanitize_callback' => 'absint',
			'default'           => 1,
		) );
		register_setting( 'yukdigitalz_kb_settings_group', 'yukdigitalz_kb_enable_ai_chat', array(
			'sanitize_callback' => 'absint',
			'default'           => 1,
		) );
		register_setting( 'yukdigitalz_kb_settings_group', 'yukdigitalz_kb_enable_comments', array(
			'sanitize_callback' => 'absint',
			'default'           => 0,
		) );
		register_setting( 'yukdigitalz_kb_settings_group', 'yukdigitalz_kb_gemini_api_key', array(
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		// Security/AI Anti-Spam Rate Limit Settings
		register_setting( 'yukdigitalz_kb_settings_group', 'yukdigitalz_kb_enable_rate_limit', array(
			'sanitize_callback' => 'absint',
			'default'           => 1,
		) );
		register_setting( 'yukdigitalz_kb_settings_group', 'yukdigitalz_kb_rate_limit_count', array(
			'sanitize_callback' => 'absint',
			'default'           => 20,
		) );

		register_setting( 'yukdigitalz_kb_settings_group', 'yukdigitalz_kb_category_order', array(
			'sanitize_callback' => array( $this, 'sanitize_category_order' ),
			'default'           => array(),
		) );
	}

	/**
	 * Sanitizes the custom category ordering JSON or array input.
	 *
	 * @param mixed $input Input settings.
	 * @return array Sanitized list of category IDs.
	 */
	public function sanitize_category_order( $input ) {
		if ( empty( $input ) ) {
			return array();
		}
		if ( is_array( $input ) ) {
			return array_map( 'absint', $input );
		}
		$decoded = json_decode( $input, true );
		if ( ! is_array( $decoded ) ) {
			return array();
		}
		return array_map( 'absint', $decoded );
	}

	/**
	 * Renders settings page.
	 */
	public function render_settings_page() {
		// Secure capabilities check
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only action triggered by WordPress settings page redirect after options.php form submission.
		if ( isset( $_GET['settings-updated'] ) ) {
			flush_rewrite_rules();
			add_settings_error( 'yukdigitalz_kb_messages', 'yukdigitalz_kb_message', __( 'Settings Saved. Permalinks rebuilt.', 'yukdigitalz-knowledge-base' ), 'updated' );
		}

		$tabs = array(
			'general' => array(
				'label' => __( 'General Settings', 'yukdigitalz-knowledge-base' ),
				'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg>',
			),
			'styling' => array(
				'label' => __( 'Styling Options', 'yukdigitalz-knowledge-base' ),
				'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path></svg>',
			),
			'features' => array(
				'label' => __( 'Features', 'yukdigitalz-knowledge-base' ),
				'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>',
			),
			'security' => array(
				'label' => __( 'Security & AI Limits', 'yukdigitalz-knowledge-base' ),
				'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>',
			),
			'order' => array(
				'label' => __( 'Category Order', 'yukdigitalz-knowledge-base' ),
				'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>',
			),
			'analytics' => array(
				'label' => __( 'Search Analytics', 'yukdigitalz-knowledge-base' ),
				'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>',
			),
		);

		$tabs = apply_filters( 'yukdigitalz_kb_settings_tabs', $tabs );

		settings_errors( 'yukdigitalz_kb_messages' );
		?>
		<div class="wrap yukdigitalz-kb-admin-wrap">
			<h1><?php esc_html_e( 'Yukdigitalz KB Settings', 'yukdigitalz-knowledge-base' ); ?></h1>
			
			<div class="yukdigitalz-kb-admin-layout">
				<!-- Sidebar Panel -->
				<aside class="yukdigitalz-kb-admin-sidebar">
					<div class="yukdigitalz-kb-admin-brand">
						<div class="yukdigitalz-kb-brand-logo">
							<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
						</div>
						<div class="yukdigitalz-kb-brand-meta">
							<span class="yukdigitalz-kb-brand-name">Yukdigitalz KB</span>
							<span class="yukdigitalz-kb-brand-version">v1.0.0 Free</span>
						</div>
					</div>
					<nav class="nav-tab-wrapper yukdigitalz-kb-nav-tabs">
						<?php 
						$first = true;
						foreach ( $tabs as $id => $tab ) : 
							$active_class = $first ? 'nav-tab-active' : '';
							$first = false;
							?>
							<a href="#tab-<?php echo esc_attr( $id ); ?>" class="nav-tab <?php echo esc_attr( $active_class ); ?>" data-tab="<?php echo esc_attr( $id ); ?>">
								<?php
								$allowed_svg = array(
									'svg' => array(
										'xmlns'           => true,
										'viewbox'         => true,
										'fill'            => true,
										'stroke'          => true,
										'stroke-width'    => true,
										'stroke-linecap'  => true,
										'stroke-linejoin' => true,
										'class'           => true,
									),
									'line' => array(
										'x1'     => true,
										'y1'     => true,
										'x2'     => true,
										'y2'     => true,
										'stroke' => true,
									),
									'rect' => array(
										'x'      => true,
										'y'      => true,
										'width'  => true,
										'height' => true,
										'rx'     => true,
										'ry'     => true,
										'fill'   => true,
									),
									'path' => array(
										'd'      => true,
										'fill'   => true,
										'stroke' => true,
									),
									'polygon' => array(
										'points' => true,
									),
									'polyline' => array(
										'points' => true,
									),
								);
								echo wp_kses( $tab['icon'], $allowed_svg );
								?>
								<span><?php echo esc_html( $tab['label'] ); ?></span>
							</a>
						<?php endforeach; ?>
					</nav>
				</aside>

				<!-- Main Content Panel -->
				<main class="yukdigitalz-kb-admin-main">
					<form action="options.php" method="post" class="yukdigitalz-kb-settings-form">
						<?php
						settings_fields( 'yukdigitalz_kb_settings_group' );
						?>

				<!-- TAB: GENERAL -->
				<div id="tab-general" class="yukdigitalz-kb-tab-content active">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="yukdigitalz_kb_slug"><?php esc_html_e( 'Documentation Base Slug', 'yukdigitalz-knowledge-base' ); ?></label></th>
							<td>
								<input name="yukdigitalz_kb_slug" type="text" id="yukdigitalz_kb_slug" value="<?php echo esc_attr( get_option( 'yukdigitalz_kb_slug', 'docs' ) ); ?>" class="regular-text" />
								<p class="description"><?php esc_html_e( 'Base URL structure for documentation archives. e.g. yoursite.com/docs/', 'yukdigitalz-knowledge-base' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="yukdigitalz_kb_cat_slug"><?php esc_html_e( 'Category Base Slug', 'yukdigitalz-knowledge-base' ); ?></label></th>
							<td>
								<input name="yukdigitalz_kb_cat_slug" type="text" id="yukdigitalz_kb_cat_slug" value="<?php echo esc_attr( get_option( 'yukdigitalz_kb_cat_slug', 'doc-category' ) ); ?>" class="regular-text" />
								<p class="description"><?php esc_html_e( 'Base URL structure for documentation categories. e.g. yoursite.com/doc-category/install/', 'yukdigitalz-knowledge-base' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="yukdigitalz_kb_tag_slug"><?php esc_html_e( 'Tag Base Slug', 'yukdigitalz-knowledge-base' ); ?></label></th>
							<td>
								<input name="yukdigitalz_kb_tag_slug" type="text" id="yukdigitalz_kb_tag_slug" value="<?php echo esc_attr( get_option( 'yukdigitalz_kb_tag_slug', 'doc-tag' ) ); ?>" class="regular-text" />
								<p class="description"><?php esc_html_e( 'Base URL structure for documentation tags. e.g. yoursite.com/doc-tag/install/', 'yukdigitalz-knowledge-base' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="yukdigitalz_kb_archive_posts_per_page"><?php esc_html_e( 'Articles Per Page (Archives & Categories)', 'yukdigitalz-knowledge-base' ); ?></label></th>
							<td>
								<input name="yukdigitalz_kb_archive_posts_per_page" type="number" id="yukdigitalz_kb_archive_posts_per_page" value="<?php echo esc_attr( get_option( 'yukdigitalz_kb_archive_posts_per_page', 6 ) ); ?>" min="1" max="100" class="small-text" />
								<p class="description"><?php esc_html_e( 'Number of articles to display per page on category and documentation archive pages before triggering pagination.', 'yukdigitalz-knowledge-base' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<!-- TAB: STYLING -->
				<div id="tab-styling" class="yukdigitalz-kb-tab-content">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="yukdigitalz_kb_primary_color"><?php esc_html_e( 'Primary Brand Color', 'yukdigitalz-knowledge-base' ); ?></label></th>
							<td>
								<input name="yukdigitalz_kb_primary_color" type="color" id="yukdigitalz_kb_primary_color" value="<?php echo esc_attr( get_option( 'yukdigitalz_kb_primary_color', '#2563eb' ) ); ?>" />
								<p class="description"><?php esc_html_e( 'Used for buttons, search borders, active navigation states, and text links.', 'yukdigitalz-knowledge-base' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="yukdigitalz_kb_secondary_color"><?php esc_html_e( 'Primary Hover Color', 'yukdigitalz-knowledge-base' ); ?></label></th>
							<td>
								<input name="yukdigitalz_kb_secondary_color" type="color" id="yukdigitalz_kb_secondary_color" value="<?php echo esc_attr( get_option( 'yukdigitalz_kb_secondary_color', '#1d4ed8' ) ); ?>" />
								<p class="description"><?php esc_html_e( 'Used for buttons and links hover transitions.', 'yukdigitalz-knowledge-base' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="yukdigitalz_kb_accent_color"><?php esc_html_e( 'Accent Highlight Color', 'yukdigitalz-knowledge-base' ); ?></label></th>
							<td>
								<input name="yukdigitalz_kb_accent_color" type="color" id="yukdigitalz_kb_accent_color" value="<?php echo esc_attr( get_option( 'yukdigitalz_kb_accent_color', '#f59e0b' ) ); ?>" />
								<p class="description"><?php esc_html_e( 'Used for tags, icons, notifications, and metadata highlights.', 'yukdigitalz-knowledge-base' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<!-- TAB: FEATURES -->
				<div id="tab-features" class="yukdigitalz-kb-tab-content">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Helpfulness Rating', 'yukdigitalz-knowledge-base' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php esc_html_e( 'Enable Helpfulness Rating', 'yukdigitalz-knowledge-base' ); ?></span></legend>
									<label for="yukdigitalz_kb_enable_feedback">
										<input name="yukdigitalz_kb_enable_feedback" type="checkbox" id="yukdigitalz_kb_enable_feedback" value="1" <?php checked( 1, get_option( 'yukdigitalz_kb_enable_feedback', 1 ) ); ?> />
										<?php esc_html_e( 'Display the helpfulness feedback widget (Helpful/Not Helpful) at the end of articles.', 'yukdigitalz-knowledge-base' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Table of Contents', 'yukdigitalz-knowledge-base' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php esc_html_e( 'Enable Table of Contents', 'yukdigitalz-knowledge-base' ); ?></span></legend>
									<label for="yukdigitalz_kb_enable_toc">
										<input name="yukdigitalz_kb_enable_toc" type="checkbox" id="yukdigitalz_kb_enable_toc" value="1" <?php checked( 1, get_option( 'yukdigitalz_kb_enable_toc', 1 ) ); ?> />
										<?php esc_html_e( 'Parse h2 and h3 heading tags to render a floating Table of Contents side-nav widget.', 'yukdigitalz-knowledge-base' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Breadcrumbs', 'yukdigitalz-knowledge-base' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php esc_html_e( 'Enable Breadcrumbs', 'yukdigitalz-knowledge-base' ); ?></span></legend>
									<label for="yukdigitalz_kb_enable_breadcrumbs">
										<input name="yukdigitalz_kb_enable_breadcrumbs" type="checkbox" id="yukdigitalz_kb_enable_breadcrumbs" value="1" <?php checked( 1, get_option( 'yukdigitalz_kb_enable_breadcrumbs', 1 ) ); ?> />
										<?php esc_html_e( 'Display hierarchical path links at the top of individual documentation pages.', 'yukdigitalz-knowledge-base' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Reading Time Indicator', 'yukdigitalz-knowledge-base' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php esc_html_e( 'Enable Reading Time Indicator', 'yukdigitalz-knowledge-base' ); ?></span></legend>
									<label for="yukdigitalz_kb_enable_reading_time">
										<input name="yukdigitalz_kb_enable_reading_time" type="checkbox" id="yukdigitalz_kb_enable_reading_time" value="1" <?php checked( 1, get_option( 'yukdigitalz_kb_enable_reading_time', 1 ) ); ?> />
										<?php esc_html_e( 'Display estimated reading time in minutes at the top of doc articles.', 'yukdigitalz-knowledge-base' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable AI Chat Assistant', 'yukdigitalz-knowledge-base' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php esc_html_e( 'Enable AI Chat Assistant', 'yukdigitalz-knowledge-base' ); ?></span></legend>
									<label for="yukdigitalz_kb_enable_ai_chat">
										<input name="yukdigitalz_kb_enable_ai_chat" type="checkbox" id="yukdigitalz_kb_enable_ai_chat" value="1" <?php checked( 1, get_option( 'yukdigitalz_kb_enable_ai_chat', 1 ) ); ?> />
										<?php esc_html_e( 'Embed a RAG-based AI Chat Assistant (using Gemini) inside the sidebar tabs.', 'yukdigitalz-knowledge-base' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Comments / Q&A Section', 'yukdigitalz-knowledge-base' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php esc_html_e( 'Enable Comments / Q&A Section', 'yukdigitalz-knowledge-base' ); ?></span></legend>
									<label for="yukdigitalz_kb_enable_comments">
										<input name="yukdigitalz_kb_enable_comments" type="checkbox" id="yukdigitalz_kb_enable_comments" value="1" <?php checked( 1, get_option( 'yukdigitalz_kb_enable_comments', 0 ) ); ?> />
										<?php esc_html_e( 'Enable native comments at the bottom of documentation pages for discussion/Q&A.', 'yukdigitalz-knowledge-base' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="yukdigitalz_kb_gemini_api_key"><?php esc_html_e( 'Gemini API Key', 'yukdigitalz-knowledge-base' ); ?></label></th>
							<td>
								<input name="yukdigitalz_kb_gemini_api_key" type="password" id="yukdigitalz_kb_gemini_api_key" value="<?php echo esc_attr( get_option( 'yukdigitalz_kb_gemini_api_key', '' ) ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Optional if native WordPress AI client is active', 'yukdigitalz-knowledge-base' ); ?>" autocomplete="new-password" />
								<p class="description"><?php esc_html_e( 'Direct fallback API Key for Google Gemini. If empty, the system tries to use WordPress native AI Client.', 'yukdigitalz-knowledge-base' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<!-- TAB: SECURITY & RATELIMIT -->
				<div id="tab-security" class="yukdigitalz-kb-tab-content">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable AI Chat Rate Limiting', 'yukdigitalz-knowledge-base' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php esc_html_e( 'Enable AI Chat Rate Limiting', 'yukdigitalz-knowledge-base' ); ?></span></legend>
									<label for="yukdigitalz_kb_enable_rate_limit">
										<input name="yukdigitalz_kb_enable_rate_limit" type="checkbox" id="yukdigitalz_kb_enable_rate_limit" value="1" <?php checked( 1, get_option( 'yukdigitalz_kb_enable_rate_limit', 1 ) ); ?> />
										<?php esc_html_e( 'Limit client requests from a single IP to protect API key quota and prevent spam.', 'yukdigitalz-knowledge-base' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="yukdigitalz_kb_rate_limit_count"><?php esc_html_e( 'Max Queries Per Hour', 'yukdigitalz-knowledge-base' ); ?></label></th>
							<td>
								<input name="yukdigitalz_kb_rate_limit_count" type="number" id="yukdigitalz_kb_rate_limit_count" value="<?php echo esc_attr( get_option( 'yukdigitalz_kb_rate_limit_count', 20 ) ); ?>" min="1" class="small-text" />
								<p class="description"><?php esc_html_e( 'The maximum number of questions an anonymous user (based on their Samaran/Hashed IP) can ask per hour.', 'yukdigitalz-knowledge-base' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<!-- TAB: CATEGORY ORDER -->
				<div id="tab-order" class="yukdigitalz-kb-tab-content">
					<h2><?php esc_html_e( 'Category Display Order', 'yukdigitalz-knowledge-base' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Drag and drop categories to reorder them, then click Save changes below to update their frontend display order.', 'yukdigitalz-knowledge-base' ); ?></p>
					
					<?php
					$categories = get_terms( array(
						'taxonomy'   => 'yukdigitalz_kb_cat',
						'hide_empty' => false,
					) );
					
					// Sort them first using the template sort helper
					if ( class_exists( 'YukdigitalzKnowledgeBase\Templates' ) ) {
						$categories = Templates::sort_categories( $categories );
					}
					
					if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) :
						?>
						<ul class="yukdigitalz-kb-order-list">
							<?php foreach ( $categories as $cat ) : ?>
								<li data-id="<?php echo esc_attr( $cat->term_id ); ?>">
									<?php echo esc_html( $cat->name ); ?>
								</li>
							<?php endforeach; ?>
						</ul>
						<input type="hidden" name="yukdigitalz_kb_category_order" id="yukdigitalz_kb_category_order_input" value="<?php echo esc_attr( wp_json_encode( get_option( 'yukdigitalz_kb_category_order', array() ) ) ); ?>" />
					<?php else : ?>
						<div style="margin-top: 20px; padding: 30px; background: #f8fafc; border: 1px dashed #ccd0d4; border-radius: 6px; text-align: center; color: #64748b;">
							<p style="margin: 0; font-size: 1rem; font-weight: 500;"><?php esc_html_e( 'No categories found. Create categories under the KB Docs menu first.', 'yukdigitalz-knowledge-base' ); ?></p>
						</div>
					<?php endif; ?>
				</div>

				<!-- TAB: ANALYTICS -->
				<div id="tab-analytics" class="yukdigitalz-kb-tab-content">
					<h2><?php esc_html_e( 'Search Analytics & Insights', 'yukdigitalz-knowledge-base' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Track what users search for and discover missing content (queries returning 0 results).', 'yukdigitalz-knowledge-base' ); ?></p>
					
					<?php
					$logs = get_option( 'yukdigitalz_kb_search_logs', array() );
					if ( ! empty( $logs ) && is_array( $logs ) ) :
						?>
						<table class="wp-list-table widefat fixed striped" style="margin-top: 20px; border-radius: 4px; overflow: hidden; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);">
							<thead>
								<tr>
									<th scope="col" style="font-weight: 700; padding: 12px;"><?php esc_html_e( 'Search Term', 'yukdigitalz-knowledge-base' ); ?></th>
									<th scope="col" style="font-weight: 700; padding: 12px; width: 100px; text-align: center;"><?php esc_html_e( 'Searches', 'yukdigitalz-knowledge-base' ); ?></th>
									<th scope="col" style="font-weight: 700; padding: 12px; width: 150px; text-align: center;"><?php esc_html_e( 'Last Result Count', 'yukdigitalz-knowledge-base' ); ?></th>
									<th scope="col" style="font-weight: 700; padding: 12px;"><?php esc_html_e( 'Last Searched', 'yukdigitalz-knowledge-base' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $logs as $term => $data ) : 
									$is_zero_result = (int) $data['results_count'] === 0;
									?>
									<tr>
										<td style="padding: 12px; font-weight: 500;">
											<?php echo esc_html( $term ); ?>
										</td>
										<td style="padding: 12px; text-align: center; font-weight: 600; color: #2563eb;">
											<?php echo esc_html( $data['hits'] ); ?>
										</td>
										<td style="padding: 12px; text-align: center;">
											<?php if ( $is_zero_result ) : ?>
												<span style="background: #fee2e2; color: #dc2626; padding: 3px 8px; border-radius: 9999px; font-size: 0.8rem; font-weight: 700; border: 1px solid #fecaca;">
													<?php esc_html_e( 'No Results', 'yukdigitalz-knowledge-base' ); ?>
												</span>
											<?php else : ?>
												<span style="background: #dcfce7; color: #16a34a; padding: 3px 8px; border-radius: 9999px; font-size: 0.8rem; font-weight: 700; border: 1px solid #bbf7d0;">
													<?php echo esc_html( $data['results_count'] ); ?>
												</span>
											<?php endif; ?>
										</td>
										<td style="padding: 12px; color: #64748b;">
											<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $data['last_searched'] ) ); ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						<div style="margin-top: 20px;">
							<button type="button" id="yukdigitalz-kb-clear-logs-btn" class="button button-secondary" style="border-color: #dc2626; color: #dc2626;">
								<?php esc_html_e( 'Clear Search Logs', 'yukdigitalz-knowledge-base' ); ?>
							</button>
						</div>
					<?php else : ?>
						<div style="margin-top: 20px; padding: 30px; background: #f8fafc; border: 1px dashed #ccd0d4; border-radius: 6px; text-align: center; color: #64748b;">
							<p style="margin: 0; font-size: 1rem; font-weight: 500;"><?php esc_html_e( 'No search query data logged yet.', 'yukdigitalz-knowledge-base' ); ?></p>
						</div>
					<?php endif; ?>
				</div>

				<!-- Action hook for PRO addon settings tab content -->
				<?php do_action( 'yukdigitalz_kb_render_tab_content' ); ?>

						<div class="yukdigitalz-kb-admin-footer">
							<?php submit_button(); ?>
						</div>
					</form>
				</main>
			</div>
		</div>
		<?php
	}
}
