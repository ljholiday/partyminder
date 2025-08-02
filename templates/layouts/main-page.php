<?php
/**
 * Main page layout wrapper
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="page">
    <?php
    if (isset($content_template)) {
        include PARTYMINDER_PLUGIN_DIR . 'templates/' . $content_template;
    } elseif (isset($content)) {
        echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
    ?>
</div>
