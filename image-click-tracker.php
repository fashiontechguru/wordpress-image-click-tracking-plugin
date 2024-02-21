<?php
/*
Plugin Name: Image Click Tracker
Description: Track image clicks and export data to a CSV file.
Version: 1.2.1
Author: FashionTechGuru
License: MIT
*/

// Enqueue jQuery
function enqueue_jquery() {
    wp_enqueue_script('jquery');
}
add_action('wp_enqueue_scripts', 'enqueue_jquery');

// Enqueue Javascript
function enqueue_image_click_tracking_script() {
    wp_enqueue_script('image-click-tracking', plugins_url('/javascript/image-click-tracking.js', __FILE__), array('jquery'), '1.0', true);
}
add_action('wp_enqueue_scripts', 'enqueue_image_click_tracking_script');

// Initialize $wpdb
global $wpdb;
if (!isset($wpdb)) {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $wpdb = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
}

// Create database table on plugin activation
function create_tracking_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'image_clicks';
    $charset_collate = $wpdb->get_charset_collate();

    // Check if the table already exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
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

        // Check for errors
        if ($wpdb->last_error) {
            // Handle error
            error_log('Error creating tracking table: ' . $wpdb->last_error);
            wp_die('Error creating tracking table: Database error occurred.');
        }
    } else {
        // Table already exists
        error_log('Tracking table already exists.');
    }
}

// Register the create_tracking_table function to run on plugin activation
register_activation_hook(__FILE__, 'create_tracking_table');

// Track image clicks and save to database
function track_image_click() {
    // Verify nonce to prevent CSRF attacks
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'image_click_nonce' ) ) {
        wp_die( 'Unauthorized access.' );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'image_clicks';

    // Sanitize data before insertion into the database
    $image_url = esc_url_raw($_POST['imageSrc']); // Use esc_url_raw for less restrictive sanitization
    $alt_text = sanitize_text_field($_POST['altText']);
    $tags = get_tags_for_image($_POST['imageSrc']); // Function to retrieve tags associated with the image

// Get post ID from image URL
    $post_id = attachment_url_to_postid($image_url);

    // Check if post ID is valid
    if ($post_id) {
        // Retrieve tags associated with the post
        $post_tags = get_the_tags($post_id);

        // Check if tags exist
        if ($post_tags) {
            // Extract tag names
            foreach ($post_tags as $tag) {
                $tags[] = $tag->name;
            }
        }
    }

    // Use prepared statement for inserting data
    $wpdb->insert(
        $table_name,
        array(
            'time'      => current_time('mysql'),
            'image_url' => $image_url,
            'alt_text'  => $alt_text,
            'tags'      => $tags,
        ),
        array(
            '%s', // time
            '%s', // image_url
            '%s', // alt_text
            '%s', // tags
        )
    );

    // End AJAX request
    wp_die();
}

// Function to retrieve tags associated with the image
function get_tags_for_image($image_url) {
    // Your code to retrieve and process tags associated with the image
    return $tags;
}

// Retrieve image click data from the database using prepared statement
$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY time DESC"));

// Display tracked image click data with export option and filtering controls
function display_image_clicks() {

    // Define $nonce
    $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';

    // Check if the user has permission to view the image click data
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access.');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'image_clicks';

    // Handle CSV export
    if (isset($_POST['export-csv']) && $_POST['export-csv'] === 'true') {
        // Verify nonce to prevent CSRF attacks
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        if (!wp_verify_nonce($nonce, 'image_click_nonce')) {
            wp_die('Unauthorized access.');
        }

        // Retrieve image click data from the database
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY time DESC");

        // Set CSV headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="image_clicks.csv"');

        // Open output stream
        $output = fopen('php://output', 'w');

        // Write CSV header
        fputcsv($output, array('ID', 'Time', 'Image Source', 'Alt Text'));

        // Write CSV data
        foreach ($results as $row) {
            fputcsv($output, array($row->id, $row->time, $row->image_url, $row->alt_text));
        }

        // Close output stream
        fclose($output);

        // Prevent WordPress from rendering anything else
        exit;
    }

    // Retrieve image click data from the database using prepared statement
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY time DESC"));

    // Add a button to manually refresh the data
    function add_refresh_button() {
        ?>
        <form method="post" action="">
            <input type="hidden" name="refresh-data" value="true">
            <button type="submit">Refresh Data</button>
        </form>
        <?php
    }

    // Hook the function to display the button
    add_action('admin_notices', 'add_refresh_button');

    // Display filtering options
    function display_filtering_options() {
        ?>
        <form method="post" action="">
            <label for="filter-by-date">Filter by Date:</label>
            <input type="date" id="filter-by-date" name="filter-by-date">
            <label for="filter-by-tag">Filter by Tag:</label>
            <input type="text" id="filter-by-tag" name="filter-by-tag">
            <button type="submit">Filter</button>
        </form>
        <?php
    }

    // Hook the function to display filtering options
    add_action('admin_notices', 'display_filtering_options');

    // Output the data in a table with filtering controls
    ?>
    <div class="wrap">
        <h1>Image Clicks</h1>
        <form method="post" action="">
            <label for="filter-by-date">Filter by Date:</label>
            <input type="date" id="filter-by-date" name="filter-by-date">
            <button type="submit">Filter</button>
        </form>
        <table class="widefat">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Time</th>
                    <th>Image Source</th>
                    <th>Alt Text</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row) : ?>
                    <tr>
                        <td><?php echo esc_html($row->id); ?></td>
                        <td><?php echo esc_html($row->time); ?></td>
                        <td><?php echo esc_html($row->image_url); ?></td>
                        <td><?php echo esc_html($row->alt_text); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <form method="post" action="">
            <input type="hidden" name="export-csv" value="true">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('image_click_nonce'); ?>">
            <button type="submit">Export Data to CSV</button>
        </form>
    </div>
    <?php
}

// Handle CSV export
if (isset($_POST['export-csv']) && $_POST['export-csv'] === 'true') {
    // Verify nonce to prevent CSRF attacks
    $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
    if (!wp_verify_nonce($nonce, 'image_click_nonce')) {
        wp_die('Unauthorized access.');
    }

    // Retrieve image click data from the database
    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY time DESC");

    // Set CSV headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="image_clicks.csv"');

    // Open output stream
    $output = fopen('php://output', 'w');

    // Write CSV header
    fputcsv($output, array('ID', 'Time', 'Image Source', 'Alt Text'));

    // Write CSV data
    foreach ($results as $row) {
        fputcsv($output, array($row->id, $row->time, $row->image_url, $row->alt_text));
    }

    // Close output stream
    fclose($output);

    // Prevent WordPress from rendering anything else
    exit;
}


// Error handling for database table creation
if ($wpdb->last_error) {
    // Handle error without revealing sensitive information
    error_log('Error creating tracking table: Database error occurred');
}

// Error handling for CSV export
if (!wp_verify_nonce($nonce, 'image_click_nonce')) {
    // Handle unauthorized access without revealing specific details
    wp_die('Unauthorized access.');
}

// Add a menu item to the dashboard
function image_clicks_menu() {
    add_menu_page(
        'Image Clicks',
        'Image Clicks',
        'manage_options',
        'image_clicks',
        'display_image_clicks'
    );
}
add_action('admin_menu', 'image_clicks_menu');