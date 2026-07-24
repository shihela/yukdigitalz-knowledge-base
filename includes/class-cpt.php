<?php
namespace YukdigitalzKnowledgeBase;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles Custom Post Type and Taxonomies registration.
 */
class CPT {
	/**
	 * Hooks into WordPress.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_post_type' ), 9 );
		add_action( 'init', array( $this, 'register_taxonomy' ), 10 );
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
			'rewrite'            => array( 'slug' => $slug, 'with_front' => false ),
			'capability_type'    => 'post',
			'has_archive'        => true,
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

		$cat_slug = get_option( 'yukdigitalz_kb_cat_slug', 'doc-category' );
		if ( empty( $cat_slug ) ) {
			$cat_slug = 'doc-category';
		}

		$cat_args = array(
			'hierarchical'      => true,
			'labels'            => $cat_labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => $cat_slug, 'with_front' => false ),
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
		if ( empty($tag_slug) ) {
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
}
