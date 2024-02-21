<?php
/*
Plugin Name: Image Click Tracker
Description: Track image clicks and export data to a CSV file.
Version: 1.2.2
Author: FashionTechGuru
License: MIT
*/

// Define constants if not already defined
if (!defined('IMAGE_CLICK_TRACKER_PLUGIN_DIR')) {
    define('IMAGE_CLICK_TRACKER_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('IMAGE_CLICK_TRACKER_PLUGIN_URL')) {
    define('IMAGE_CLICK_TRACKER_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Enqueue jQuery
function enqueue_jquery() {
    if (!wp_script_is('jquery', 'enqueued')) {
        wp_enqueue_script('jquery');
    }
}
add_action('wp_enqueue_scripts', 'enqueue_jquery');

// Enqueue JavaScript
function enqueue_image_click_tracking_script() {
    // Localize the script with the 'ajax_object' variable
    wp_localize_script('image-click-tracking', 'ajax_object', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('image_click_nonce')));

    // Enqueue the script in the footer
    wp_enqueue_script('image-click-tracking', IMAGE_CLICK_TRACKER_PLUGIN_URL . 'javascript/image-click-tracking.js', array('jquery'), '1.0', true);
}

add_action('wp_enqueue_scripts', 'enqueue_image_click_tracking_script');

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
        tags varchar(255),
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
}

// Register activation hook
register_activation_hook(__FILE__, 'create_tracking_table');

// Track image clicks and save to database
function track_image_click() {
    global $wpdb;
    // Verify nonce to prevent CSRF attacks
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    if (!isset($_POST['nonce']) || !wp_verify_nonce($nonce, 'image_click_nonce')) {
        wp_die('Unauthorized access.');
    }

    $table_name = $wpdb->prefix . 'image_clicks';

    // Sanitize data before insertion into the database
    $image_url = isset($_POST['imageSrc']) ? esc_url_raw($_POST['imageSrc']) : '';
    $alt_text = isset($_POST['altText']) ? sanitize_text_field($_POST['altText']) : '';
    $tags = get_tags_for_image($image_url); // Function to retrieve tags associated with the image

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
            'tags'      => implode(', ', $tags),
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

// Retrieve image click data from the database using prepared statement
function retrieve_image_click_data($limit = 200, $offset = 0, $date_range = '30 days', $tags = '') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'image_clicks';

    $where = '';
    // Filter by date range
    if (!empty($date_range)) {
        $date_range_sql = date('Y-m-d H:i:s', strtotime("-{$date_range}"));
        $where .= $wpdb->prepare(" AND time >= %s", $date_range_sql);
    }

    // Filter by tags
    if (!empty($tags)) {
        $tags = array_map('sanitize_text_field', $tags);
        $tags = implode(', ', $tags);
        $where .= $wpdb->prepare(" AND tags IN (%s)", $tags);
    }

    $sql = $wpdb->prepare("SELECT * FROM $table_name WHERE 1=1 $where LIMIT %d OFFSET %d", $limit, $offset);

    return $wpdb->get_results($sql);
}

// Define the function to retrieve all post tags in the database.
function get_all_tags() {
    $tags = get_tags(); // Retrieve all tags from the WordPress database
    return $tags;
}

// Display tracked image click data with export option and filtering controls
function display_image_clicks() {
    global $wpdb;
    // Check if the user has permission to view the image click data
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access.');
    }

    $limit = 200; // Limit displayed rows to 200
    $offset = isset($_GET['offset']) ? absint($_GET['offset']) : 0;

    // Handle CSV export
    if (isset($_POST['export-csv']) && $_POST['export-csv'] === 'true') {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        if (!wp_verify_nonce($nonce, 'image_click_nonce')) {
            wp_die('Unauthorized access.');
        }

        $date_range = isset($_POST['date-range']) ? sanitize_text_field($_POST['date-range']) : '30 days';
        $tags = isset($_POST['tags']) ? $_POST['tags'] : array();

        $results = retrieve_image_click_data($limit, $offset, $date_range, $tags);

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
    $date_range = isset($_POST['date-range']) ? sanitize_text_field($_POST['date-range']) : '30 days';
    $tags = isset($_POST['tags']) ? $_POST['tags'] : array();
    $results = retrieve_image_click_data($limit, $offset, $date_range, $tags);

    // Output the data in a table with filtering controls
    ?>
    <div class="wrap">
        <h1>Image Clicks</h1>
        <form method="post" action="">
            <label for="filter-by-date">Filter by Date:</label>
            <select name="date-range" id="filter-by-date">
                <option value="30 days" <?php selected($date_range, '30 days'); ?>>Last 30 Days</option>
                <option value="60 days" <?php selected($date_range, '60 days'); ?>>Last 60 Days</option>
                <option value="90 days" <?php selected($date_range, '90 days'); ?>>Last 90 Days</option>
            </select>
            <label for="filter-by-tag">Filter by Tag:</label>
            <?php 
                $all_tags = get_all_tags(); // Custom function to get all tags
                foreach ($all_tags as $tag) {
                    echo '<input type="checkbox" name="tags[]" value="' . esc_attr($tag->name) . '" id="' . esc_attr($tag->name) . '">';
                    echo '<label for="' . esc_attr($tag->name) . '">' . esc_html($tag->name) . '</label>';
                }
            ?>
            <button type="submit">Filter</button>
        </form>
        <hr> <!-- Spacer line -->
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
            <input type="hidden" name="date-range" value="<?php echo esc_attr($date_range); ?>">
            <?php 
                foreach ($tags as $tag) {
                    echo '<input type="hidden" name="tags[]" value="' . esc_attr($tag) . '">';
                }
            ?>
            <button type="submit">Export Data to CSV</button>
        </form>
        <?php 
            // Pagination
            $total_records = count_total_records(); // Custom function to count total records
            $total_pages = ceil($total_records / $limit);
            $current_page = ($offset / $limit) + 1;
            echo paginate_links(array(
                'base' => add_query_arg('offset', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => $total_pages,
                'current' => $current_page
            ));
        ?>
    </div>
    <?php
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

// Function to retrieve tags associated with an image
function get_tags_for_image($image_url) {
    // Your implementation to retrieve tags for an image
}

// Function to retrieve tags associated with a post
function get_tags_for_post($post_id) {
    $tags = array();
    // Retrieve tags associated with the post
    $post_tags = get_the_tags($post_id);
    // Check if tags exist
    if ($post_tags) {
        // Extract tag names
        foreach ($post_tags as $tag) {
            $tags[] = $tag->name;
        }
    }
    return $tags;
}