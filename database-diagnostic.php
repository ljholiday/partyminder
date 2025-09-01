<?php
/**
 * Database Diagnostic Tool
 * 
 * Run this to understand current database state before making changes
 * Access at: yoursite.com/wp-admin/admin.php?page=partyminder-database-diagnostic
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Only allow admin access
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized access');
}

global $wpdb;
$user_id = get_current_user_id();
$communities_table = $wpdb->prefix . 'partyminder_communities';
$members_table = $wpdb->prefix . 'partyminder_community_members';

?>
<div class="wrap">
    <h1>PartyMinder Database Diagnostic</h1>
    
    <div class="card" style="margin: 20px 0; padding: 20px;">
        <h2>Current User: <?php echo $user_id; ?> (<?php echo wp_get_current_user()->user_login; ?>)</h2>
        
        <h3>1. Communities Table Structure</h3>
        <?php
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $communities_table");
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr></thead><tbody>';
        foreach ($columns as $column) {
            echo '<tr>';
            echo '<td><strong>' . $column->Field . '</strong></td>';
            echo '<td>' . $column->Type . '</td>';
            echo '<td>' . $column->Null . '</td>';
            echo '<td>' . ($column->Default ?? 'NULL') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        ?>
        
        <h3>2. Members Table Structure</h3>
        <?php
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $members_table");
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr></thead><tbody>';
        foreach ($columns as $column) {
            echo '<tr>';
            echo '<td><strong>' . $column->Field . '</strong></td>';
            echo '<td>' . $column->Type . '</td>';
            echo '<td>' . $column->Null . '</td>';
            echo '<td>' . ($column->Default ?? 'NULL') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        ?>
        
        <h3>3. All Communities in System</h3>
        <?php
        $all_communities = $wpdb->get_results("SELECT * FROM $communities_table ORDER BY created_at DESC");
        echo '<p><strong>Total:</strong> ' . count($all_communities) . ' communities</p>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>ID</th><th>Name</th><th>Slug</th><th>Creator ID</th><th>Personal Owner ID</th><th>Active</th><th>Created</th></tr></thead><tbody>';
        foreach ($all_communities as $community) {
            echo '<tr>';
            echo '<td>' . $community->id . '</td>';
            echo '<td>' . esc_html($community->name) . '</td>';
            echo '<td>' . $community->slug . '</td>';
            echo '<td>' . $community->creator_id . '</td>';
            echo '<td>' . ($community->personal_owner_user_id ?? '<em>NULL</em>') . '</td>';
            echo '<td>' . ($community->is_active ? 'Yes' : 'No') . '</td>';
            echo '<td>' . $community->created_at . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        ?>
        
        <h3>4. Current User's Memberships</h3>
        <?php
        $user_memberships = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, c.name, c.slug FROM $members_table m 
             JOIN $communities_table c ON m.community_id = c.id 
             WHERE m.user_id = %d ORDER BY m.joined_at DESC", 
            $user_id
        ));
        echo '<p><strong>Total:</strong> ' . count($user_memberships) . ' memberships</p>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Community</th><th>Role</th><th>Status</th><th>Joined</th></tr></thead><tbody>';
        foreach ($user_memberships as $membership) {
            $status_display = $membership->status === '' ? '<strong style="color: red;">EMPTY STRING</strong>' : 
                             ($membership->status === null ? '<strong style="color: red;">NULL</strong>' : 
                             '<strong>' . $membership->status . '</strong>');
            echo '<tr>';
            echo '<td>' . esc_html($membership->name) . ' (' . $membership->slug . ')</td>';
            echo '<td>' . ($membership->role ?? '<em>NULL</em>') . '</td>';
            echo '<td>' . $status_display . '</td>';
            echo '<td>' . $membership->joined_at . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        ?>
        
        <h3>5. get_user_communities() Method Test</h3>
        <?php
        if (class_exists('PartyMinder_Community_Manager')) {
            $community_manager = new PartyMinder_Community_Manager();
            $result_communities = $community_manager->get_user_communities($user_id);
            echo '<p><strong>Method returns:</strong> ' . count($result_communities) . ' communities</p>';
            
            if (!empty($result_communities)) {
                echo '<ul>';
                foreach ($result_communities as $community) {
                    echo '<li>' . esc_html($community->name) . ' - Role: ' . $community->role . ', Status: ' . $community->member_status . '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p style="color: red;"><strong>NO COMMUNITIES RETURNED - This is why My Communities is empty!</strong></p>';
            }
        } else {
            echo '<p style="color: red;">PartyMinder_Community_Manager class not found</p>';
        }
        ?>
        
        <h3>6. Expected SQL Query</h3>
        <p>The get_user_communities() method uses this query:</p>
        <pre style="background: #f5f5f5; padding: 15px; border-left: 4px solid #0073aa;">
SELECT c.*, m.role, m.joined_at, m.status as member_status
FROM <?php echo $communities_table; ?> c
INNER JOIN <?php echo $members_table; ?> m ON c.id = m.community_id
WHERE m.user_id = <?php echo $user_id; ?> AND m.status = 'active' AND c.is_active = 1
ORDER BY m.joined_at DESC
LIMIT 20
        </pre>
        
        <h3>7. Diagnosis Summary</h3>
        <?php
        $diagnosis = array();
        
        // Check if personal_owner_user_id column exists
        $has_personal_column = false;
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $communities_table");
        foreach ($columns as $column) {
            if ($column->Field === 'personal_owner_user_id') {
                $has_personal_column = true;
                break;
            }
        }
        
        if ($has_personal_column) {
            $diagnosis[] = "✓ personal_owner_user_id column exists in communities table";
        } else {
            $diagnosis[] = "✗ personal_owner_user_id column MISSING from communities table";
        }
        
        // Check if user has any memberships
        $membership_count = count($user_memberships);
        if ($membership_count > 0) {
            $diagnosis[] = "✓ User has {$membership_count} community memberships";
            
            // Check status values
            $active_count = 0;
            $empty_count = 0;
            $null_count = 0;
            $other_count = 0;
            
            foreach ($user_memberships as $membership) {
                if ($membership->status === 'active') {
                    $active_count++;
                } elseif ($membership->status === '') {
                    $empty_count++;
                } elseif ($membership->status === null) {
                    $null_count++;
                } else {
                    $other_count++;
                }
            }
            
            if ($active_count > 0) {
                $diagnosis[] = "✓ {$active_count} memberships have 'active' status";
            } else {
                $diagnosis[] = "✗ NO memberships have 'active' status";
            }
            
            if ($empty_count > 0) {
                $diagnosis[] = "⚠ {$empty_count} memberships have EMPTY STRING status";
            }
            
            if ($null_count > 0) {
                $diagnosis[] = "⚠ {$null_count} memberships have NULL status";
            }
            
            if ($other_count > 0) {
                $diagnosis[] = "⚠ {$other_count} memberships have other status values";
            }
        } else {
            $diagnosis[] = "✗ User has NO community memberships";
        }
        
        // Check get_user_communities result
        if (!empty($result_communities)) {
            $diagnosis[] = "✓ get_user_communities() returns " . count($result_communities) . " communities";
        } else {
            $diagnosis[] = "✗ get_user_communities() returns NO communities (this is the problem!)";
        }
        
        echo '<ul>';
        foreach ($diagnosis as $item) {
            echo '<li>' . $item . '</li>';
        }
        echo '</ul>';
        ?>
    </div>
</div>

<style>
.card {
    background: white;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    margin: 20px 0;
    padding: 20px;
}
</style>