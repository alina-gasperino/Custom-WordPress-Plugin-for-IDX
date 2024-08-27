<h2>Site Configuration</h2>
<form method="post" action="options.php">
    <?php
    settings_fields('my_idx_general');
    do_settings_sections('my_idx_general');
    submit_button('Save General Settings');
    ?>
</form>
