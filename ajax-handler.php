<?php
add_action('wp_ajax_track_image_click', 'track_image_click');

function track_image_click() {
    // Verify nonce to prevent CSRF attacks
    if (!wp_verify_nonce($_POST['nonce'], 'image_click_nonce')) {
        error_log('Unauthorized access: Nonce verification failed.');
        wp_send_json_error('Unauthorized access.', 403);
        return; // Exit the function
    }

    // Retrieve image URL from the AJAX request
    $imageUrl = $_POST['image_url'];

    // Insert tracking data into the database
    $inserted = wp_insert_event(
        'image_clicks', // event type
        array(
            'time' => current_time('mysql'),
            'image_url' => $imageUrl
        )
    );

    if (!$inserted) {
        error_log('Error inserting image click tracking data into the database.');
        wp_send_json_error('Error inserting tracking data into the database.', 500);
    } else {
        wp_send_json_success('Image click tracked successfully.');
    }
}