<?php
/**
 * The template for displaying Yukdigitalz KB category taxonomy archives.
 * Features smart hierarchical branching:
 * - Parent Categories (e.g. /docs/plugin/): Displays an interactive Product Directory Grid.
 * - Child Categories (e.g. /docs/plugin/ai-sectionblocks/): Displays the Product Documentation Hub.
 *
 * @package YukdigitalzKnowledgeBase
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

\Shihela\YukdigitalzKnowledgeBase\Templates::get_header();

$yukdigitalz_kb_current_term    = get_queried_object();
$yukdigitalz_kb_current_term_id = $yukdigitalz_kb_current_term->term_id;
$yukdigitalz_kb_enable_ai_chat  = get_option( 'yukdigitalz_kb_enable_ai_chat', 1 );
$yukdigitalz_kb_primary_color   = sanitize_hex_color( get_option( 'yukdigitalz_kb_primary_color', '#2563eb' ) ) ?: '#2563eb';
$yukdigitalz_kb_secondary_color = sanitize_hex_color( get_option( 'yukdigitalz_kb_secondary_color', '#1d4ed8' ) ) ?: '#1d4ed8';
$yukdigitalz_kb_accent_color    = sanitize_hex_color( get_option( 'yukdigitalz_kb_accent_color', '#f59e0b' ) ) ?: '#f59e0b';

// Check if this category has child categories (Parent Category / Product Group)
$yukdigitalz_kb_subcats = get_terms( array(
	'taxonomy'   => 'yukdigitalz_kb_cat',
	'parent'     => $yukdigitalz_kb_current_term_id,
	'hide_empty' => false,
) );

$yukdigitalz_kb_is_parent = false;
if ( ! empty( $yukdigitalz_kb_subcats ) && ! is_wp_error( $yukdigitalz_kb_subcats ) ) {
	$yukdigitalz_kb_subcats   = \Shihela\YukdigitalzKnowledgeBase\Templates::sort_categories( $yukdigitalz_kb_subcats );
	$yukdigitalz_kb_is_parent = true;
}
?>

<div class="yukdigitalz-kb-doc-layout">
	<div class="yukdigitalz-kb-doc-layout-inner">
	<!-- Left Sidebar: Categories Navigation Tree -->
	<aside class="yukdigitalz-kb-sidebar-nav" aria-label="<?php esc_attr_e( 'Documentation Navigation', 'yukdigitalz-knowledge-base' ); ?>">
		<button type="button" class="yukdigitalz-kb-mobile-nav-toggle" aria-expanded="false">
			<span><?php esc_html_e( 'Browse Categories', 'yukdigitalz-knowledge-base' ); ?></span>
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="yukdigitalz-kb-mobile-chevron"><polyline points="6 9 12 15 18 9"></polyline></svg>
		</button>
		<h2 class="yukdigitalz-kb-sidebar-title"><?php esc_html_e( 'Categories', 'yukdigitalz-knowledge-base' ); ?></h2>
		<div class="yukdigitalz-kb-sidebar-accordion">
			<?php \Shihela\YukdigitalzKnowledgeBase\Templates::render_sidebar_navigation( 0, $yukdigitalz_kb_current_term_id ); ?>
		</div>
	</aside>

	<!-- Central Section: Category Content -->
	<section class="yukdigitalz-kb-article-container">
		<!-- Hierarchical Breadcrumbs -->
		<nav class="yukdigitalz-kb-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'yukdigitalz-knowledge-base' ); ?>">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'yukdigitalz-knowledge-base' ); ?></a>
			<span class="yukdigitalz-kb-breadcrumb-sep" aria-hidden="true">/</span>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'yukdigitalz_kb_doc' ) ); ?>"><?php esc_html_e( 'Docs', 'yukdigitalz-knowledge-base' ); ?></a>
			<?php
			// Render parent categories chain if nested
			if ( ! empty( $yukdigitalz_kb_current_term->parent ) ) {
				$yukdigitalz_kb_ancestors = get_ancestors( $yukdigitalz_kb_current_term->term_id, 'yukdigitalz_kb_cat', 'taxonomy' );
				if ( ! empty( $yukdigitalz_kb_ancestors ) && is_array( $yukdigitalz_kb_ancestors ) ) {
					$yukdigitalz_kb_ancestors = array_reverse( $yukdigitalz_kb_ancestors );
					foreach ( $yukdigitalz_kb_ancestors as $yukdigitalz_kb_ancestor_id ) {
						$yukdigitalz_kb_ancestor_term = get_term( $yukdigitalz_kb_ancestor_id, 'yukdigitalz_kb_cat' );
						if ( $yukdigitalz_kb_ancestor_term && ! is_wp_error( $yukdigitalz_kb_ancestor_term ) ) {
							echo '<span class="yukdigitalz-kb-breadcrumb-sep" aria-hidden="true">/</span>';
							echo '<a href="' . esc_url( get_term_link( $yukdigitalz_kb_ancestor_term ) ) . '">' . esc_html( $yukdigitalz_kb_ancestor_term->name ) . '</a>';
						}
					}
				}
			}
			?>
			<span class="yukdigitalz-kb-breadcrumb-sep" aria-hidden="true">/</span>
			<span class="yukdigitalz-kb-breadcrumb-current" aria-current="page"><?php echo esc_html( $yukdigitalz_kb_current_term->name ); ?></span>
		</nav>

		<!-- Archive Header -->
		<header class="yukdigitalz-kb-archive-header">
			<div class="yukdigitalz-kb-article-header-left">
				<h1 class="yukdigitalz-kb-article-title"><?php echo esc_html( $yukdigitalz_kb_current_term->name ); ?></h1>
				<?php if ( ! empty( $yukdigitalz_kb_current_term->description ) ) : ?>
					<p class="yukdigitalz-kb-category-description" style="color: var(--yukdigitalz-kb-text-muted); font-size: 1.05rem; margin-top: 8px; line-height: 1.6;"><?php echo esc_html( $yukdigitalz_kb_current_term->description ); ?></p>
				<?php elseif ( $yukdigitalz_kb_is_parent ) : ?>
					<p class="yukdigitalz-kb-category-description" style="color: var(--yukdigitalz-kb-text-muted); font-size: 1.05rem; margin-top: 8px; line-height: 1.6;"><?php esc_html_e( 'Select a product below to explore comprehensive guides and technical documentation.', 'yukdigitalz-knowledge-base' ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( $yukdigitalz_kb_enable_ai_chat ) : ?>
				<button type="button" class="yukdigitalz-kb-ai-header-btn" aria-label="<?php esc_attr_e( 'Ask AI about this category', 'yukdigitalz-knowledge-base' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="14" height="14" aria-hidden="true"><path d="M12 2l2.4 7.2L22 12l-7.6 2.4-2.4 7.2-2.4-7.2L2 12l7.6-2.4z"/></svg>
					<span><?php esc_html_e( 'Ask AI', 'yukdigitalz-knowledge-base' ); ?></span>
				</button>
			<?php endif; ?>
		</header>

		<?php if ( $yukdigitalz_kb_is_parent ) : ?>
			<!-- VIEW A: PARENT CATEGORY DIRECTORY (Product Cards Showcase Grid) -->
			<div class="yukdigitalz-kb-product-grid">
				<?php foreach ( $yukdigitalz_kb_subcats as $yukdigitalz_kb_sub_cat ) :
					$yukdigitalz_kb_sub_doc_count = \Shihela\YukdigitalzKnowledgeBase\Templates::get_category_doc_count( $yukdigitalz_kb_sub_cat );
					$yukdigitalz_kb_recent_docs_query = new \WP_Query( array(
						'post_type'      => 'yukdigitalz_kb_doc',
						'post_status'    => 'publish',
						'posts_per_page' => 3,
						'tax_query'      => array(
							array(
								'taxonomy'         => 'yukdigitalz_kb_cat',
								'field'            => 'term_id',
								'terms'            => $yukdigitalz_kb_sub_cat->term_id,
								'include_children' => false,
							),
						),
					) );
					?>
					<div class="yukdigitalz-kb-product-card">
						<div class="yukdigitalz-kb-product-card-top">
							<div class="yukdigitalz-kb-product-card-badge-row">
								<div class="yukdigitalz-kb-product-icon">
									<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-box"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
								</div>
								<span class="yukdigitalz-kb-product-badge">
									<?php
									/* translators: %s: number of articles */
									printf( esc_html( _n( '%s Guide', '%s Guides', $yukdigitalz_kb_sub_doc_count, 'yukdigitalz-knowledge-base' ) ), esc_html( $yukdigitalz_kb_sub_doc_count ) );
									?>
								</span>
							</div>

							<h2 class="yukdigitalz-kb-product-title">
								<a href="<?php echo esc_url( get_term_link( $yukdigitalz_kb_sub_cat ) ); ?>">
									<?php echo esc_html( $yukdigitalz_kb_sub_cat->name ); ?>
								</a>
							</h2>

							<?php if ( ! empty( $yukdigitalz_kb_sub_cat->description ) ) : ?>
								<p class="yukdigitalz-kb-product-desc">
									<?php echo esc_html( wp_trim_words( $yukdigitalz_kb_sub_cat->description, 16 ) ); ?>
								</p>
							<?php endif; ?>

							<?php if ( $yukdigitalz_kb_recent_docs_query->have_posts() ) : ?>
								<ul class="yukdigitalz-kb-product-recent-list">
									<?php while ( $yukdigitalz_kb_recent_docs_query->have_posts() ) : $yukdigitalz_kb_recent_docs_query->the_post(); ?>
										<li>
											<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
											<a href="<?php echo esc_url( get_permalink() ); ?>">
												<?php echo esc_html( get_the_title() ); ?>
											</a>
										</li>
									<?php endwhile; wp_reset_postdata(); ?>
								</ul>
							<?php endif; ?>
						</div>

						<a href="<?php echo esc_url( get_term_link( $yukdigitalz_kb_sub_cat ) ); ?>" class="yukdigitalz-kb-product-btn">
							<span><?php esc_html_e( 'Explore Documentation', 'yukdigitalz-knowledge-base' ); ?></span>
							<span aria-hidden="true">&rarr;</span>
						</a>
					</div>
				<?php endforeach; ?>
			</div>

			<?php
			// Check if parent category also has direct posts assigned directly to it
			$yukdigitalz_kb_parent_direct_query = new \WP_Query( array(
				'post_type'      => 'yukdigitalz_kb_doc',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'tax_query'      => array(
					array(
						'taxonomy'         => 'yukdigitalz_kb_cat',
						'field'            => 'term_id',
						'terms'            => $yukdigitalz_kb_current_term_id,
						'include_children' => false,
					),
				),
			) );

			if ( $yukdigitalz_kb_parent_direct_query->have_posts() ) :
			?>
				<div class="yukdigitalz-kb-parent-direct-section" style="margin-top: 40px;">
					<h2 style="font-size: 1.3rem; margin-bottom: 16px; font-weight: 700;"><?php esc_html_e( 'General Guides', 'yukdigitalz-knowledge-base' ); ?></h2>
					<div class="yukdigitalz-kb-category-archive-list" style="display: flex; flex-direction: column; gap: 16px;">
						<?php while ( $yukdigitalz_kb_parent_direct_query->have_posts() ) : $yukdigitalz_kb_parent_direct_query->the_post(); ?>
							<article class="yukdigitalz-kb-archive-item-card" style="padding: 20px; border: 1px solid var(--yukdigitalz-kb-border); border-radius: var(--yukdigitalz-kb-radius-sm); background: var(--yukdigitalz-kb-card-bg);">
								<h3 style="margin: 0 0 8px 0; font-size: 1.15rem;">
									<a href="<?php echo esc_url( get_permalink() ); ?>" style="color: var(--yukdigitalz-kb-text-main); text-decoration: none;">
										<?php echo esc_html( get_the_title() ); ?>
									</a>
								</h3>
								<p style="margin: 0 0 12px 0; color: var(--yukdigitalz-kb-text-muted); font-size: 0.9rem;">
									<?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?>
								</p>
								<a href="<?php echo esc_url( get_permalink() ); ?>" style="color: var(--yukdigitalz-kb-primary); text-decoration: none; font-weight: 600; font-size: 0.88rem;">
									<?php esc_html_e( 'Read Article', 'yukdigitalz-knowledge-base' ); ?> &rarr;
								</a>
							</article>
						<?php endwhile; wp_reset_postdata(); ?>
					</div>
				</div>
			<?php endif; ?>

		<?php else : ?>
			<!-- VIEW B: CHILD / PRODUCT DOCUMENTATION HUB (List of Specific Guides) -->
			<div class="yukdigitalz-kb-category-archive-list" style="display: flex; flex-direction: column; gap: 20px;">
				<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
					<article class="yukdigitalz-kb-archive-item-card" style="padding: 24px; border: 1px solid var(--yukdigitalz-kb-border); border-radius: var(--yukdigitalz-kb-radius-sm); transition: var(--yukdigitalz-kb-transition-smooth); background: var(--yukdigitalz-kb-card-bg);">
						<h2 class="yukdigitalz-kb-archive-item-title" style="margin: 0 0 10px 0; font-size: 1.25rem; font-weight: 700;">
							<a href="<?php echo esc_url( get_permalink() ); ?>" style="color: var(--yukdigitalz-kb-text-main); text-decoration: none; transition: var(--yukdigitalz-kb-transition-smooth);">
								<?php echo esc_html( get_the_title() ); ?>
							</a>
						</h2>
						<p style="margin: 0 0 16px 0; color: var(--yukdigitalz-kb-text-muted); font-size: 0.95rem; line-height: 1.6;">
							<?php echo esc_html( wp_trim_words( get_the_excerpt(), 25 ) ); ?>
						</p>
						<a href="<?php echo esc_url( get_permalink() ); ?>" style="color: var(--yukdigitalz-kb-primary); text-decoration: none; font-weight: 600; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 4px;">
							<?php esc_html_e( 'Read Article', 'yukdigitalz-knowledge-base' ); ?> &rarr;
						</a>
					</article>
				<?php endwhile; ?>
					<!-- Pagination -->
					<nav class="yukdigitalz-kb-pagination" aria-label="<?php esc_attr_e( 'Pagination', 'yukdigitalz-knowledge-base' ); ?>" style="margin-top: 32px;">
						<?php
						$yukdigitalz_kb_pagination_links = paginate_links( array(
							'type'      => 'list',
							'prev_text' => '&larr; ' . __( 'Previous', 'yukdigitalz-knowledge-base' ),
							'next_text' => __( 'Next', 'yukdigitalz-knowledge-base' ) . ' &rarr;',
						) );

						if ( is_string( $yukdigitalz_kb_pagination_links ) && '' !== trim( $yukdigitalz_kb_pagination_links ) ) {
							echo wp_kses_post( $yukdigitalz_kb_pagination_links );
						}
						?>
					</nav>
				<?php else : ?>
					<div class="yukdigitalz-kb-empty-box" style="padding: 36px; text-align: center; background: var(--yukdigitalz-kb-card-bg); border: 1px dashed var(--yukdigitalz-kb-border); border-radius: var(--yukdigitalz-kb-radius-md);">
						<p style="color: var(--yukdigitalz-kb-text-muted); margin: 0; font-size: 1rem;"><?php esc_html_e( 'No documentation articles found in this category.', 'yukdigitalz-knowledge-base' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</section>

	<!-- Right Sidebar: Floating AI Chat Drawer -->
	<?php if ( $yukdigitalz_kb_enable_ai_chat ) : ?>
		<button id="yukdigitalz-kb-ai-trigger" class="yukdigitalz-kb-ai-trigger-fab" aria-label="<?php esc_attr_e( 'Ask AI', 'yukdigitalz-knowledge-base' ); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-aperture" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="14.31" y1="8" x2="20.05" y2="17.94"></line><line x1="9.69" y1="8" x2="21.17" y2="8"></line><line x1="7.38" y1="12" x2="13.12" y2="2.06"></line><line x1="9.69" y1="16" x2="3.95" y2="6.06"></line><line x1="14.31" y1="16" x2="2.83" y2="16"></line><line x1="16.62" y1="12" x2="10.88" y2="21.94"></line></svg>
		</button>

		<div id="yukdigitalz-kb-ai-backdrop" class="yukdigitalz-kb-ai-backdrop" aria-hidden="true"></div>

		<div id="yukdigitalz-kb-ai-drawer" class="yukdigitalz-kb-ai-drawer" aria-hidden="true">
			<div class="yukdigitalz-kb-ai-drawer-header">
				<div class="yukdigitalz-kb-ai-drawer-title">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-aperture" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="14.31" y1="8" x2="20.05" y2="17.94"></line><line x1="9.69" y1="8" x2="21.17" y2="8"></line><line x1="7.38" y1="12" x2="13.12" y2="2.06"></line><line x1="9.69" y1="16" x2="3.95" y2="6.06"></line><line x1="14.31" y1="16" x2="2.83" y2="16"></line><line x1="16.62" y1="12" x2="10.88" y2="21.94"></line></svg>
					<span><?php esc_html_e( 'Yukdigitalz KB AI Assistant', 'yukdigitalz-knowledge-base' ); ?></span>
				</div>
				<button id="yukdigitalz-kb-ai-close" class="yukdigitalz-kb-ai-close-btn" aria-label="<?php esc_attr_e( 'Close drawer', 'yukdigitalz-knowledge-base' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
				</button>
			</div>
			
			<div class="yukdigitalz-kb-ai-chat-container">
				<div class="yukdigitalz-kb-ai-chat-history">
					<div class="yukdigitalz-kb-chat-message assistant">
						<div class="yukdigitalz-kb-chat-bubble">
							<?php esc_html_e( 'Hello! I am the Yukdigitalz KB AI Assistant. How can I help you with our documentation today?', 'yukdigitalz-knowledge-base' ); ?>
						</div>
					</div>
				</div>
				<form class="yukdigitalz-kb-ai-chat-form" onsubmit="event.preventDefault();">
					<input type="text" placeholder="<?php esc_attr_e( 'Ask a question...', 'yukdigitalz-knowledge-base' ); ?>" class="yukdigitalz-kb-ai-chat-input" required />
					<button type="submit" class="yukdigitalz-kb-ai-chat-submit" aria-label="<?php esc_attr_e( 'Send message', 'yukdigitalz-knowledge-base' ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-send" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
					</button>
				</form>
			</div>
		</div>
	<?php endif; ?>
	</div>
</div>

<?php
\Shihela\YukdigitalzKnowledgeBase\Templates::get_footer();
