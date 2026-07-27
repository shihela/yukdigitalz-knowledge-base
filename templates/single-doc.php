<?php
/**
 * The template for displaying a single Yukdigitalz KB documentation post.
 * Features sidebar category-navigation accordion and floating article layout.
 *
 * @package YukdigitalzKnowledgeBase
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

\Shihela\YukdigitalzKnowledgeBase\Templates::get_header();

$yukdigitalz_kb_current_post_id = get_the_ID();

// Read config options
$yukdigitalz_kb_enable_feedback     = get_option( 'yukdigitalz_kb_enable_feedback', 1 );
$yukdigitalz_kb_enable_toc          = get_option( 'yukdigitalz_kb_enable_toc', 1 );
$yukdigitalz_kb_enable_breadcrumbs  = get_option( 'yukdigitalz_kb_enable_breadcrumbs', 1 );
$yukdigitalz_kb_enable_reading_time = get_option( 'yukdigitalz_kb_enable_reading_time', 1 );
$yukdigitalz_kb_enable_ai_chat      = get_option( 'yukdigitalz_kb_enable_ai_chat', 1 );
$yukdigitalz_kb_enable_comments     = get_option( 'yukdigitalz_kb_enable_comments', 0 );

// Fetch associated categories for breadcrumbs
$yukdigitalz_kb_terms         = get_the_terms( $yukdigitalz_kb_current_post_id, 'yukdigitalz_kb_cat' );
$yukdigitalz_kb_category_name = '';
$yukdigitalz_kb_category_url  = '';
if ( ! empty( $yukdigitalz_kb_terms ) && ! is_wp_error( $yukdigitalz_kb_terms ) ) {
	$yukdigitalz_kb_primary_term  = $yukdigitalz_kb_terms[0];
	$yukdigitalz_kb_category_name = $yukdigitalz_kb_primary_term->name;
	$yukdigitalz_kb_category_url  = get_term_link( $yukdigitalz_kb_primary_term );
}

// Calculate reading time
$yukdigitalz_kb_content       = get_post_field( 'post_content', $yukdigitalz_kb_current_post_id );
$yukdigitalz_kb_word_count    = str_word_count( wp_strip_all_tags( $yukdigitalz_kb_content ) );
$yukdigitalz_kb_reading_time  = ceil( $yukdigitalz_kb_word_count / 200 ); // Average 200 words per minute
if ( $yukdigitalz_kb_reading_time < 1 ) {
	$yukdigitalz_kb_reading_time = 1;
}
$yukdigitalz_kb_primary_color   = sanitize_hex_color( get_option( 'yukdigitalz_kb_primary_color', '#2563eb' ) ) ?: '#2563eb';
$yukdigitalz_kb_secondary_color = sanitize_hex_color( get_option( 'yukdigitalz_kb_secondary_color', '#1d4ed8' ) ) ?: '#1d4ed8';
$yukdigitalz_kb_accent_color    = sanitize_hex_color( get_option( 'yukdigitalz_kb_accent_color', '#f59e0b' ) ) ?: '#f59e0b';
?>

<div class="yukdigitalz-kb-doc-layout">
	<template shadowrootmode="open">
		<div class="yukdigitalz-kb-doc-layout-inner">
	<!-- Left Sidebar: Collapsible Categories Accordion -->
	<aside class="yukdigitalz-kb-sidebar-nav" aria-label="<?php esc_attr_e( 'Documentation Navigation', 'yukdigitalz-knowledge-base' ); ?>">
		<button type="button" class="yukdigitalz-kb-mobile-nav-toggle" aria-expanded="false">
			<span><?php esc_html_e( 'Browse Categories', 'yukdigitalz-knowledge-base' ); ?></span>
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="yukdigitalz-kb-mobile-chevron"><polyline points="6 9 12 15 18 9"></polyline></svg>
		</button>
		<h2 class="yukdigitalz-kb-sidebar-title"><?php esc_html_e( 'Categories', 'yukdigitalz-knowledge-base' ); ?></h2>
		<div class="yukdigitalz-kb-sidebar-accordion">
			<?php \Shihela\YukdigitalzKnowledgeBase\Templates::render_sidebar_navigation( $yukdigitalz_kb_current_post_id, 0 ); ?>
		</div>
	</aside>

	<!-- Central Section: Post Content -->
	<article class="yukdigitalz-kb-article-container">
		<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
			
			<!-- Breadcrumbs -->
			<?php if ( $yukdigitalz_kb_enable_breadcrumbs ) : ?>
				<nav class="yukdigitalz-kb-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'yukdigitalz-knowledge-base' ); ?>">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'yukdigitalz-knowledge-base' ); ?></a>
					<span class="yukdigitalz-kb-breadcrumb-sep" aria-hidden="true">/</span>
					<a href="<?php echo esc_url( get_post_type_archive_link( 'yukdigitalz_kb_doc' ) ); ?>"><?php esc_html_e( 'Docs', 'yukdigitalz-knowledge-base' ); ?></a>
					<?php if ( ! empty( $yukdigitalz_kb_category_name ) ) : ?>
						<span class="yukdigitalz-kb-breadcrumb-sep" aria-hidden="true">/</span>
						<a href="<?php echo esc_url( $yukdigitalz_kb_category_url ); ?>"><?php echo esc_html( $yukdigitalz_kb_category_name ); ?></a>
					<?php endif; ?>
					<span class="yukdigitalz-kb-breadcrumb-sep" aria-hidden="true">/</span>
					<span class="yukdigitalz-kb-breadcrumb-current" aria-current="page"><?php echo esc_html( get_the_title() ); ?></span>
				</nav>
			<?php endif; ?>

			<!-- Title and Meta -->
			<header class="yukdigitalz-kb-article-header">
				<h1 class="yukdigitalz-kb-article-title"><?php echo esc_html( get_the_title() ); ?></h1>
				<div class="yukdigitalz-kb-article-meta">
					<span class="yukdigitalz-kb-meta-item">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-calendar" aria-hidden="true" style="width: 16px; height: 16px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
						<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
					</span>
					
					<?php if ( $yukdigitalz_kb_enable_reading_time ) : ?>
						<span class="yukdigitalz-kb-meta-item">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-clock" aria-hidden="true" style="width: 16px; height: 16px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
							<?php
							/* translators: %s: number of reading minutes */
							printf( esc_html( _n( '%s Min Read', '%s Mins Read', $yukdigitalz_kb_reading_time, 'yukdigitalz-knowledge-base' ) ), esc_html( $yukdigitalz_kb_reading_time ) );
							?>
						</span>
					<?php endif; ?>
				</div>
			</header>

			<!-- Dynamic Table of Contents Injected Here -->
			<?php if ( $yukdigitalz_kb_enable_toc ) : ?>
				<nav class="yukdigitalz-kb-toc-wrapper" aria-label="<?php esc_attr_e( 'Table of contents', 'yukdigitalz-knowledge-base' ); ?>">
					<h2 class="yukdigitalz-kb-toc-title"><?php esc_html_e( 'Table of Contents', 'yukdigitalz-knowledge-base' ); ?></h2>
					<div id="yukdigitalz-kb-toc-content"></div>
				</nav>
			<?php endif; ?>

			<!-- Article content -->
			<div class="yukdigitalz-kb-article-body">
				<?php the_content(); ?>
			</div>

			<!-- Helpfulness Vote Widget -->
			<?php if ( $yukdigitalz_kb_enable_feedback ) : ?>
				<?php
				$yukdigitalz_kb_helpful_count     = (int) get_post_meta( $yukdigitalz_kb_current_post_id, '_yukdigitalz_kb_helpful_votes', true );
				$yukdigitalz_kb_not_helpful_count = (int) get_post_meta( $yukdigitalz_kb_current_post_id, '_yukdigitalz_kb_not_helpful_votes', true );
				?>
				<section class="yukdigitalz-kb-voting-widget" data-post-id="<?php echo esc_attr( $yukdigitalz_kb_current_post_id ); ?>" aria-label="<?php esc_attr_e( 'Article feedback', 'yukdigitalz-knowledge-base' ); ?>">
					<h3 class="yukdigitalz-kb-voting-title"><?php esc_html_e( 'Was this article helpful?', 'yukdigitalz-knowledge-base' ); ?></h3>
					<div class="yukdigitalz-kb-vote-buttons">
						<button class="yukdigitalz-kb-vote-btn" data-vote="helpful" aria-label="<?php esc_attr_e( 'Yes, this article was helpful', 'yukdigitalz-knowledge-base' ); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-thumbs-up" aria-hidden="true" style="width: 16px; height: 16px;"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>
							<span><?php esc_html_e( 'Yes', 'yukdigitalz-knowledge-base' ); ?></span>
							<span class="yukdigitalz-kb-helpful-count"><?php echo esc_html( $yukdigitalz_kb_helpful_count ); ?></span>
						</button>
						<button class="yukdigitalz-kb-vote-btn" data-vote="not_helpful" aria-label="<?php esc_attr_e( 'No, this article was not helpful', 'yukdigitalz-knowledge-base' ); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-thumbs-down" aria-hidden="true" style="width: 16px; height: 16px;"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h3a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-3"></path></svg>
							<span><?php esc_html_e( 'No', 'yukdigitalz-knowledge-base' ); ?></span>
							<span class="yukdigitalz-kb-nothelpful-count"><?php echo esc_html( $yukdigitalz_kb_not_helpful_count ); ?></span>
						</button>
					</div>
					<div class="yukdigitalz-kb-vote-response" aria-live="polite"></div>
				</section>
			<?php endif; ?>

			<!-- Comments Template for Q&A Discussions -->
			<slot name="comments"></slot>

		<?php endwhile; endif; ?>
	</article>

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
							<?php esc_html_e( 'Halo! Saya asisten AI Yukdigitalz KB. Ada yang bisa saya bantu terkait dokumentasi kami?', 'yukdigitalz-knowledge-base' ); ?>
						</div>
					</div>
				</div>
				<form class="yukdigitalz-kb-ai-chat-form" onsubmit="event.preventDefault();">
					<input type="text" placeholder="<?php esc_attr_e( 'Tanyakan sesuatu...', 'yukdigitalz-knowledge-base' ); ?>" class="yukdigitalz-kb-ai-chat-input" required />
					<button type="submit" class="yukdigitalz-kb-ai-chat-submit" aria-label="<?php esc_attr_e( 'Kirim', 'yukdigitalz-knowledge-base' ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-send" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
					</button>
				</form>
			</div>
		</div>
	<?php endif; ?>
		</div>
	</template>

	<!-- Comments Template in the Light DOM (projected via slot) -->
	<?php if ( $yukdigitalz_kb_enable_comments && ( comments_open() || get_comments_number() ) ) : ?>
		<div slot="comments" class="yukdigitalz-kb-comments-wrapper">
			<?php comments_template(); ?>
		</div>
	<?php endif; ?>
</div>

<?php
\Shihela\YukdigitalzKnowledgeBase\Templates::get_footer();
