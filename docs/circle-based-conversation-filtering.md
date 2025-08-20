# Circle-Based Conversation Filtering in PartyMinder

## Abstract

This paper describes the implementation of a three-tier circle-based conversation filtering system in PartyMinder, a WordPress-based social platform. The system organizes user interactions into Close, Trusted, and Extended circles based on community membership relationships, providing progressive access to conversations while maintaining privacy and social context. This approach enables users to filter conversation visibility based on their social proximity within the platform's community structure.

## 1. Introduction

Social platforms face the challenge of balancing content discovery with privacy and relevance. Traditional approaches use binary privacy settings (public/private) or basic friend/follower models that don't reflect the nuanced nature of real-world social relationships. PartyMinder's circle-based system addresses this by implementing a community-centric model that expands user visibility in concentric circles based on shared community membership.

## 2. System Architecture

### 2.1 Circle Definitions

The system implements three distinct circles, each encompassing progressively larger social networks:

**Close Circle**: The user's immediate social network
- User's own communities
- Direct members of those communities

**Trusted Circle**: Extended network of known connections
- All users and communities from Close Circle
- Communities that Close Circle members belong to
- All members of those additional communities

**Extended Circle**: Broader network of indirect connections
- All users and communities from Trusted Circle
- Communities that Trusted Circle members belong to
- All members of those additional communities

### 2.2 Database Schema

The circle resolution system operates on three primary database tables:

```sql
-- Community membership tracking
partyminder_community_members (
    id, community_id, user_id, status, created_at
)

-- Community definitions
partyminder_communities (
    id, name, slug, privacy, is_active, creator_id
)

-- Conversation storage
partyminder_conversations (
    id, title, content, author_id, community_id, event_id, privacy
)
```

### 2.3 Core Implementation

The circle resolution logic is implemented in the `PartyMinder_Circle_Scope` class, which provides static methods for scope calculation:

```php
class PartyMinder_Circle_Scope {
    public static function resolve_conversation_scope($user_id, $circle) {
        // Returns array with 'users' and 'communities' in scope
    }
    
    private static function get_close_circle_scope($user_id) { }
    private static function get_trusted_circle_scope($user_id) { }
    private static function get_extended_circle_scope($user_id) { }
}
```

## 3. Circle Resolution Algorithm

### 3.1 Close Circle Algorithm

```
Input: user_id
Output: {users: [user_ids], communities: [community_ids]}

1. Initialize scope with user_id
2. Query user's active community memberships
3. For each community:
   - Add community to scope.communities
   - Query all active members of community
   - Add members to scope.users
4. Return deduplicated scope
```

### 3.2 Trusted Circle Algorithm

```
Input: user_id
Output: {users: [user_ids], communities: [community_ids]}

1. Get Close Circle scope as base
2. For each user in Close Circle:
   - Query their community memberships
   - Add communities to scope.communities
3. For each new community:
   - Query all active members
   - Add members to scope.users
4. Return deduplicated scope
```

### 3.3 Extended Circle Algorithm

```
Input: user_id  
Output: {users: [user_ids], communities: [community_ids]}

1. Get Trusted Circle scope as base
2. For each user in Trusted Circle:
   - Query their community memberships
   - Add communities to scope.communities
3. For each new community:
   - Query all active members
   - Add members to scope.users
4. Return deduplicated scope
```

## 4. Conversation Filtering Implementation

### 4.1 Scope-Based Querying

The conversation manager implements scope-based filtering through two primary methods:

```php
public function get_conversations_by_scope($scope, $topic_slug, $page, $per_page) {
    // Builds WHERE clause from scope arrays
    // Filters by author_id IN (scope.users) OR community_id IN (scope.communities)
}

public function get_conversations_count_by_scope($scope, $topic_slug) {
    // Returns total count for pagination
}
```

### 4.2 AJAX Integration

The system provides real-time conversation filtering through an AJAX endpoint:

```php
public function ajax_get_conversations() {
    $circle = sanitize_text_field($_POST['circle'] ?? 'close');
    $current_user_id = get_current_user_id();
    
    // Resolve scope for current user and selected circle
    $scope = PartyMinder_Circle_Scope::resolve_conversation_scope($current_user_id, $circle);
    
    // Get filtered conversations
    $conversations = $conversation_manager->get_conversations_by_scope($scope, $topic_slug, $page, $per_page);
    
    // Return JSON response with HTML and metadata
}
```

### 4.3 User Interface

The filtering interface consists of three navigation buttons representing each circle level. The implementation includes:

- **Accessibility**: ARIA roles and keyboard navigation support
- **Visual feedback**: Active states and loading indicators  
- **Progressive enhancement**: Graceful degradation without JavaScript
- **Responsive design**: Mobile-optimized button layouts

## 5. Privacy and Security Considerations

### 5.1 Privacy Model

The circle-based system provides granular privacy control while maintaining discoverability:

- **Public conversations**: Visible to all circles when not tied to private communities
- **Community conversations**: Inherit privacy from parent community
- **Author-based filtering**: Users always see their own conversations
- **Guest access**: Non-logged users see only public community conversations

### 5.2 Security Implementation

- **Nonce verification**: All AJAX requests use WordPress nonce tokens
- **Input sanitization**: Circle parameters validated against allowed values
- **SQL injection prevention**: Prepared statements for all database queries
- **Permission checking**: User authentication required for private content access

## 6. Performance Characteristics

### 6.1 Query Complexity

The circle resolution involves multiple database queries with complexity scaling based on community interconnectedness:

- **Close Circle**: O(n) where n = user's community count
- **Trusted Circle**: O(n×m) where m = average members per community
- **Extended Circle**: O(n×m×k) where k = average communities per member

### 6.2 Optimization Strategies

Current optimizations include:

- **Scope caching**: Results cached per user session to avoid recalculation
- **Prepared statements**: Query optimization through statement reuse
- **Pagination**: Conversation loading limited to 20 items per request
- **Lazy loading**: Circle calculation only performed when filtering activated

Future optimizations planned:

- **Server-side caching**: Redis/Memcached integration for scope results
- **Database indexing**: Optimized indexes on community_id and user_id columns
- **Background processing**: Pre-calculation of circle scopes for active users

## 7. Real-World Application

### 7.1 Use Cases

The circle-based filtering addresses several key social platform challenges:

**Content Discovery**: Users discover relevant conversations from their extended network without information overload

**Privacy Gradation**: Progressive disclosure allows users to share different levels of information with different social distances

**Community Building**: Encourages community participation by showing conversations from related communities

**Social Navigation**: Helps users understand their position within the broader social network

### 7.2 User Experience

The system provides intuitive conversation filtering through:

- **Contextual titles**: Conversations display as "Event Name: Title" or "Community Name: Title"
- **Progressive filtering**: Users can expand their view from close to extended circles
- **Real-time updates**: AJAX loading provides immediate feedback
- **Empty state handling**: Appropriate messaging when no conversations exist in selected circle

## 8. Technical Integration

### 8.1 WordPress Integration

The system integrates seamlessly with WordPress architecture:

- **Custom post types**: Leverages WordPress's built-in content management
- **User management**: Utilizes WordPress user authentication and capabilities
- **AJAX framework**: Built on WordPress's wp_ajax action system
- **Database abstraction**: Uses WordPress $wpdb for database interactions

### 8.2 Template System

Conversation display uses WordPress's template hierarchy with custom partials:

```php
templates/
├── partials/
│   ├── conversations-nav.php     // Circle navigation buttons
│   ├── conversations-list.php    // Reusable conversation list
│   └── conversation-item.php     // Individual conversation display
├── conversations.php             // Main conversations page
└── dashboard-content.php         // Dashboard integration
```

## 9. Future Enhancements

### 9.1 Advanced Features

Planned enhancements to the circle-based system include:

**Topic-based filtering**: Integration with conversation topics for further granularity

**Temporal circles**: Time-based circle adjustments (recent activity weighting)

**Manual circle management**: User-defined custom circles beyond community-based automation

**Circle analytics**: Insights into user's social network composition and engagement patterns

### 9.2 Scalability Considerations

For large-scale deployment, several architectural improvements are planned:

**Distributed caching**: Multi-server cache invalidation strategies

**Database sharding**: Horizontal scaling for community and membership tables

**API optimization**: GraphQL implementation for efficient data fetching

**Real-time updates**: WebSocket integration for live conversation updates

## 10. Conclusion

PartyMinder's circle-based conversation filtering system successfully implements a nuanced approach to social content organization. By leveraging community membership relationships to define progressive social circles, the system provides users with intuitive control over conversation visibility while maintaining the discoverability essential for community building.

The implementation demonstrates how community-centric social models can provide more meaningful content filtering than traditional friend/follower approaches. The progressive circle expansion allows users to tune their conversation feed based on social proximity, creating a more relevant and manageable social media experience.

The system's WordPress integration ensures maintainability and extensibility, while the modular architecture supports future enhancements. Performance characteristics scale reasonably with community interconnectedness, and planned optimizations will support larger user bases.

This approach to social content filtering provides a foundation for building more meaningful online communities where content relevance is determined by genuine social relationships rather than algorithmic assumptions.

---

**Keywords**: social networks, content filtering, community-based systems, privacy gradation, WordPress development, database optimization, user experience design

**Authors**: PartyMinder Development Team

**Implementation Status**: Production Ready

**Last Updated**: August 2025