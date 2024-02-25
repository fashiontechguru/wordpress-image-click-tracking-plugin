<?php

add_action('wp_dashboard_setup', 'click_track_dashboard_widgets');

function click_track_dashboard_widgets() {
    wp_add_dashboard_widget('click_track_widget', 'Image Interaction Analytics HUD', 'click_track_dashboard_image_interactions');
}

function click_track_dashboard_image_interactions() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'image_clicks';

    // Display interaction counts by type
    $results = $wpdb->get_results("
        SELECT interaction_type, COUNT(*) as count
        FROM $table_name
        GROUP BY interaction_type
    ");

    if ($results) {
        echo "<ul>";
        foreach ($results as $result) {
            // Corrected for safe output
            echo "<li>" . esc_html(ucfirst($result->interaction_type)) . "s: " . esc_html($result->count) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No interactions recorded yet.</p>";
    }

    // Query to get monthly clicks to loads ratio
    $monthly_ratios = $wpdb->get_results("
        SELECT
            DATE_FORMAT(time, '%Y-%m') as month,
            SUM(CASE WHEN interaction_type = 'click' THEN 1 ELSE 0 END) as clicks,
            SUM(CASE WHEN interaction_type = 'load' THEN 1 ELSE 0 END) as loads
        FROM $table_name
        GROUP BY month
        ORDER BY month DESC
    ");

    echo "<h4>Monthly Clicks to Loads Ratio</h4>";
    if ($monthly_ratios) {
        echo "<ul>";
        foreach ($monthly_ratios as $ratio) {
            if ($ratio->loads > 0) { // Avoid division by zero
                $clicks_to_loads_ratio = number_format($ratio->clicks / $ratio->loads, 3);
                $formatted_month = DateTime::createFromFormat('Y-m', $ratio->month)->format('F Y'); // Format 'YYYY-MM' to 'Month YYYY'
                // Corrected for safe output
                echo "<li>" . esc_html($formatted_month) . ": (" . esc_html($ratio->clicks) . " clicks) / (" . esc_html($ratio->loads) . " loads) = " . esc_html($clicks_to_loads_ratio) . " ratio</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p>No monthly data available.</p>";
    }
}
?>