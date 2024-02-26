<?php

function create_tracking_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $sql_image_clicks = "CREATE TABLE {$wpdb->prefix}image_clicks (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        interaction_type varchar(10) NOT NULL,
        image_url varchar(255) NOT NULL,
        alt_text varchar(255) DEFAULT NULL,
        tags varchar(255) DEFAULT NULL,
        file_size bigint(20) UNSIGNED DEFAULT 0,
        user_ip varchar(100) NOT NULL,
        weekly_tally bit DEFAULT 0 NOT NULL, 
        occurrence_count bigint(20) UNSIGNED DEFAULT 0 NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_image_clicks);
    }

?>