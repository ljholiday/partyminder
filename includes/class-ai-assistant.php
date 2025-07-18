<?php

class PartyMinder_AI_Assistant {
    
    private $api_key;
    private $provider;
    private $model;
    private $cost_limit;
    
    public function __construct() {
        $this->api_key = get_option('partyminder_ai_api_key');
        $this->provider = get_option('partyminder_ai_provider', 'openai');
        $this->model = get_option('partyminder_ai_model', 'gpt-4');
        $this->cost_limit = get_option('partyminder_ai_cost_limit_monthly', 50);
    }
    
    public function generate_plan($event_type, $guest_count, $dietary, $budget) {
        // Use demo mode if no API key
        if (empty($this->api_key) || get_option('partyminder_demo_mode', true)) {
            return $this->get_demo_plan($event_type, $guest_count, $dietary, $budget);
        }
        
        // Check cost limits
        if (!$this->check_cost_limits()) {
            return array(
                'error' => __('Monthly AI cost limit reached. Please increase limit in settings.', 'partyminder')
            );
        }
        
        $prompt = $this->build_prompt($event_type, $guest_count, $dietary, $budget);
        
        try {
            if ($this->provider === 'openai') {
                $result = $this->call_openai_api($prompt);
            } else {
                return array('error' => __('AI provider not supported in demo', 'partyminder'));
            }
            
            // Log interaction for cost tracking
            $this->log_interaction($prompt, $result);
            
            return $result;
            
        } catch (Exception $e) {
            error_log('PartyMinder AI Error: ' . $e->getMessage());
            return array('error' => __('AI service temporarily unavailable.', 'partyminder'));
        }
    }
    
    private function build_prompt($event_type, $guest_count, $dietary, $budget) {
        $prompt = "Create a party plan for a {$event_type} for {$guest_count} people.\n\n";
        $prompt .= "Budget: {$budget}\n";
        if ($dietary) {
            $prompt .= "Dietary restrictions: {$dietary}\n";
        }
        $prompt .= "\nProvide:\n";
        $prompt .= "1. Menu suggestions (appetizers, main course, dessert)\n";
        $prompt .= "2. Shopping list with estimated quantities\n";
        $prompt .= "3. Preparation timeline\n";
        $prompt .= "4. Estimated total cost\n";
        $prompt .= "5. Party atmosphere suggestions\n\n";
        $prompt .= "Format as JSON with keys: menu, shopping_list, timeline, estimated_cost, atmosphere, prep_time";
        
        return $prompt;
    }
    
    private function call_openai_api($prompt) {
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'model' => $this->model,
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => 'You are a party planning expert. Respond with practical, actionable advice in JSON format.'
                    ),
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'max_tokens' => 1000,
                'temperature' => 0.7
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            throw new Exception($data['error']['message']);
        }
        
        if (isset($data['choices'][0]['message']['content'])) {
            $content = trim($data['choices'][0]['message']['content']);
            
            return array(
                'plan' => $content,
                'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                'provider' => $this->provider
            );
        }
        
        throw new Exception(__('No response from AI service', 'partyminder'));
    }
    
    private function get_demo_plan($event_type, $guest_count, $dietary, $budget) {
        $demo_plans = array(
            'dinner' => array(
                'menu' => array(
                    'appetizers' => 'Bruschetta with tomato and basil, cheese and crackers',
                    'main_course' => 'Herb-crusted chicken with roasted vegetables and rice pilaf',
                    'dessert' => 'Chocolate cake with fresh berries',
                    'beverages' => 'Wine, sparkling water, coffee'
                ),
                'shopping_list' => array(
                    'Chicken breasts (' . $guest_count . ' pieces)',
                    'Fresh vegetables (carrots, broccoli, bell peppers)',
                    'Rice (2 cups)',
                    'Bread for bruschetta',
                    'Tomatoes and basil',
                    'Assorted cheeses',
                    'Chocolate cake mix',
                    'Fresh berries',
                    'Wine (2 bottles)',
                    'Sparkling water'
                ),
                'timeline' => array(
                    'day_before' => 'Shop for ingredients, prep vegetables, make dessert',
                    'morning_of' => 'Prepare appetizers, marinate chicken',
                    '2_hours_before' => 'Start cooking rice, preheat oven',
                    '1_hour_before' => 'Cook chicken and vegetables, set table'
                ),
                'estimated_cost' => $guest_count * 25,
                'atmosphere' => array(
                    'music' => 'Soft jazz or acoustic playlist',
                    'lighting' => 'Dimmed lights with candles',
                    'decorations' => 'Fresh flowers, cloth napkins'
                ),
                'prep_time' => '3-4 hours total'
            ),
            'birthday' => array(
                'menu' => array(
                    'appetizers' => 'Mini sandwiches and fruit kabobs',
                    'main_course' => 'Pizza variety platter',
                    'dessert' => 'Birthday cake and ice cream',
                    'beverages' => 'Soft drinks, juice, coffee'
                ),
                'shopping_list' => array(
                    'Pizza ingredients or order pizzas',
                    'Sandwich fixings',
                    'Fresh fruit for kabobs',
                    'Birthday cake or cake mix',
                    'Ice cream (2 flavors)',
                    'Soft drinks variety pack',
                    'Fruit juice',
                    'Birthday decorations',
                    'Candles'
                ),
                'timeline' => array(
                    'day_before' => 'Order pizzas, shop for ingredients, make cake',
                    'morning_of' => 'Prepare fruit kabobs, make sandwiches',
                    '2_hours_before' => 'Set up decorations',
                    '1_hour_before' => 'Pick up pizzas, final setup'
                ),
                'estimated_cost' => $guest_count * 20,
                'atmosphere' => array(
                    'music' => 'Upbeat party music',
                    'lighting' => 'Bright and cheerful',
                    'decorations' => 'Balloons, banners, party hats'
                ),
                'prep_time' => '2-3 hours total'
            )
        );
        
        $plan = $demo_plans[$event_type] ?? $demo_plans['dinner'];
        
        // Adjust cost based on budget
        if ($budget === 'budget') {
            $plan['estimated_cost'] = intval($plan['estimated_cost'] * 0.7);
        } elseif ($budget === 'premium') {
            $plan['estimated_cost'] = intval($plan['estimated_cost'] * 1.5);
        }
        
        return array(
            'plan' => json_encode($plan),
            'demo_mode' => true,
            'provider' => 'demo'
        );
    }
    
    private function check_cost_limits() {
        if (empty($this->api_key)) {
            return true; // Demo mode always allowed
        }
        
        global $wpdb;
        $ai_table = $wpdb->prefix . 'partyminder_ai_interactions';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$ai_table'") != $ai_table) {
            return true;
        }
        
        $current_month = date('Y-m');
        $monthly_cost = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(cost_cents) FROM $ai_table WHERE DATE_FORMAT(created_at, '%%Y-%%m') = %s",
            $current_month
        ));
        
        $monthly_cost_dollars = ($monthly_cost ?? 0) / 100;
        
        return $monthly_cost_dollars < $this->cost_limit;
    }
    
    private function log_interaction($prompt, $result) {
        global $wpdb;
        
        $ai_table = $wpdb->prefix . 'partyminder_ai_interactions';
        
        if (isset($result['demo_mode'])) {
            return; // Don't log demo interactions
        }
        
        $tokens_used = $result['tokens_used'] ?? 0;
        $cost_cents = $this->calculate_cost($tokens_used);
        
        $wpdb->insert(
            $ai_table,
            array(
                'user_id' => get_current_user_id() ?: 0,
                'interaction_type' => 'party_plan_generation',
                'prompt_text' => substr($prompt, 0, 1000),
                'response_text' => substr($result['plan'] ?? '', 0, 2000),
                'tokens_used' => $tokens_used,
                'cost_cents' => $cost_cents,
                'provider' => $this->provider,
                'model' => $this->model
            ),
            array('%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s')
        );
    }
    
    private function calculate_cost($tokens) {
        // OpenAI GPT-4 pricing (approximate)
        if ($this->provider === 'openai' && strpos($this->model, 'gpt-4') !== false) {
            return intval($tokens * 0.003); // ~$0.03 per 1k tokens
        }
        
        return 0;
    }
    
    public function get_monthly_usage() {
        global $wpdb;
        $ai_table = $wpdb->prefix . 'partyminder_ai_interactions';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$ai_table'") != $ai_table) {
            return array('total' => 0, 'interactions' => 0, 'limit' => $this->cost_limit);
        }
        
        $current_month = date('Y-m');
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT SUM(cost_cents) as total_cents, COUNT(*) as interaction_count 
             FROM $ai_table 
             WHERE DATE_FORMAT(created_at, '%%Y-%%m') = %s",
            $current_month
        ));
        
        return array(
            'total' => ($result->total_cents ?? 0) / 100,
            'interactions' => $result->interaction_count ?? 0,
            'limit' => $this->cost_limit,
            'remaining' => $this->cost_limit - (($result->total_cents ?? 0) / 100)
        );
    }
}