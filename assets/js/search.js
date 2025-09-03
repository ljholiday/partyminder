/**
 * PartyMinder Search Functionality
 * Handles omnibox search with real-time results
 */

jQuery(document).ready(function($) {
    const searchInput = $('#pm-search-input');
    const searchResults = $('#pm-search-results');
    let searchTimeout;
    
    if (!searchInput.length) return;
    
    // Search function
    function performSearch(query) {
        if (query.length < 2) {
            searchResults.hide().empty();
            return;
        }
        
        $.ajax({
            url: '/wp-json/partyminder/v1/search',
            method: 'GET',
            data: {
                q: query,
                limit: 10
            },
            beforeSend: function() {
                searchResults.html('<div class="pm-text-center pm-p-4">Searching...</div>').show();
            },
            success: function(response) {
                if (response.items && response.items.length > 0) {
                    renderSearchResults(response.items);
                } else {
                    searchResults.html('<div class="pm-text-center pm-p-4 pm-text-muted">No results found</div>').show();
                }
            },
            error: function() {
                searchResults.html('<div class="pm-text-center pm-p-4 pm-text-error">Search error occurred</div>').show();
            }
        });
    }
    
    // Render search results
    function renderSearchResults(items) {
        let html = '<div class="pm-search-results-list">';
        
        items.forEach(function(item) {
            const typeIcon = getTypeIcon(item.entity_type);
            html += `
                <div class="pm-search-result-item">
                    <div class="pm-flex pm-gap-4">
                        <div class="pm-search-result-icon">${typeIcon}</div>
                        <div class="pm-flex-1">
                            <h4 class="pm-search-result-title">
                                <a href="${item.url}" class="pm-text-primary">${item.title}</a>
                            </h4>
                            <p class="pm-search-result-snippet pm-text-muted">${item.snippet}</p>
                            <div class="pm-search-result-meta">
                                <span class="pm-badge pm-badge-${item.entity_type}">${item.entity_type}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        searchResults.html(html).show();
    }
    
    // Get icon for entity type
    function getTypeIcon(type) {
        const icons = {
            'event': '📅',
            'community': '👥', 
            'conversation': '💬',
            'member': '👤'
        };
        return icons[type] || '📄';
    }
    
    // Handle input
    searchInput.on('input', function() {
        const query = $(this).val().trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            searchResults.hide().empty();
            return;
        }
        
        searchTimeout = setTimeout(function() {
            performSearch(query);
        }, 300);
    });
    
    // Handle focus
    searchInput.on('focus', function() {
        const query = $(this).val().trim();
        if (query.length >= 2) {
            performSearch(query);
        }
    });
    
    // Hide results when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.pm-search-box').length) {
            searchResults.hide();
        }
    });
    
    // Handle keyboard navigation
    searchInput.on('keydown', function(e) {
        const results = searchResults.find('.pm-search-result-item');
        let current = results.filter('.active').index();
        
        if (e.keyCode === 38) { // Up arrow
            e.preventDefault();
            current = current > 0 ? current - 1 : results.length - 1;
            results.removeClass('active').eq(current).addClass('active');
        } else if (e.keyCode === 40) { // Down arrow
            e.preventDefault();
            current = current < results.length - 1 ? current + 1 : 0;
            results.removeClass('active').eq(current).addClass('active');
        } else if (e.keyCode === 13) { // Enter
            e.preventDefault();
            const activeResult = results.filter('.active');
            if (activeResult.length) {
                const link = activeResult.find('a').first();
                if (link.length) {
                    window.location.href = link.attr('href');
                }
            }
        } else if (e.keyCode === 27) { // Escape
            searchResults.hide();
            searchInput.blur();
        }
    });
});