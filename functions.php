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

// Add action for logged-in and non-logged-in users
add_action('wp_ajax_my_action', 'my_action'); // for logged-in users
add_action('wp_ajax_nopriv_my_action', 'my_action'); // for non-logged-in users

?>