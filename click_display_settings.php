<?php

// ERROR TRACKING: Log the start of execution for troubleshooting.
image_click_tracker_debug_log('Image Click Tracker: START PHP file click_display_settings.php.');

// Enqueue admin-specific styles and scripts.
if (!function_exists('image_click_tracker_admin_enqueue_assets')) {
    add_action('admin_enqueue_scripts', 'image_click_tracker_admin_enqueue_assets');
    function image_click_tracker_admin_enqueue_assets($hook) {
        global $wp_styles; // Use global styles variable to check if styles are enqueued successfully
        if ($hook !== 'toplevel_page_image-click-tracker-settings') {
            return;
        }

        $style_path = plugin_dir_path(__FILE__) . 'click_styles.css';
        $script_path = plugin_dir_path(__FILE__) . 'click_javascript_admin.js';
        wp_enqueue_style('image-click-tracker-admin-style', plugin_dir_url(__FILE__) . 'click_styles.css', [], filemtime($style_path));
        wp_enqueue_script('image-click-tracker-admin-script', plugin_dir_url(__FILE__) . 'click_javascript_admin.js', ['jquery'], filemtime($script_path), true);

        // Check if the styles and scripts were enqueued successfully
        if(wp_style_is('image-click-tracker-admin-style', 'enqueued')) {
            image_click_tracker_debug_log('Image Click Tracker: SUCCESS - Admin styles enqueued.');
        } else {
            image_click_tracker_debug_log('Image Click Tracker: ERROR - Admin styles not enqueued.');
        }

        if(wp_script_is('image-click-tracker-admin-script', 'enqueued')) {
            image_click_tracker_debug_log('Image Click Tracker: SUCCESS - Admin script enqueued.');
        } else {
            image_click_tracker_debug_log('Image Click Tracker: ERROR - Admin script not enqueued.');
        }
    }
}

// ERROR TRACKING - GRANULAR
image_click_tracker_debug_log('Image Click Tracker: CHECKPOINT 1 of 3 click_display_settings.php - Executed function image_click_tracker_admin_enqueue_assets.');

// Register settings to ensure they are recognized and handled by WordPress correctly.
// This should only be done once, preferably on 'admin_init'.
add_action('admin_init', 'image_click_tracker_register_settings');
function image_click_tracker_register_settings() {

    // REMOVE_CODE_START: Moving the default option values setting to the activation hook
    /*
    // Set default values for options if they're not set yet
    if (get_option('click_tracking_enabled') === false) {
        update_option('click_tracking_enabled', '1'); // Enable by default
    }
    if (get_option('load_tracking_enabled') === false) {
        update_option('load_tracking_enabled', '1'); // Enable by default
    }
    */
    // REMOVE_CODE_END

    // NEW_CODE_START: Directly registering settings without checking for their previous state.
    register_setting('image_click_tracker_options_group', 'click_tracking_enabled');
    register_setting('image_click_tracker_options_group', 'load_tracking_enabled');
    register_setting('image_click_tracker_options_group', 'drop_table_on_uninstall');
    // NEW_CODE_END

    // Add a new section to a settings page
    add_settings_section(
        'image_click_tracker_general_section',
        'General Settings',
        'image_click_tracker_general_section_callback',
        'image_click_tracker'
    );

    // Add new fields to the section of the settings page
    add_settings_field(
        'image_click_tracker_click_tracking',
        'Enable Click Tracking',
        'image_click_tracker_click_tracking_callback',
        'image_click_tracker',
        'image_click_tracker_general_section'
    );
    add_settings_field(
        'image_click_tracker_load_tracking',
        'Enable Load Tracking',
        'image_click_tracker_load_tracking_callback',
        'image_click_tracker',
        'image_click_tracker_general_section'
    );
    add_settings_field(
        'image_click_tracker_drop_table_on_uninstall',
        'Drop Table on Uninstall',
        'image_click_tracker_drop_table_on_uninstall_callback',
        'image_click_tracker',
        'image_click_tracker_general_section'
    );

    // ERROR TRACKING - GRANULAR
    image_click_tracker_debug_log('Image Click Tracker: CHECKPOINT 2 of 3 click_display_settings.php - Registered settings, sections, and fields.');
}

// The callback function for displaying the plugin's settings page
// This function is called when the menu page is clicked.
function image_click_tracker_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('image_click_tracker_options_group');
            do_settings_sections('image_click_tracker');
            submit_button('Save Changes');
            ?>
        </form>
    </div>
    <?php
}

// ERROR TRACKING - GRANULAR
image_click_tracker_debug_log('Image Click Tracker: CHECKPOINT 3 of 3 click_display_settings.php - Settings page function defined.');

// Callbacks for section and fields:
function image_click_tracker_general_section_callback() {
    echo '<p>General settings for the Image Click Tracker plugin.</p>';
}

function image_click_tracker_click_tracking_callback() {
    $option = get_option('click_tracking_enabled');
    echo '<input type="checkbox" name="click_tracking_enabled" value="1" '. checked(1, $option, false) .' />';
}

function image_click_tracker_load_tracking_callback() {
    $option = get_option('load_tracking_enabled');
    echo '<input type="checkbox" name="load_tracking_enabled" value="1" '. checked(1, $option, false) .' />';
}

function image_click_tracker_drop_table_on_uninstall_callback() {
    $option = get_option('drop_table_on_uninstall');
    echo '<input type="checkbox" name="drop_table_on_uninstall" value="1" '. checked(1, $option, false) .' />';
}

// ERROR TRACKING: Log the end of file execution.
image_click_tracker_debug_log('Image Click Tracker: END PHP file click_display_settings.php.');