<?php
/**
 * Base Form Template
 * Centered single-column layout for forms and focused tasks
 * 
 * Variables expected:
 * - $page_title (string)
 * - $page_description (string, optional)
 * - $breadcrumbs (array, optional) 
 * - $content (string) - main form content
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="pm-page-form">
    <?php if (!empty($breadcrumbs)): ?>
    <!-- Breadcrumbs -->
    <div class="pm-text-muted mb-4" style="font-size: 14px;">
        <?php 
        $breadcrumb_parts = array();
        foreach ($breadcrumbs as $crumb) {
            if (isset($crumb['url'])) {
                $breadcrumb_parts[] = '<a href="' . esc_url($crumb['url']) . '" class="pm-text-primary">' . esc_html($crumb['title']) . '</a>';
            } else {
                $breadcrumb_parts[] = '<span>' . esc_html($crumb['title']) . '</span>';
            }
        }
        echo implode(' › ', $breadcrumb_parts);
        ?>
    </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="pm-header">
        <h1 class="pm-heading pm-heading-lg pm-text-primary"><?php echo esc_html($page_title); ?></h1>
        <?php if (!empty($page_description)): ?>
            <p class="pm-text-muted"><?php echo esc_html($page_description); ?></p>
        <?php endif; ?>
    </div>

    <!-- Form Content -->
    <div class="pm-section">
        <?php echo $content; ?>
    </div>
</div>