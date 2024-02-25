<?php

// Add action for tracking image clicks
add_action('wp_ajax_track_image_click', 'track_image_click');

// Function to track image clicks
function track_image_click() {
    // Verify nonce to prevent CSRF attacks
    if (!wp_verify_nonce($_POST['nonce'], 'image_click_nonce')) {
        error_log('Unauthorized access: Nonce verification failed.');
        wp_send_json_error('Unauthorized access.', 403);
        return;
    }

    // Retrieve image URL from the AJAX request
    $imageUrl = esc_url_raw($_POST['image_url']);

    // Insert tracking data into the database
    $inserted = wp_insert_event(
        'image_clicks',
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

// Add action for handling image interaction batch
add_action('wp_ajax_track_image_interaction_batch', 'handle_image_interaction_batch');
add_action('wp_ajax_nopriv_track_image_interaction_batch', 'handle_image_interaction_batch');

// Function to handle image interaction batch
function handle_image_interaction_batch() {
    global $wpdb;
    check_ajax_referer('image_click_tracker_nonce', 'nonce');

    // Retrieve events from the AJAX request
    $events = isset($_POST['events']) ? $_POST['events'] : array();

    // Loop through events and insert tracking data into the database
    foreach ($events as $event) {
        $type = sanitize_text_field($event['type']);
        $imageSrc = esc_url_raw($event['imageSrc']);
        $time = current_time('mysql');
        $userIp = anonymize_ip($_SERVER['REMOTE_ADDR']);

        $wpdb->insert(
            $wpdb->prefix . 'image_interactions',
            array(
                'interaction_type' => $type,
                'image_url' => $imageSrc,
                'time' => $time,
                'user_ip' => $userIp
            ),
            array('%s', '%s', '%s', '%s')
        );
    }

    wp_send_json_success('Batch processed successfully.');
}

// Function to anonymize IP address
function anonymize_ip($ip_address) {
    if (filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return preg_replace('/\.\d+$/', '.0', $ip_address);
    } elseif (filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return preg_replace('/:[\da-f]{1,4}$/i', ':0000', $ip_address);
    }
    return $ip_address; // Return original if neither IPv4 nor IPv6
}