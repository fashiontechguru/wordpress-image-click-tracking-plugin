<?php
/*
Plugin Name: Image Click Tracker
Description: Track image clicks and save to WordPress database.
Version: 1.0
Author: FashionTechGuru
MIT License
*/

// Enqueue jQuery
function enqueue_jquery() {
    wp_enqueue_script('jquery');
}
add_action('wp_enqueue_scripts', 'enqueue_jquery');

// Create database table on plugin activation
function create_tracking_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'image_clicks';
    $charset_collate = $wpdb->get_charset_collate();

    // Define SQL query to create the database table
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        image_url varchar(255) NOT NULL,
        alt_text varchar(255),
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // Include necessary WordPress file for database operations
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    // Execute the SQL query to create the table
    dbDelta($sql);
}
// Register the create_tracking_table function to run on plugin activation
register_activation_hook(__FILE__, 'create_tracking_table');

// Track image clicks and save to database
function track_image_click() {
    // Check for nonce to prevent CSRF attacks
    check_ajax_referer('image_click_nonce', 'nonce');

    global $wpdb;
    $table_name = $wpdb->prefix . 'image_clicks';

    // Sanitize data before insertion into the database
    $image_url = esc_url($_POST['imageSrc']);
    $alt_text = sanitize_text_field($_POST['altText']);

    // Insert sanitized data into the database table
    $wpdb->insert(
        $table_name,
        array(
            'time'      => current_time('mysql'),
            'image_url' => $image_url,
            'alt_text'  => $alt_text,
        )
    );

    // End AJAX request
    wp_die();
}
// Register the track_image_click function to handle AJAX requests for tracking image clicks
add_action('wp_ajax_track_image_click', 'track_image_click');
add_action('wp_ajax_nopriv_track_image_click', 'track_image_click');

// Add save button for images larger than 400x400 pixels
function add_save_button_script() {
    // Retrieve button size, color, and font options from plugin settings
    $button_size = get_option('save_button_size', '14px');
    $button_color = get_option('save_button_color', '#0073aa');
    $button_font = get_option('save_button_font', 'inherit');

    ?>
    <style>
        /* Define CSS styles for the Save button */
        .save-button {
            font-size: <?php echo esc_attr($button_size); ?>;
            color: <?php echo esc_attr($button_color); ?>;
            font-family: '<?php echo esc_attr($button_font); ?>', sans-serif;
            /* Additional styles can be added here */
        }
    </style>

    <script>
        jQuery(document).ready(function($) {
            $('img').each(function() {
                var img = $(this);

                // Check if image dimensions are larger than 400x400 pixels
                if (img.width() > 400 && img.height() > 400) {
                    // Create a Save button for each qualifying image
                    var saveButton = $('<button>', {
                        text: 'Save',
                        class: 'save-button',
                        click: function() {
                            // Retrieve image source and alt text
                            var imageSrc = img.attr('src');
                            var altText = img.attr('alt');

                            // Send AJAX request to track image click
                            $.ajax({
                                type: 'POST',
                                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                                data: {
                                    action: 'track_image_click',
                                    imageSrc: imageSrc,
                                    altText: altText,
                                    nonce: '<?php echo wp_create_nonce('image_click_nonce'); ?>'
                                }
                            });
                        }
                    });

                    // Insert the Save button after the qualifying image
                    img.after(saveButton);
                }
            });
        });
    </script>
    <?php
}
// Register the add_save_button_script function to output the Save button script in the footer
add_action('wp_footer', 'add_save_button_script');

// Add settings to the WordPress dashboard
function image_clicks_settings() {
    add_options_page(
        'Image Clicks Settings',
        'Image Clicks Settings',
        'manage_options',
        'image_clicks_settings',
        'display_image_clicks_settings'
    );
}
// Register the image_clicks_settings function to add settings page to the dashboard
add_action('admin_menu', 'image_clicks_settings');

function display_image_clicks_settings() {
    ?>
    <div class="wrap">
        <h2>Image Clicks Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('image_clicks_settings_group'); ?>
            <?php do_settings_sections('image_clicks_settings_group'); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function image_clicks_settings_init() {
    // Register plugin settings
    register_setting('image_clicks_settings_group', 'save_button_size');
    register_setting('image_clicks_settings_group', 'save_button_color');
    register_setting('image_clicks_settings_group', 'save_button_font');
}
// Register the image_clicks_settings_init function to initialize plugin settings
add_action('admin_init', 'image_clicks_settings_init');
