<form method="post" action="options.php" class="general_settings">
    <?php
    settings_fields('my_idx_general');
    do_settings_sections('my_idx_general');
    submit_button('Save Settings');
    ?>
</form>
