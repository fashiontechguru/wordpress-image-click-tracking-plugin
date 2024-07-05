jQuery(document).ready(function($) {
    $('img').each(function() {
        var imgElement = $(this);
        var imgSrc = $(this).attr('src');

        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                'action': 'get_image_file_size',
                'image_src': imgSrc,
            },
            success: function(response) {
                if (response && !response.error) {
                    imgElement.attr('notation_click_filesize', response.file_size);
                }
            }
        });
    });
});