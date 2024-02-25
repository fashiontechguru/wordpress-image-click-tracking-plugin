jQuery(document).ready(function($) {

    // Debounce function to limit the rate of function execution
    function debounce(func, wait) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }

    var eventData = []; // Array to store both click and load data for batching
    var batchSendDelay = 5000; // Delay in milliseconds to send data, e.g., every 5 seconds

    // Function to handle sending event data in batches
    function sendEventDataBatch() {
        if (eventData.length > 0) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'track_image_interaction_batch',
                    events: eventData,
                    nonce: imageTracker.nonce
                },
                success: function(response) {
                    console.log('Batch event data sent:', eventData);
                    eventData = []; // Clear the array after sending
                },
                error: function(error) {
                    console.error('Error sending batch event data:', error);
                }
            });
        }
    }

    // Modified event handler for click tracking
    if (imageTracker.click_tracking_enabled === '1') {
        $(document).on('click', 'img', function() {
            var imageSrc = $(this).attr('src');
            eventData.push({type: 'click', imageSrc: imageSrc, time: Date.now()});
            debounce(sendEventDataBatch, batchSendDelay)(); // Debounce the batch sending function
        });
    }

    // Modified event handler for load tracking
    if (imageTracker.load_tracking_enabled === '1') {
        $('img').each(function() {
            $(this).on('load', function() {
                var imageSrc = $(this).attr('src');
                eventData.push({type: 'load', imageSrc: imageSrc, time: Date.now()});
                debounce(sendEventDataBatch, batchSendDelay)(); // Debounce the batch sending function
            });
        });
    }

});