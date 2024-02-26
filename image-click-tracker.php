<?php
/*
Plugin Name: Image Click Tracker
Description: Track image clicks and export data to a CSV file.
Version: 00.1.1
Author: FashionTechGuru
License: MIT
*/

// Plugin metadata
define('IMAGE_CLICK_TRACKER_VERSION', '00.1.1');
define('IMAGE_CLICK_TRACKER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IMAGE_CLICK_TRACKER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include other plugin files
require_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'ajax-handler.php';
require_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'database.php';
require_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'csv-export.php';
require_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'dashboard_display.php';
require_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'functions.php';
require_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'click_track_widget.php';
require_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'uninstall.php';

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


function image_click_tracker_enqueue_scripts() {
    if (get_option('click_tracking_enabled') || get_option('load_tracking_enabled')) {

    wp_enqueue_script('image-click-tracker', plugin_dir_url(__FILE__) . 'image-click-tracker.js', array('jquery'), null, true);

    $nonce_action = 'image_click_tracker_nonce';

    wp_localize_script('image-click-tracker', 'imageTracker', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce($nonce_action),
        'click_tracking_enabled' => get_option('image_click_tracker_click_tracking_enabled', '0'), // Default to '0' if not set
        'load_tracking_enabled' => get_option('image_click_tracker_load_tracking_enabled', '0'), // Default to '0' if not set
        // You can add any additional data needed for your script here.
    ));
// included extra curly bracket because of the IF statement used above:
}}

add_action('admin_enqueue_scripts', 'image_click_tracker_enqueue_scripts');

register_activation_hook(__FILE__, 'create_tracking_tables');

function image_click_tracker_activation() {
}

register_deactivation_hook(__FILE__, 'image_click_tracker_deactivation');

function image_click_tracker_deactivation() {
    // Perform necessary clean-up.
}