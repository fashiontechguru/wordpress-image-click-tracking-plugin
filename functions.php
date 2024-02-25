<?php

// Define retrieve_image_click_data function
function retrieve_image_click_data($limit, $offset, $date_range, $tags) {
  global $wpdb;
  $table_name = $wpdb->prefix . 'image_clicks';

  // Debugging: Log a message to error log to check if the function is being called
  error_log('retrieve_image_click_data function called.');

  // Construct SQL query
  $sql = $wpdb->prepare("
      SELECT * 
      FROM $table_name
      WHERE DATE_SUB(CURDATE(), INTERVAL %d DAY) <= time
      LIMIT %d OFFSET %d
  ", $date_range, $limit, $offset);

  // Execute query
  $results = $wpdb->get_results($sql);

  return $results;
}

// Define the get_all_tags function
function get_all_tags() {
  global $wpdb;

  // Query to retrieve all tags
  $tags = $wpdb->get_results("SELECT name FROM $wpdb->terms");

  return $tags;
}

function handle_image_interaction_tracking() {
    check_ajax_referer('image_click_tracker_nonce', 'nonce');

    $interaction_type = $_POST['interaction_type'];
    $image_src = $_POST['image_src'];

    // Process tracking data here. Example: save to database, log, etc.

    wp_send_json_success('Image interaction tracked successfully.');
}
add_action('wp_ajax_track_image_interaction', 'handle_image_interaction_tracking');
add_action('wp_ajax_nopriv_track_image_interaction', 'handle_image_interaction_tracking');

// Function to retrieve tags associated with a post
function get_tags_for_post($post_id) {
    $postTags = array();
    // Retrieve tags associated with the post
    $post_tags = get_the_tags($post_id);
    // Check if tags exist
    if ($post_tags) {
        // Extract tag names
        foreach ($post_tags as $tag) {
            $postTags[] = $tag->name;
        }
    }
    return $postTags;
}

// Add action for logged-in and non-logged-in users
add_action('wp_ajax_my_action', 'my_action'); // for logged-in users
add_action('wp_ajax_nopriv_my_action', 'my_action'); // for non-logged-in users

// Function to register plugin settings
function register_image_click_settings() {
    // Ensure default values are set for the plugin's settings
    add_option('image_click_tracking_enabled', '1'); // Enable click tracking by default
    add_option('image_load_tracking_enabled', '1'); // Enable load tracking by default

    // Register settings for later retrieval
    register_setting('image_click_options_group', 'image_click_tracking_enabled');
    register_setting('image_click_options_group', 'image_load_tracking_enabled');
}
    
    add_action('admin_init', 'register_image_click_settings');

// Register settings
function image_click_settings() {
    add_option('image_click_tracking_enabled', '1'); // Default to enabled
    add_option('image_load_tracking_enabled', '1'); // Default to enabled
    register_setting('image_click_options_group', 'image_click_click_tracking_enabled');
    register_setting('image_load_options_group', 'image_click_load_tracking_enabled');
}

// GDPR compliance routine

// Register activation hook for setting up scheduled event
register_activation_hook(__FILE__, 'click_track_activate'); // No change here

function click_track_activate() { // Corrected function name to match the registered hook
    if (!wp_next_scheduled('click_track_clear_old_image_interactions')) { // Ensure consistency in hook naming
        wp_schedule_event(time(), 'daily', 'click_track_clear_old_image_interactions');
    }
}

// Hook into that event
add_action('click_track_clear_old_image_interactions', 'click_track_overwrite_old_image_ips'); // Changed function name for clarity

// Function to overwrite IP addresses older than 90 days with "255.255.255.255"
function click_track_overwrite_old_image_ips() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'image_clicks';
    $wpdb->query("UPDATE $table_name SET user_ip = '255.255.255.255' WHERE time < DATE_SUB(NOW(), INTERVAL 90 DAY)");
}

// Ensure the scheduled event is cleared on plugin deactivation
register_deactivation_hook(__FILE__, 'click_track_deactivate'); // No change here

function click_track_deactivate() { // No change in function name
    wp_clear_scheduled_hook('click_track_clear_old_image_interactions'); // Ensure consistency in hook naming
}

function count_total_records() {
    $table_name = $wpdb->prefix . 'image_clicks';
    $total_records = wp_count_rows( $table_name );
    return $total_records;
}

?>