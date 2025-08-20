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
- **Close Circle**: User's own communities and their direct members
- **Trusted Circle**: Close + members of those communities' other communities  
- **Extended Circle**: Trusted + members of those communities' other communities

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

## Implementation Complete

### Circle Logic Implemented
- `class-circle-scope.php` - Complete circle resolution based on community membership
- Real-time scope calculation for Close, Trusted, and Extended circles
- Proper conversation filtering by user and community scope

### TODO Items
- Add server-side caching with short TTL (performance optimization)
- Implement "Load More" pagination UI
- Add analytics tracking integration  
- Enhance empty state messaging per circle

## Testing
- Verify button states and ARIA attributes
- Test keyboard navigation
- Confirm AJAX filtering works
- Check mobile responsiveness
- Validate conversation smart titles display correctly