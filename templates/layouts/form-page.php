<?php
/**
 * Form page layout wrapper
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="pm-layout-form">
    <div class="pm-layout-form-inner">
        <?php echo $form_content ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
</div>
