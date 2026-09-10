(function() {

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

	// Initialize Header AI Button
	initAIHeaderButtons();
});

/**
 * Sidebar Accordion functionality with state preservation via localStorage.
 */
function initSidebarAccordion() {
	// Mobile Sidebar Accordion Toggle
	const mobileToggles = querySelectorAllKB('.yukdigitalz-kb-mobile-nav-toggle');
	mobileToggles.forEach(toggle => {
		toggle.addEventListener('click', function(e) {
			e.preventDefault();
			const sidebarNav = toggle.closest('.yukdigitalz-kb-sidebar-nav');
			if (!sidebarNav) return;
			const isExpanded = sidebarNav.classList.contains('mobile-expanded');
			if (isExpanded) {
				sidebarNav.classList.remove('mobile-expanded');
				toggle.setAttribute('aria-expanded', 'false');
			} else {
				sidebarNav.classList.add('mobile-expanded');
				toggle.setAttribute('aria-expanded', 'true');
			}
		});
	});

	const headerWraps = querySelectorAllKB('.yukdigitalz-kb-sidebar-cat-header-wrap, .yukdigitalz-kb-sidebar-subcat-header-wrap');
	
	// Load collapsed/expanded configuration
	let accordionState = {};
	try {
		accordionState = JSON.parse(localStorage.getItem('yukdigitalz_kb_sidebar_state')) || {};
	} catch (e) {
		accordionState = {};
	}

	if (headerWraps.length > 0) {
		headerWraps.forEach(wrap => {
			const targetId = wrap.getAttribute('data-target');
			if (!targetId) return;
			const content  = getElementByIdKB(targetId);
			if (!content) return;

			const toggleBtn = wrap.querySelector('.yukdigitalz-kb-sidebar-toggle-btn');

			// Auto-expand if category holds active article, active category, or has stored state as expanded
			const hasActiveChild = content.querySelector('.yukdigitalz-kb-active-article') !== null 
				|| wrap.classList.contains('is-expanded') 
				|| wrap.querySelector('.is-active') !== null;
			
			let shouldExpand = false;
			if (hasActiveChild) {
				// Active page section MUST always expand regardless of past localStorage
				shouldExpand = true;
			} else if (accordionState[targetId] !== undefined) {
				shouldExpand = (accordionState[targetId] === true);
			} else {
				// Default behavior: top-level categories start expanded, subcategories start collapsed
				shouldExpand = wrap.classList.contains('yukdigitalz-kb-sidebar-cat-header-wrap');
			}

			if (shouldExpand) {
				content.style.maxHeight = 'none';
				wrap.classList.add('is-expanded');
				wrap.setAttribute('aria-expanded', 'true');
				if (toggleBtn) {
					toggleBtn.classList.add('is-expanded');
					toggleBtn.setAttribute('aria-expanded', 'true');
				}
			} else {
				content.style.maxHeight = '0px';
				wrap.classList.remove('is-expanded');
				wrap.setAttribute('aria-expanded', 'false');
				if (toggleBtn) {
					toggleBtn.classList.remove('is-expanded');
					toggleBtn.setAttribute('aria-expanded', 'false');
				}
			}

			function toggleItem(e) {
				if (e) {
					e.preventDefault();
					e.stopPropagation();
				}

				const isExpanded = wrap.classList.contains('is-expanded');
				if (isExpanded) {
					content.style.maxHeight = '0px';
					wrap.classList.remove('is-expanded');
					wrap.setAttribute('aria-expanded', 'false');
					if (toggleBtn) {
						toggleBtn.classList.remove('is-expanded');
						toggleBtn.setAttribute('aria-expanded', 'false');
					}
					accordionState[targetId] = false;
				} else {
					content.style.maxHeight = 'none';
					wrap.classList.add('is-expanded');
					wrap.setAttribute('aria-expanded', 'true');
					if (toggleBtn) {
						toggleBtn.classList.add('is-expanded');
						toggleBtn.setAttribute('aria-expanded', 'true');
					}
					accordionState[targetId] = true;

					// Expand parent accordion containers if nested inside another
					let parentArticles = wrap.closest('.yukdigitalz-kb-sidebar-articles');
					while (parentArticles) {
						parentArticles.style.maxHeight = 'none';
						const parentGroup = parentArticles.parentElement;
						if (parentGroup) {
							const parentWrap = parentGroup.querySelector(':scope > .yukdigitalz-kb-sidebar-cat-header-wrap, :scope > .yukdigitalz-kb-sidebar-subcat-header-wrap');
							if (parentWrap) {
								parentWrap.classList.add('is-expanded');
								parentWrap.setAttribute('aria-expanded', 'true');
								const pBtn = parentWrap.querySelector('.yukdigitalz-kb-sidebar-toggle-btn');
								if (pBtn) {
									pBtn.classList.add('is-expanded');
									pBtn.setAttribute('aria-expanded', 'true');
								}
								const pTargetId = parentWrap.getAttribute('data-target');
								if (pTargetId) {
									accordionState[pTargetId] = true;
								}
							}
						}
						parentArticles = parentGroup ? parentGroup.closest('.yukdigitalz-kb-sidebar-articles') : null;
					}
				}
				localStorage.setItem('yukdigitalz_kb_sidebar_state', JSON.stringify(accordionState));
			}

			wrap.addEventListener('click', toggleItem);
			wrap.addEventListener('keydown', function(e) {
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					toggleItem(e);
				}
			});
		});
	} else {
		// Fallback for legacy markup
		const toggles = querySelectorAllKB('.yukdigitalz-kb-sidebar-toggle-btn, .yukdigitalz-kb-sidebar-cat-header');
		if (toggles.length === 0) {
			return;
		}

		toggles.forEach(toggle => {
			const targetId = toggle.getAttribute('data-target');
			const content  = getElementByIdKB(targetId);
			if (!content) {
				return;
			}

			const hasActiveChild = content.querySelector('.yukdigitalz-kb-active-article') !== null || toggle.classList.contains('is-expanded');
			
			if (hasActiveChild || accordionState[targetId] === true || (accordionState[targetId] === undefined && hasActiveChild)) {
				content.style.maxHeight = 'none';
				toggle.classList.add('is-expanded');
				toggle.setAttribute('aria-expanded', 'true');
			} else {
				content.style.maxHeight = '0px';
				toggle.classList.remove('is-expanded');
				toggle.setAttribute('aria-expanded', 'false');
			}

			toggle.addEventListener('click', function(e) {
				e.stopPropagation();
				const isExpanded = toggle.classList.contains('is-expanded');
				if (isExpanded) {
					content.style.maxHeight = '0px';
					toggle.classList.remove('is-expanded');
					toggle.setAttribute('aria-expanded', 'false');
					accordionState[targetId] = false;
				} else {
					content.style.maxHeight = 'none';
					toggle.classList.add('is-expanded');
					toggle.setAttribute('aria-expanded', 'true');
					accordionState[targetId] = true;

					let parentArticles = toggle.closest('.yukdigitalz-kb-sidebar-articles');
					while (parentArticles) {
						parentArticles.style.maxHeight = 'none';
						parentArticles = parentArticles.parentElement ? parentArticles.parentElement.closest('.yukdigitalz-kb-sidebar-articles') : null;
					}
				}
				localStorage.setItem('yukdigitalz_kb_sidebar_state', JSON.stringify(accordionState));
			});
		});
	}
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
	const backdrop   = getElementByIdKB('yukdigitalz-kb-ai-backdrop');
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

	function openAIDrawer() {
		// Hoist backdrop and drawer directly to document.body on mobile/tablet to break free from theme stacking contexts
		if (window.innerWidth <= 1024 && drawer.parentElement !== document.body) {
			drawer._parentContainer = drawer.parentElement;
			drawer._nextSibling = drawer.nextSibling;
			if (backdrop) {
				document.body.appendChild(backdrop);
			}
			document.body.appendChild(drawer);
		}

		drawer.classList.add('open');
		drawer.setAttribute('aria-hidden', 'false');
		if (backdrop) {
			backdrop.classList.add('open');
			backdrop.setAttribute('aria-hidden', 'false');
		}
		if (layout) {
			layout.classList.add('ai-drawer-open');
		}
		document.documentElement.classList.add('ai-drawer-open');
		document.body.classList.add('ai-drawer-open');
		setTimeout(() => {
			chatInput.focus();
		}, 300);
	}

	function closeAIDrawer() {
		drawer.classList.remove('open');
		drawer.setAttribute('aria-hidden', 'true');
		if (backdrop) {
			backdrop.classList.remove('open');
			backdrop.setAttribute('aria-hidden', 'true');
		}
		if (layout) {
			layout.classList.remove('ai-drawer-open');
		}
		document.documentElement.classList.remove('ai-drawer-open');
		document.body.classList.remove('ai-drawer-open');

		// Restore elements to original parent container if hoisted
		if (drawer && drawer._parentContainer) {
			if (backdrop) {
				drawer._parentContainer.insertBefore(backdrop, drawer._nextSibling);
			}
			drawer._parentContainer.insertBefore(drawer, drawer._nextSibling);
			delete drawer._parentContainer;
			delete drawer._nextSibling;
		}
	}

	window.yukdigitalzKBOpenAIDrawer = openAIDrawer;
	window.yukdigitalzKBCloseAIDrawer = closeAIDrawer;

	// Open drawer logic
	if (triggerBtn) {
		triggerBtn.addEventListener('click', openAIDrawer);
	}

	// Close drawer logic
	if (closeBtn) {
		closeBtn.addEventListener('click', closeAIDrawer);
	}

	if (backdrop) {
		backdrop.addEventListener('click', closeAIDrawer);
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
				const errorMsg = data.data && data.data.message ? data.data.message : (yukdigitalz_kb_vars.strings.ai_error || yukdigitalz_kb_vars.strings.voting_error);
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
			appendChatMessage('assistant', yukdigitalz_kb_vars.strings.ai_error || yukdigitalz_kb_vars.strings.voting_error, true);
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
 * Handles click interactions for the Ask AI header button.
 */
function initAIHeaderButtons() {
	document.addEventListener('click', function(e) {
		const aiBtn = e.target.closest('.yukdigitalz-kb-ai-header-btn');
		if (!aiBtn) {
			return;
		}

		e.preventDefault();
		e.stopPropagation();

		if (typeof window.yukdigitalzKBOpenAIDrawer === 'function') {
			window.yukdigitalzKBOpenAIDrawer();
		} else {
			const trigger = getElementByIdKB('yukdigitalz-kb-ai-trigger');
			if (trigger) {
				trigger.click();
			}
		}
	});
}
})();
