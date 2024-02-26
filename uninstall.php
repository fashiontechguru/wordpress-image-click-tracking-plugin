<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// Option name
$option_name = 'image_click_tracker_option_name';

// For Single site
delete_option($option_name);

// For Multisite
delete_site_option($option_name);

// Drop a custom database table
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}your_table_name");

?>