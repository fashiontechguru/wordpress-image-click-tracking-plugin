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
            interaction_type VARCHAR(10) NOT NULL,
            image_url VARCHAR(255) NOT NULL,
            alt_text VARCHAR(255),
            tags VARCHAR(255),
            file_size BIGINT DEFAULT 0
            user_ip VARCHAR(100) NOT NULL
        ) {$charset_collate};";

    // Execute the SQL query to create the table
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $sql_image_clicks = "CREATE TABLE {$wpdb->prefix}image_clicks (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        interaction_type varchar(100) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_image_clicks);
}

?>