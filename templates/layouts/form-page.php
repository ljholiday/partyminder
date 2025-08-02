<?php
/**
 * Form page layout wrapper
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="page form-page">
    <div class="form">
        <?php echo $form_content ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
</div>
