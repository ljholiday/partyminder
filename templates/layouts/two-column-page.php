<?php
/**
 * Two column page layout
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="page content-columns">
    <div class="column">
        <?php echo $main_content ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
    <div class="column">
        <?php
        if (isset($sidebar_template)) {
            include PARTYMINDER_PLUGIN_DIR . 'templates/' . $sidebar_template;
        } elseif (isset($sidebar_content)) {
            echo $sidebar_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        ?>
    </div>
</div>
