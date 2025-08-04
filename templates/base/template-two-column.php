<?php
/**
 * Base Two-Column Template  
 * Main content + sidebar layout for listings and detailed views
 * 
 * Variables expected:
 * - $page_title (string)
 * - $page_description (string, optional)
 * - $breadcrumbs (array, optional) 
 * - $main_content (string) - main content area
 * - $sidebar_content (string) - sidebar content area
 * - $nav_items (array, optional) - navigation tabs
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<?php if (!empty($breadcrumbs)): ?>
<!-- Breadcrumbs -->
<div class="text-muted mb-4" style="font-size: 14px;">
    <?php 
    $breadcrumb_parts = array();
    foreach ($breadcrumbs as $crumb) {
        if (isset($crumb['url'])) {
            $breadcrumb_parts[] = '<a href="' . esc_url($crumb['url']) . '" class="text-primary">' . esc_html($crumb['title']) . '</a>';
        } else {
            $breadcrumb_parts[] = '<span>' . esc_html($crumb['title']) . '</span>';
        }
    }
    echo implode(' › ', $breadcrumb_parts);
    ?>
</div>
<?php endif; ?>

<!-- Page Header - Spans Full Width -->
<div class="header mb-4">
    <h1 class="heading heading-lg text-primary"><?php echo esc_html($page_title); ?></h1>
    <?php if (!empty($page_description)): ?>
        <p class="text-muted"><?php echo esc_html($page_description); ?></p>
    <?php endif; ?>
</div>

<?php if (!empty($nav_items)): ?>
<!-- Navigation - Spans Full Width -->
<div class="nav">
    <?php foreach ($nav_items as $nav_item): ?>
        <a href="<?php echo esc_url($nav_item['url']); ?>" 
           class="nav-item <?php echo !empty($nav_item['active']) ? 'active' : ''; ?>">
            <?php if (!empty($nav_item['icon'])): ?>
                <span><?php echo $nav_item['icon']; ?></span>
            <?php endif; ?>
            <?php echo esc_html($nav_item['title']); ?>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Two Column Layout -->
<div class="page-two-column">
    <div class="main">
        <!-- Main Content -->
        <?php echo $main_content; ?>
    </div>

    <div class="sidebar">
        <!-- Sidebar Content -->
        <?php echo $sidebar_content; ?>
    </div>
</div>