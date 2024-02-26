<?php
// Include necessary files
include_once(plugin_dir_path(__FILE__) . 'database.php');
require_once(plugin_dir_path(__FILE__) . 'functions.php');

// Display tracked image click data with export option and filtering controls
function display_image_clicks() {
    global $wpdb;

    // Check if the form is submitted for CSV export
    if (isset($_POST['export-csv']) && $_POST['export-csv'] === 'true') {
        handle_csv_export();
        return; // Exit to prevent displaying HTML content
    }

    // Check if the user has permission to view the image click data
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access.');
    }

    // Define $limit and $offset
    $limit = 200; // Limit displayed rows to 200
    $offset = isset($_GET['offset']) ? absint($_GET['offset']) : 0;

    // Retrieve stored image click and load data from the database to make it available for display to the WordPress administrator on the plugin's Dashboard page
    $date_range = isset($_POST['date-range']) ? sanitize_text_field($_POST['date-range']) : '30 days';
    $tags = isset($_POST['tags']) ? $_POST['tags'] : array();

    // Check if the function retrieve_image_click_data exists before calling it
    if (!function_exists('retrieve_image_click_data')) {
        // Debugging: Log a message to error log to check if this point is reached
        error_log('retrieve_image_click_data function not found.');
        
        // Display an error message or handle the situation appropriately
        echo 'Error: retrieve_image_click_data function not found.';
        return;
    }

    // Retrieve image click data
    $results = retrieve_image_click_data($limit, $offset, $date_range, $tags);

?>
        <!-- Style for display_image_clicks function below -->
        <style>
            .tab button { /* Styles for tab buttons */ }
            .tab button:hover { /* Hover state styles */ }
            .tab button.active { /* Active tab button styles */ }
            .tabcontent { /* Styles for tab content */ }
        </style>

<?php
// Data Display Page Multiple Interface Tabs
    function display_image_clicks() {
?>

        <h2>Image Click and Load WordPress Plugin</h2>

        <!-- Tab Links -->
        <div class="tab">
          <!-- Each button corresponds to a tab. onClick, it triggers the openPage function to display the associated tab content. -->
          <button class="tablinks" onclick="openPage(event, 'Recent')" id="defaultOpen">Recent</button>
          <button class="tablinks" onclick="openPage(event, 'Settings')">Settings</button>
          <!-- Historic tab button -->
          <button class="tablinks" onclick="openPage(event, 'Historic')">Historic</button>
        </div>

        <div id="DataViewer" class="tabcontent">
          <h3>Image Click and Load Data</h3>
            // Output the data in a table with filtering controls
            ?>
            <div class="wrap">
                <h1>Image Clicks - Recent Raw Data</h1>
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

                    <!-- Add hidden input field to store the current page's data -->
                    <input type="hidden" name="export-data" value="<?php echo htmlspecialchars(json_encode($results)); ?>">

                    <button type="submit">Filter</button>
                    
                    <!-- Add Refresh Button -->
                    <button type="button" id="click-load-data-refresh-button">Refresh</button>
                </form>
                <hr> <!-- Spacer line -->
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Time</th>
                            <th>Image Source</th>
                            <th>Alt Text</th>
                            <th>File Size (bytes)</th> <!-- Add column header for file size -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row) : ?>
                            <tr>
                                <td><?php echo esc_html($row->id); ?></td>
                                <td><?php echo esc_html($row->time); ?></td>
                                <td><?php echo esc_html($row->image_url); ?></td>
                                <td><?php echo esc_html($row->alt_text); ?></td>
                                <td><?php echo esc_html($row->file_size); ?></td> 
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <form method="post" action="">
                    <input type="hidden" name="export-csv" value="true">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('image_click_nonce'); ?>">
                    <input type="hidden" name="date-range" value="<?php echo esc_attr($date_range); ?>">
                    <?php 
                        // Check if tags are available
                        if (!empty($tags)) {
                            foreach ($tags as $tag) {
                                // Output hidden input fields for each selected tag
                                echo '<input type="hidden" name="tags[]" value="' . esc_attr($tag) . '">';
                            }
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
        </div>

        <div id="Settings" class="tabcontent">
          <h3>Tracker Plugin Settings</h3>
            <div id="Settings" class="tabcontent">
                <h3>Tracker Plugin Settings</h3>
                <form method="post" action="options.php">
                    <?php
                        // Output security fields for the registered setting "wp_image_click_tracker"
                        settings_fields('wp_image_click_tracker_group');
                        // Output setting sections and their fields
                        do_settings_sections('wp_image_click_tracker');
                        // Output save settings button
                        submit_button('Save Changes');
                    ?>
                </form>
            </div>
        </div>

        <div id="Historic" class="tabcontent">
          <h3>Historic Image Click Data</h3>
          <?php

          // Query to fetch historical data where weekly_tally = 1
          // Display the data accordingly

          ?>

            // Output the data in a table with filtering controls
            ?>
            <div class="wrap">
                <h1>Image Clicks - Compiled Results Over Time</h1>
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

                    <!-- Add hidden input field to store the current page's data -->
                    <input type="hidden" name="export-data" value="<?php echo htmlspecialchars(json_encode($results)); ?>">

                    <button type="submit">Filter</button>
                    
                    <!-- Add Refresh Button -->
                    <button type="button" id="click-load-data-refresh-button">Refresh</button>
                </form>
                <hr> <!-- Spacer line -->
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Time</th>
                            <th>Image Source</th>
                            <th>Alt Text</th>
                            <th>File Size (bytes)</th> <!-- Add column header for file size -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row) : ?>
                            <tr>
                                <td><?php echo esc_html($row->id); ?></td>
                                <td><?php echo esc_html($row->time); ?></td>
                                <td><?php echo esc_html($row->image_url); ?></td>
                                <td><?php echo esc_html($row->alt_text); ?></td>
                                <td><?php echo esc_html($row->file_size); ?></td> 
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <form method="post" action="">
                    <input type="hidden" name="export-csv" value="true">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('image_click_nonce'); ?>">
                    <input type="hidden" name="date-range" value="<?php echo esc_attr($date_range); ?>">
                    <?php 
                        // Check if tags are available
                        if (!empty($tags)) {
                            foreach ($tags as $tag) {
                                // Output hidden input fields for each selected tag
                                echo '<input type="hidden" name="tags[]" value="' . esc_attr($tag) . '">';
                            }
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
        </div>

        <script>
        function openPage(evt, pageName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tabcontent");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tablinks = document.getElementsByClassName("tablinks");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(pageName).style.display = "block";
            evt.currentTarget.className += " active";
        }
        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById("defaultOpen").click();
        });
        </script>

        <?php
    }
}
?> 
    <script type="text/javascript">
        // JavaScript to handle refreshing functionality
        document.getElementById('click-load-data-refresh-button').addEventListener('click', function() {
            // Reload the page to refresh data
            location.reload();
        });
    </script>