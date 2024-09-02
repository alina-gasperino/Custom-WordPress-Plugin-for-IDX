<form method="post" action="options.php" class="filters_settings">
    <?php
    settings_fields('my_idx_filters');
    do_settings_sections('my_idx_filters');
    submit_button('Save Settings');
    ?>
</form>
