=== Yukdigitalz Knowledge Base ===
Contributors: shihela
Tags: knowledge base, documentation, wiki, docs, rag ai assistant
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

An enterprise-grade, high-performance Knowledge Base and Documentation plugin for WordPress featuring a secure, rate-limited RAG AI Assistant.

== Description ==

Yukdigitalz Knowledge Base is a lightweight, secure, and accessible documentation plugin for WordPress. Designed for enterprise-level performance and premium modern aesthetics, it provides a seamless repository-ready system to publish, manage, and interact with your technical resources.

In addition to traditional Knowledge Base grids and navigation, Yukdigitalz Knowledge Base is equipped with a state-of-the-art **RAG AI Chat Assistant** drawer (SaaS style). The AI assistant utilizes Retrieval-Augmented Generation (RAG) to scan your published articles, retrieve matching context, and reply contextually. It leverages the native WordPress AI Client and falls back to a direct Google Gemini API connection. To protect your server and API keys from quota exhaustion, it features robust Hashed IP Rate Limiting and client-side anti-spam cooldown mechanisms.

== Features ==

* **SaaS-Style AI Chat Assistant (RAG)**: A floating spark button slides out a contextual chat drawer. The assistant automatically queries the knowledge base (`yukdigitalz_kb_doc`), inserts matching content records as context, and replies contextually using Gemini.
* **WordPress Native AI Client & Gemini API Fallback**: Seamlessly hooks into `wp_ai_client_prompt` to leverage options configured in your WP dashboard, with direct backup API configurations.
* **AI Chat Rate Limiting & Anti-Spam Security**: Built-in privacy-safe hashed IP request tracking via WordPress Transients (limits maximum queries per hour per user) and client-side cooldown locks.
* **Premium Performance Layouts**: Built entirely using zero-dependency, modern Vanilla JS and lightweight clean CSS (fully compatible with Gutenberg, Classic Editor, and Block/FSE Themes).
* **AJAX Live Search**: Accessible instant searching matching post titles, categories, and article snippets with a loading indicator.
* **Collapsible Accordion Sidebar**: Interactive, accessible list of categories and documents in the sidebar that preserves expansion states.
* **Automatic Table of Contents (TOC)**: Dynamically parses `h2` and `h3` tags inside article content to render a floating index widget.
* **Helpfulness Feedback Widget**: AJAX-based user voting ("Was this article helpful?") with custom cookie session locks to prevent spam ratings.
* **Breadcrumbs Navigation**: Automated microdata-ready hierarchical path links at the top of individual documentation pages.
* **Enterprise Customizer Settings**: A premium, tabbed dashboard options page allowing you to configure custom base slugs, primary hover colors, rating widgets, TOCs, Q&A Comments toggle, rate limit parameters, and API credentials.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/yukdigitalz-knowledge-base` directory, or install through the WordPress Plugins dashboard.
2. Activate the plugin.
3. Save options under **Settings > Yukdigitalz KB** in your admin dashboard to flush rewrite rules and initialize custom paths.
4. Insert the `[yukdigitalz_kb]` shortcode on any Page to display the main Knowledge Base category grid card portal.
5. Create categories under **Yukdigitalz KB > Categories** and write documentation posts.

== Frequently Asked Questions ==

= Can I customize the colors to match my brand? =
Yes. Navigate to Settings > Yukdigitalz KB > Styling Options to customize primary, hover transition, and accent highlight colors via color pickers.

= How does the AI Assistant retrieve information? =
When a user asks a question, the plugin performs an internal `WP_Query` search on the custom post type `yukdigitalz_kb_doc`. The top 3 matching articles are injected directly into the Gemini model instructions as ground-truth context.

= Can I override templates in my child theme? =
Yes. Copy any template file from the plugin's `templates/` folder and paste it into a `yukdigitalz-kb/` folder inside your active theme directory to override layouts safely.

== Screenshots ==

1. The premium main card grid portal view.
2. Contextual single article template view.
3. Slide-out RAG AI Chat Assistant drawer interface.

== Changelog ==

= 1.0.0 =
* Initial release.
* Added hierarchical CPT and taxonomy registration.
* Added AJAX Live Search, helpfulness rating, and automatic Table of Contents.
* Added SaaS-style right-side slide-over RAG AI Chat Assistant drawer.
* Added hashed IP rate limiting and 2-second anti-spam cooldown lock to the AI Chat.
