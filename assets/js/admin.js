/**
 * Yukdigitalz KB Admin Settings JavaScript.
 * Manages UI transitions such as tab-switching inside the settings screen.
 */
document.addEventListener('DOMContentLoaded', function() {
	const tabs = document.querySelectorAll('.yukdigitalz-kb-nav-tabs a');
	const contents = document.querySelectorAll('.yukdigitalz-kb-tab-content');

	if (tabs.length === 0) {
		return;
	}

	tabs.forEach(tab => {
		tab.addEventListener('click', function(e) {
			e.preventDefault();

			// Remove active highlight from all tabs
			tabs.forEach(t => t.classList.remove('nav-tab-active'));
			// Highlight current active tab
			this.classList.add('nav-tab-active');

			// Hide all tab panes
			contents.forEach(c => c.classList.remove('active'));

			// Display targeted panel content
			const target = this.getAttribute('href');
			const targetContent = document.querySelector(target);
			if (targetContent) {
				targetContent.classList.add('active');
			}
		});
	});

	// Drag and drop for Category Reordering
	const orderList = document.querySelector('.yukdigitalz-kb-order-list');
	const orderInput = document.getElementById('yukdigitalz-kb-category_order_input');

	if (orderList && orderInput) {
		const items = orderList.querySelectorAll('li');
		
		items.forEach(item => {
			item.setAttribute('draggable', 'true');
			
			item.addEventListener('dragstart', () => {
				item.classList.add('dragging');
			});
			
			item.addEventListener('dragend', () => {
				item.classList.remove('dragging');
				updateOrder();
			});
		});
		
		orderList.addEventListener('dragover', e => {
			e.preventDefault();
			const afterElement = getDragAfterElement(orderList, e.clientY);
			const dragging = document.querySelector('.dragging');
			if (dragging) {
				if (afterElement == null) {
					orderList.appendChild(dragging);
				} else {
					orderList.insertBefore(dragging, afterElement);
				}
			}
		});
		
		function getDragAfterElement(container, y) {
			const draggableElements = [...container.querySelectorAll('li:not(.dragging)')];
			
			return draggableElements.reduce((closest, child) => {
				const box = child.getBoundingClientRect();
				const offset = y - box.top - box.height / 2;
				if (offset < 0 && offset > closest.offset) {
					return { offset: offset, element: child };
				} else {
					return closest;
				}
			}, { offset: Number.NEGATIVE_INFINITY }).element;
		}
		
		function updateOrder() {
			const updatedItems = [...orderList.querySelectorAll('li')];
			const ids = updatedItems.map(item => parseInt(item.getAttribute('data-id'), 10));
			orderInput.value = JSON.stringify(ids);
		}
	}

	// Clear Search Logs AJAX Action
	const clearLogsBtn = document.getElementById('yukdigitalz-kb-clear-logs-btn');
	if (clearLogsBtn && typeof yukdigitalz_kb_admin_vars !== 'undefined') {
		clearLogsBtn.addEventListener('click', function() {
			if (!confirm('Are you sure you want to clear all search logs?')) {
				return;
			}
			
			clearLogsBtn.setAttribute('disabled', 'disabled');
			clearLogsBtn.textContent = 'Clearing...';
			
			const formData = new FormData();
			formData.append('action', 'yukdigitalz_kb_clear_search_logs');
			formData.append('security', yukdigitalz_kb_admin_vars.nonce);
			
			fetch(yukdigitalz_kb_admin_vars.ajax_url, {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					window.location.reload();
				} else {
					alert(data.data.message || 'Error clearing logs.');
					clearLogsBtn.removeAttribute('disabled');
					clearLogsBtn.textContent = 'Clear Search Logs';
				}
			})
			.catch(() => {
				alert('Connection error. Please try again.');
				clearLogsBtn.removeAttribute('disabled');
				clearLogsBtn.textContent = 'Clear Search Logs';
			});
		});
	}
});
