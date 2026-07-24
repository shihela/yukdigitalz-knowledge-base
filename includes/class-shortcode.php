<?php
namespace YukdigitalzKnowledgeBase;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the [yukdigitalz_kb] shortcode output.
 */
class Shortcode {
	/**
	 * Register the shortcode.
	 */
	public function init() {
		add_shortcode( 'yukdigitalz_kb', array( $this, 'render_kb' ) );
	}

	/**
	 * Renders the main knowledge base portal interface.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_kb( $atts ) {
		// Ensure styles and scripts are loaded when the shortcode renders
		wp_enqueue_style( 'yukdigitalz-kb-public' );
		wp_enqueue_script( 'yukdigitalz-kb-public' );

		// Parse shortcode arguments
		$atts = shortcode_atts(
			array(
				'categories' => '',
				'limit'      => 5,
			),
			$atts,
			'yukdigitalz_kb'
		);

		// Fetch options
		$primary_color   = sanitize_hex_color( get_option( 'yukdigitalz_kb_primary_color', '#2563eb' ) ) ?: '#2563eb';
		$secondary_color = sanitize_hex_color( get_option( 'yukdigitalz_kb_secondary_color', '#1d4ed8' ) ) ?: '#1d4ed8';
		$accent_color    = sanitize_hex_color( get_option( 'yukdigitalz_kb_accent_color', '#f59e0b' ) ) ?: '#f59e0b';

		ob_start();
		?>
		<div class="yukdigitalz-kb-wrapper">
			<template shadowrootmode="open">
				<div class="yukdigitalz-kb-wrapper-inner">
			<!-- Header Search Section -->
			<header class="yukdigitalz-kb-header">
				<h1 class="yukdigitalz-kb-title"><?php esc_html_e( 'Documentation & Knowledge Base', 'yukdigitalz-knowledge-base' ); ?></h1>
				<p class="yukdigitalz-kb-subtitle"><?php esc_html_e( 'Find guides, tutorials, and answers to your questions.', 'yukdigitalz-knowledge-base' ); ?></p>
				
				<div class="yukdigitalz-kb-search-container">
					<form class="yukdigitalz-kb-search-form" action="" method="get" role="search" onsubmit="event.preventDefault();">
						<label for="yukdigitalz-kb-search-input" class="screen-reader-text"><?php esc_html_e( 'Search documentation', 'yukdigitalz-knowledge-base' ); ?></label>
						<span class="yukdigitalz-kb-search-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-search" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
						</span>
						<input type="search" id="yukdigitalz-kb-search-input" class="yukdigitalz-kb-search-input" placeholder="<?php esc_attr_e( 'Search for answers...', 'yukdigitalz-knowledge-base' ); ?>" autocomplete="off" aria-label="<?php esc_attr_e( 'Search for answers...', 'yukdigitalz-knowledge-base' ); ?>" />
						<div class="yukdigitalz-kb-search-spinner" style="display: none;" aria-hidden="true"></div>
					</form>
					<div class="yukdigitalz-kb-search-results" style="display: none;" aria-live="polite"></div>
				</div>
			</header>

			<!-- Categories Grid -->
			<main class="yukdigitalz-kb-categories-grid">
				<?php
				$taxonomy = 'yukdigitalz_kb_cat';
				$args = array(
					'taxonomy'   => $taxonomy,
					'parent'     => 0,
					'hide_empty' => true,
				);

				if ( ! empty( $atts['categories'] ) ) {
					$args['include'] = array_map( 'absint', explode( ',', $atts['categories'] ) );
					unset( $args['parent'] );
				}

				$categories = get_terms( $args );
				if ( ! is_wp_error( $categories ) ) {
					$categories = \YukdigitalzKnowledgeBase\Templates::sort_categories( $categories );
				}

				if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
					foreach ( $categories as $category ) {
						// Retrieve subcategories if any exist under this parent
						$sub_categories = get_terms( array(
							'taxonomy'   => $taxonomy,
							'parent'     => $category->term_id,
							'hide_empty' => true,
						) );
						if ( ! is_wp_error( $sub_categories ) ) {
							$sub_categories = \YukdigitalzKnowledgeBase\Templates::sort_categories( $sub_categories );
						}
						?>
						<div class="yukdigitalz-kb-category-card">
							<div class="yukdigitalz-kb-category-header">
								<span class="yukdigitalz-kb-category-icon" aria-hidden="true">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-folder"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
								</span>
								<div class="yukdigitalz-kb-category-info">
									<h2 class="yukdigitalz-kb-category-title">
										<a href="<?php echo esc_url( get_term_link( $category ) ); ?>">
											<?php echo esc_html( $category->name ); ?>
										</a>
									</h2>
									<span class="yukdigitalz-kb-category-count">
										<?php
										$cat_doc_count = \YukdigitalzKnowledgeBase\Templates::get_category_doc_count( $category );
										/* translators: %s: number of articles */
										printf( esc_html( _n( '%s Article', '%s Articles', $cat_doc_count, 'yukdigitalz-knowledge-base' ) ), esc_html( $cat_doc_count ) );
										?>
									</span>
								</div>
							</div>
							
							<?php if ( ! empty( $category->description ) ) : ?>
								<p class="yukdigitalz-kb-category-desc"><?php echo esc_html( wp_trim_words( $category->description, 15 ) ); ?></p>
							<?php endif; ?>

							<div class="yukdigitalz-kb-category-docs-wrapper">
								<?php
								if ( ! empty( $sub_categories ) && ! is_wp_error( $sub_categories ) ) {
									// Display Subcategories (e.g. Showcase Portfolio Addon) inside Parent Card
									foreach ( $sub_categories as $sub_cat ) {
										$sub_doc_query = new \WP_Query( array(
											'post_type'      => 'yukdigitalz_kb_doc',
											'post_status'    => 'publish',
											'posts_per_page' => absint( $atts['limit'] ),
											'tax_query'      => array(
												array(
													'taxonomy'         => $taxonomy,
													'field'            => 'term_id',
													'terms'            => $sub_cat->term_id,
													'include_children' => false,
												),
											),
										) );
										?>
										<div class="yukdigitalz-kb-portal-subcat-section">
											<h3 class="yukdigitalz-kb-portal-subcat-title">
												<a href="<?php echo esc_url( get_term_link( $sub_cat ) ); ?>">
													<?php echo esc_html( $sub_cat->name ); ?>
												</a>
											</h3>
											<ul class="yukdigitalz-kb-category-docs">
												<?php if ( $sub_doc_query->have_posts() ) : ?>
													<?php while ( $sub_doc_query->have_posts() ) : $sub_doc_query->the_post(); ?>
														<li>
															<a href="<?php echo esc_url( get_permalink() ); ?>">
																<span class="yukdigitalz-kb-doc-icon" aria-hidden="true">
																	<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file-text"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
																</span>
																<?php echo esc_html( get_the_title() ); ?>
															</a>
														</li>
													<?php endwhile; ?>
													<?php wp_reset_postdata(); ?>
												<?php else : ?>
													<li class="yukdigitalz-kb-no-docs"><?php esc_html_e( 'No articles in this category.', 'yukdigitalz-knowledge-base' ); ?></li>
												<?php endif; ?>
											</ul>
										</div>
										<?php
									}
								} else {
									// No subcategories - display direct articles
									$doc_query = new \WP_Query( array(
										'post_type'      => 'yukdigitalz_kb_doc',
										'post_status'    => 'publish',
										'posts_per_page' => absint( $atts['limit'] ),
										'tax_query'      => array(
											array(
												'taxonomy' => $taxonomy,
												'field'    => 'term_id',
												'terms'    => $category->term_id,
											),
										),
									) );
									?>
									<ul class="yukdigitalz-kb-category-docs">
										<?php if ( $doc_query->have_posts() ) : ?>
											<?php while ( $doc_query->have_posts() ) : $doc_query->the_post(); ?>
												<li>
													<a href="<?php echo esc_url( get_permalink() ); ?>">
														<span class="yukdigitalz-kb-doc-icon" aria-hidden="true">
															<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file-text"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
														</span>
														<?php echo esc_html( get_the_title() ); ?>
													</a>
												</li>
											<?php endwhile; ?>
											<?php wp_reset_postdata(); ?>
										<?php else : ?>
											<li class="yukdigitalz-kb-no-docs"><?php esc_html_e( 'No articles in this category.', 'yukdigitalz-knowledge-base' ); ?></li>
										<?php endif; ?>
									</ul>
									<?php
								}
								?>
							</div>

							<a href="<?php echo esc_url( get_term_link( $category ) ); ?>" class="yukdigitalz-kb-view-all">
								<?php esc_html_e( 'View All Articles', 'yukdigitalz-knowledge-base' ); ?> &rarr;
							</a>
						</div>
						<?php
					}
				} else {
					?>
					<div class="yukdigitalz-kb-no-categories">
						<p><?php esc_html_e( 'No documentation categories found. Create categories and write documentation in your admin panel.', 'yukdigitalz-knowledge-base' ); ?></p>
					</div>
					<?php
				}
				?>
			</main>
				</div>
			</template>
		</div>
		<?php
		return ob_get_clean();
	}
}
