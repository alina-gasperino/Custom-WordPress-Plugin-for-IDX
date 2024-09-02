<form method="post" action="options.php" class="tools_settings">
    <?php
    settings_fields('my_idx_tools');
    do_settings_sections('my_idx_tools');
    submit_button('Save Settings');
    ?>
</form>
