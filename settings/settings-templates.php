<form method="post" action="options.php" class="templates_settings">
    <?php
    settings_fields('my_idx_templates');
    do_settings_sections('my_idx_templates');
    submit_button('Save Settings');
    ?>
</form>
