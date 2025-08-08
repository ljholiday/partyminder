<?php

class PartyMinder_Conversation_Manager {

    public function __construct() {
        // Constructor can be used for initialization if needed
    }

    /**
     * Get all conversation topics
     */
    public function get_topics($active_only = true) {
        global $wpdb;
        
        $topics_table = $wpdb->prefix . 'partyminder_conversation_topics';
        $where_clause = $active_only ? 'WHERE is_active = 1' : '';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $topics_table 
            $where_clause 
            ORDER BY sort_order ASC
        "));
    }

    /**
     * Get conversations by topic
     */
    public function get_conversations_by_topic($topic_id, $limit = 10, $offset = 0) {
        global $wpdb;
        
        $conversations_table = $wpdb->prefix . 'partyminder_conversations';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $conversations_table 
            WHERE topic_id = %d 
            ORDER BY is_pinned DESC, last_reply_date DESC
            LIMIT %d OFFSET %d
        ", $topic_id, $limit, $offset));
    }

    /**
     * Get recent conversations across all topics
     */
    public function get_recent_conversations($limit = 10, $exclude_event_conversations = false, $exclude_community_conversations = false) {
        global $wpdb;
        
        $conversations_table = $wpdb->prefix . 'partyminder_conversations';
        $event_clause = $exclude_event_conversations ? 'AND event_id IS NULL' : '';
        $community_clause = $exclude_community_conversations ? 'AND community_id IS NULL' : '';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT c.*, t.name as topic_name, t.icon as topic_icon, t.slug as topic_slug
            FROM $conversations_table c
            LEFT JOIN {$wpdb->prefix}partyminder_conversation_topics t ON c.topic_id = t.id
            WHERE 1=1 $event_clause $community_clause
            ORDER BY c.last_reply_date DESC
            LIMIT %d
        ", $limit));
    }

    /**
     * Get event-related conversations
     */
    public function get_event_conversations($event_id = null, $limit = 10) {
        global $wpdb;
        
        $conversations_table = $wpdb->prefix . 'partyminder_conversations';
        $events_table = $wpdb->prefix . 'partyminder_events';
        
        $where_clause = $event_id ? 'WHERE c.event_id = %d' : 'WHERE c.event_id IS NOT NULL';
        $prepare_values = $event_id ? array($event_id, $limit) : array($limit);
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT c.*, e.title as event_title, e.slug as event_slug, e.event_date, t.slug as topic_slug
            FROM $conversations_table c
            LEFT JOIN $events_table e ON c.event_id = e.id
            LEFT JOIN {$wpdb->prefix}partyminder_conversation_topics t ON c.topic_id = t.id
            $where_clause
            ORDER BY c.last_reply_date DESC
            LIMIT %d
        ", ...$prepare_values));
    }

    /**
     * Get community-related conversations
     */
    public function get_community_conversations($community_id = null, $limit = 10) {
        global $wpdb;
        
        $conversations_table = $wpdb->prefix . 'partyminder_conversations';
        $communities_table = $wpdb->prefix . 'partyminder_communities';
        
        $where_clause = $community_id ? 'WHERE c.community_id = %d' : 'WHERE c.community_id IS NOT NULL';
        $prepare_values = $community_id ? array($community_id, $limit) : array($limit);
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT c.*, cm.name as community_name, cm.slug as community_slug, t.slug as topic_slug
            FROM $conversations_table c
            LEFT JOIN $communities_table cm ON c.community_id = cm.id
            LEFT JOIN {$wpdb->prefix}partyminder_conversation_topics t ON c.topic_id = t.id
            $where_clause
            ORDER BY c.last_reply_date DESC
            LIMIT %d
        ", ...$prepare_values));
    }

    /**
     * Create a new conversation
     */
    public function create_conversation($data) {
        global $wpdb;
        
        $conversations_table = $wpdb->prefix . 'partyminder_conversations';
        
        // Generate slug from title
        $slug = $this->generate_conversation_slug($data['title']);
        
        $result = $wpdb->insert(
            $conversations_table,
            array(
                'topic_id' => $data['topic_id'],
                'event_id' => $data['event_id'] ?? null,
                'community_id' => $data['community_id'] ?? null,
                'title' => sanitize_text_field($data['title']),
                'slug' => $slug,
                'content' => wp_kses_post($data['content']),
                'author_id' => $data['author_id'],
                'author_name' => sanitize_text_field($data['author_name']),
                'author_email' => sanitize_email($data['author_email']),
                'is_pinned' => $data['is_pinned'] ?? 0,
                'created_at' => current_time('mysql'),
                'last_reply_date' => current_time('mysql'),
                'last_reply_author' => sanitize_text_field($data['author_name'])
            ),
            array('%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return false;
        }
        
        $conversation_id = $wpdb->insert_id;
        
        // Auto-follow the conversation creator
        $this->follow_conversation($conversation_id, $data['author_id'], $data['author_email']);
        
        return $conversation_id;
    }

    /**
     * Add a reply to a conversation
     */
    public function add_reply($conversation_id, $data) {
        global $wpdb;
        
        $replies_table = $wpdb->prefix . 'partyminder_conversation_replies';
        $conversations_table = $wpdb->prefix . 'partyminder_conversations';
        
        // Calculate depth level
        $depth = 0;
        if (!empty($data['parent_reply_id'])) {
            $parent = $wpdb->get_row($wpdb->prepare(
                "SELECT depth_level FROM $replies_table WHERE id = %d",
                $data['parent_reply_id']
            ));
            $depth = $parent ? ($parent->depth_level + 1) : 0;
            $depth = min($depth, 5); // Max depth of 5 levels
        }
        
        // Insert reply
        $result = $wpdb->insert(
            $replies_table,
            array(
                'conversation_id' => $conversation_id,
                'parent_reply_id' => $data['parent_reply_id'] ?? null,
                'content' => wp_kses_post($data['content']),
                'author_id' => $data['author_id'],
                'author_name' => sanitize_text_field($data['author_name']),
                'author_email' => sanitize_email($data['author_email']),
                'depth_level' => $depth,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%d', '%s', '%s', '%d', '%s')
        );
        
        if ($result === false) {
            return false;
        }
        
        // Update conversation reply count and last reply info
        $wpdb->update(
            $conversations_table,
            array(
                'reply_count' => $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $replies_table WHERE conversation_id = %d",
                    $conversation_id
                )),
                'last_reply_date' => current_time('mysql'),
                'last_reply_author' => sanitize_text_field($data['author_name'])
            ),
            array('id' => $conversation_id),
            array('%d', '%s', '%s'),
            array('%d')
        );
        
        $reply_id = $wpdb->insert_id;
        
        // Auto-follow the conversation for reply author
        $this->follow_conversation($conversation_id, $data['author_id'], $data['author_email']);
        
        return $reply_id;
    }

    /**
     * Get conversation by ID or slug
     */
    public function get_conversation($identifier, $by_slug = false) {
        global $wpdb;
        
        $conversations_table = $wpdb->prefix . 'partyminder_conversations';
        $field = $by_slug ? 'slug' : 'id';
        
        $conversation = $wpdb->get_row($wpdb->prepare("
            SELECT c.*, t.name as topic_name, t.icon as topic_icon
            FROM $conversations_table c
            LEFT JOIN {$wpdb->prefix}partyminder_conversation_topics t ON c.topic_id = t.id
            WHERE c.$field = %s
        ", $identifier));
        
        if ($conversation && $by_slug === false) {
            // Get replies if getting by ID
            $conversation->replies = $this->get_conversation_replies($conversation->id);
        }
        
        return $conversation;
    }

    /**
     * Get replies for a conversation
     */
    public function get_conversation_replies($conversation_id) {
        global $wpdb;
        
        $replies_table = $wpdb->prefix . 'partyminder_conversation_replies';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $replies_table 
            WHERE conversation_id = %d 
            ORDER BY created_at ASC
        ", $conversation_id));
    }

    /**
     * Follow a conversation
     */
    public function follow_conversation($conversation_id, $user_id, $email) {
        global $wpdb;
        
        $follows_table = $wpdb->prefix . 'partyminder_conversation_follows';
        
        // Check if already following
        $existing = $wpdb->get_var($wpdb->prepare("
            SELECT id FROM $follows_table 
            WHERE conversation_id = %d AND user_id = %d AND email = %s
        ", $conversation_id, $user_id, $email));
        
        if ($existing) {
            return $existing; // Already following
        }
        
        $result = $wpdb->insert(
            $follows_table,
            array(
                'conversation_id' => $conversation_id,
                'user_id' => $user_id,
                'email' => $email,
                'last_read_at' => current_time('mysql'),
                'notification_frequency' => 'immediate',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Unfollow a conversation
     */
    public function unfollow_conversation($conversation_id, $user_id, $email) {
        global $wpdb;
        
        $follows_table = $wpdb->prefix . 'partyminder_conversation_follows';
        
        return $wpdb->delete(
            $follows_table,
            array(
                'conversation_id' => $conversation_id,
                'user_id' => $user_id,
                'email' => $email
            ),
            array('%d', '%d', '%s')
        );
    }

    /**
     * Check if user is following a conversation
     */
    public function is_following($conversation_id, $user_id, $email) {
        global $wpdb;
        
        $follows_table = $wpdb->prefix . 'partyminder_conversation_follows';
        
        return (bool) $wpdb->get_var($wpdb->prepare("
            SELECT id FROM $follows_table 
            WHERE conversation_id = %d AND user_id = %d AND email = %s
        ", $conversation_id, $user_id, $email));
    }

    /**
     * Generate unique slug for conversation
     */
    private function generate_conversation_slug($title) {
        global $wpdb;
        
        $conversations_table = $wpdb->prefix . 'partyminder_conversations';
        $base_slug = sanitize_title($title);
        $slug = $base_slug;
        $counter = 1;
        
        while ($wpdb->get_var($wpdb->prepare("SELECT id FROM $conversations_table WHERE slug = %s", $slug))) {
            $slug = $base_slug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Get conversation statistics
     */
    public function get_stats() {
        global $wpdb;
        
        $conversations_table = $wpdb->prefix . 'partyminder_conversations';
        $replies_table = $wpdb->prefix . 'partyminder_conversation_replies';
        $follows_table = $wpdb->prefix . 'partyminder_conversation_follows';
        
        $stats = new stdClass();
        $stats->total_conversations = $wpdb->get_var("SELECT COUNT(*) FROM $conversations_table");
        $stats->total_replies = $wpdb->get_var("SELECT COUNT(*) FROM $replies_table");
        $stats->total_follows = $wpdb->get_var("SELECT COUNT(*) FROM $follows_table");
        $stats->active_conversations = $wpdb->get_var("
            SELECT COUNT(*) FROM $conversations_table 
            WHERE last_reply_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        
        return $stats;
    }

    /**
     * Auto-create event conversation when event is created
     */
    public function create_event_conversation($event_id, $event_data) {
        // Find the party planning topic
        $party_planning_topic = $this->get_topic_by_slug('party-planning');
        if (!$party_planning_topic) {
            return false;
        }
        
        $conversation_data = array(
            'topic_id' => $party_planning_topic->id,
            'event_id' => $event_id,
            'title' => sprintf(__('Planning: %s', 'partyminder'), $event_data['title']),
            'content' => sprintf(
                __('Let\'s plan an amazing %s together! Share ideas, coordinate details, and help make this event unforgettable.', 'partyminder'),
                $event_data['title']
            ),
            'author_id' => $event_data['author_id'],
            'author_name' => $event_data['author_name'],
            'author_email' => $event_data['author_email']
        );
        
        return $this->create_conversation($conversation_data);
    }

    /**
     * Auto-create community conversation when community is created
     */
    public function create_community_conversation($community_id, $community_data) {
        // Find the welcome & introductions topic
        $welcome_topic = $this->get_topic_by_slug('welcome-introductions');
        if (!$welcome_topic) {
            return false;
        }
        
        $conversation_data = array(
            'topic_id' => $welcome_topic->id,
            'community_id' => $community_id,
            'title' => sprintf(__('Welcome to %s!', 'partyminder'), $community_data['name']),
            'content' => sprintf(
                __('Welcome to the %s community! This is our gathering place to connect, share experiences, and plan amazing events together. Please introduce yourself and let us know what brings you here!', 'partyminder'),
                $community_data['name']
            ),
            'author_id' => $community_data['creator_id'],
            'author_name' => $community_data['creator_name'],
            'author_email' => $community_data['creator_email'],
            'is_pinned' => 1 // Pin the welcome conversation
        );
        
        return $this->create_conversation($conversation_data);
    }

    /**
     * Get topic by slug
     */
    public function get_topic_by_slug($slug) {
        global $wpdb;
        
        $topics_table = $wpdb->prefix . 'partyminder_conversation_topics';
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT * FROM $topics_table WHERE slug = %s AND is_active = 1
        ", $slug));
    }
}