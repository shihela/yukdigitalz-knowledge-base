<?php
namespace Shihela\YukdigitalzKnowledgeBase;

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
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-search" aria-hidden="true" style="width: 20px; height: 20px;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
						</span>
						<input type="search" id="yukdigitalz-kb-search-input" class="yukdigitalz-kb-search-input" placeholder="<?php esc_attr_e( 'Search for answers...', 'yukdigitalz-knowledge-base' ); ?>" autocomplete="off" aria-label="<?php esc_attr_e( 'Search for answers...', 'yukdigitalz-knowledge-base' ); ?>" />
						<div class="yukdigitalz-kb-search-spinner" style="display: none;" aria-hidden="true"></div>
					</form>
					<div class="yukdigitalz-kb-search-results" style="display: none;" aria-live="polite"></div>
				</div>
			</header>

			<!-- Categories & Product Sections -->
			<main class="yukdigitalz-kb-portal-container">
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
					$categories = \Shihela\YukdigitalzKnowledgeBase\Templates::sort_categories( $categories );
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
							$sub_categories = \Shihela\YukdigitalzKnowledgeBase\Templates::sort_categories( $sub_categories );
						}

						$cat_doc_count = \Shihela\YukdigitalzKnowledgeBase\Templates::get_category_doc_count( $category );
						?>
						<section class="yukdigitalz-kb-portal-section">
							<header class="yukdigitalz-kb-portal-section-header">
								<span class="yukdigitalz-kb-portal-section-icon" aria-hidden="true">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-folder" style="width: 24px; height: 24px;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
								</span>
								<div class="yukdigitalz-kb-portal-section-info">
									<h2 class="yukdigitalz-kb-portal-section-title">
										<a href="<?php echo esc_url( get_term_link( $category ) ); ?>">
											<?php echo esc_html( $category->name ); ?>
										</a>
									</h2>
									<span class="yukdigitalz-kb-portal-section-count">
										<?php
										/* translators: %s: number of articles */
										printf( esc_html( _n( '%s Article', '%s Articles', $cat_doc_count, 'yukdigitalz-knowledge-base' ) ), esc_html( $cat_doc_count ) );
										?>
									</span>
								</div>
							</header>

							<div class="yukdigitalz-kb-subcat-grid">
								<?php
								if ( ! empty( $sub_categories ) && ! is_wp_error( $sub_categories ) ) {
									// Display Subcategories as individual product cards in a responsive grid
									foreach ( $sub_categories as $sub_cat ) {
										$sub_count = \Shihela\YukdigitalzKnowledgeBase\Templates::get_category_doc_count( $sub_cat );
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
										<div class="yukdigitalz-kb-category-card">
											<div class="yukdigitalz-kb-category-header">
												<span class="yukdigitalz-kb-category-icon" aria-hidden="true">
													<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-box" style="width: 20px; height: 20px;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
												</span>
												<div class="yukdigitalz-kb-category-info">
													<h3 class="yukdigitalz-kb-category-title">
														<a href="<?php echo esc_url( get_term_link( $sub_cat ) ); ?>">
															<?php echo esc_html( $sub_cat->name ); ?>
														</a>
													</h3>
													<span class="yukdigitalz-kb-category-count">
														<?php
														/* translators: %s: number of articles */
														printf( esc_html( _n( '%s Article', '%s Articles', $sub_count, 'yukdigitalz-knowledge-base' ) ), esc_html( $sub_count ) );
														?>
													</span>
												</div>
											</div>

											<?php if ( ! empty( $sub_cat->description ) ) : ?>
												<p class="yukdigitalz-kb-category-desc"><?php echo esc_html( wp_trim_words( $sub_cat->description, 15 ) ); ?></p>
											<?php endif; ?>

											<ul class="yukdigitalz-kb-category-docs">
												<?php if ( $sub_doc_query->have_posts() ) : ?>
													<?php while ( $sub_doc_query->have_posts() ) : $sub_doc_query->the_post(); ?>
														<li>
															<a href="<?php echo esc_url( get_permalink() ); ?>">
																<span class="yukdigitalz-kb-doc-icon" aria-hidden="true">
																	<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file-text" style="width: 16px; height: 16px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
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

											<a href="<?php echo esc_url( get_term_link( $sub_cat ) ); ?>" class="yukdigitalz-kb-view-all">
												<?php
												/* translators: %s: number of total articles */
												printf( esc_html__( 'View All %s Articles', 'yukdigitalz-knowledge-base' ), esc_html( $sub_count ) );
												?> &rarr;
											</a>
										</div>
										<?php
									}
								} else {
									// No subcategories - display top category direct articles as a card
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
									<div class="yukdigitalz-kb-category-card">
										<div class="yukdigitalz-kb-category-header">
											<span class="yukdigitalz-kb-category-icon" aria-hidden="true">
												<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-folder" style="width: 24px; height: 24px;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
											</span>
											<div class="yukdigitalz-kb-category-info">
												<h3 class="yukdigitalz-kb-category-title">
													<a href="<?php echo esc_url( get_term_link( $category ) ); ?>">
														<?php echo esc_html( $category->name ); ?>
													</a>
												</h3>
												<span class="yukdigitalz-kb-category-count">
													<?php
													/* translators: %s: number of articles */
													printf( esc_html( _n( '%s Article', '%s Articles', $cat_doc_count, 'yukdigitalz-knowledge-base' ) ), esc_html( $cat_doc_count ) );
													?>
												</span>
											</div>
										</div>

										<?php if ( ! empty( $category->description ) ) : ?>
											<p class="yukdigitalz-kb-category-desc"><?php echo esc_html( wp_trim_words( $category->description, 15 ) ); ?></p>
										<?php endif; ?>

										<ul class="yukdigitalz-kb-category-docs">
											<?php if ( $doc_query->have_posts() ) : ?>
												<?php while ( $doc_query->have_posts() ) : $doc_query->the_post(); ?>
													<li>
														<a href="<?php echo esc_url( get_permalink() ); ?>">
															<span class="yukdigitalz-kb-doc-icon" aria-hidden="true">
																<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file-text" style="width: 16px; height: 16px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
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

										<a href="<?php echo esc_url( get_term_link( $category ) ); ?>" class="yukdigitalz-kb-view-all">
											<?php
											/* translators: %s: number of total articles */
											printf( esc_html__( 'View All %s Articles', 'yukdigitalz-knowledge-base' ), esc_html( $cat_doc_count ) );
											?> &rarr;
										</a>
									</div>
									<?php
								}
								?>
							</div>
						</section>
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
