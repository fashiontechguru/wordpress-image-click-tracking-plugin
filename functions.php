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

// Schedule event on plugin activation
register_activation_hook(__FILE__, 'image_click_tracker_schedule_event');
function image_click_tracker_schedule_event() {
    if (!wp_next_scheduled('image_click_tracker_weekly_event')) {
        wp_schedule_event(strtotime('next Tuesday 02:00:00'), 'weekly', 'image_click_tracker_weekly_event');
    }
}

// Clear scheduled event on plugin deactivation
register_deactivation_hook(__FILE__, 'image_click_tracker_clear_scheduled_event');
function image_click_tracker_clear_scheduled_event() {
    $timestamp = wp_next_scheduled('image_click_tracker_weekly_event');
    wp_unschedule_event($timestamp, 'image_click_tracker_weekly_event');
}

// Handler for the scheduled event
add_action('image_click_tracker_weekly_event', 'compile_image_clicks_data');
function compile_image_clicks_data() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'image_clicks';

    // Get the start of the current week in UTC
    $week_start = date('Y-m-d H:i:s', strtotime('last monday', strtotime('tomorrow')));

    // SQL to compile data
    $compile_sql = "
        INSERT INTO $table_name (time, interaction_type, image_url, alt_text, tags, file_size, user_ip, weekly_tally, occurrence_count)
        SELECT MIN(time), interaction_type, image_url, alt_text, tags, file_size, user_ip, 1, COUNT(*)
        FROM $table_name
        WHERE weekly_tally = 0 AND time < %s
        GROUP BY image_url;
    ";

    // Execute compilation
    $wpdb->query($wpdb->prepare($compile_sql, $week_start));

    // Set weekly_tally for compiled records
    $update_sql = "
        UPDATE $table_name
        SET weekly_tally = 1
        WHERE weekly_tally = 0 AND time < %s;
    ";

    // Execute update
    $wpdb->query($wpdb->prepare($update_sql, $week_start));
}

// Function to display historic data in the Admin Dashboard
function display_historic_data() {
    global $wpdb; // Access the WordPress database object
    $table_name = $wpdb->prefix . 'image_clicks'; // Determine the table name with WP prefix

    // Pagination setup: Determine current page number from URL, or default to 1
    $page_number = isset($_GET['pagenum']) ? absint($_GET['pagenum']) : 1;
    $items_per_page = 10; // Define how many items to show per page
    $offset = ($page_number - 1) * $items_per_page; // Calculate offset for the current page

    // Basic query to fetch historic data, where weekly_tally is set (consolidated data)
    $query = "SELECT * FROM $table_name WHERE weekly_tally = 1";

    // If a tag filter is applied, modify the query to include a LIKE condition
    $tag_filter = isset($_GET['tag']) ? sanitize_text_field($_GET['tag']) : '';
    if (!empty($tag_filter)) {
        $query .= $wpdb->prepare(" AND tags LIKE %s", '%' . $wpdb->esc_like($tag_filter) . '%');
    }

    // Append ordering by time and limit by pagination parameters
    $query .= $wpdb->prepare(" ORDER BY time DESC LIMIT %d OFFSET %d", $items_per_page, $offset);

    // Execute the query to get results for the current page
    $results = $wpdb->get_results($query);

    // Begin table HTML for displaying results
    echo "<table>";
    echo "<tr><th>Image URL</th><th>Time</th><th>Occurrence Count</th></tr>";
    // Loop through each result and display it in a new table row
    foreach ($results as $row) {
        echo "<tr>";
        echo "<td>" . esc_url($row->image_url) . "</td>"; // Escape and display the image URL
        echo "<td>" . esc_html($row->time) . "</td>"; // Escape and display the time
        echo "<td>" . esc_html($row->occurrence_count) . "</td>"; // Escape and display the occurrence count
        echo "</tr>";
    }
    echo "</table>"; // End of the table HTML

    // Pagination logic: Calculate total pages needed
    $total_query = "SELECT COUNT(1) FROM {$table_name} WHERE weekly_tally = 1";
    $total = $wpdb->get_var($total_query);
    $total_pages = ceil($total / $items_per_page);

    // Generate and display pagination links if there are more than one page
    if ($total_pages > 1) {
        $page_links = paginate_links(array(
            'base' => add_query_arg('pagenum', '%#%'),
            'format' => '',
            'prev_text' => __('&laquo;', 'text-domain'),
            'next_text' => __('&raquo;', 'text-domain'),
            'total' => $total_pages,
            'current' => $page_number
        ));

        if ($page_links) {
            echo "<nav class='wp-paginate'>" . $page_links . "</nav>"; // Display pagination links
        }
    }
}

function wp_image_click_tracker_settings_init() {
    // Register a new setting for "wp_image_click_tracker" page
    register_setting('wp_image_click_tracker_group', 'wp_image_click_tracker_settings');

    // Register a new section in the "wp_image_click_tracker" page
    add_settings_section(
        'wp_image_click_tracker_section',
        __('Image Click Tracker Settings', 'wp_image_click_tracker'),
        'wp_image_click_tracker_section_callback',
        'wp_image_click_tracker'
    );

    // Register a new field for the section we've added
    add_settings_field(
        'wp_image_click_tracker_field_click', // As used in the 'id' attribute of tags
        __('Enable Click Tracking', 'wp_image_click_tracker'), // Title
        'wp_image_click_tracker_field_click_callback', // Callback for rendering the setting
        'wp_image_click_tracker', // Page to display on
        'wp_image_click_tracker_section' // Section to display in
    );

    add_settings_field(
        'wp_image_click_tracker_field_load',
        __('Enable Load Tracking', 'wp_image_click_tracker'),
        'wp_image_click_tracker_field_load_callback',
        'wp_image_click_tracker',
        'wp_image_click_tracker_section'
    );
}

add_action('admin_init', 'wp_image_click_tracker_settings_init');

// Section callback function is optional
function wp_image_click_tracker_section_callback() {
    echo '<p>' . __('Select the interactions you wish to track.', 'wp_image_click_tracker') . '</p>';
}

// Click Tracking checkbox callback
function wp_image_click_tracker_field_click_callback() {
    $options = get_option('wp_image_click_tracker_settings');
    ?>
    <input type="checkbox" name="wp_image_click_tracker_settings[click_tracking]" <?php checked(isset($options['click_tracking']), true); ?> value="1">
    <?php
}

// Load Tracking checkbox callback
function wp_image_click_tracker_field_load_callback() {
    $options = get_option('wp_image_click_tracker_settings');
    ?>
    <input type="checkbox" name="wp_image_click_tracker_settings[load_tracking]" <?php checked(isset($options['load_tracking']), true); ?> value="1">
    <?php
}

?>