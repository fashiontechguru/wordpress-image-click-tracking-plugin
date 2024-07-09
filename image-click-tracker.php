<?php
/*
Plugin Name: Image Click Tracker
Description: Track image clicks and export data to a CSV file.
Version: 0.1.1
Author: FashionTechGuru
License: MIT
 */

// Debug lines like the one below are meant to be used with the Logdancer plugin: https://github.com/fashiontechguru/WordPress-Logdancer-Plugin  

error_log('Image Click Tracker: START');

// Define plugin constants for easy reference.
define('IMAGE_CLICK_TRACKER_VERSION', '0.1.1');
define('IMAGE_CLICK_TRACKER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IMAGE_CLICK_TRACKER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include dependency files.
include_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'click_debug.php';
include_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'click_database.php';
include_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'click_functions.php';
include_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'click_csv-export.php';
include_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'click_widget.php';

// Register plugin activation and deactivation hooks.
register_activation_hook(__FILE__, 'image_click_tracker_activation');
register_deactivation_hook(__FILE__, 'image_click_tracker_deactivation');
register_uninstall_hook(__FILE__, 'image_click_tracker_uninstall');

// Enqueue scripts for the front-end.
add_action('wp_enqueue_scripts', 'image_click_tracker_client_enqueue_scripts');

function image_click_tracker_client_enqueue_scripts() {
    // Enqueue front-end scripts only if click or load tracking is enabled.
    if (!is_admin()) {
        wp_enqueue_script(
            'image-click-tracker-client-script',
            IMAGE_CLICK_TRACKER_PLUGIN_URL . 'click_javascript_clientside.js',
            ['jquery'],
            IMAGE_CLICK_TRACKER_VERSION,
            true
        );

        wp_localize_script('image-click-tracker-client-script', 'imageClickTrackerData', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('image_click_tracker_nonce'),
            'click_tracking_enabled' => get_option('image_click_tracker_click_tracking_enabled', '0'),
            'load_tracking_enabled' => get_option('image_click_tracker_load_tracking_enabled', '0'),
        ]);
    }
}

// Register the admin menu and conditionally include admin files
add_action('admin_menu', 'image_click_tracker_admin_menu');

function image_click_tracker_admin_menu() {
    // Conditional include within the admin_menu hook to ensure it only runs in admin area
    //    
    include_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'click_display_settings.php';

    // Add the top-level admin menu
    $main_page = add_menu_page(
        __('Image Click Tracker', 'image-click-tracker'), // page title
        __('Image Click Tracker', 'image-click-tracker'), // menu title
        'manage_options', // capability
        'image-click-tracker', // menu slug
        'image_click_tracker_display_page', // function to display the content of the main plugin page
        'dashicons-welcome-widgets-menus' // icon URL
    );

    // Add the submenu page for settings
    $settings_page = add_submenu_page(
        'image-click-tracker', // parent slug
        __('Settings', 'image-click-tracker'), // page title
        __('Settings', 'image-click-tracker'), // menu title
        'manage_options', // capability
        'image-click-tracker-settings', // menu slug
        'image_click_tracker_settings_page' // function to display the content of the settings page
    );

    // Ensure the scripts and styles are loaded only on the plugin's pages
    add_action('load-' . $main_page, 'image_click_tracker_load_scripts');
    add_action('load-' . $settings_page, 'image_click_tracker_load_scripts');
}

// Include the AJAX handler, ensuring it's loaded after all dependencies.
include_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'click_ajax-handler.php';

// Function to load scripts and styles
function image_click_tracker_load_scripts() {
    // Enqueue scripts and styles here using wp_enqueue_script() and wp_enqueue_style()
}

function image_click_tracker_deactivation() {
    // Remove scheduled events
    $timestamp = wp_next_scheduled('image_click_tracker_weekly_event');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'image_click_tracker_weekly_event');
    }

    // ERROR TRACKING - Log that the plugin has been deactivated
    error_log('Image Click Tracker: Plugin deactivated.');
}

// ERROR TRACKING: Log the start of execution for troubleshooting.
image_click_tracker_debug_log('Image Click Tracker: END PHP file image-click-tracker.php.');

function image_click_tracker_display_page() {
    include_once IMAGE_CLICK_TRACKER_PLUGIN_DIR . 'click_display.php';
}
