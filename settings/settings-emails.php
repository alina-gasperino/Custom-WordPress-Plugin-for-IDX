<form method="post" action="options.php" class="emails_settings">
    <?php
    settings_fields('my_idx_emails');
    do_settings_sections('my_idx_emails');
    submit_button('Save Settings');
    ?>
</form>
