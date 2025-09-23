# PartyMinder Database Structure

> **Note**: Database schema updated and consolidated as of 2025-01-30. Major schema changes completed 2025-09-02 for circles implementation and privacy/visibility field standardization. All tables now have unified creation methods in `class-activator.php` with proper migration support.

## Core Tables

### Events System

#### `partyminder_events` - Main events table
```sql
id mediumint(9) NOT NULL AUTO_INCREMENT
title varchar(255) NOT NULL
slug varchar(255) NOT NULL
description text
excerpt text
event_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL
event_time varchar(20) DEFAULT ''
guest_limit int(11) DEFAULT 0
venue_info text
host_email varchar(100) DEFAULT ''
host_notes text
ai_plan longtext
privacy varchar(20) DEFAULT 'public'
event_status varchar(20) DEFAULT 'active'
author_id bigint(20) UNSIGNED DEFAULT 1
community_id mediumint(9) DEFAULT NULL
featured_image varchar(255) DEFAULT ''
meta_title varchar(255) DEFAULT ''
meta_description text DEFAULT ''
created_at datetime DEFAULT CURRENT_TIMESTAMP
updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

#### `partyminder_guests` - Event attendees/RSVPs (includes anonymous RSVP support)
```sql
id mediumint(9) NOT NULL AUTO_INCREMENT
rsvp_token varchar(255) DEFAULT ''
temporary_guest_id varchar(32) DEFAULT ''
converted_user_id bigint(20) UNSIGNED DEFAULT NULL
event_id mediumint(9) NOT NULL
name varchar(100) NOT NULL
email varchar(100) NOT NULL
phone varchar(20) DEFAULT ''
status varchar(20) DEFAULT 'pending'
dietary_restrictions text
plus_one tinyint(1) DEFAULT 0
plus_one_name varchar(100) DEFAULT ''
notes text
rsvp_date datetime DEFAULT CURRENT_TIMESTAMP
reminder_sent tinyint(1) DEFAULT 0
PRIMARY KEY (id)
KEY event_id (event_id)
KEY email (email)
KEY status (status)
KEY rsvp_token (rsvp_token)
KEY temporary_guest_id (temporary_guest_id)
KEY converted_user_id (converted_user_id)
UNIQUE KEY unique_guest_event (event_id, email)
```

#### `partyminder_event_invitations` - Event invitation tracking
```sql
id mediumint(9) NOT NULL AUTO_INCREMENT
event_id mediumint(9) NOT NULL
invited_by_user_id bigint(20) UNSIGNED NOT NULL
invited_email varchar(100) NOT NULL
invitation_token varchar(32) NOT NULL
status varchar(20) DEFAULT 'pending'
expires_at datetime DEFAULT NULL
created_at datetime DEFAULT CURRENT_TIMESTAMP
responded_at datetime DEFAULT NULL
PRIMARY KEY (id)
KEY event_id (event_id)
KEY invited_by_user_id (invited_by_user_id)
KEY invited_email (invited_email)
KEY invitation_token (invitation_token)
KEY status (status)
```

#### `partyminder_event_rsvps` - Modern RSVP flow (separate from guests)
```sql
id mediumint(9) NOT NULL AUTO_INCREMENT
event_id mediumint(9) NOT NULL
name varchar(100) NOT NULL
email varchar(100) NOT NULL
phone varchar(20) DEFAULT ''
dietary_restrictions text DEFAULT ''
accessibility_needs text DEFAULT ''
plus_one tinyint(1) DEFAULT 0
plus_one_name varchar(100) DEFAULT ''
plus_one_dietary text DEFAULT ''
notes text DEFAULT ''
status varchar(20) DEFAULT 'pending'
invitation_token varchar(255) DEFAULT ''
user_id bigint(20) UNSIGNED DEFAULT NULL
created_at datetime DEFAULT CURRENT_TIMESTAMP
updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
PRIMARY KEY (id)
KEY event_id (event_id)
KEY email (email)
KEY status (status)
KEY user_id (user_id)
KEY invitation_token (invitation_token)
```

### Communities System

#### `partyminder_communities` - Communities/groups
```sql
id mediumint(9) NOT NULL AUTO_INCREMENT
name varchar(255) NOT NULL
slug varchar(255) NOT NULL
description text
type varchar(50) DEFAULT 'standard'
personal_owner_user_id bigint(20) UNSIGNED DEFAULT NULL
visibility enum('public','private') NOT NULL DEFAULT 'public'
member_count int(11) DEFAULT 0
event_count int(11) DEFAULT 0
creator_id bigint(20) UNSIGNED NOT NULL
creator_email varchar(100) NOT NULL
featured_image varchar(255) DEFAULT ''
settings longtext DEFAULT ''
at_protocol_did varchar(255) DEFAULT ''
at_protocol_handle varchar(255) DEFAULT ''
at_protocol_data longtext DEFAULT ''
is_active tinyint(1) DEFAULT 1
requires_approval tinyint(1) DEFAULT 0
created_at datetime DEFAULT CURRENT_TIMESTAMP
updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
PRIMARY KEY (id)
UNIQUE KEY slug (slug)
KEY creator_id (creator_id)
KEY personal_owner_user_id (personal_owner_user_id)
KEY visibility (visibility)
KEY type (type)
KEY is_active (is_active)
```

#### `partyminder_community_members` - Community membership
```sql
id mediumint(9) NOT NULL AUTO_INCREMENT
community_id mediumint(9) NOT NULL
user_id bigint(20) UNSIGNED NOT NULL
email varchar(100) NOT NULL
display_name varchar(100) NOT NULL
role varchar(50) DEFAULT 'member'
permissions longtext DEFAULT ''
status varchar(20) DEFAULT 'active'
at_protocol_did varchar(255) DEFAULT ''
joined_at datetime DEFAULT CURRENT_TIMESTAMP
last_seen_at datetime DEFAULT CURRENT_TIMESTAMP
invitation_data longtext DEFAULT ''
```

#### `partyminder_community_events` - Community-event relationships
```sql
id mediumint(9) NOT NULL AUTO_INCREMENT
community_id mediumint(9) NOT NULL
event_id mediumint(9) NOT NULL
created_at datetime DEFAULT CURRENT_TIMESTAMP
```

#### `partyminder_community_invitations` - Community invitation tracking
```sql
id mediumint(9) NOT NULL AUTO_INCREMENT
community_id mediumint(9) NOT NULL
invited_by_member_id mediumint(9) NOT NULL
invited_email varchar(100) NOT NULL
invitation_token varchar(255) NOT NULL
message text DEFAULT ''
status varchar(20) DEFAULT 'pending'
expires_at datetime DEFAULT NULL
created_at datetime DEFAULT CURRENT_TIMESTAMP
responded_at datetime DEFAULT NULL
```

### Conversations System

#### `partyminder_conversation_topics` - Conversation topics/categories
```sql
id mediumint(9) NOT NULL AUTO_INCREMENT
name varchar(255) NOT NULL
slug varchar(255) NOT NULL
description text
icon varchar(10) DEFAULT ''
sort_order int(11) DEFAULT 0
is_active tinyint(1) DEFAULT 1
created_at datetime DEFAULT CURRENT_TIMESTAMP
```

#### `partyminder_conversations` - Discussion threads
```sql
id mediumint(9) NOT NULL AUTO_INCREMENT
event_id mediumint(9) DEFAULT NULL
community_id mediumint(9) DEFAULT NULL
title varchar(255) NOT NULL
slug varchar(255) NOT NULL
content longtext NOT NULL
author_id bigint(20) UNSIGNED NOT NULL
author_name varchar(100) NOT NULL
author_email varchar(100) NOT NULL
privacy varchar(20) DEFAULT 'public'
is_pinned tinyint(1) DEFAULT 0
is_locked tinyint(1) DEFAULT 0
reply_count int(11) DEFAULT 0
last_reply_date datetime DEFAULT CURRENT_TIMESTAMP
last_reply_author varchar(100) DEFAULT ''
featured_image varchar(255) DEFAULT ''
created_at datetime DEFAULT CURRENT_TIMESTAMP
updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

#### `partyminder_conversation_replies` - Conversation replies
```sql
id mediumint(9) NOT NULL AUTO_INCREMENT
conversation_id mediumint(9) NOT NULL
parent_reply_id mediumint(9) DEFAULT NULL
content longtext NOT NULL
author_id bigint(20) UNSIGNED NOT NULL
author_name varchar(100) NOT NULL
author_email varchar(100) NOT NULL
depth_level int(11) DEFAULT 0
created_at datetime DEFAULT CURRENT_TIMESTAMP
updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

#### `partyminder_conversation_follows` - Conversation follows/subscriptions
```sql
id mediumint(9) NOT NULL AUTO_INCREMENT
conversation_id mediumint(9) NOT NULL
user_id bigint(20) UNSIGNED NOT NULL
email varchar(100) NOT NULL
last_read_at datetime DEFAULT CURRENT_TIMESTAMP
notification_frequency varchar(20) DEFAULT 'immediate'
created_at datetime DEFAULT CURRENT_TIMESTAMP
```

### User System

#### `partyminder_user_profiles` - Extended user profiles
```sql
id mediumint(9) NOT NULL AUTO_INCREMENT
user_id bigint(20) UNSIGNED NOT NULL
display_name varchar(255) DEFAULT ''
bio text DEFAULT ''
location varchar(255) DEFAULT ''
profile_image varchar(255) DEFAULT ''
cover_image varchar(255) DEFAULT ''
avatar_source varchar(20) DEFAULT 'gravatar'
website_url varchar(255) DEFAULT ''
social_links longtext DEFAULT ''
hosting_preferences longtext DEFAULT ''
notification_preferences longtext DEFAULT ''
privacy_settings longtext DEFAULT ''
events_hosted int(11) DEFAULT 0
events_attended int(11) DEFAULT 0
host_rating decimal(3,2) DEFAULT 0.00
host_reviews_count int(11) DEFAULT 0
available_times longtext DEFAULT ''
dietary_restrictions text DEFAULT ''
accessibility_needs text DEFAULT ''
is_verified tinyint(1) DEFAULT 0
is_active tinyint(1) DEFAULT 1
last_active datetime DEFAULT CURRENT_TIMESTAMP
created_at datetime DEFAULT CURRENT_TIMESTAMP
updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

#### `partyminder_member_identities` - AT Protocol/Bluesky integration
```sql
id mediumint(9) NOT NULL AUTO_INCREMENT
user_id bigint(20) UNSIGNED NOT NULL
email varchar(100) NOT NULL
did varchar(255) DEFAULT ''
handle varchar(255) DEFAULT ''
access_jwt text DEFAULT ''
refresh_jwt text DEFAULT ''
pds_url varchar(255) DEFAULT ''
profile_data longtext DEFAULT ''
created_at datetime DEFAULT CURRENT_TIMESTAMP
updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

#### `partyminder_at_protocol_sync_log` - AT Protocol sync tracking
```sql
id mediumint(9) NOT NULL AUTO_INCREMENT
entity_type varchar(50) NOT NULL
entity_id mediumint(9) NOT NULL
user_id bigint(20) UNSIGNED NOT NULL
action varchar(50) NOT NULL
at_uri varchar(255) DEFAULT ''
success tinyint(1) DEFAULT 0
error_message text DEFAULT ''
created_at datetime DEFAULT CURRENT_TIMESTAMP
```

### AI/Analytics System

#### `partyminder_ai_interactions` - AI usage tracking
```sql
id mediumint(9) NOT NULL AUTO_INCREMENT
user_id bigint(20) UNSIGNED NOT NULL
event_id mediumint(9) DEFAULT NULL
interaction_type varchar(50) NOT NULL
prompt_text text
response_text longtext
tokens_used int(11) DEFAULT 0
cost_cents int(11) DEFAULT 0
provider varchar(20) DEFAULT 'openai'
model varchar(50) DEFAULT ''
created_at datetime DEFAULT CURRENT_TIMESTAMP
```

### Media System

#### `partyminder_post_images` - Event/conversation image attachments
```sql
id mediumint(9) NOT NULL AUTO_INCREMENT
event_id mediumint(9) NOT NULL
user_id bigint(20) UNSIGNED NOT NULL
image_url varchar(500) NOT NULL
thumbnail_url varchar(500) DEFAULT ''
alt_text varchar(255) DEFAULT ''
caption text DEFAULT ''
display_order int(11) DEFAULT 0
created_at datetime DEFAULT CURRENT_TIMESTAMP
```

## Table Relationships

### Events Flow
```
partyminder_events (1) → (many) partyminder_guests
partyminder_events (1) → (many) partyminder_event_invitations  
partyminder_events (1) → (many) partyminder_event_rsvps
partyminder_events (1) → (many) partyminder_conversations
partyminder_events (1) → (many) partyminder_post_images
wp_users (1) → (many) partyminder_events (author_id)
```

### Communities Flow  
```
partyminder_communities (1) → (many) partyminder_community_members
partyminder_communities (1) → (many) partyminder_community_invitations
partyminder_communities (1) → (many) partyminder_community_events
partyminder_communities (1) → (many) partyminder_conversations
wp_users (1) → (many) partyminder_communities (creator_id)
```

### Conversations Flow
```
partyminder_conversation_topics (1) → (many) partyminder_conversations
partyminder_conversations (1) → (many) partyminder_conversation_replies
partyminder_conversations (1) → (many) partyminder_conversation_follows
wp_users (1) → (many) partyminder_conversations (author_id)
wp_users (1) → (many) partyminder_conversation_replies (author_id)
```

### User Connections
```
wp_users (1) → (1) partyminder_user_profiles
wp_users (1) → (1) partyminder_member_identities
wp_users (1) → (many) partyminder_ai_interactions
wp_users (1) → (many) partyminder_post_images
```

## Status Enums

### RSVP Status
- `pending` - Initial state, invitation sent
- `yes` - Confirmed attendance  
- `no` - Declined attendance
- `maybe` - Tentative attendance

### Event Status
- `active` - Event is published and accepting RSVPs
- `cancelled` - Event has been cancelled
- `completed` - Event has concluded

### Community Visibility  
- `public` - Open to join, visible in listings
- `private` - Invite-only, hidden from public listings

> **Note**: Field renamed from `privacy` to `visibility` as of 2025-09-02 to avoid confusion with privacy values.

### Community Types
- `standard` - General purpose community  
- `personal` - Personal community for circles implementation (one per user)
- `food` - Food and dining focused
- `hobby` - Hobby-based community
- `professional` - Work/career focused
- `family` - Family-oriented events
- `faith` - Religious/spiritual community

### Community Roles
- `admin` - Full management permissions
- `member` - Standard member access

### Invitation Status
- `pending` - Invitation sent, awaiting response
- `accepted` - Invitation accepted
- `declined` - Invitation declined  
- `expired` - Invitation expired without response

## Page Routing Patterns

### Form Pages (Single Column, Focused)
- **Events**: `/create-event`, `/edit-event/{id}`, `/rsvp/{token}`
- **Communities**: `/create-community`, `/communities/join?token=xxx`  
- **Conversations**: `/create-conversation`, `/conversations/{slug}/reply`
- **Profile**: `/profile/edit`, `/login`, `/register`

### Two-Column Listing Pages (Main + Sidebar)
- **Events**: `/events`, `/my-events`, `/events/{slug}`
- **Communities**: `/communities`, `/my-communities`, `/communities/{slug}`  
- **Conversations**: `/conversations`, `/conversations/{slug}`, `/topic/{slug}`
- **Dashboard**: `/dashboard`, `/profile/{user}`

### Management Pages
- **Administration**: `/manage-community?community_id=X`, `/edit-event?event_id=X`

## Schema Consolidation Notes (2025-01-30)

The database activator has been refactored for better maintainability:

### New Structure
- **Unified table creation**: Single `create_tables()` method calls individual table methods
- **Clear schema documentation**: Each table method contains complete schema with indexes
- **Migration-aware**: All table methods include historical migrations for accurate structure
- **Better organization**: Tables grouped by system (events, conversations, communities)

### Key Improvements
- **Anonymous RSVP support**: `partyminder_guests` includes `rsvp_token`, `temporary_guest_id`, `converted_user_id` columns
- **Complete indexes**: All tables now document their full index structure
- **Migration tracking**: Database changes properly tracked and applied
- **Cleaner code**: Individual methods per table make maintenance easier

This structure ensures DATABASE_STRUCTURE.md stays accurate with the actual schema.

## Circles Implementation (2025-09-02)

### Overview
The circles system creates relationship networks based on community membership:
- **Inner Circle**: Communities created by the viewer
- **Trusted Circle**: Communities created by members of Inner communities  
- **Extended Circle**: Communities created by members of Trusted communities

### Personal Communities
Each user gets a personal community (`type = 'personal'`) identified by:
- `personal_owner_user_id` - Links to the owning user
- Used as the foundation for the circles relationship system
- Created automatically for new users (when feature flag enabled)

### Key Classes
- `PartyMinder_Circles_Resolver` - Calculates and caches circle relationships
- `PartyMinder_Conversation_Feed` - Filters content based on circle membership  
- `PartyMinder_Personal_Community_Service` - Manages personal community creation

### Database Relationships for Circles
```sql
-- Find Inner Circle (communities user created)
SELECT id FROM partyminder_communities WHERE creator_id = ? AND is_active = 1

-- Find Trusted Circle (communities created by Inner circle members)  
SELECT DISTINCT c.id FROM partyminder_communities c
JOIN partyminder_community_members m ON c.creator_id = m.user_id
WHERE m.community_id IN (inner_community_ids) AND m.status = 'active'

-- Find Extended Circle (communities created by Trusted circle members)
SELECT DISTINCT c.id FROM partyminder_communities c  
JOIN partyminder_community_members m ON c.creator_id = m.user_id
WHERE m.community_id IN (trusted_community_ids) AND m.status = 'active'
```

### Performance Optimizations
- Results cached in WordPress transients (90 second TTL)
- Cache key: `partyminder_circles_{user_id}`
- Calculation metrics tracked for debugging
- Permission gates applied at query level for efficiency

### Privacy/Visibility Field Changes
- **Before**: `privacy` field with values 'public'/'private'
- **After**: `visibility` enum('public','private') with NOT NULL constraint
- **Reason**: Eliminates confusion between field name and values
- **Migration**: Automatic conversion during plugin activation