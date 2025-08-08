# PartyMinder Database Structure

## Core Tables

### Events System
- **`partyminder_events`** - Main events table
  - `id, title, slug, description, excerpt, event_date, location, max_guests, created_by, status, created_at, updated_at`

- **`partyminder_guests`** - Event attendees/RSVPs  
  - `id, event_id, name, email, phone, rsvp_status, dietary_restrictions, additional_info, invited_by, created_at`

- **`partyminder_event_invitations`** - Event invitation tracking
  - `id, event_id, inviter_id, invited_email, invitation_token, status, expires_at, created_at, responded_at`

### Communities System  
- **`partyminder_communities`** - Communities/groups
  - `id, name, slug, description, type, privacy, created_by, member_count, event_count, created_at, updated_at`

- **`partyminder_community_members`** - Community membership
  - `id, community_id, user_id, email, display_name, role, status, joined_at, last_active`

- **`partyminder_community_invitations`** - Community invitation tracking
  - `id, community_id, invited_by_member_id, invited_email, invitation_token, message, status, expires_at, created_at, responded_at`

### Conversations System
- **`partyminder_topics`** - Conversation topics/categories
  - `id, name, slug, description, icon, color, is_active, sort_order, created_at`

- **`partyminder_conversations`** - Discussion threads
  - `id, topic_id, event_id, community_id, title, slug, description, author_id, is_pinned, is_locked, reply_count, last_reply_at, created_at, updated_at`

- **`partyminder_replies`** - Conversation replies
  - `id, conversation_id, parent_reply_id, content, author_id, is_ai_response, created_at, updated_at`

- **`partyminder_follows`** - Conversation follows/subscriptions
  - `id, conversation_id, user_id, email, last_read_at, created_at`

### User System
- **`partyminder_user_profiles`** - Extended user profiles
  - `id, user_id, display_name, bio, profile_image, cover_image, phone, website, location, timezone, preferences, privacy_settings, last_active, created_at, updated_at`

- **`partyminder_member_identities`** - AT Protocol/Bluesky integration
  - `id, user_id, did, handle, access_jwt, refresh_jwt, pds_url, created_at, updated_at`

### AI/Analytics System
- **`partyminder_ai_interactions`** - AI usage tracking
  - `id, user_id, event_id, interaction_type, prompt_text, response_text, tokens_used, processing_time, created_at`

## Table Relationships

### Events Flow
```
partyminder_events (1) → (many) partyminder_guests
partyminder_events (1) → (many) partyminder_event_invitations  
partyminder_events (1) → (many) partyminder_conversations
```

### Communities Flow  
```
partyminder_communities (1) → (many) partyminder_community_members
partyminder_communities (1) → (many) partyminder_community_invitations
partyminder_communities (1) → (many) partyminder_conversations
```

### Conversations Flow
```
partyminder_topics (1) → (many) partyminder_conversations
partyminder_conversations (1) → (many) partyminder_replies
partyminder_conversations (1) → (many) partyminder_follows
```

### User Connections
```
wp_users (1) → (1) partyminder_user_profiles
wp_users (1) → (1) partyminder_member_identities
wp_users (1) → (many) partyminder_events (created_by)
wp_users (1) → (many) partyminder_community_members
wp_users (1) → (many) partyminder_conversations (author_id)
wp_users (1) → (many) partyminder_replies (author_id)
```

## Page Type Categories

### Form Pages (Single Column, Focused)
- **Events**: `/create-event`, `/edit-event/{id}`, `/events/{slug}/rsvp`
- **Communities**: `/create-community`, `/communities/join?token=xxx`  
- **Conversations**: `/create-conversation`, `/conversations/{slug}/reply`
- **Profile**: `/profile/edit`, `/login`, `/register`

### Two-Column Listing Pages (Main + Sidebar)
- **Events**: `/events`, `/my-events`, `/events/{slug}`
- **Communities**: `/communities`, `/my-communities`, `/communities/{slug}`  
- **Conversations**: `/conversations`, `/conversations/{slug}`, `/topic/{slug}`
- **Dashboard**: `/dashboard`, `/profile/{user}`

### Navigation Pages
- **Manage**: `/manage-community`, `/manage-event` (admin interfaces)

## Status Enums

### RSVP Status
- `pending`, `yes`, `no`, `maybe`

### Community Privacy  
- `public`, `private`

### Community Types
- `general`, `food`, `hobby`, `professional`, `family`, `faith`, `work`

### Community Roles
- `admin`, `member`

### Invitation Status
- `pending`, `accepted`, `declined`, `expired`