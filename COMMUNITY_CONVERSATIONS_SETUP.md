# Community Conversations Setup

## Professional Implementation

This implementation follows PartyMinder coding standards:
- No emojis in professional web application
- Proper `.pm-` CSS class prefixes
- Clean, technical language
- WordPress coding standards compliance

## What's Been Added

### 1. Database Schema
- Added `community_id` column to `partyminder_conversations` table
- Automatic upgrade script that adds the column to existing installations

### 2. PHP Backend
- Extended `PartyMinder_Conversation_Manager` with community conversation methods
- Updated `PartyMinder_Community_Manager` to auto-create welcome conversations
- Enhanced AJAX handlers to support community conversations

### 3. Frontend Templates
- Created `community-conversations-content.php` template
- Updated single community page to show recent conversations
- Enhanced dashboard with community discussions section
- Updated create conversation form to support community context

### 4. URL Routing
- Added rewrite rule: `/communities/{slug}/conversations`
- Added routing handlers and content injection methods

## To Activate

### 1. Flush Rewrite Rules
After uploading these changes, flush WordPress rewrite rules:
- Go to WordPress Admin → Settings → Permalinks
- Click "Save Changes" (this flushes rewrite rules)

### 2. Database Upgrade
The database will automatically upgrade when the plugin loads. The upgrade:
- Adds `community_id` column to conversations table
- Adds database index for performance

### 3. Test the Functionality

#### Test URLs:
- Community overview: `/communities/{community-slug}`
- Community conversations: `/communities/{community-slug}/conversations`
- Create community conversation: `/create-conversation?community_id={id}`

#### Test Steps:
1. Visit a community page - should see "Conversations" tab
2. Click conversations tab - should see dedicated conversations page
3. Create a new community - should auto-create welcome conversation
4. Start a conversation from community page - should associate with community
5. Check dashboard - should show community discussions section

## Key Features

### Community Context
- Conversations are properly associated with communities
- Community conversations appear on community pages
- Separate from general and event conversations

### Automatic Welcome Conversations
- New communities get pinned welcome conversations
- Uses "Welcome & Introductions" topic
- Created by community founder

### Dashboard Integration
- Community conversations appear alongside event conversations
- Expandable/collapsible sections
- Shows reply counts and activity

### Navigation
- "Conversations" tab in community navigation
- Easy access to start community-specific conversations
- Breadcrumb navigation for context

## Database Schema

The `partyminder_conversations` table now supports:
- `event_id` - for event conversations
- `community_id` - for community conversations  
- `NULL` values for general conversations

This allows the same conversation system to handle:
- General conversations (no event_id or community_id)
- Event conversations (has event_id)
- Community conversations (has community_id)