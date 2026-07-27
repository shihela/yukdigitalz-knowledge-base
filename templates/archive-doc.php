<?php
/**
 * The template for displaying Yukdigitalz KB documentation archive.
 * Reuses the shortcode view engine to render categories grid and live search.
 *
 * @package YukdigitalzKnowledgeBase
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

\Shihela\YukdigitalzKnowledgeBase\Templates::get_header();
?>

<div id="primary" class="content-area yukdigitalz-kb-archive-content-area">
	<main id="main" class="site-main" role="main">
		<div class="yukdigitalz-kb-archive-container">
			<?php
			if ( class_exists( 'Shihela\YukdigitalzKnowledgeBase\Shortcode' ) ) {
				$yukdigitalz_kb_shortcode = new \Shihela\YukdigitalzKnowledgeBase\Shortcode();
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is safe HTML from Shortcode class, which is already fully escaped internally.
				echo $yukdigitalz_kb_shortcode->render_kb( array() );
			}
			?>
		</div>
	</main>
</div>

<?php
\Shihela\YukdigitalzKnowledgeBase\Templates::get_footer();
