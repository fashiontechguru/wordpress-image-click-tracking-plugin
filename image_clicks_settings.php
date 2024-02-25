<?php

// HTML for the options page
function image_clicks_settings_html() {
    ?>
    <div class="wrap">
        <h1>Image Tracking Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('click_track_options_group'); ?>
            <label for="click_tracking">Enable Click Tracking:</label>
            <input type="checkbox" id="click_tracking" name="click_track_click_tracking_enabled" value="1" <?php checked('1', get_option('click_track_click_tracking_enabled')); ?>><br>
            <label for="load_tracking">Enable Load Tracking:</label>
            <input type="checkbox" id="load_tracking" name="click_track_load_tracking_enabled" value="1" <?php checked('1', get_option('click_track_load_tracking_enabled')); ?>>
            <?php  submit_button(); ?>
        </form>
    </div>
    <?php
}