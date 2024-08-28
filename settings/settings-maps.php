<form method="post" action="options.php" class="maps_settings">
    <?php
    settings_fields('my_idx_maps');
    do_settings_sections('my_idx_maps');
    submit_button('Save Settings');
    ?>
</form>
