<?php
namespace Shihela\YukdigitalzKnowledgeBase;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles Custom Post Type and Taxonomies registration with enterprise-grade
 * hierarchical permalink structures and backward-compatibility redirection.
 */
class CPT {
	/**
	 * Hooks into WordPress.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_rewrite_tags' ), 8 );
		add_action( 'init', array( $this, 'register_post_type' ), 9 );
		add_action( 'init', array( $this, 'register_taxonomy' ), 10 );
		add_action( 'init', array( $this, 'add_custom_rewrite_rules' ), 11 );
		add_filter( 'post_type_link', array( $this, 'filter_post_type_link' ), 10, 2 );
		add_filter( 'request', array( $this, 'resolve_request_collision' ) );
		add_action( 'template_redirect', array( $this, 'handle_legacy_redirects' ) );
	}

	/**
	 * Registers custom rewrite tags for dynamic hierarchical permalinks.
	 */
	public function register_rewrite_tags() {
		add_rewrite_tag( '%yukdigitalz_kb_cat%', '(.+?)', 'yukdigitalz_kb_cat=' );
	}

	/**
	 * Register the yukdigitalz_kb_doc Custom Post Type.
	 */
	public function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Docs', 'Post type general name', 'yukdigitalz-knowledge-base' ),
			'singular_name'         => _x( 'Doc', 'Post type singular name', 'yukdigitalz-knowledge-base' ),
			'menu_name'             => _x( 'Yukdigitalz KB', 'Admin Menu text', 'yukdigitalz-knowledge-base' ),
			'name_admin_bar'        => _x( 'Doc', 'Add New on Toolbar', 'yukdigitalz-knowledge-base' ),
			'add_new'               => __( 'Add New Doc', 'yukdigitalz-knowledge-base' ),
			'add_new_item'          => __( 'Add New Doc', 'yukdigitalz-knowledge-base' ),
			'new_item'              => __( 'New Doc', 'yukdigitalz-knowledge-base' ),
			'edit_item'             => __( 'Edit Doc', 'yukdigitalz-knowledge-base' ),
			'view_item'             => __( 'View Doc', 'yukdigitalz-knowledge-base' ),
			'all_items'             => __( 'All Docs', 'yukdigitalz-knowledge-base' ),
			'search_items'          => __( 'Search Docs', 'yukdigitalz-knowledge-base' ),
			'parent_item_colon'     => __( 'Parent Docs:', 'yukdigitalz-knowledge-base' ),
			'not_found'             => __( 'No docs found.', 'yukdigitalz-knowledge-base' ),
			'not_found_in_trash'    => __( 'No docs found in Trash.', 'yukdigitalz-knowledge-base' ),
			'featured_image'        => _x( 'Doc Cover Image', 'Featured Image label', 'yukdigitalz-knowledge-base' ),
			'set_featured_image'    => _x( 'Set cover image', 'Set featured image label', 'yukdigitalz-knowledge-base' ),
			'remove_featured_image' => _x( 'Remove cover image', 'Remove featured image label', 'yukdigitalz-knowledge-base' ),
			'use_featured_image'    => _x( 'Use as cover image', 'Use featured image label', 'yukdigitalz-knowledge-base' ),
			'archives'              => _x( 'Doc Archives', 'Post type archive label', 'yukdigitalz-knowledge-base' ),
			'insert_into_item'      => _x( 'Insert into doc', 'Insert into post label', 'yukdigitalz-knowledge-base' ),
			'uploaded_to_this_item' => _x( 'Uploaded to this doc', 'Uploaded to post label', 'yukdigitalz-knowledge-base' ),
			'filter_items_list'     => _x( 'Filter docs list', 'Screen reader list filter label', 'yukdigitalz-knowledge-base' ),
			'items_list_navigation' => _x( 'Docs list navigation', 'Screen reader list nav label', 'yukdigitalz-knowledge-base' ),
			'items_list'            => _x( 'Docs list', 'Screen reader items list label', 'yukdigitalz-knowledge-base' ),
		);

		// Get custom slug from settings or use default
		$slug = get_option( 'yukdigitalz_kb_slug', 'docs' );
		if ( empty( $slug ) ) {
			$slug = 'docs';
		}

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array(
				'slug'       => $slug . '/%yukdigitalz_kb_cat%',
				'with_front' => false,
			),
			'capability_type'    => 'post',
			'has_archive'        => $slug,
			'hierarchical'       => true, // supports parent-child document nesting
			'menu_position'      => 25,
			'menu_icon'          => 'dashicons-book-alt',
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'page-attributes', 'author', 'comments' ),
			'show_in_rest'       => true, // Enable block editor (Gutenberg)
			'taxonomies'         => array( 'yukdigitalz_kb_cat', 'yukdigitalz_kb_tag' ),
		);

		register_post_type( 'yukdigitalz_kb_doc', $args );
	}

	/**
	 * Register the yukdigitalz_kb_cat and yukdigitalz_kb_tag taxonomies.
	 */
	public function register_taxonomy() {
		// Category (hierarchical)
		$cat_labels = array(
			'name'              => _x( 'Doc Categories', 'Taxonomy general name', 'yukdigitalz-knowledge-base' ),
			'singular_name'     => _x( 'Doc Category', 'Taxonomy singular name', 'yukdigitalz-knowledge-base' ),
			'search_items'      => __( 'Search Doc Categories', 'yukdigitalz-knowledge-base' ),
			'all_items'         => __( 'All Doc Categories', 'yukdigitalz-knowledge-base' ),
			'parent_item'       => __( 'Parent Category', 'yukdigitalz-knowledge-base' ),
			'parent_item_colon' => __( 'Parent Category:', 'yukdigitalz-knowledge-base' ),
			'edit_item'         => __( 'Edit Doc Category', 'yukdigitalz-knowledge-base' ),
			'update_item'       => __( 'Update Doc Category', 'yukdigitalz-knowledge-base' ),
			'add_new_item'      => __( 'Add New Doc Category', 'yukdigitalz-knowledge-base' ),
			'new_item_name'     => __( 'New Doc Category Name', 'yukdigitalz-knowledge-base' ),
			'menu_name'         => __( 'Categories', 'yukdigitalz-knowledge-base' ),
		);

		$slug = get_option( 'yukdigitalz_kb_slug', 'docs' );
		if ( empty( $slug ) ) {
			$slug = 'docs';
		}

		$cat_args = array(
			'hierarchical'      => true,
			'labels'            => $cat_labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array(
				'slug'         => $slug,
				'with_front'   => false,
				'hierarchical' => true,
			),
			'show_in_rest'      => true,
		);

		register_taxonomy( 'yukdigitalz_kb_cat', array( 'yukdigitalz_kb_doc' ), $cat_args );

		// Tag (non-hierarchical)
		$tag_labels = array(
			'name'                       => _x( 'Doc Tags', 'Taxonomy general name', 'yukdigitalz-knowledge-base' ),
			'singular_name'              => _x( 'Doc Tag', 'Taxonomy singular name', 'yukdigitalz-knowledge-base' ),
			'search_items'               => __( 'Search Doc Tags', 'yukdigitalz-knowledge-base' ),
			'popular_items'              => __( 'Popular Doc Tags', 'yukdigitalz-knowledge-base' ),
			'all_items'                  => __( 'All Doc Tags', 'yukdigitalz-knowledge-base' ),
			'edit_item'                  => __( 'Edit Doc Tag', 'yukdigitalz-knowledge-base' ),
			'update_item'                => __( 'Update Doc Tag', 'yukdigitalz-knowledge-base' ),
			'add_new_item'               => __( 'Add New Doc Tag', 'yukdigitalz-knowledge-base' ),
			'new_item_name'              => __( 'New Doc Tag Name', 'yukdigitalz-knowledge-base' ),
			'separate_items_with_commas' => __( 'Separate tags with commas', 'yukdigitalz-knowledge-base' ),
			'add_or_remove_items'        => __( 'Add or remove tags', 'yukdigitalz-knowledge-base' ),
			'choose_from_most_used'      => __( 'Choose from the most used tags', 'yukdigitalz-knowledge-base' ),
			'not_found'                  => __( 'No tags found.', 'yukdigitalz-knowledge-base' ),
			'menu_name'                  => __( 'Tags', 'yukdigitalz-knowledge-base' ),
		);

		$tag_slug = get_option( 'yukdigitalz_kb_tag_slug', 'doc-tag' );
		if ( empty( $tag_slug ) ) {
			$tag_slug = 'doc-tag';
		}

		$tag_args = array(
			'hierarchical'          => false,
			'labels'                => $tag_labels,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var'             => true,
			'rewrite'               => array( 'slug' => $tag_slug, 'with_front' => false ),
			'show_in_rest'          => true,
		);

		register_taxonomy( 'yukdigitalz_kb_tag', array( 'yukdigitalz_kb_doc' ), $tag_args );
	}

	/**
	 * Adds explicit rewrite rules ensuring the unified base slug routes
	 * correctly between documentation archives, categories, pagination, and single articles.
	 */
	public function add_custom_rewrite_rules() {
		$slug = get_option( 'yukdigitalz_kb_slug', 'docs' );
		if ( empty( $slug ) ) {
			$slug = 'docs';
		}

		// 1. Pagination for category archives: e.g. docs/{category}/page/{n}/
		add_rewrite_rule(
			'^' . $slug . '/(.+?)/page/?([0-9]{1,})/?$',
			'index.php?yukdigitalz_kb_cat=$matches[1]&paged=$matches[2]',
			'top'
		);

		// 2. Single doc matching category and doc slug: e.g. docs/{category}/{post}/
		add_rewrite_rule(
			'^' . $slug . '/(.+?)/([^/]+)/?$',
			'index.php?yukdigitalz_kb_cat=$matches[1]&yukdigitalz_kb_doc=$matches[2]',
			'top'
		);

		// 3. Category archive: e.g. docs/{category}/
		add_rewrite_rule(
			'^' . $slug . '/(.+?)/?$',
			'index.php?yukdigitalz_kb_cat=$matches[1]',
			'top'
		);
	}

	/**
	 * Filters post type links to replace the %yukdigitalz_kb_cat% placeholder
	 * with the primary hierarchical taxonomy term slug path.
	 *
	 * @param string   $post_link The post permalink URL.
	 * @param \WP_Post $post      The post object.
	 * @return string Filtered permalink URL.
	 */
	public function filter_post_type_link( $post_link, $post ) {
		if ( ! is_object( $post ) || $post->post_type !== 'yukdigitalz_kb_doc' ) {
			return $post_link;
		}

		if ( strpos( $post_link, '%yukdigitalz_kb_cat%' ) === false ) {
			return $post_link;
		}

		$terms = wp_get_object_terms( $post->ID, 'yukdigitalz_kb_cat' );

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			// Select the primary term; if multiple, prefer a child term
			$primary_term = $terms[0];
			if ( count( $terms ) > 1 ) {
				foreach ( $terms as $term ) {
					if ( ! empty( $term->parent ) ) {
						$primary_term = $term;
						break;
					}
				}
			}

			// Build full hierarchical ancestor slug path
			$cat_slug_path = $primary_term->slug;
			$parent_id     = $primary_term->parent;
			while ( ! empty( $parent_id ) ) {
				$parent_term = get_term( $parent_id, 'yukdigitalz_kb_cat' );
				if ( ! $parent_term || is_wp_error( $parent_term ) ) {
					break;
				}
				$cat_slug_path = $parent_term->slug . '/' . $cat_slug_path;
				$parent_id     = $parent_term->parent;
			}

			$post_link = str_replace( '%yukdigitalz_kb_cat%', $cat_slug_path, $post_link );
		} else {
			// Fallback placeholder when no category is assigned yet
			$post_link = str_replace( '%yukdigitalz_kb_cat%', 'general', $post_link );
		}

		return $post_link;
	}

	/**
	 * Resolves rewrite query collisions between subcategories and single articles.
	 * Ensures requests matching subcategories are properly parsed as category taxonomy queries.
	 *
	 * @param array $query_vars Query variables.
	 * @return array Filtered query variables.
	 */
	public function resolve_request_collision( $query_vars ) {
		if ( isset( $query_vars['yukdigitalz_kb_doc'] ) && isset( $query_vars['yukdigitalz_kb_cat'] ) ) {
			$cat_path  = $query_vars['yukdigitalz_kb_cat'];
			$post_slug = $query_vars['yukdigitalz_kb_doc'];

			// Check if a single doc post exists with this slug
			$post = get_page_by_path( $post_slug, OBJECT, 'yukdigitalz_kb_doc' );
			if ( ! $post ) {
				$posts = get_posts( array(
					'name'           => $post_slug,
					'post_type'      => 'yukdigitalz_kb_doc',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'fields'         => 'ids',
				) );
				if ( ! empty( $posts ) ) {
					$post = true;
				}
			}

			// If no post was found with this slug, check if it is actually a nested child category
			if ( ! $post ) {
				$candidate_term = get_term_by( 'slug', $post_slug, 'yukdigitalz_kb_cat' );
				if ( $candidate_term && ! is_wp_error( $candidate_term ) ) {
					// Route as subcategory archive
					$query_vars['yukdigitalz_kb_cat'] = $cat_path . '/' . $post_slug;
					unset( $query_vars['yukdigitalz_kb_doc'] );
				}
			} else {
				// Explicitly assign post_type and post_name to render single template reliably
				$query_vars['post_type'] = 'yukdigitalz_kb_doc';
				$query_vars['name']      = $post_slug;
			}
		}

		return $query_vars;
	}

	/**
	 * Handles backward compatibility and 301 canonical redirects:
	 * 1. Old flat single doc URLs (e.g. /docs/{post-slug}/) -> 301 to /docs/{cat}/{post-slug}/
	 * 2. Old /doc-category/{slug}/ URLs -> 301 to /docs/{slug}/
	 * 3. Canonical enforcement for single docs
	 */
	public function handle_legacy_redirects() {
		$base_slug    = get_option( 'yukdigitalz_kb_slug', 'docs' );
		$old_cat_slug = get_option( 'yukdigitalz_kb_cat_slug', 'doc-category' );
		if ( empty( $base_slug ) ) {
			$base_slug = 'docs';
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		// 1. Check if user is accessing legacy /doc-category/{slug}/
		if ( ! empty( $old_cat_slug ) && $old_cat_slug !== $base_slug && ! empty( $request_uri ) ) {
			if ( strpos( $request_uri, '/' . $old_cat_slug . '/' ) !== false ) {
				$parsed_path = trim( (string) wp_parse_url( $request_uri, PHP_URL_PATH ), '/' );
				$segments    = explode( '/', $parsed_path );
				$idx         = array_search( $old_cat_slug, $segments, true );
				if ( false !== $idx && isset( $segments[ $idx + 1 ] ) ) {
					$term_slug = sanitize_title( $segments[ $idx + 1 ] );
					$term      = get_term_by( 'slug', $term_slug, 'yukdigitalz_kb_cat' );
					if ( $term && ! is_wp_error( $term ) ) {
						wp_safe_redirect( get_term_link( $term ), 301 );
						exit;
					}
				}
			}
		}

		// 2. Check if a single doc was requested via legacy flat URL /docs/{post-slug}/
		// WordPress may parse this into yukdigitalz_kb_cat if the term rule matched
		if ( is_tax( 'yukdigitalz_kb_cat' ) ) {
			$queried_obj = get_queried_object();
			if ( empty( $queried_obj ) || ! isset( $queried_obj->term_id ) ) {
				$tax_slug = get_query_var( 'yukdigitalz_kb_cat' );
				if ( ! empty( $tax_slug ) ) {
					$doc_post = get_page_by_path( $tax_slug, OBJECT, 'yukdigitalz_kb_doc' );
					if ( ! $doc_post ) {
						$posts = get_posts( array(
							'name'           => $tax_slug,
							'post_type'      => 'yukdigitalz_kb_doc',
							'post_status'    => 'publish',
							'posts_per_page' => 1,
						) );
						if ( ! empty( $posts ) ) {
							$doc_post = $posts[0];
						}
					}

					if ( $doc_post ) {
						wp_safe_redirect( get_permalink( $doc_post->ID ), 301 );
						exit;
					}
				}
			}
		}

		// 3. Ensure canonical URL on single doc pages if accessed via flat URL
		if ( is_singular( 'yukdigitalz_kb_doc' ) ) {
			$canonical_url = get_permalink( get_the_ID() );
			if ( ! empty( $canonical_url ) && ! empty( $request_uri ) ) {
				$path_canonical = wp_parse_url( $canonical_url, PHP_URL_PATH );
				$path_current   = wp_parse_url( $request_uri, PHP_URL_PATH );
				if ( $path_canonical && $path_current && rtrim( $path_current, '/' ) !== rtrim( $path_canonical, '/' ) ) {
					// Check if current requested URL is the flat /docs/{slug} format
					$post_name     = get_post_field( 'post_name', get_the_ID() );
					$expected_flat = '/' . trim( $base_slug, '/' ) . '/' . $post_name;
					if ( rtrim( $path_current, '/' ) === rtrim( $expected_flat, '/' ) ) {
						wp_safe_redirect( $canonical_url, 301 );
						exit;
					}
				}
			}
		}
	}
}
