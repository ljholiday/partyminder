/**
 * PartyMinder Search Functionality
 * Handles omnibox search with real-time results
 */

jQuery(document).ready(function($) {
    const desktopSearchInput = $('#pm-search-input');
    const mobileSearchInput = $('#pm-mobile-search-input');
    const desktopSearchResults = $('#pm-search-results');
    const mobileSearchResults = $('#pm-mobile-search-results');
    let searchTimeout;
    
    // Handle both desktop and mobile search inputs
    const searchInputs = $('.pm-input[id*="search-input"]');
    
    if (!searchInputs.length) return;
    
    // Search function
    function performSearch(query, resultsContainer) {
        if (query.length < 2) {
            resultsContainer.hide().empty();
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
                resultsContainer.html('<div class="pm-text-center pm-p-4">Searching...</div>').show();
            },
            success: function(response) {
                if (response.items && response.items.length > 0) {
                    renderSearchResults(response.items, resultsContainer);
                } else {
                    resultsContainer.html('<div class="pm-text-center pm-p-4 pm-text-muted">No results found</div>').show();
                }
            },
            error: function() {
                resultsContainer.html('<div class="pm-text-center pm-p-4 pm-text-error">Search error occurred</div>').show();
            }
        });
    }
    
    // Render search results
    function renderSearchResults(items, resultsContainer) {
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
        resultsContainer.html(html).show();
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
    
    // Handle input for both desktop and mobile search
    searchInputs.on('input', function() {
        const query = $(this).val().trim();
        const currentInput = $(this);
        const resultsContainer = currentInput.attr('id') === 'pm-search-input' ? desktopSearchResults : mobileSearchResults;
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            resultsContainer.hide().empty();
            return;
        }
        
        searchTimeout = setTimeout(function() {
            performSearch(query, resultsContainer);
        }, 300);
    });
    
    // Handle focus for both inputs
    searchInputs.on('focus', function() {
        const query = $(this).val().trim();
        const currentInput = $(this);
        const resultsContainer = currentInput.attr('id') === 'pm-search-input' ? desktopSearchResults : mobileSearchResults;
        
        if (query.length >= 2) {
            performSearch(query, resultsContainer);
        }
    });
    
    // Hide results when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.pm-search-box, .pm-mb-4').length) {
            desktopSearchResults.hide();
            mobileSearchResults.hide();
        }
    });
    
    // Handle keyboard navigation for both inputs
    searchInputs.on('keydown', function(e) {
        const currentInput = $(this);
        const resultsContainer = currentInput.attr('id') === 'pm-search-input' ? desktopSearchResults : mobileSearchResults;
        const results = resultsContainer.find('.pm-search-result-item');
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
            resultsContainer.hide();
            currentInput.blur();
        }
    });
});