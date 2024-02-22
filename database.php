<?php

// Create database table on plugin activation
register_activation_hook(__FILE__, 'create_tracking_tables');

function create_tracking_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Define SQL query to create the database tables
    $sql_image_clicks = "
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}image_clicks (
            id SERIAL PRIMARY KEY,
            time TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
            image_url VARCHAR(255) NOT NULL,
            alt_text VARCHAR(255),
            tags VARCHAR(255),
            file_size BIGINT DEFAULT 0
        ) {$charset_collate};";

    // Execute the SQL query to create the table
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_image_clicks);
}

?>