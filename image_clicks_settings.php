<?php

function image_click_tracker_register_settings() {
    // Register settings for click tracking
    register_setting('image_click_tracker_options_group', 'click_tracking_enabled');
    // Register settings for load tracking
    register_setting('image_click_tracker_options_group', 'load_tracking_enabled');
}

// HTML for the options page
function image_clicks_settings_html() {
    ?>
    <div class="wrap">
        <h1>Image Click and Load Tracking Settings</h1>
			<form method="post" action="options.php">
			    <?php settings_fields('image_click_tracker_options_group'); ?>
			    <?php do_settings_sections('image_click_tracker_options_group'); ?>
			    <table class="form-table">
			        <tr valign="top">
			        <th scope="row">Enable Click Tracking</th>
			        <td><input type="checkbox" name="click_tracking_enabled" value="1" <?php checked(1, get_option('click_tracking_enabled'), true); ?> /></td>
			        </tr>
			         
			        <tr valign="top">
			        <th scope="row">Enable Load Tracking</th>
			        <td><input type="checkbox" name="load_tracking_enabled" value="1" <?php checked(1, get_option('load_tracking_enabled'), true); ?> /></td>
			        </tr>
			    </table>
			    
			    <?php submit_button(); ?>
			</form>
    </div>
    <?php
}