/**
 * Conversations Circles Navigation
 * Handles filtering conversations by circles of trust
 */

jQuery(document).ready(function($) {
	'use strict';

	// Toast notification function
	function pmToast(message, type = 'error') {
		// Simple console log for now - can be enhanced with actual toast UI
		console.log(`[PartyMinder ${type.toUpperCase()}]: ${message}`);
		
		// You can replace this with your actual toast implementation
		if (type === 'error') {
			alert(message);
		}
	}

	// Analytics tracking function
	function pmAnalytics(event, data) {
		// Simple console log for now - can be enhanced with actual analytics
		console.log(`[PartyMinder Analytics]: ${event}`, data);
	}

	// Main conversations circle navigation handler
	function initConversationsCircles() {
		const $nav = $('.pm-conversations-nav');
		const $list = $('#pm-convo-list');

		if (!$nav.length || !$list.length) {
			return; // Navigation not present on this page
		}

		// Load conversations for a specific circle
		function loadCircle(circle, options = {}) {
			const topicSlug = $list.data('topic') || options.topic || '';
			const page = options.page || 1;

			// Add loading state
			$list.addClass('pm-is-loading');

			// Prepare AJAX data
			const ajaxData = {
				action: 'partyminder_get_conversations',
				nonce: partyminder_ajax.nonce,
				circle: circle,
				page: page
			};

			// Add topic if present
			if (topicSlug) {
				ajaxData.topic_slug = topicSlug;
			}

			// Make AJAX request
			$.post(partyminder_ajax.ajax_url, ajaxData)
				.done(function(response) {
					if (response && response.success) {
						// Update the list content
						$list.html(response.data.html);
						
						// Track analytics
						pmAnalytics('conversations.filter', {
							circle: circle,
							topic: topicSlug,
							count: response.data.meta.count
						});

						// TODO: Handle pagination if response.data.meta.has_more is true
						
					} else {
						pmToast(response.data || 'Could not load conversations.');
					}
				})
				.fail(function(xhr, status, error) {
					console.error('AJAX error:', status, error);
					pmToast('Network error. Please try again.');
				})
				.always(function() {
					// Remove loading state
					$list.removeClass('pm-is-loading');
				});
		}

		// Handle circle button clicks
		$nav.on('click', 'button[data-circle]', function(e) {
			e.preventDefault();
			
			const $button = $(this);
			const circle = $button.data('circle');

			// Update button states
			$nav.find('button')
				.removeClass('is-active')
				.attr('aria-selected', 'false');
			
			$button
				.addClass('is-active')
				.attr('aria-selected', 'true');

			// Load conversations for the selected circle
			loadCircle(circle);
		});

		// Load initial circle (default to 'close')
		const initialCircle = $nav.find('button.is-active').data('circle') || 'close';
		loadCircle(initialCircle);

		// Keyboard navigation support
		$nav.on('keydown', 'button', function(e) {
			const $buttons = $nav.find('button');
			const currentIndex = $buttons.index(this);
			let targetIndex = currentIndex;

			switch(e.which) {
				case 37: // Left arrow
					targetIndex = currentIndex > 0 ? currentIndex - 1 : $buttons.length - 1;
					break;
				case 39: // Right arrow
					targetIndex = currentIndex < $buttons.length - 1 ? currentIndex + 1 : 0;
					break;
				case 36: // Home
					targetIndex = 0;
					break;
				case 35: // End
					targetIndex = $buttons.length - 1;
					break;
				default:
					return; // Exit if not a navigation key
			}

			e.preventDefault();
			$buttons.eq(targetIndex).focus().click();
		});
	}

	// Initialize when DOM is ready
	initConversationsCircles();

	// Re-initialize if content is dynamically loaded
	$(document).on('partyminder:conversations:reload', initConversationsCircles);
});