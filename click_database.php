<?php

// ERROR TRACKING 
image_click_tracker_debug_log('Image Click Tracker: START PHP file click_database.php.');

/**
 * Function to create the tracking tables needed for the plugin.
 */
function create_tracking_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

// ERROR TRACKING 
image_click_tracker_debug_log('Image Click Tracker: MID-CODE "A" CHECKPOINT IN PHP file click_database.php.');

    // SQL to create the image_clicks table with all the specified columns
    $sql_image_clicks = "CREATE TABLE {$wpdb->prefix}image_clicks (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        interaction_type varchar(48) NOT NULL,
        image_url varchar(2048) NOT NULL,
        alt_text varchar(255) DEFAULT NULL,
        tags varchar(255) DEFAULT NULL,
        file_size bigint(20) UNSIGNED DEFAULT 0,
        user_ip varchar(100) NOT NULL,
        weekly_tally bit DEFAULT 0 NOT NULL, 
        occurrence_count bigint(20) UNSIGNED DEFAULT 0 NOT NULL,
        PRIMARY KEY (id),
        INDEX image_url_idx (image_url),
        INDEX interaction_type_idx (interaction_type)
    ) $charset_collate;";

// ERROR TRACKING 
image_click_tracker_debug_log('Image Click Tracker: MID-CODE "B" CHECKPOINT IN PHP file click_database.php.');

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $result = dbDelta($sql_image_clicks);

// Enhanced error logging
global $EZSQL_ERROR;
if (!empty($EZSQL_ERROR)) {
    foreach ($EZSQL_ERROR as $e) {
        error_log("SQL Error: " . $e['error']);
    }
}

// ERROR TRACKING 
image_click_tracker_debug_log('Image Click Tracker: MID-CODE "C" CHECKPOINT PHP file click_database.php.');

if (empty($result)) {
    image_click_tracker_debug_log('Failed to create or update the image_clicks table');
} else {
    image_click_tracker_debug_log('Image Click Tracker: image_clicks table created or updated successfully.');
}

// ERROR TRACKING 
image_click_tracker_debug_log('Image Click Tracker: END PHP file click_database.php.');

}