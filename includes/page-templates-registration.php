<?php
function property_template($templates) {
    $templates['property.php'] = 'Single Property';
    return $templates;
}
add_filter('theme_page_templates', 'property_template');

function load_property_template($template) {
    if (is_page_template('property.php')) {
        $plugin_template = plugin_dir_path(__DIR__) . 'page-templates/property.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    return $template;
}
add_filter('template_include', 'load_property_template');