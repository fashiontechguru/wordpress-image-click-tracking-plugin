add_action('wp_loaded', 'track_image_clicks');
add_action('wp_loaded', 'track_image_loads');
add_action('wp_mouseover', 'track_image_hover');

function track_image_clicks() {
    ?>
    <script>
        jQuery(document).ready(function($) {
            $(document).on('click', 'img', function() {
                var imageData = {
                    action: 'track_image_click',
                    nonce: <?php echo wp_create_nonce('track_image_click_nonce'); ?>,
                    image_url: $(this).attr('src')
                };

                $.ajax({
                    url: <?php echo admin_url('admin-ajax.php'); ?>,
                    type: 'POST',
                    data: imageData,
                    success: function(response) {
                        console.log('Image click tracked successfully.');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error tracking image click: ' + error);
                    }
                });
            });
        });
    </script>
    <?php
}

function track_image_loads() {
    ?>
    <script>
        jQuery(window).on('load', function() {
            $('img').each(function() {
                var imageData = {
                    action: 'track_image_load',
                    nonce: <?php echo wp_create_nonce('track_image_load_nonce'); ?>,
                    image_url: $(this).attr('src')
                };

                $.ajax({
                    url: <?php echo admin_url('admin-ajax.php'); ?>,
                    type: 'POST',
                    data: imageData,
                    success: function(response) {
                        console.log('Image load tracked successfully.');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error tracking image load: ' + error);
                    }
                });
            });
        });
    </script>
    <?php
}

function track_image_hover() {
    ?>
    <script>
        jQuery(document).on('mouseover', 'img', function() {
            var imageData = {
                action: 'track_image_hover',
                nonce: <?php echo wp_create_nonce('track_image_hover_nonce'); ?>,
                image_url: $(this).attr('src')
            };

            $.ajax({
                url: <?php echo admin_url('admin-ajax.php'); ?>,
                type: 'POST',
                data: imageData,
                success: function(response) {
                    console.log('Image hover tracked successfully.');
                },
                error: function(xhr, status, error) {
                    console.error('Error tracking image hover: ' + error);
                }
            });
        });
    </script>
    <?php
}