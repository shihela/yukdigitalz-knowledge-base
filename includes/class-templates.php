<?php
namespace Shihela\YukdigitalzKnowledgeBase;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles template routing, allowing fallback to plugin templates
 * when themes do not provide custom overrides.
 */
class Templates {
	/**
	 * Hook template redirects.
	 */
	public function init() {
		add_filter( 'single_template', array( $this, 'load_single_template' ) );
		add_filter( 'archive_template', array( $this, 'load_archive_template' ) );
		add_filter( 'taxonomy_template', array( $this, 'load_taxonomy_template' ) );
		add_action( 'pre_get_posts', array( $this, 'adjust_archive_query' ) );
	}

	/**
	 * Adjusts post limit per page on Knowledge Base archives and category pages for clean pagination.
	 *
	 * @param \WP_Query $query The main WP_Query instance.
	 */
	public function adjust_archive_query( $query ) {
		if ( ! is_admin() && $query->is_main_query() ) {
			if ( $query->is_tax( 'yukdigitalz_kb_cat' ) || $query->is_tax( 'yukdigitalz_kb_tag' ) || $query->is_post_type_archive( 'yukdigitalz_kb_doc' ) ) {
				$posts_per_page = (int) get_option( 'yukdigitalz_kb_archive_posts_per_page', 6 );
				if ( $posts_per_page < 1 ) {
					$posts_per_page = 6;
				}
				$query->set( 'posts_per_page', $posts_per_page );
			}
		}
	}

	/**
	 * Renders single doc pages with template fallbacks.
	 *
	 * @param string $template Current template path.
	 * @return string Filtered template path.
	 */
	public function load_single_template( $template ) {
		if ( get_post_type() === 'yukdigitalz_kb_doc' ) {
			// Locate theme-level overrides first
			$theme_file = locate_template( array( 'single-yukdigitalz_kb_doc.php' ) );
			if ( ! $theme_file ) {
				return YUKDIGITALZ_KB_PATH . 'templates/single-doc.php';
			}
		}
		return $template;
	}

	/**
	 * Renders doc archive list pages with template fallbacks.
	 *
	 * @param string $template Current template path.
	 * @return string Filtered template path.
	 */
	public function load_archive_template( $template ) {
		if ( is_post_type_archive( 'yukdigitalz_kb_doc' ) ) {
			$theme_file = locate_template( array( 'archive-yukdigitalz_kb_doc.php' ) );
			if ( ! $theme_file ) {
				return YUKDIGITALZ_KB_PATH . 'templates/archive-doc.php';
			}
		}
		return $template;
	}

	/**
	 * Renders category taxonomy pages with template fallbacks.
	 *
	 * @param string $template Current template path.
	 * @return string Filtered template path.
	 */
	public function load_taxonomy_template( $template ) {
		if ( is_tax( 'yukdigitalz_kb_cat' ) ) {
			$theme_file = locate_template( array( 'taxonomy-yukdigitalz_kb_cat.php' ) );
			if ( ! $theme_file ) {
				return YUKDIGITALZ_KB_PATH . 'templates/taxonomy-doc-cat.php';
			}
		}
		return $template;
	}

	/**
	 * Safe header loader supporting both classic and custom/FSE themes.
	 *
	 * @param string|null $name Name of the specific header template.
	 * @param array       $args Arguments to pass to the header template.
	 */
	public static function get_header( $name = null, $args = array() ) {
		// Jika tema mendukung FSE (Block Theme) atau memiliki file header.php fisik
		if ( function_exists( 'wp_is_block_theme' ) && call_user_func( 'wp_is_block_theme' ) ) {
			get_header( $name, $args );
		} elseif ( ! empty( locate_template( 'header.php' ) ) ) {
			get_header( $name, $args );
		} else {
			?>
			<!DOCTYPE html>
			<html <?php language_attributes(); ?>>
			<head>
				<meta charset="<?php bloginfo( 'charset' ); ?>">
				<meta name="viewport" content="width=device-width, initial-scale=1">
				<?php wp_head(); ?>
			</head>
			<body <?php body_class(); ?>>
			<?php
			wp_body_open();
		}
	}

	/**
	 * Safe footer loader supporting both classic and custom/FSE themes.
	 *
	 * @param string|null $name Name of the specific footer template.
	 * @param array       $args Arguments to pass to the footer template.
	 */
	public static function get_footer( $name = null, $args = array() ) {
		// Jika tema mendukung FSE (Block Theme) atau memiliki file footer.php fisik
		if ( function_exists( 'wp_is_block_theme' ) && call_user_func( 'wp_is_block_theme' ) ) {
			get_footer( $name, $args );
		} elseif ( ! empty( locate_template( 'footer.php' ) ) ) {
			get_footer( $name, $args );
		} else {
			/**
			 * Prevents the WordPress deprecated message when the theme has no footer.php
			 * You must call wp_footer() so other scripts/styles can load properly.
			 */
			wp_footer();
			echo '</body></html>'; // Close standard tags
		}
	}

	/**
	 * Sorts category term objects dynamically based on the configured category order option.
	 *
	 * @param array $categories List of taxonomy term objects.
	 * @return array Sorted list of terms.
	 */
	public static function sort_categories( $categories ) {
		if ( empty( $categories ) || is_wp_error( $categories ) ) {
			return $categories;
		}

		$order = get_option( 'yukdigitalz_kb_category_order', array() );
		if ( empty( $order ) || ! is_array( $order ) ) {
			return $categories;
		}

		usort( $categories, function( $a, $b ) use ( $order ) {
			$pos_a = array_search( $a->term_id, $order );
			$pos_b = array_search( $b->term_id, $order );

			if ( $pos_a === false ) {
				$pos_a = 9999;
			}
			if ( $pos_b === false ) {
				$pos_b = 9999;
			}

			if ( $pos_a === $pos_b ) {
				return strcasecmp( $a->name, $b->name );
			}

			return $pos_a - $pos_b;
		} );

		return $categories;
	}

	/**
	 * Calculates total article count for a category term, recursively including child categories.
	 *
	 * @param \WP_Term|int $term Category term or term ID.
	 * @return int Total number of published articles.
	 */
	public static function get_category_doc_count( $term ) {
		$term_id = is_object( $term ) ? $term->term_id : absint( $term );

		$query = new \WP_Query( array(
			'post_type'              => 'yukdigitalz_kb_doc',
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'tax_query'              => array(
				array(
					'taxonomy'         => 'yukdigitalz_kb_cat',
					'field'            => 'term_id',
					'terms'            => $term_id,
					'include_children' => true,
				),
			),
		) );

		return (int) $query->found_posts;
	}

	/**
	 * Renders hierarchical sidebar category navigation (Parent Categories -> Child Categories -> Articles).
	 *
	 * @param int $current_post_id Active post ID (if on single doc page).
	 * @param int $current_term_id Active term ID (if on taxonomy page).
	 */
	public static function render_sidebar_navigation( $current_post_id = 0, $current_term_id = 0 ) {
		$top_categories = get_terms( array(
			'taxonomy'   => 'yukdigitalz_kb_cat',
			'parent'     => 0,
			'hide_empty' => true,
		) );
		$top_categories = self::sort_categories( $top_categories );

		if ( empty( $top_categories ) || is_wp_error( $top_categories ) ) {
			echo '<p class="yukdigitalz-kb-no-data">' . esc_html__( 'No categories found.', 'yukdigitalz-knowledge-base' ) . '</p>';
			return;
		}

		foreach ( $top_categories as $top_cat ) {
			// Check for child categories under this top category
			$child_categories = get_terms( array(
				'taxonomy'   => 'yukdigitalz_kb_cat',
				'parent'     => $top_cat->term_id,
				'hide_empty' => true,
			) );
			$child_categories = self::sort_categories( $child_categories );

			$group_id = 'cat-group-' . $top_cat->term_id;
			$is_top_active = ( $top_cat->term_id === $current_term_id );

			// Check if active post or active category is inside this group
			$has_active_child = false;
			if ( ! empty( $child_categories ) && ! is_wp_error( $child_categories ) ) {
				foreach ( $child_categories as $child_cat ) {
					if ( $child_cat->term_id === $current_term_id ) {
						$has_active_child = true;
						break;
					}
				}
			}

			?>
			<div class="yukdigitalz-kb-sidebar-cat-group">
				<button class="yukdigitalz-kb-sidebar-cat-header <?php echo ( $is_top_active || $has_active_child ) ? 'is-expanded' : ''; ?>" data-target="<?php echo esc_attr( $group_id ); ?>" aria-expanded="<?php echo ( $is_top_active || $has_active_child ) ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $group_id ); ?>">
					<span><?php echo esc_html( $top_cat->name ); ?></span>
					<span class="yukdigitalz-kb-chevron-icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg>
					</span>
				</button>
				
				<div id="<?php echo esc_attr( $group_id ); ?>" class="yukdigitalz-kb-sidebar-articles" role="region" aria-label="<?php echo esc_attr( $top_cat->name ); ?>">
					<?php
					if ( ! empty( $child_categories ) && ! is_wp_error( $child_categories ) ) {
						// Render Child Categories under this Parent Category
						foreach ( $child_categories as $child_cat ) {
							$sub_group_id = 'subcat-group-' . $child_cat->term_id;
							$is_child_active = ( $child_cat->term_id === $current_term_id );

							// Query posts for this specific child category (don't include sub-children)
							$child_docs_query = new \WP_Query( array(
								'post_type'      => 'yukdigitalz_kb_doc',
								'post_status'    => 'publish',
								'posts_per_page' => -1,
								'tax_query'      => array(
									array(
										'taxonomy'         => 'yukdigitalz_kb_cat',
										'field'            => 'term_id',
										'terms'            => $child_cat->term_id,
										'include_children' => false,
									),
								),
							) );

							// Check if active post is inside this child category
							$child_has_active_post = false;
							if ( $child_docs_query->have_posts() ) {
								foreach ( $child_docs_query->posts as $p ) {
									if ( $p->ID === $current_post_id ) {
										$child_has_active_post = true;
										break;
									}
								}
							}
							?>
							<div class="yukdigitalz-kb-sidebar-subcat-group">
								<button class="yukdigitalz-kb-sidebar-cat-header yukdigitalz-kb-sidebar-subcat-header <?php echo ( $is_child_active || $child_has_active_post ) ? 'is-expanded' : ''; ?>" data-target="<?php echo esc_attr( $sub_group_id ); ?>" aria-expanded="<?php echo ( $is_child_active || $child_has_active_post ) ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $sub_group_id ); ?>">
									<span><?php echo esc_html( $child_cat->name ); ?></span>
									<span class="yukdigitalz-kb-chevron-icon" aria-hidden="true">
										<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg>
									</span>
								</button>
								<ul id="<?php echo esc_attr( $sub_group_id ); ?>" class="yukdigitalz-kb-sidebar-articles yukdigitalz-kb-sidebar-subcat-articles" role="region" aria-label="<?php echo esc_attr( $child_cat->name ); ?>">
									<?php if ( $child_docs_query->have_posts() ) : ?>
										<?php while ( $child_docs_query->have_posts() ) : $child_docs_query->the_post(); ?>
											<?php $is_active = ( get_the_ID() === $current_post_id ); ?>
											<li class="<?php echo $is_active ? 'yukdigitalz-kb-active-article' : ''; ?>">
												<a href="<?php echo esc_url( get_permalink() ); ?>" <?php echo $is_active ? 'aria-current="page"' : ''; ?>>
													<?php echo esc_html( get_the_title() ); ?>
												</a>
											</li>
										<?php endwhile; ?>
										<?php wp_reset_postdata(); ?>
									<?php else : ?>
										<li><?php esc_html_e( 'No articles.', 'yukdigitalz-knowledge-base' ); ?></li>
									<?php endif; ?>
								</ul>
							</div>
							<?php
						}

						// Also check if top_cat has direct posts (assigned specifically to top_cat)
						$top_direct_docs_query = new \WP_Query( array(
							'post_type'      => 'yukdigitalz_kb_doc',
							'post_status'    => 'publish',
							'posts_per_page' => -1,
							'tax_query'      => array(
								array(
									'taxonomy'         => 'yukdigitalz_kb_cat',
									'field'            => 'term_id',
									'terms'            => $top_cat->term_id,
									'include_children' => false,
								),
							),
						) );

						if ( $top_direct_docs_query->have_posts() ) {
							?>
							<ul class="yukdigitalz-kb-sidebar-articles yukdigitalz-kb-direct-articles">
								<?php while ( $top_direct_docs_query->have_posts() ) : $top_direct_docs_query->the_post(); ?>
									<?php $is_active = ( get_the_ID() === $current_post_id ); ?>
									<li class="<?php echo $is_active ? 'yukdigitalz-kb-active-article' : ''; ?>">
										<a href="<?php echo esc_url( get_permalink() ); ?>" <?php echo $is_active ? 'aria-current="page"' : ''; ?>>
											<?php echo esc_html( get_the_title() ); ?>
										</a>
									</li>
								<?php endwhile; ?>
								<?php wp_reset_postdata(); ?>
							</ul>
							<?php
						}

					} else {
						// Top category HAS NO child categories - list its direct posts
						$top_docs_query = new \WP_Query( array(
							'post_type'      => 'yukdigitalz_kb_doc',
							'post_status'    => 'publish',
							'posts_per_page' => -1,
							'tax_query'      => array(
								array(
									'taxonomy' => 'yukdigitalz_kb_cat',
									'field'    => 'term_id',
									'terms'    => $top_cat->term_id,
								),
							),
						) );
						?>
						<ul class="yukdigitalz-kb-sidebar-articles">
							<?php if ( $top_docs_query->have_posts() ) : ?>
								<?php while ( $top_docs_query->have_posts() ) : $top_docs_query->the_post(); ?>
									<?php $is_active = ( get_the_ID() === $current_post_id ); ?>
									<li class="<?php echo $is_active ? 'yukdigitalz-kb-active-article' : ''; ?>">
										<a href="<?php echo esc_url( get_permalink() ); ?>" <?php echo $is_active ? 'aria-current="page"' : ''; ?>>
											<?php echo esc_html( get_the_title() ); ?>
										</a>
									</li>
								<?php endwhile; ?>
								<?php wp_reset_postdata(); ?>
							<?php else : ?>
								<li><?php esc_html_e( 'No articles.', 'yukdigitalz-knowledge-base' ); ?></li>
							<?php endif; ?>
						</ul>
						<?php
					}
					?>
				</div>
			</div>
			<?php
		}
	}
}
