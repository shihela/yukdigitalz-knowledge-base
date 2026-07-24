<?php
/**
 * The template for displaying Yukdigitalz KB category taxonomy archives.
 * Lists all articles associated with a category, featuring sidebar accordion.
 *
 * @package YukdigitalzKnowledgeBase
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

\YukdigitalzKnowledgeBase\Templates::get_header();

$yukdigitalz_kb_current_term    = get_queried_object();
$yukdigitalz_kb_current_term_id = $yukdigitalz_kb_current_term->term_id;
$yukdigitalz_kb_enable_ai_chat  = get_option( 'yukdigitalz_kb_enable_ai_chat', 1 );
$yukdigitalz_kb_primary_color   = sanitize_hex_color( get_option( 'yukdigitalz_kb_primary_color', '#2563eb' ) ) ?: '#2563eb';
$yukdigitalz_kb_secondary_color = sanitize_hex_color( get_option( 'yukdigitalz_kb_secondary_color', '#1d4ed8' ) ) ?: '#1d4ed8';
$yukdigitalz_kb_accent_color    = sanitize_hex_color( get_option( 'yukdigitalz_kb_accent_color', '#f59e0b' ) ) ?: '#f59e0b';
?>

<div class="yukdigitalz-kb-doc-layout">
	<template shadowrootmode="open">
		<div class="yukdigitalz-kb-doc-layout-inner">
	<!-- Left Sidebar: Collapsible Categories Accordion -->
	<aside class="yukdigitalz-kb-sidebar-nav" aria-label="<?php esc_attr_e( 'Documentation Navigation', 'yukdigitalz-knowledge-base' ); ?>">
		<h2 class="yukdigitalz-kb-sidebar-title"><?php esc_html_e( 'Categories', 'yukdigitalz-knowledge-base' ); ?></h2>
		<div class="yukdigitalz-kb-sidebar-accordion">
			<?php \YukdigitalzKnowledgeBase\Templates::render_sidebar_navigation( 0, $yukdigitalz_kb_current_term_id ); ?>
		</div>
	</aside>

	<!-- Central Section: Category Archive Content -->
	<section class="yukdigitalz-kb-article-container">
		<!-- Breadcrumbs -->
		<nav class="yukdigitalz-kb-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'yukdigitalz-knowledge-base' ); ?>">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'yukdigitalz-knowledge-base' ); ?></a>
			<span class="yukdigitalz-kb-breadcrumb-sep" aria-hidden="true">/</span>
			<a href="<?php echo esc_url( get_post_type_archive_link( 'yukdigitalz_kb_doc' ) ); ?>"><?php esc_html_e( 'Docs', 'yukdigitalz-knowledge-base' ); ?></a>
			<span class="yukdigitalz-kb-breadcrumb-sep" aria-hidden="true">/</span>
			<span class="yukdigitalz-kb-breadcrumb-current" aria-current="page"><?php echo esc_html( $yukdigitalz_kb_current_term->name ); ?></span>
		</nav>

		<header class="yukdigitalz-kb-archive-header" style="margin-bottom: 32px; padding-bottom: 20px; border-bottom: 1px solid var(--yukdigitalz-kb-border);">
			<h1 class="yukdigitalz-kb-article-title"><?php echo esc_html( $yukdigitalz_kb_current_term->name ); ?></h1>
			<?php if ( ! empty( $yukdigitalz_kb_current_term->description ) ) : ?>
				<p class="yukdigitalz-kb-category-description" style="color: var(--yukdigitalz-kb-text-muted); font-size: 1.05rem; margin-top: 8px;"><?php echo esc_html( $yukdigitalz_kb_current_term->description ); ?></p>
			<?php endif; ?>
		</header>

		<!-- List of Documents inside Category -->
		<div class="yukdigitalz-kb-category-archive-list" style="display: flex; flex-direction: column; gap: 24px;">
			<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
				<article class="yukdigitalz-kb-archive-item-card" style="padding: 24px; border: 1px solid var(--yukdigitalz-kb-border); border-radius: var(--yukdigitalz-kb-radius-sm); transition: var(--yukdigitalz-kb-transition-smooth); background: var(--yukdigitalz-kb-card-bg);">
					<h2 class="yukdigitalz-kb-archive-item-title" style="margin: 0 0 10px 0; font-size: 1.3rem; font-weight: 700;">
						<a href="<?php echo esc_url( get_permalink() ); ?>" style="color: var(--yukdigitalz-kb-text-main); text-decoration: none; transition: var(--yukdigitalz-kb-transition-smooth);">
							<?php echo esc_html( get_the_title() ); ?>
						</a>
					</h2>
					<p style="margin: 0 0 16px 0; color: var(--yukdigitalz-kb-text-muted); font-size: 0.95rem; line-height: 1.5;">
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
				<p><?php esc_html_e( 'No articles found in this category.', 'yukdigitalz-knowledge-base' ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<!-- Right Sidebar: Floating AI Chat Drawer -->
	<?php if ( $yukdigitalz_kb_enable_ai_chat ) : ?>
		<button id="yukdigitalz-kb-ai-trigger" class="yukdigitalz-kb-ai-trigger-fab" aria-label="<?php esc_attr_e( 'Ask AI', 'yukdigitalz-knowledge-base' ); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-aperture" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="14.31" y1="8" x2="20.05" y2="17.94"></line><line x1="9.69" y1="8" x2="21.17" y2="8"></line><line x1="7.38" y1="12" x2="13.12" y2="2.06"></line><line x1="9.69" y1="16" x2="3.95" y2="6.06"></line><line x1="14.31" y1="16" x2="2.83" y2="16"></line><line x1="16.62" y1="12" x2="10.88" y2="21.94"></line></svg>
		</button>

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
</div>

<?php
\YukdigitalzKnowledgeBase\Templates::get_footer();
