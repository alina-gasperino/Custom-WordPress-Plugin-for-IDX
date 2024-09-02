<form method="post" action="options.php" class="sms_settings">
    <?php
    settings_fields('my_idx_sms');
    do_settings_sections('my_idx_sms');
    submit_button('Save Settings');
    ?>
</form>
