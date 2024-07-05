<?php

// ERROR TRACKING 
error_log('Image Click Tracker: START PHP file uninstall.php.');

// First, make sure this script is being called from the uninstall process
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// Include WordPress functions to be able to use get_option()
if (!function_exists('get_option')) {
    // Adjust the path as necessary to find wp-load.php, ensuring WordPress functions are available
    require_once(dirname(__FILE__, 3) . '/wp-load.php');
}

// Check if the option to drop the table on uninstall is enabled
if (get_option('drop_table_on_uninstall') == '1') {
    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}image_clicks");
}

// Delete plugin options
delete_option('click_tracking_enabled');
delete_option('load_tracking_enabled');
delete_option('drop_table_on_uninstall');

// If it's a multisite, you might also want to loop through the blogs and delete options accordingly
// This is an example and might need adjustments based on your setup
if (is_multisite()) {
    $blogs = get_sites();
    foreach ($blogs as $blog) {
        switch_to_blog($blog->blog_id);
        delete_option('click_tracking_enabled');
        delete_option('load_tracking_enabled');
        delete_option('drop_table_on_uninstall');
        restore_current_blog();
    }
}

// ERROR TRACKING 
error_log('Image Click Tracker: END PHP file uninstall.php.');