<form method="post" action="options.php" class="autotask_settings">
    <?php
    settings_fields('my_idx_autotask');
    do_settings_sections('my_idx_autotask');
    submit_button('Save Settings');
    ?>
</form>
