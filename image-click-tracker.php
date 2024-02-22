<?php
/*
Plugin Name: Image Click Tracker
Description: Track image clicks and export data to a CSV file.
Version: 1.2.3
Author: FashionTechGuru
License: MIT
*/

// Plugin metadata
define('IMAGE_CLICK_TRACKER_VERSION', '1.2.3');
define('IMAGE_CLICK_TRACKER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IMAGE_CLICK_TRACKER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include other plugin files
require_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'ajax-handler.php';
require_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'database.php';
require_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'csv-export.php';
require_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'display.php';
require_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'utilities.php';
require_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'tag-functions.php';
require_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'functions.php';

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
    wp_enqueue_script('image-click-tracker', IMAGE_CLICK_TRACKER_PLUGIN_URL . 'image-click-tracker.js', array('jquery'), '1.0', true);
    
    // Pass data from PHP to JavaScript if needed
    wp_localize_script('image-click-tracker', 'image_click_tracker_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('image_click_nonce')
    ));
}