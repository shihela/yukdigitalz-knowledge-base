(function() {
	function initShadowRootFixes() {
		if (!HTMLTemplateElement.prototype.hasOwnProperty('shadowRootMode')) {
			polyfill(document);
		} else {
			injectStylesToAllNativeShadowRoots();
		}
	}

	function polyfill(root) {
		root.querySelectorAll('template[shadowrootmode]').forEach(function(template) {
			var mode = template.getAttribute('shadowrootmode');
			var shadowRoot = template.parentNode.attachShadow({ mode: mode });
			shadowRoot.appendChild(template.content);
			template.remove();
			injectStylesToShadow(shadowRoot);
			polyfill(shadowRoot);
		});
	}

	function injectStylesToAllNativeShadowRoots() {
		document.querySelectorAll('.yukdigitalz-kb-wrapper, .yukdigitalz-kb-doc-layout').forEach(host => {
			if (host.shadowRoot) {
				injectStylesToShadow(host.shadowRoot);
			}
		});
	}

	function injectStylesToShadow(shadowRoot) {
		if (!shadowRoot) return;
		if (window.yukdigitalz_kb_vars && window.yukdigitalz_kb_vars.public_css_url) {
			if (!shadowRoot.querySelector('link[href*="public.css"]')) {
				const link = document.createElement('link');
				link.rel = 'stylesheet';
				link.href = window.yukdigitalz_kb_vars.public_css_url;
				shadowRoot.insertBefore(link, shadowRoot.firstChild);
			}
		}
		if (window.yukdigitalz_kb_vars && window.yukdigitalz_kb_vars.colors) {
			if (!shadowRoot.querySelector('#yukdigitalz-kb-dynamic-colors')) {
				const style = document.createElement('style');
				style.id = 'yukdigitalz-kb-dynamic-colors';
				style.textContent = `
					:host {
						--yukdigitalz-kb-primary: ${window.yukdigitalz_kb_vars.colors.primary} !important;
						--yukdigitalz-kb-primary-hover: ${window.yukdigitalz_kb_vars.colors.secondary} !important;
						--yukdigitalz-kb-accent: ${window.yukdigitalz_kb_vars.colors.accent} !important;
					}
				`;
				shadowRoot.appendChild(style);
			}
		}
	}

	// Run immediately
	initShadowRootFixes();

	// Also run on DOMContentLoaded just in case
	document.addEventListener('DOMContentLoaded', initShadowRootFixes);
})();

/**
 * Helper to retrieve active roots (both Light DOM document and all active Shadow Roots).
 */
function getKBRoots() {
	const roots = [document];
	document.querySelectorAll('.yukdigitalz-kb-wrapper, .yukdigitalz-kb-doc-layout').forEach(host => {
		if (host.shadowRoot) {
			roots.push(host.shadowRoot);
		}
	});
	return roots;
}

/**
 * Custom wrappers for standard query selectors that target elements inside the layout.
 */
function querySelectorKB(selector) {
	const roots = getKBRoots();
	for (let root of roots) {
		const el = root.querySelector(selector);
		if (el) return el;
	}
	return null;
}

function querySelectorAllKB(selector) {
	const roots = getKBRoots();
	let results = [];
	roots.forEach(root => {
		const els = root.querySelectorAll(selector);
		if (els.length > 0) {
			results = results.concat(Array.from(els));
		}
	});
	return results;
}

function getElementByIdKB(id) {
	const roots = getKBRoots();
	for (let root of roots) {
		const el = root.getElementById ? root.getElementById(id) : root.querySelector('#' + id);
		if (el) return el;
	}
	return null;
}

document.addEventListener('DOMContentLoaded', function() {
	// Initialize Sidebar Accordion
	initSidebarAccordion();

	// Initialize AJAX Live Search
	initLiveSearch();

	// Initialize Table of Contents
	initTableOfContents();

	// Initialize Feedback Voting widget
	initFeedbackVoting();

	// Initialize AI Chat Assistant
	initAIChat();

	// Initialize SaaS Header AI Button
	injectAIHeaderButton();
	let injectAttempts = 0;
	const injectInterval = setInterval(function() {
		injectAIHeaderButton();
		injectAttempts++;
		if (injectAttempts > 30) {
			clearInterval(injectInterval);
		}
	}, 100);
});

/**
 * Sidebar Accordion functionality with state preservation via localStorage.
 */
function initSidebarAccordion() {
	const headers = querySelectorAllKB('.yukdigitalz-kb-sidebar-cat-header');
	if (headers.length === 0) {
		return;
	}

	// Load collapsed/expanded configuration
	let accordionState = {};
	try {
		accordionState = JSON.parse(localStorage.getItem('yukdigitalz_kb_sidebar_state')) || {};
	} catch (e) {
		accordionState = {};
	}

	headers.forEach(header => {
		const targetId = header.getAttribute('data-target');
		const content  = getElementByIdKB(targetId);
		if (!content) {
			return;
		}

		// Auto-expand if category holds active article, or has stored state as expanded
		const hasActiveChild = content.querySelector('.yukdigitalz-kb-active-article') !== null || header.classList.contains('is-expanded');
		
		if (accordionState[targetId] === true || (accordionState[targetId] === undefined && hasActiveChild)) {
			content.style.maxHeight = 'none';
			header.classList.add('is-expanded');
			header.setAttribute('aria-expanded', 'true');
		} else {
			content.style.maxHeight = '0px';
			header.classList.remove('is-expanded');
			header.setAttribute('aria-expanded', 'false');
		}

		header.addEventListener('click', function(e) {
			e.stopPropagation();
			const isExpanded = header.classList.contains('is-expanded');
			if (isExpanded) {
				content.style.maxHeight = '0px';
				header.classList.remove('is-expanded');
				header.setAttribute('aria-expanded', 'false');
				accordionState[targetId] = false;
			} else {
				content.style.maxHeight = 'none';
				header.classList.add('is-expanded');
				header.setAttribute('aria-expanded', 'true');
				accordionState[targetId] = true;

				// Expand parent accordion containers if nested inside another
				let parentArticles = header.closest('.yukdigitalz-kb-sidebar-articles');
				while (parentArticles) {
					parentArticles.style.maxHeight = 'none';
					parentArticles = parentArticles.parentElement ? parentArticles.parentElement.closest('.yukdigitalz-kb-sidebar-articles') : null;
				}
			}
			localStorage.setItem('yukdigitalz_kb_sidebar_state', JSON.stringify(accordionState));
		});
	});
}

/**
 * Live search handling with debounced triggers and keyboard navigation.
 */
function initLiveSearch() {
	const searchInput     = getElementByIdKB('yukdigitalz-kb-search-input');
	const resultsContainer = querySelectorKB('.yukdigitalz-kb-search-results');
	const spinner          = querySelectorKB('.yukdigitalz-kb-search-spinner');

	if (!searchInput || !resultsContainer) {
		return;
	}

	let debounceTimeout = null;
	let focusedIndex    = -1;

	searchInput.addEventListener('input', function() {
		clearTimeout(debounceTimeout);
		const query = this.value.trim();

		if (query.length < 2) {
			resultsContainer.style.display = 'none';
			resultsContainer.innerHTML = '';
			return;
		}

		if (spinner) {
			spinner.style.display = 'block';
		}

		// Debounce request to 300ms to reduce database load
		debounceTimeout = setTimeout(() => {
			const formData = new FormData();
			formData.append('action', 'yukdigitalz_kb_search');
			formData.append('query', query);
			formData.append('security', yukdigitalz_kb_vars.nonce);

			fetch(yukdigitalz_kb_vars.ajax_url, {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (spinner) {
					spinner.style.display = 'none';
				}
				focusedIndex = -1;

				if (data.success && data.data.length > 0) {
					let html = '<ul class="yukdigitalz-kb-search-results-list" role="listbox">';
					data.data.forEach((item, index) => {
						html += `
							<li id="search-result-${index}" class="yukdigitalz-kb-search-result-item" role="option" data-url="${item.permalink}">
								<a href="${item.permalink}">
									${item.category ? `<span class="yukdigitalz-kb-result-cat">${item.category}</span>` : ''}
									<div class="yukdigitalz-kb-result-title">${item.title}</div>
									${item.excerpt ? `<div class="yukdigitalz-kb-result-excerpt">${item.excerpt}</div>` : ''}
								</a>
							</li>
						`;
					});
					html += '</ul>';
					resultsContainer.innerHTML = html;
					resultsContainer.style.display = 'block';
				} else {
					resultsContainer.innerHTML = `
						<div class="yukdigitalz-kb-search-no-results">
							${data.data && data.data.message ? data.data.message : yukdigitalz_kb_vars.strings.search_no_res}
						</div>
					`;
					resultsContainer.style.display = 'block';
				}
			})
			.catch(() => {
				if (spinner) {
					spinner.style.display = 'none';
				}
				resultsContainer.innerHTML = `<div class="yukdigitalz-kb-search-error">${yukdigitalz_kb_vars.strings.voting_error}</div>`;
				resultsContainer.style.display = 'block';
			});
		}, 300);
	});

	// Keyboard arrows selection
	searchInput.addEventListener('keydown', function(e) {
		const items = resultsContainer.querySelectorAll('.yukdigitalz-kb-search-result-item');
		if (items.length === 0) {
			return;
		}

		if (e.key === 'ArrowDown') {
			e.preventDefault();
			focusedIndex = (focusedIndex + 1) % items.length;
			highlightItem(items);
		} else if (e.key === 'ArrowUp') {
			e.preventDefault();
			focusedIndex = (focusedIndex - 1 + items.length) % items.length;
			highlightItem(items);
		} else if (e.key === 'Enter') {
			e.preventDefault();
			if (focusedIndex >= 0 && focusedIndex < items.length) {
				window.location.href = items[focusedIndex].getAttribute('data-url');
			} else if (items.length > 0) {
				window.location.href = items[0].getAttribute('data-url');
			}
		} else if (e.key === 'Escape') {
			resultsContainer.style.display = 'none';
			searchInput.blur();
		}
	});

	function highlightItem(items) {
		items.forEach((item, index) => {
			if (index === focusedIndex) {
				item.classList.add('is-focused');
				searchInput.setAttribute('aria-activedescendant', `search-result-${index}`);
				item.scrollIntoView({ block: 'nearest' });
			} else {
				item.classList.remove('is-focused');
			}
		});
	}

	// Close search popup if clicked outside (considering Shadow DOM path)
	document.addEventListener('click', function(e) {
		const path = e.composedPath();
		if (searchInput && resultsContainer && !path.includes(searchInput) && !path.includes(resultsContainer)) {
			resultsContainer.style.display = 'none';
		}
	});
}

/**
 * Generates dynamic Table of Contents (TOC) with ScrollSpy.
 */
function initTableOfContents() {
	const articleBody = querySelectorKB('.yukdigitalz-kb-article-body');
	const tocContainer = getElementByIdKB('yukdigitalz-kb-toc-content');

	if (!articleBody || !tocContainer) {
		return;
	}

	const headings = articleBody.querySelectorAll('h2, h3');
	if (headings.length === 0) {
		const tocWrapper = querySelectorKB('.yukdigitalz-kb-toc-wrapper');
		if (tocWrapper) {
			tocWrapper.style.display = 'none';
		}
		return;
	}

	tocContainer.innerHTML = '';

	// Injects list elements
	const tocList = document.createElement('ul');
	tocList.className = 'yukdigitalz-kb-toc-list';

	headings.forEach((heading, index) => {
		// Set ID from text to support anchor jump
		if (!heading.id) {
			const cleanText = heading.textContent.toLowerCase()
				.replace(/[^a-z0-9]+/g, '-')
				.replace(/(^-|-$)/g, '');
			heading.id = cleanText || `section-heading-${index}`;
		}

		const listItem = document.createElement('li');
		listItem.className = `yukdigitalz-kb-toc-item toc-depth-${heading.tagName.toLowerCase()}`;

		const link = document.createElement('a');
		link.href = `#${heading.id}`;
		link.textContent = heading.textContent;
		link.className = 'yukdigitalz-kb-toc-link';

		// Direct click handler to support Shadow DOM and smooth scrolling offset
		link.addEventListener('click', function(e) {
			e.preventDefault();
			e.stopPropagation();

			const targetRect = heading.getBoundingClientRect();
			const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
			const offsetPosition = targetRect.top + scrollTop - 80;

			window.scrollTo({
				top: Math.max(0, offsetPosition),
				behavior: 'smooth'
			});

			if (history.pushState) {
				history.pushState(null, null, `#${heading.id}`);
			}
		});

		listItem.appendChild(link);
		tocList.appendChild(listItem);
	});

	tocContainer.appendChild(tocList);

	// ScrollSpy highlighting algorithm
	const tocLinks = tocContainer.querySelectorAll('.yukdigitalz-kb-toc-link');
	
	function highlightActiveSection() {
		let currentSectionId = '';
		const scrollPos = window.scrollY + 120; // top offset space

		headings.forEach(heading => {
			const offsetTop = heading.getBoundingClientRect().top + window.scrollY;
			if (scrollPos >= offsetTop) {
				currentSectionId = heading.id;
			}
		});

		tocLinks.forEach(link => {
			if (link.getAttribute('href') === `#${currentSectionId}`) {
				link.classList.add('is-active');
			} else {
				link.classList.remove('is-active');
			}
		});
	}

	window.addEventListener('scroll', highlightActiveSection);
	highlightActiveSection(); // Run on startup

	// Scroll to section if hash is present in initial URL
	if (window.location.hash) {
		const hashId = window.location.hash.substring(1);
		headings.forEach(heading => {
			if (heading.id === hashId) {
				setTimeout(function() {
					const targetRect = heading.getBoundingClientRect();
					const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
					const offsetPosition = targetRect.top + scrollTop - 80;
					window.scrollTo({
						top: Math.max(0, offsetPosition),
						behavior: 'smooth'
					});
				}, 300);
			}
		});
	}
}

/**
 * Helpful voting submission using fetch.
 */
function initFeedbackVoting() {
	const votingContainer = querySelectorKB('.yukdigitalz-kb-voting-widget');
	if (!votingContainer) {
		return;
	}

	const post_id     = votingContainer.getAttribute('data-post-id');
	const buttons     = votingContainer.querySelectorAll('.yukdigitalz-kb-vote-btn');
	const responseMsg = votingContainer.querySelector('.yukdigitalz-kb-vote-response');

	buttons.forEach(button => {
		button.addEventListener('click', function() {
			const voteType = this.getAttribute('data-vote');

			// Disable buttons
			buttons.forEach(btn => btn.setAttribute('disabled', 'disabled'));

			const formData = new FormData();
			formData.append('action', 'yukdigitalz_kb_vote');
			formData.append('post_id', post_id);
			formData.append('vote', voteType);
			formData.append('security', yukdigitalz_kb_vars.nonce);

			fetch(yukdigitalz_kb_vars.ajax_url, {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					responseMsg.textContent = data.data.message || yukdigitalz_kb_vars.strings.voting_thanks;
					responseMsg.className = 'yukdigitalz-kb-vote-response success-msg';
					
					// Optional update of counts UI
					const helpfulCount    = votingContainer.querySelector('.yukdigitalz-kb-helpful-count');
					const notHelpfulCount = votingContainer.querySelector('.yukdigitalz-kb-nothelpful-count');
					if (helpfulCount && data.data.helpful !== undefined) {
						helpfulCount.textContent = data.data.helpful;
					}
					if (notHelpfulCount && data.data.not_helpful !== undefined) {
						notHelpfulCount.textContent = data.data.not_helpful;
					}
				} else {
					responseMsg.textContent = data.data.message || yukdigitalz_kb_vars.strings.voting_error;
					responseMsg.className = 'yukdigitalz-kb-vote-response error-msg';
					buttons.forEach(btn => btn.removeAttribute('disabled'));
				}
			})
			.catch(() => {
				responseMsg.textContent = yukdigitalz_kb_vars.strings.voting_error;
				responseMsg.className = 'yukdigitalz-kb-vote-response error-msg';
				buttons.forEach(btn => btn.removeAttribute('disabled'));
			});
		});
	});
}

/**
 * AI RAG Chat Drawer interface controller logic.
 */
function initAIChat() {
	const triggerBtn = getElementByIdKB('yukdigitalz-kb-ai-trigger');
	const closeBtn   = getElementByIdKB('yukdigitalz-kb-ai-close');
	const drawer     = getElementByIdKB('yukdigitalz-kb-ai-drawer');
	const layout     = document.querySelector('.yukdigitalz-kb-doc-layout') || querySelectorKB('.yukdigitalz-kb-doc-layout');

	if (!drawer) {
		return;
	}

	const chatContainer = drawer.querySelector('.yukdigitalz-kb-ai-chat-container');
	const chatForm      = chatContainer.querySelector('.yukdigitalz-kb-ai-chat-form');
	const chatInput     = chatContainer.querySelector('.yukdigitalz-kb-ai-chat-input');
	const chatHistory   = chatContainer.querySelector('.yukdigitalz-kb-ai-chat-history');
	const submitBtn     = chatContainer.querySelector('.yukdigitalz-kb-ai-chat-submit');

	if (!chatForm || !chatInput || !chatHistory) {
		return;
	}

	// Open drawer logic
	if (triggerBtn) {
		triggerBtn.addEventListener('click', function() {
			drawer.classList.add('open');
			drawer.setAttribute('aria-hidden', 'false');
			if (layout) {
				layout.classList.add('ai-drawer-open');
			}
			document.documentElement.classList.add('ai-drawer-open');
			document.body.classList.add('ai-drawer-open');
			triggerBtn.style.opacity = '0';
			triggerBtn.style.pointerEvents = 'none';
			setTimeout(() => {
				chatInput.focus();
			}, 300);
		});
	}

	// Close drawer logic
	if (closeBtn) {
		closeBtn.addEventListener('click', function() {
			drawer.classList.remove('open');
			drawer.setAttribute('aria-hidden', 'true');
			if (layout) {
				layout.classList.remove('ai-drawer-open');
			}
			document.documentElement.classList.remove('ai-drawer-open');
			document.body.classList.remove('ai-drawer-open');
			if (triggerBtn) {
				triggerBtn.style.opacity = '1';
				triggerBtn.style.pointerEvents = 'auto';
			}
		});
	}

	let conversationHistory = [];

	chatForm.addEventListener('submit', function(e) {
		e.preventDefault();
		const question = chatInput.value.trim();
		if (!question) {
			return;
		}

		// Clear input fields
		chatInput.value = '';
		chatInput.focus();

		// Render User bubble in log
		appendChatMessage('user', question);

		// Show dynamic thinking dots loader
		const thinkingId = appendChatThinkingIndicator();

		// Add prompt context memory
		conversationHistory.push({ role: 'user', content: question });

		// Lock input during loading
		submitBtn.setAttribute('disabled', 'disabled');
		chatInput.setAttribute('disabled', 'disabled');

		const formData = new FormData();
		formData.append('action', 'yukdigitalz_kb_ai_chat');
		formData.append('message', question);
		formData.append('history', JSON.stringify(conversationHistory));
		formData.append('security', yukdigitalz_kb_vars.nonce);

		fetch(yukdigitalz_kb_vars.ajax_url, {
			method: 'POST',
			body: formData
		})
		.then(response => response.json())
		.then(data => {
			removeChatThinkingIndicator(thinkingId);
			if (data.success && data.data && data.data.response) {
				const reply = data.data.response;
				appendChatMessage('assistant', reply);
				conversationHistory.push({ role: 'assistant', content: reply });
			} else {
				const errorMsg = data.data && data.data.message ? data.data.message : yukdigitalz_kb_vars.strings.voting_error;
				appendChatMessage('assistant', errorMsg, true);
			}

			// 2-second anti-spam client cooldown
			setTimeout(() => {
				submitBtn.removeAttribute('disabled');
				chatInput.removeAttribute('disabled');
				chatInput.focus();
			}, 2000);
		})
		.catch(() => {
			removeChatThinkingIndicator(thinkingId);
			appendChatMessage('assistant', yukdigitalz_kb_vars.strings.voting_error, true);
			setTimeout(() => {
				submitBtn.removeAttribute('disabled');
				chatInput.removeAttribute('disabled');
				chatInput.focus();
			}, 2000);
		});
	});

	function appendChatMessage(role, content, isError = false) {
		const messageWrapper = document.createElement('div');
		messageWrapper.className = `yukdigitalz-kb-chat-message ${role}`;

		const bubble = document.createElement('div');
		bubble.className = 'yukdigitalz-kb-chat-bubble';

		if (isError) {
			bubble.style.color = '#dc2626';
			bubble.textContent = content;
		} else if (role === 'user') {
			bubble.textContent = content;
		} else {
			bubble.innerHTML = formatMarkdownToHTML(content);
		}

		messageWrapper.appendChild(bubble);
		chatHistory.appendChild(messageWrapper);
		chatHistory.scrollTop = chatHistory.scrollHeight; // Auto scroll down
	}

	function appendChatThinkingIndicator() {
		const indicatorId = 'thinking-' + Date.now();
		const messageWrapper = document.createElement('div');
		messageWrapper.className = 'yukdigitalz-kb-chat-message assistant';
		messageWrapper.id = indicatorId;

		const bubble = document.createElement('div');
		bubble.className = 'yukdigitalz-kb-chat-bubble';

		const indicator = document.createElement('div');
		indicator.className = 'yukdigitalz-kb-typing-indicator';
		indicator.innerHTML = '<span></span><span></span><span></span>';

		bubble.appendChild(indicator);
		messageWrapper.appendChild(bubble);
		chatHistory.appendChild(messageWrapper);
		chatHistory.scrollTop = chatHistory.scrollHeight;

		return indicatorId;
	}

	function removeChatThinkingIndicator(id) {
		const indicator = getElementByIdKB(id);
		if (indicator) {
			indicator.remove();
		}
	}

	/**
	 * Basic, clean regex-based markdown parser to avoid external libraries.
	 */
	function formatMarkdownToHTML(text) {
		if (!text) {
			return '';
		}

		// Escape potential HTML tags
		let clean = text
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;');

		// Bold tags: **text**
		clean = clean.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

		// List entries
		clean = clean.replace(/^\s*[-*]\s+(.*)$/gm, '<li>$1</li>');

		// Contiguous li tags wrapped in ul
		clean = clean.replace(/(<li>.*<\/li>)/gs, '<ul>$1</ul>');

		// Paragraph breaks
		clean = clean.replace(/\n\n/g, '</p><p>');
		clean = clean.replace(/\n/g, '<br>');

		return '<p>' + clean + '</p>';
	}
}

/**
 * Injects a Cloudflare-style header pill button for the AI assistant inside Shadow DOM layouts
 * and hides the native floating FAB button.
 */
function injectAIHeaderButton() {
	// Inject Light DOM layout transitions so they cleanly override theme wrappers
	if (!document.getElementById('yukdigitalz-kb-free-ai-light-styles')) {
		const lightStyle = document.createElement('style');
		lightStyle.id = 'yukdigitalz-kb-free-ai-light-styles';
		lightStyle.textContent = `
			html.ai-drawer-open,
			body.ai-drawer-open {
				padding-right: 0 !important;
			}
		`;
		document.head.appendChild(lightStyle);
	}

	const hosts = document.querySelectorAll('.yukdigitalz-kb-doc-layout');
	hosts.forEach(host => {
		const shadowRoot = host.shadowRoot;
		if (!shadowRoot) return;

		// 1. Hide the native floating FAB button trigger
		const fab = shadowRoot.querySelector('#yukdigitalz-kb-ai-trigger');
		if (fab) {
			fab.style.display = 'none';
		}

		// 2. Inject styling overrides directly into the Shadow DOM context
		if (!shadowRoot.querySelector('#yukdigitalz-kb-pro-ai-inject-styles')) {
			const style = document.createElement('style');
			style.id = 'yukdigitalz-kb-pro-ai-inject-styles';
			style.textContent = `
				#yukdigitalz-kb-ai-trigger {
					display: none !important;
				}
				.yukdigitalz-kb-article-header,
				.yukdigitalz-kb-archive-header {
					display: flex !important;
					justify-content: space-between !important;
					align-items: flex-start !important;
					gap: 20px !important;
					border-bottom: 1px solid var(--yukdigitalz-kb-border, #e2e8f0) !important;
					padding-bottom: 16px !important;
					margin-bottom: 24px !important;
				}
				.yukdigitalz-kb-article-header-left {
					flex: 1 !important;
				}
				.yukdigitalz-kb-ai-header-btn {
					display: inline-flex !important;
					align-items: center !important;
					gap: 6px !important;
					background-color: transparent !important;
					border: 1px solid var(--yukdigitalz-kb-border, #e2e8f0) !important;
					padding: 6px 14px !important;
					border-radius: 9999px !important;
					font-size: 0.85rem !important;
					font-weight: 600 !important;
					color: var(--yukdigitalz-kb-text-muted, #64748b) !important;
					cursor: pointer !important;
					transition: all 0.2s ease !important;
					margin-top: 4px !important;
					white-space: nowrap !important;
					font-family: inherit !important;
				}
				.yukdigitalz-kb-ai-header-btn:hover {
					background-color: var(--yukdigitalz-kb-bg-body, #f8fafc) !important;
					color: var(--yukdigitalz-kb-primary, #2563eb) !important;
					border-color: var(--yukdigitalz-kb-primary, #2563eb) !important;
				}
				.yukdigitalz-kb-ai-header-btn svg {
					color: var(--yukdigitalz-kb-accent, #f59e0b) !important;
				}
			`;
			shadowRoot.appendChild(style);
		}

		// 3. Locate the header to append the button
		const articleHeader = shadowRoot.querySelector('.yukdigitalz-kb-article-header') || shadowRoot.querySelector('.yukdigitalz-kb-archive-header');
		if (articleHeader && !articleHeader.querySelector('.yukdigitalz-kb-ai-header-btn')) {
			// Wrap current header child nodes inside a left-aligned container
			const leftDiv = document.createElement('div');
			leftDiv.className = 'yukdigitalz-kb-article-header-left';
			while (articleHeader.firstChild) {
				leftDiv.appendChild(articleHeader.firstChild);
			}
			articleHeader.appendChild(leftDiv);

			// Create the Cloudflare-style Ask AI header button
			const aiBtn = document.createElement('button');
			aiBtn.type = 'button';
			aiBtn.className = 'yukdigitalz-kb-ai-header-btn';
			aiBtn.innerHTML = `
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="14" height="14" style="margin-right: 4px;"><path d="M12 2l2.4 7.2L22 12l-7.6 2.4-2.4 7.2-2.4-7.2L2 12l7.6-2.4z"/></svg>
				<span>Ask AI</span>
			`;

			// Bind trigger action
			aiBtn.addEventListener('click', function() {
				const trigger = shadowRoot.querySelector('#yukdigitalz-kb-ai-trigger');
				if (trigger) {
					trigger.click();
				}
			});

			articleHeader.appendChild(aiBtn);
		}
	});
}
