# Circles of Trust Navigation

This feature adds conversation filtering by PartyMinder's circles of trust: Close, Trusted, and Extended.

## Implementation

### Files Added
- `templates/partials/conversations-nav.php` - Navigation button group
- `templates/partials/conversations-list.php` - Reusable conversation list
- `assets/js/conversations-circles.js` - AJAX filtering functionality
- `assets/css/partyminder.css` - Navigation styling (added to existing file)

### Files Modified
- `includes/class-conversation-ajax-handler.php` - Added AJAX endpoint
- `templates/conversations.php` - Integrated navigation
- `templates/dashboard-content.php` - Added navigation to dashboard
- `partyminder.php` - Enqueued JavaScript for conversations and dashboard pages

### AJAX Endpoint
- **Action**: `partyminder_get_conversations`
- **Parameters**: `circle`, `topic_slug`, `page`, `nonce`
- **Response**: JSON with HTML content and pagination metadata

### Circle Definitions
- **Close Circle**: Direct connections (1st circle)
- **Trusted Circle**: Close + vetted 2nd circle  
- **Extended Circle**: Close + Trusted + broader network

### Accessibility Features
- ARIA roles and attributes for tab navigation
- Keyboard navigation support (arrow keys, Home, End)
- Screen reader announcements for content updates
- Focus management

### Performance
- Server-side caching ready (TODO: implement cache layer)
- Pagination support (20 conversations per page)
- Loading states with spinner animation

## Usage

The navigation appears automatically on:
- Main conversations page (`/conversations`)
- Dashboard conversations section (`/dashboard`)

Users can click circle buttons to filter conversations, with AJAX loading and smart title display maintaining context.

## TODO Items
- Implement actual circle-based identity scope resolution
- Add server-side caching with short TTL
- Implement "Load More" pagination UI
- Add analytics tracking integration
- Enhance empty state messaging per circle

## Testing
- Verify button states and ARIA attributes
- Test keyboard navigation
- Confirm AJAX filtering works
- Check mobile responsiveness
- Validate conversation smart titles display correctly