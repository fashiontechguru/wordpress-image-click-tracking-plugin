<?php
// ERROR TRACKING 
image_click_tracker_debug_log('Image Click Tracker: START PHP file click_csv-export.php.');

// Handle CSV export
function handle_csv_export() {

    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'image_click_nonce')) {
        error_log('Unauthorized access: Nonce verification failed.');
        wp_send_json_error('Unauthorized access.', 403);
        return;
    }

    // Check if export data is provided
    if (isset($_POST['export-data'])) {

        // Decode JSON data
        $results = json_decode($_POST['export-data'], true);

        // Set CSV headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="image_clicks.csv"');

        // Open output stream
        $output = fopen('php://output', 'w');

        // Write CSV header
        fputcsv($output, array_keys($results[0]));

        // Write CSV data
        foreach ($results as $row) {
            fputcsv($output, $row);
        }

        // Close output stream
        fclose($output);

        // Prevent WordPress from rendering anything else
        exit;
    } else {
        // If export data is not provided, inform the user
        wp_die('No data to export.');
    }
}

// ERROR TRACKING 
image_click_tracker_debug_log('Image Click Tracker: END PHP file click_csv-export.php.');