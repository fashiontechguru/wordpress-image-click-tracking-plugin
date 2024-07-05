<?php

// ERROR TRACKING 
image_click_tracker_debug_log('Image Click Tracker: START PHP file check_ajax_handler.php.');

// Function to track image clicks 
function track_image_click() {

    global $wpdb; // Ensure global $wpdb is accessible

    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'image_click_tracker_nonce')) {
        wp_die('Nonce not valid', 'Image Click Tracker Error', array('response' => 403));
    }

    // Data processing
    $imageUrl = esc_url_raw($_POST['image_url']);

    // Respond based on the success of the operation
    if ($inserted) {
        wp_send_json_success('Image click tracked successfully.');
    } else {
        wp_send_json_error('Error inserting tracking data.', 500);
    }
}

// Add action for tracking image clicks
add_action('wp_ajax_track_image_click', 'track_image_click');

// Function to handle image interaction batch
function handle_image_interaction_batch() {
    global $wpdb; // Access global $wpdb inside function

    // Verify nonce for security
    check_ajax_referer('image_click_tracker_nonce', 'nonce');

    $events = json_decode(stripslashes($_POST['events']), true);

    foreach ($events as $event) {
        // Ensure all necessary variables are defined and sanitized
        $type = sanitize_text_field($event['type']);
        $imageSrc = esc_url_raw($event['imageSrc']);
        // Insert each event into the database
        $wpdb->insert(
            $wpdb->prefix . 'image_clicks', 
            array(
                'interaction_type' => $type,
                'image_url' => $imageSrc,
                'time' => current_time('mysql'),
                'user_ip' => anonymize_ip($_SERVER['REMOTE_ADDR'])
            ),
            array('%s', '%s', '%s', '%s') // Adjust format specifiers based on actual data types
        );
    }

    wp_send_json_success('Batch processed successfully.');
}

// Add actions for handling image interaction batch
add_action('wp_ajax_track_image_interaction_batch', 'handle_image_interaction_batch');
add_action('wp_ajax_nopriv_track_image_interaction_batch', 'handle_image_interaction_batch');

// Function to anonymize IP address
function anonymize_ip($ip_address) {
    if (filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return preg_replace('/\.\d+$/', '.0', $ip_address);
    } elseif (filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return preg_replace('/:[\da-f]{1,4}$/i', ':0000', $ip_address);
    }
    return $ip_address; // Return original if neither IPv4 nor IPv6
}

// ERROR TRACKING 
image_click_tracker_debug_log('Image Click Tracker: END PHP file check_ajax_handler.php.');