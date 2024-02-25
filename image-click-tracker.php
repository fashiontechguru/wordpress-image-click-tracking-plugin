<?php
/*
Plugin Name: Image Click Tracker
Description: Track image clicks and export data to a CSV file.
Version: 00.1.0
Author: FashionTechGuru
License: MIT
*/

// Plugin metadata
define('IMAGE_CLICK_TRACKER_VERSION', '1.2.4');
define('IMAGE_CLICK_TRACKER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IMAGE_CLICK_TRACKER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include other plugin files
require_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'ajax-handler.php';
require_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'database.php';
require_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'csv-export.php';
require_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'dashboard_display.php';
require_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'functions.php';
require_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'click_track_widget.php';

// Add a menu item to the dashboard
add_action('admin_menu', 'image_clicks_menu');
function image_clicks_menu() {
    add_menu_page(
        'Image Clicks',
        'Image Clicks',
        'manage_options',
        'image_clicks',
        'display_image_clicks'
    );
}

// Add options page
function click_track_add_settings_page() {
    add_options_page(
        'Image Clicks',
        'Image Clicks',
        'manage_options',
        'image_clicks settings',
        'image_clicks_settings_html');
}
add_action('admin_menu', 'click_track_add_settings_page');

// Register activation hook
register_activation_hook(__FILE__, 'image_click_tracker_activation');
function image_click_tracker_activation() {
    // Add debug statement or error logging
    error_log('Image Click Tracker plugin activated. Creating tracking tables...');
    // Call the function responsible for creating tables
    create_tracking_tables();
}

// Enqueue JavaScript file for tracking interactions on both admin dashboard and frontend
add_action('admin_enqueue_scripts', 'image_click_tracker_enqueue_scripts');
add_action('wp_enqueue_scripts', 'image_click_tracker_enqueue_scripts');

function image_click_tracker_enqueue_scripts() {
    wp_enqueue_script('image-click-tracker', plugin_dir_url(__FILE__) . 'image-click-tracker.js', array('jquery'), null, true);
    wp_localize_script('image-click-tracker', 'imageTracker', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('image_click_tracker_nonce'),
        'click_tracking_enabled' => get_option('image_click_tracker_click_tracking_enabled', '0'), // Default to '0' if not set
        'load_tracking_enabled' => get_option('image_click_tracker_load_tracking_enabled', '0'), // Default to '0' if not set
    ));
}

add_action('wp_enqueue_scripts', 'enqueue_my_plugin_scripts');
    
// Pass data from PHP to JavaScript if needed
wp_localize_script('image-click-tracker', 'image_click_tracker_vars', array(
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('image_click_nonce')
    ));
}