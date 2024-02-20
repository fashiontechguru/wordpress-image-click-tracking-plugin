<?php
/*
Plugin Name: Image Click Tracker
Description: Track image clicks and save to WordPress database.
Version: 1.1
Author: FashionTechGuru
MIT License
*/

// Enqueue jQuery
function enqueue_jquery() {
    wp_enqueue_script('jquery');
}
add_action('wp_enqueue_scripts', 'enqueue_jquery');

// Create database table on plugin activation
function create_tracking_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'image_clicks';
    $charset_collate = $wpdb->get_charset_collate();

    // Define SQL query to create the database table
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        image_url varchar(255) NOT NULL,
        alt_text varchar(255),
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // Include necessary WordPress file for database operations
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    // Execute the SQL query to create the table
    dbDelta($sql);
}
// Register the create_tracking_table function to run on plugin activation
register_activation_hook(__FILE__, 'create_tracking_table');

// Track image clicks and save to database
function track_image_click() {
    // Check for nonce to prevent CSRF attacks
    check_ajax_referer('image_click_nonce', 'nonce');

    global $wpdb;
    $table_name = $wpdb->prefix . 'image_clicks';

    // Sanitize data before insertion into the database
    $image_url = esc_url($_POST['imageSrc']);
    $alt_text = sanitize_text_field($_POST['altText']);

    // Insert sanitized data into the database table
    $wpdb->insert(
        $table_name,
        array(
            'time'      => current_time('mysql'),
            'image_url' => $image_url,
            'alt_text'  => $alt_text,
        )
    );

    // End AJAX request
    wp_die();
}
// Register the track_image_click function to handle AJAX requests for tracking image clicks
add_action('wp_ajax_track_image_click', 'track_image_click');
add_action('wp_ajax_nopriv_track_image_click', 'track_image_click');