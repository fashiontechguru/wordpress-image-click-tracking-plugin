<?php

// ERROR TRACKING: Log the start of execution for troubleshooting.
image_click_tracker_debug_log('Image Click Tracker: START PHP file click_display.php.');

// Prevent direct access to the file for security.
if (!defined('ABSPATH')) {
    exit;
}

// ERROR TRACKING - GRANULAR
image_click_tracker_debug_log('Image Click Tracker: CHECKPOINT 1 click_display.php - Confirmed WP core files loaded by using defined abspath.');

// Dynamically load debugging script based on a constant from another file.
if (defined('IMAGE_CLICK_TRACKER_DEBUG') && IMAGE_CLICK_TRACKER_DEBUG) {
    echo "<script>console.log('Debugging Enabled');</script>";
}

// Display tracked image click data with export option and filtering controls on a wordpress admin dashboard page
function display_image_clicks() {
echo esc_html__('Hello World! This is the Image Click Tracker plugin page.', 'image-click-tracker');

// ERROR TRACKING - GRANULAR
image_click_tracker_debug_log('Image Click Tracker: CHECKPOINT 2 click_display.php - Hello World declared.');

    global $wpdb;

    // Initialize $results to ensure it's always an array
    $results = []; 
    $limit = 200; // NEWCODE: Adjusted comment to match actual code
    $offset = isset($_GET['offset']) ? absint($_GET['offset']) : 0;
    $date_range = isset($_POST['date-range']) ? sanitize_text_field($_POST['date-range']) : '30 days';

    // Check if the form is submitted for CSV export
    if (isset($_POST['export-csv']) && $_POST['export-csv'] === 'true') {
        // NEWCODE: Verify nonce for security
        if (!wp_verify_nonce($_POST['_wpnonce'], 'export_csv_nonce_action')) {
            wp_die('Nonce verification failed!', 403);
        }
        handle_csv_export();
        return; // Stops further HTML output
    }

    // Permission check for security
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access.');
    }

    // Data retrieval for display with sanitization for $tags
    $tags = isset($_POST['tags']) ? array_map('sanitize_text_field', (array) $_POST['tags']) : [];

    // Data retrieval with added error handling
    try {
        $results = retrieve_image_click_data($limit, $offset, $date_range, $tags);
    } catch (Exception $e) {
        // Handle possible errors during data retrieval
        image_click_tracker_debug_log('Error retrieving image click data: ' . $e->getMessage());
        $results = []; // Ensure $results is an array even in case of error
    }

}

// ERROR TRACKING - GRANULAR
image_click_tracker_debug_log('Image Click Tracker: CHECKPOINT 3 click_display.php - Display Image Clicks Function.');

?>

<hr>

<h2>RECENT</h2>

<?php

// The RECENT section is used to create a table displaying the accumulated click, load, and hover tracking data recently collected by the plugin. It displays 200 rows at a time, and the data can be sorted by any column's content. The data can also be filtered by interaction type, date ranges in 12 hour increments, by anonymized user ip, by keywords in the alt text, or by tags included in the database by reading the WordPress post tags associated with the post the image was included in.

// ERROR TRACKING 
image_click_tracker_debug_log('Image Click Tracker: START RECENT SECTION in click_display.php.');

// ERROR TRACKING - GRANULAR
image_click_tracker_debug_log('Image Click Tracker: CHECKPOINT Recent 1 of 7 in click_display.php.');

// Define items per page at the beginning to avoid undefined variable error
$items_per_page = 100; // Display 100 rows at a time

// Initialize and sanitize variables from user input
$date_range = isset($_POST['date-range']) ? sanitize_text_field($_POST['date-range']) : '30 days';
$interaction_type = isset($_POST['interaction-type']) ? sanitize_key($_POST['interaction-type']) : '';
$anonymized_ip = isset($_POST['anonymized-ip']) ? sanitize_text_field($_POST['anonymized-ip']) : '';
$keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
$tags = isset($_POST['tags']) ? array_map('sanitize_text_field', (array)$_POST['tags']) : [];

// Pagination and total records for current filters, ensure SQL queries are prepared correctly
$total_records = count_filtered_records($interaction_type, $date_range, $anonymized_ip, $keyword, $tags); // Assuming count_filtered_records is secure and uses prepared statements
$total_pages = ceil($total_records / $items_per_page);

// Calculate page number after knowing the total pages to ensure it's within the valid range
$page_number = isset($_GET['pagenum']) ? max(1, min(absint($_GET['pagenum']), $total_pages)) : 1;

// Calculate offset for the current page
$offset = ($page_number - 1) * $items_per_page;

// Fetch data based on the filters applied, ensuring queries are prepared to prevent SQL injection
$results = retrieve_image_click_data_prepared($interaction_type, $date_range, $anonymized_ip, $keyword, $tags, $offset, $items_per_page); // Assuming retrieve_image_click_data_prepared uses $wpdb->prepare

// ERROR TRACKING - GRANULAR
image_click_tracker_debug_log('Image Click Tracker: CHECKPOINT Recent 2 of 7 in click_display.php.');

?>
<div id="DataViewer">
    <h3>Image Click, Load, and Hover Data</h3>
    <div class='wrap'>
        <!-- Form for filters -->
        <form method="post" action="">
            <!-- Interaction Type Filter -->
            <label for="interaction-type">Filter by Interaction Type:</label>
            <select name="interaction-type" id="interaction-type">
                <option value="">All Interactions</option>
                <option value="click" <?php selected($interaction_type, 'click'); ?>>Click</option>
                <option value="load" <?php selected($interaction_type, 'load'); ?>>Load</option>
                <option value="hover" <?php selected($interaction_type, 'hover'); ?>>Hover</option>
            </select>

            <!-- Date Range Filter -->
            <label for="filter-by-date">Filter by Date Range:</label>
            <input type="text" name="date-range" value="<?php echo esc_attr($date_range); ?>" placeholder="e.g., Last 30 Days">

<?php

// ERROR TRACKING - GRANULAR
image_click_tracker_debug_log('Image Click Tracker: CHECKPOINT Recent 3 of 7 in click_display.php.');

?>

            <!-- Anonymized IP Filter -->
            <label for="anonymized-ip">Filter by Anonymized IP:</label>
            <input type="text" name="anonymized-ip" id="anonymized-ip" value="<?php echo esc_attr($anonymized_ip); ?>">

<?php

// ERROR TRACKING - GRANULAR
image_click_tracker_debug_log('Image Click Tracker: CHECKPOINT Recent 4 of 7 in click_display.php.');

?>

            <!-- Keyword Filter -->
            <label for="keyword">Filter by Keyword in Alt Text:</label>
            <input type="text" name="keyword" id="keyword" value="<?php echo esc_attr($keyword); ?>">

<?php

// ERROR TRACKING - GRANULAR
image_click_tracker_debug_log('Image Click Tracker: CHECKPOINT Recent 5 of 7 in click_display.php.');

?>

            <!-- Tag Filter -->
            <label for="filter-by-tag">Filter by Tag:</label>
            <!-- Dynamic tag checkboxes based on available tags -->

<?php

// ERROR TRACKING - GRANULAR
image_click_tracker_debug_log('Image Click Tracker: CHECKPOINT Recent 6 of 7 in click_display.php.');

?>

            <!-- Submit Button for Filters -->
            <button type="submit">Apply Filters</button>
        </form>

<?php

// ERROR TRACKING - GRANULAR
image_click_tracker_debug_log('Image Click Tracker: CHECKPOINT Recent 7 of 7 in click_display.php.');

?>

        <!-- Table Displaying Data -->
        <table class="widefat">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Time</th>
                    <th>Interaction Type</th>
                    <th>Image Source</th>
                    <th>Alt Text</th>
                    <th>Anonymized IP</th>
                    <th>Tags</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row) : ?>
                <tr>
                    <td><?php echo esc_html($row->id); ?></td>
                    <td><?php echo esc_html($row->time); ?></td>
                    <td><?php echo esc_html($row->interaction_type); ?></td>
                    <td><?php echo esc_html($row->image_url); ?></td>
                    <td><?php echo esc_html($row->alt_text); ?></td>
                    <td><?php echo esc_html($row->anonymized_ip); ?></td>
                    <td><?php echo esc_html(implode(', ', $row->tags)); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
        <?php 
            // Generate the pagination links
            for ($i = 1; $i <= $total_pages; $i++) {
                // Construct the link for each page. Adjust 'admin.php?page=image-click-tracker-display' as necessary.
                $link = admin_url('admin.php?page=image-click-tracker-display&pagenum=' . $i);

                // Check if it's the current page
                $class = ($i == $page_number) ? ' class="active"' : '';

                echo "<a href=\"{$link}\"{$class}>{$i}</a> ";
            }
        ?>
        </div>

    </div>
</div>

<?php
// ERROR TRACKING 
image_click_tracker_debug_log('Image Click Tracker: END RECENT SECTION in click_display.php.');
?>

<hr>

<h2>HISTORIC</h2>

<?php

// ERROR TRACKING 
image_click_tracker_debug_log('Image Click Tracker: START HISTORIC SECTION in click_display.php.');

// Pagination setup

global $wpdb;
$table_name = $wpdb->prefix . 'image_clicks';

$page_number = isset($_GET['pagenum']) ? absint($_GET['pagenum']) : 1;
$items_per_page = 10; // Define how many items to show per page
$offset = ($page_number - 1) * $items_per_page; // Calculate offset for the current page

// Initialize and sanitize tag filter if provided
$tag_filter = isset($_POST['tags']) ? array_map('sanitize_text_field', $_POST['tags']) : []; // NEWEDIT: Corrected to ensure tag_filter is sanitized

// CHATGPT: ADD REFERENCE TO "$table_name = $wpdb->prefix . 'image_clicks';" HERE 

// Construct base query
$query = "SELECT * FROM {$table_name} WHERE weekly_tally = 1";

// Apply tag filter if necessary
if (!empty($tag_filter)) {
    // Create SQL condition for each tag
    $tags_conditions = array_map(function ($tag) use ($wpdb) {
        return $wpdb->prepare("FIND_IN_SET(%s, tags) > 0", $tag); // NEWEDIT: Correct way to include tags in SQL query
    }, $tag_filter);
    $query .= " AND (" . implode(' OR ', $tags_conditions) . ")";
}

// Validate each tag against a list of known tags to ensure data integrity
$valid_tags = get_all_tags(); // Assume get_all_tags() safely fetches all valid tags from the database
$tag_filter = array_filter($tag_filter, function($tag) use ($valid_tags) {
    return in_array($tag, $valid_tags);
});

// Construct base query with proper preparation for variable inputs
$query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE weekly_tally = 1 ORDER BY time DESC LIMIT %d OFFSET %d", $items_per_page, $offset);

// Fetch results
$historicResults = $wpdb->get_results($query);

// After fetching results
if (empty($historicResults)) {
    // Provide user feedback if no results are found
    echo '<p>No historic data found for the selected filters.</p>';
} else {
    // Existing code to display the table goes here
}

// For total records query, adjust for tag filters without LIMIT and OFFSET
$total_query = "SELECT COUNT(*) FROM {$table_name} WHERE weekly_tally = 1";
if (!empty($tag_filter)) {
    $total_query .= " AND (" . implode(' OR ', $tags_conditions) . ")";
}
$total_records = $wpdb->get_var($total_query);
$total_pages = ceil($total_records / $items_per_page);

// Switch to HTML to display results in a table
?>

<div id="Historic">
    <h3>Historic Image Click Data</h3>
    <div class="wrap">
        <!-- Form for filtering by tags -->
        <form method="post" action="">
            <label for="filter-by-tag">Filter by Tag:</label>
            <!-- Tag checkboxes logic -->
            <button type="submit">Filter</button>
        </form>
        <hr>
        <?php if (!empty($historicResults)): ?>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Time</th>
                        <th>Image Source</th>
                        <th>Alt Text</th>
                        <th>Occurrence Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historicResults as $row): ?>
                        <tr>
                            <td><?php echo esc_html($row->id); ?></td>
                            <td><?php echo esc_html($row->time); ?></td>
                            <td><?php echo esc_html($row->image_url); ?></td>
                            <td><?php echo esc_html($row->alt_text); ?></td>
                            <td><?php echo esc_html($row->occurrence_count); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No historic data found for the selected filters.</p>
        <?php endif; ?>

        <!-- Pagination logic -->
    </div>
</div>
    <div class="pagination">

            <?php 
            // Generate pagination links
            for ($i = 1; $i <= $total_pages; $i++) {
                $link = admin_url('admin.php?page=image-click-tracker-display&pagenum=' . $i);
                $class = ($i == $page_number) ? ' class="active"' : '';
                echo "<a href=\"{$link}\"{$class}>{$i}</a> ";
    }
    ?>

    </div>
    
<?php

// ERROR TRACKING 
image_click_tracker_debug_log('Image Click Tracker: END HISTORIC SECTION in click_display.php.');

// ERROR TRACKING 
image_click_tracker_debug_log('Image Click Tracker: END PHP file click_display.php.');