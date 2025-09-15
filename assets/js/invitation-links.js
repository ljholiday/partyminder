/**
 * Invitation Links JavaScript
 * Handles copying invitation links and messages
 */

window.PartyMinderInvitations = (function($) {
	'use strict';

	function init() {
		bindEvents();
	}

	function bindEvents() {
		// Copy invitation link
		$(document).on('click', '.pm-copy-invitation-link', function(e) {
			e.preventDefault();
			copyInvitationLink();
		});

		// Copy invitation link with message
		$(document).on('click', '.pm-copy-invitation-with-message', function(e) {
			e.preventDefault();
			copyInvitationWithMessage();
		});
	}

	function copyInvitationLink() {
		const linkInput = document.getElementById('invitation-link');
		if (!linkInput) return;

		linkInput.select();
		document.execCommand('copy');

		showCopySuccess('Invitation link copied to clipboard!');
	}

	function copyInvitationWithMessage() {
		const linkInput = document.getElementById('invitation-link');
		const messageInput = document.getElementById('custom-message');

		if (!linkInput) return;

		const link = linkInput.value;
		const message = messageInput ? messageInput.value.trim() : '';

		let textToCopy = link;
		if (message) {
			textToCopy = message + '\n\n' + link;
		}

		if (navigator.clipboard) {
			navigator.clipboard.writeText(textToCopy).then(function() {
				showCopySuccess('Invitation link with message copied to clipboard!');
			}).catch(function() {
				fallbackCopy(textToCopy);
			});
		} else {
			fallbackCopy(textToCopy);
		}
	}

	function fallbackCopy(text) {
		const textarea = document.createElement('textarea');
		textarea.value = text;
		document.body.appendChild(textarea);
		textarea.select();
		document.execCommand('copy');
		document.body.removeChild(textarea);

		showCopySuccess('Text copied to clipboard!');
	}

	function showCopySuccess(message) {
		// Create or update success message
		let successDiv = document.getElementById('copy-success-message');
		if (!successDiv) {
			successDiv = document.createElement('div');
			successDiv.id = 'copy-success-message';
			successDiv.className = 'pm-alert pm-alert-success pm-mt-2';
			successDiv.style.display = 'none';

			const cardBody = document.querySelector('.pm-copy-invitation-link').closest('.pm-card-body');
			if (cardBody) {
				cardBody.appendChild(successDiv);
			}
		}

		successDiv.textContent = message;
		successDiv.style.display = 'block';

		// Hide after 3 seconds
		setTimeout(function() {
			successDiv.style.display = 'none';
		}, 3000);
	}

	// Public API
	return {
		init: init
	};

})(jQuery);

// Initialize when document is ready
jQuery(document).ready(function() {
	PartyMinderInvitations.init();
});