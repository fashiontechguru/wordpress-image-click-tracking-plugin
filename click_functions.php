<?php

// ERROR TRACKING 
image_click_tracker_debug_log('Image Click Tracker: START PHP file click_functions.php.');

// Function definitions

function image_click_tracker_activation() {
    create_tracking_tables();
    image_click_tracker_insert_default_entry();
    
    // Set default options for click and load tracking if they haven't been set
    if (get_option('click_tracking_enabled') === false) {
        update_option('click_tracking_enabled', '1'); // '1' to indicate true
    }

    if (get_option('load_tracking_enabled') === false) {
        update_option('load_tracking_enabled', '1'); // '1' to indicate true
    }
}

function retrieve_image_click_data($limit, $offset, $date_range, $tags) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'image_clicks';

    // Start constructing the SQL query
    $sql = $wpdb->prepare("
        SELECT * FROM $table_name
        WHERE DATE_SUB(CURDATE(), INTERVAL %d DAY) <= time
    ", $date_range);

    // Check if there are tags to filter by and add them to the query
    if (!empty($tags)) {
        $tag_conditions = [];
        foreach ($tags as $tag) {
            // Sanitize each tag for safe inclusion
            $sanitized_tag = sanitize_text_field($tag);
            // Use FIND_IN_SET for comma-separated tag values in the 'tags' column
            $tag_conditions[] = $wpdb->prepare("FIND_IN_SET(%s, tags) > 0", $sanitized_tag);
        }
        // Append the tag conditions to the main SQL query
        $sql .= " AND (" . implode(' OR ', $tag_conditions) . ")";
    }

    // Append LIMIT and OFFSET to the query
    $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $limit, $offset);

    // Execute the query and return results
    $results = $wpdb->get_results($sql);
    return $results;
}

// Make a default entry in the database and remove it when the first record is written (2 functions).
function image_click_tracker_insert_default_entry() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'image_clicks';

    // Check if the table is empty
    $entry_exists = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

    if ($entry_exists == 0) {
        // Insert default entry
        $result = $wpdb->insert(
            $table_name,
            array(
                // 'id' is auto-increment, so no need to specify
                'time' => current_time('mysql'), // Current timestamp
                'interaction_type' => 'default_click', // Placeholder for interaction type
                'image_url' => 'https://example.com/default-image.jpg', // A placeholder image URL
                'alt_text' => 'Default Entry', // Alt text for the placeholder image
                'tags' => 'default, placeholder', // Example tags
                'file_size' => 1024, // Example file size in bytes
                'user_ip' => '255.255.255.255', // Anonymized IP address
                'weekly_tally' => 0, // Initial value indicating not tallied for weekly aggregation
                'occurrence_count' => 1, // Default occurrence
            ),
            array(
                '%s', // time
                '%s', // interaction_type
                '%s', // image_url
                '%s', // alt_text
                '%s', // tags
                '%d', // file_size
                '%s', // user_ip
                '%d', // weekly_tally, assuming bit is treated as int for insertion purposes
                '%d', // occurrence_count
            )
        );
        if ($result === false) {
            image_click_tracker_debug_log('Failed to insert default entry in ' . $table_name);
            // Consider returning false or throwing an exception based on your error handling strategy
        }
    }
}

function image_click_tracker_remove_default_entry() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'image_clicks';

    // Optional: Check if the default entry exists before attempting to remove it
    // This could be identified by a unique characteristic of the default entry, like a specific 'image_url' or 'alt_text'

    // Remove the default entry
    $result = $wpdb->delete(
        $table_name,
        array(
            'alt_text' => 'Default Entry', // Identify the default record by a unique characteristic
            // Adjust the criteria as necessary
        )
    );

    if ($result === false) {
        image_click_tracker_debug_log('Failed to delete default entry from ' . $table_name);
        // Consider returning false or throwing an exception based on your error handling strategy
    }
}

// Define the get_all_tags function
function get_all_tags() {
  global $wpdb;

  // Query to retrieve all tags
  $tags = $wpdb->get_results("SELECT name FROM $wpdb->terms");

  return $tags;
}

function handle_image_interaction_tracking() {
    // Verify the AJAX nonce for security
    check_ajax_referer('image_click_tracker_nonce', 'nonce');

    // Sanitize and validate all inputs meticulously
    $interaction_type = isset($_POST['interaction_type']) ? sanitize_text_field($_POST['interaction_type']) : '';
    $image_src = isset($_POST['image_src']) ? esc_url_raw($_POST['image_src']) : '';
    $alt_text = isset($_POST['alt_text']) ? sanitize_text_field($_POST['alt_text']) : 'Default Entry';
    $tags = isset($_POST['tags']) ? sanitize_text_field($_POST['tags']) : 'default, placeholder';
    $file_size = isset($_POST['file_size']) ? intval($_POST['file_size']) : 1024;
    // Use a dedicated function for IP sanitization to ensure proper anonymization and format
    $user_ip = isset($_POST['user_ip']) ? sanitize_text_field($_POST['user_ip']) : '255.255.255.255';

    // Ensure required fields are provided
    if (empty($interaction_type) || empty($image_src)) {
        wp_send_json_error('Missing required interaction data.');
        wp_die();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'image_clicks';

    // Insert the interaction record
    $data = array(
        'time' => current_time('mysql'), // Securely fetch the current time in MySQL format
        'interaction_type' => $interaction_type,
        'image_url' => $image_src,
        'alt_text' => $alt_text,
        'tags' => $tags,
        'file_size' => $file_size,
        'user_ip' => $user_ip // Ensure this is either sanitized here or pre-sanitized if coming from another function
    );
    $format = array('%s', '%s', '%s', '%s', '%s', '%d', '%s');
    
    $inserted = $wpdb->insert($table_name, $data, $format);

    // Check insertion result and respond accordingly
    if ($inserted === false) {
        $error_message = 'Database insertion failed in ' . $table_name . ': ' . $wpdb->last_error;
        image_click_tracker_debug_log($error_message);
        wp_send_json_error('Failed to track image interaction due to a database error.');
        wp_die();
    }
}

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

// Responsible for registering settings with WordPress
function register_image_click_settings() {
    register_setting('image_click_options_group', 'image_click_setting_name', 'sanitize_callback_function');
}

// Adds a settings section and fields for the user to interact with
function image_click_settings() {
    add_settings_section('image_click_section_id', 'Image Click Settings', 'section_callback_function', 'image_click_plugin');
    
    add_settings_field('image_click_field_id', 'Click Tracking Enabled', 'field_callback_function', 'image_click_plugin', 'image_click_section_id', array('label_for' => 'image_click_field_id'));
}

// GDPR compliance routine

function click_track_activate() { // Corrected function name to match the registered hook
    if (!wp_next_scheduled('click_track_clear_old_image_interactions')) { // Ensure consistency in hook naming
        wp_schedule_event(time(), 'daily', 'click_track_clear_old_image_interactions');
    }
}

// Function to overwrite IP addresses older than 90 days with "255.255.255.255"
function click_track_overwrite_old_image_ips() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'image_clicks';
    $wpdb->query("UPDATE $table_name SET user_ip = '255.255.255.255' WHERE time < DATE_SUB(NOW(), INTERVAL 90 DAY)");
}

function click_track_deactivate() { // No change in function name
    wp_clear_scheduled_hook('click_track_clear_old_image_interactions'); // Ensure consistency in hook naming
}

function count_total_records() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'image_clicks';

    // Ensure the table exists to prevent errors
    if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
        image_click_tracker_debug_log("Table {$table_name} does not exist.");
        return 0; // Return 0 to indicate no records if table does not exist
    }

    // Construct the query to count records
    $query = "SELECT COUNT(*) FROM {$table_name};";
    $total_records = $wpdb->get_var($query);

    // Check for SQL errors
    if($wpdb->last_error) {
        image_click_tracker_debug_log("SQL Error in count_total_records: " . $wpdb->last_error);
        return 0; // Return 0 to indicate an error condition safely
    }

    return intval($total_records); // Ensure the return value is always an integer
}

function count_filtered_records($date_range, $tags) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'image_clicks';

    // Start constructing the SQL query for counting
    $sql = $wpdb->prepare("
        SELECT COUNT(*) FROM $table_name
        WHERE DATE_SUB(CURDATE(), INTERVAL %d DAY) <= time
    ", $date_range);

    // Conditional to ensure tags are added only if they exist
    if (!empty($tags) && is_array($tags)) {
        $tag_conditions = array_map(function($tag) use ($wpdb) {
            return $wpdb->prepare("FIND_IN_SET(%s, tags) > 0", sanitize_text_field($tag));
        }, $tags);

        $sql .= " AND (" . implode(' OR ', $tag_conditions) . ")";
    } else {
        // Avoid SQL syntax error when there are no tags
        $sql .= " AND 1=1";
    }

    $count = $wpdb->get_var($sql);
    return $count ? $count : 0;
}

function image_click_tracker_schedule_event() {
    if (!wp_next_scheduled('image_click_tracker_weekly_event')) {
        wp_schedule_event(strtotime('next Tuesday 02:00:00'), 'weekly', 'image_click_tracker_weekly_event');
    }
}

function image_click_tracker_clear_scheduled_event() {
    $timestamp = wp_next_scheduled('image_click_tracker_weekly_event');
    wp_unschedule_event($timestamp, 'image_click_tracker_weekly_event');
}

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

// Logic for Admin Dashboard Historic Data tab
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
            echo "<nav class='image-click-tracker-paginate'>{$page_links}</nav>"; // Display pagination links
        }
    }
}

// Logic for Admin Dashboard Display Settings tab
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

function wp_image_click_tracker_register_settings() {
    register_setting(
        'wp_image_click_tracker_group', // Option group
        'wp_image_click_tracker_settings', // Option name
        array('sanitize_callback' => 'wp_image_click_tracker_sanitize') // Sanitize callback
    );
}
add_action('admin_init', 'wp_image_click_tracker_register_settings');

function get_image_file_size_callback() {
    $image_src = isset($_POST['image_src']) ? esc_url_raw($_POST['image_src']) : '';
    
    // Convert URL to local file path
    $image_path = parse_url($image_src, PHP_URL_PATH);
    $image_full_path = ABSPATH . trim($image_path, '/');

    if (file_exists($image_full_path)) {
        $file_size = filesize($image_full_path);
        $file_size_formatted = size_format($file_size);
        wp_send_json_success(array('file_size' => $file_size_formatted));
    } else {
        wp_send_json_error(array('message' => 'File does not exist'));
    }

    wp_die(); // this is required to terminate immediately and return a proper response
}

function add_image_file_size_to_img_tags($content) {
    // Regular expression to match all img tags
    $pattern = '/<img(.*?)src="([^"]+)"(.*?)>/i';

    // Callback function to process each img tag
    $callback = function($matches) {
        // Get the image file path from URL
        $image_path = parse_url($matches[2], PHP_URL_PATH);
        $image_full_path = $_SERVER['DOCUMENT_ROOT'] . $image_path;

        // Get the file size
        $file_size = file_exists($image_full_path) ? filesize($image_full_path) : 0;

        // Convert file size to a human-readable format (e.g., KB, MB)
        $file_size_formatted = size_format($file_size);

        // Reconstruct the img tag with the new attribute
        return "<img{$matches[1]}src=\"{$matches[2]}\"{$matches[3]} notation_click_filesize=\"{$file_size_formatted}\">";
    };

    // Apply the callback to each img tag in the content
    $content = preg_replace_callback($pattern, $callback, $content);

    return $content;
}
add_filter('the_content', 'add_image_file_size_to_img_tags');

function wp_image_click_tracker_sanitize($options) {
    // Ensure the input is an array and sanitize contents
    $clean_options = [];
    if (is_array($options) && !empty($options)) {
        foreach ($options as $key => $value) {
            // Example: Sanitize each option here if needed
            $clean_options[$key] = sanitize_text_field($value);
        }
    }
    return $clean_options;
}

function image_click_tracker_uninstall() {
    // Cleanup logic here. This function should replicate what's in your `uninstall.php` or `click_uninstall.php`.
    if (!defined('WP_UNINSTALL_PLUGIN')) {
        die;
    }

    $option_name = 'image_click_tracker_option_name';
    delete_option($option_name);
    delete_site_option($option_name);

    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}image_clicks");
}

// Hook registrations
add_action('wp_ajax_track_image_interaction', 'handle_image_interaction_tracking');
add_action('wp_ajax_nopriv_track_image_interaction', 'handle_image_interaction_tracking');
add_action('admin_init', 'register_image_click_settings');
register_activation_hook(__FILE__, 'click_track_activate');
add_action('click_track_clear_old_image_interactions', 'click_track_overwrite_old_image_ips');
register_deactivation_hook(__FILE__, 'click_track_deactivate');
register_activation_hook(__FILE__, 'image_click_tracker_schedule_event');
register_deactivation_hook(__FILE__, 'image_click_tracker_clear_scheduled_event');
add_action('image_click_tracker_weekly_event', 'compile_image_clicks_data');
add_action('admin_init', 'wp_image_click_tracker_settings_init');
add_action('admin_init', 'wp_image_click_tracker_register_settings');
add_action('wp_ajax_get_image_file_size', 'get_image_file_size_callback');
add_action('wp_ajax_nopriv_get_image_file_size', 'get_image_file_size_callback');
add_filter('the_content', 'add_image_file_size_to_img_tags');

// ERROR TRACKING 
image_click_tracker_debug_log('Image Click Tracker: END PHP file click_functions.php.');

function retrieve_image_click_data_prepared($limit, $offset, $date_range, $tags) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'image_clicks';

    // Start constructing the SQL query
    $sql = $wpdb->prepare("
        SELECT * FROM $table_name
        WHERE DATE_SUB(CURDATE(), INTERVAL %d DAY) <= time
    ", $date_range);

    // Check if there are tags to filter by and add them to the query
    if (!empty($tags)) {
        $tag_conditions = [];
        foreach ($tags as $tag) {
            // Sanitize each tag for safe inclusion
            $sanitized_tag = sanitize_text_field($tag);
            // Use FIND_IN_SET for comma-separated tag values in the 'tags' column
            $tag_conditions[] = $wpdb->prepare("FIND_IN_SET(%s, tags) > 0", $sanitized_tag);
        }
        // Append the tag conditions to the main SQL query
        $sql .= " AND (" . implode(' OR ', $tag_conditions) . ")";
    }

    // Append LIMIT and OFFSET to the query
    $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $limit, $offset);

    // Execute the query and return results
    $results = $wpdb->get_results($sql);
    return $results;
}