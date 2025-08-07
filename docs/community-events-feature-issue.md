# Implement Community-Events Relationship Feature

## Overview
Currently, the community events page (`/communities/[slug]/events/`) displays all public events with a placeholder message "Community-specific events coming soon!" This issue implements the missing relationship between communities and events to show actual community-specific events.

## Current State
- Community events page exists and works (no 500 errors)
- Page uses proper two-column layout and follows all coding standards
- Shows all public events as placeholder content
- Members can create regular events, but not community-specific events

## Required Implementation

### 1. Database Schema Updates
- Add `community_id` column to events table (nullable for standalone events)
- Update database migration/activation hooks
- Ensure backward compatibility with existing events

### 2. Community Manager Updates
Add new method to `class-community-manager.php`:
```php
public function get_community_events($community_id, $limit = 20, $offset = 0)
```

### 3. Event Creation Flow
**Key Requirement**: Use existing create event page for both regular events AND community events
- No dropdown selection on main create event form
- Detect community context from URL/referrer when creating events
- Track community_id automatically based on where event creation was initiated
- Keep it simple - events belong to ONE community or NO community (not multiple)

### 4. URL Routing Considerations
Determine how users create community events:
- From community events tab with "Create Event" button?
- Pass community context to existing create event form?
- Handle community_id parameter in event creation flow?

### 5. Template Updates
**Critical**: Do not change structure of existing pages we've perfected
- Update `community-events-content.php` to use actual community events
- Remove placeholder message and use real community event filtering
- Possibly update event creation templates to handle community context

### 6. Permissions & Logic
- Any community member can create events for that community
- Community events appear in both community events tab AND main events listing
- Standalone events (community_id = null) appear only in main events

## Success Criteria
- [ ] Community events page shows only events associated with that specific community
- [ ] Members can create events that are automatically associated with the community
- [ ] Existing event creation flow works unchanged for regular events
- [ ] All existing page structures remain intact
- [ ] Database changes are backward compatible
- [ ] No 500 errors or breaking changes

## Technical Notes
- Follow all existing coding standards from CLAUDE.md
- Use pm- prefixed CSS classes only
- No emojis anywhere in implementation
- Proper WordPress security (nonces, sanitization)
- Use existing Event Manager and Community Manager patterns

## Files Likely to be Modified
- `includes/class-community-manager.php` - Add get_community_events method
- `includes/class-event-manager.php` - Update create_event method
- `templates/community-events-content.php` - Use real community events
- Database activation/migration code
- Possibly event creation templates for community context

## Context
This builds on the successfully implemented community events page that uses proper two-column layout and follows all project standards. The page currently works perfectly - this issue just adds the missing data relationship to show actual community events instead of placeholder content.