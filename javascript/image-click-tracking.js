jQuery(document).ready(function($) {
    // Track image clicks
    $('img').on('click', function() {
        // Get image source and alt text
        var imageSrc = $(this).attr('src');
        var altText = $(this).attr('alt');
        
        // Get post ID
        var postID = $(this).closest('.post').attr('id'); // Assuming each post has a unique ID
        
        // Send data to server via AJAX
        $.ajax({
            url: ajaxurl, // WordPress AJAX URL
            type: 'POST',
            data: {
                action: 'track_image_click',
                nonce: my_ajax_obj.nonce,
                imageSrc: imageSrc,
                altText: altText,
                postID: postID // Include post ID in the data
            },
            success: function(response) {
                // Handle success
                console.log('Image click tracked successfully.');
            },
            error: function(xhr, status, error) {
                // Handle error
                console.error('Error tracking image click:', error);
            }
        });
    });
});
