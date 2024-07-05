// Front-End Specific Script
document.addEventListener('DOMContentLoaded', function() {

    // Debounce function - Commonly used in front-end for optimizing event listeners like scroll or resize
    const debounce = (func, wait) => {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    };

    // Event data management for click and load tracking
    let eventData = [];
    const batchSendDelay = 5000;
    const maxEventsBeforeSend = 10;

    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'track_image_click',
            nonce: imageClickTrackerData.nonce, // Assuming nonce is passed to JS via wp_localize_script()
            // other data...
        },
        success: function(response) {
            // Handle success
        },
        error: function(error) {
            // Handle error
        }
    });

    // Batch sending of event data - Primary front-end functionality for tracking interactions

    function sendEventDataBatch() {
        if (eventData.length > 0) {
            fetch(ajaxurl, {
                method: 'POST',
                credentials: 'same-origin', // Ensure credentials are included for cookie-based authentication
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'track_image_interaction_batch', // Correct action to match the registered WordPress AJAX handler
                    nonce: imageClickTrackerData.nonce, // Include nonce passed to JS via wp_localize_script()
                    events: JSON.stringify(eventData), // Ensure eventData is correctly serialized
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Batch event data sent:', eventData);
                eventData = []; // Reset eventData after successful send
            })
            .catch(error => {
                console.error('Error sending batch event data:', error);
            });
        }
    }

    // Call this function at intervals or in response to specific events as originally intended
    setInterval(sendEventDataBatch, batchSendDelay);

    // Adding event data on interactions
    function addEventData(event) {
        eventData.push(event);
        if (eventData.length >= maxEventsBeforeSend) {
            sendEventDataBatch();
        }
    }

    // Tracking clicks and loads on images
    if (imageTracker.click_tracking_enabled === '1') {
        document.addEventListener('click', function(event) {
            var target = event.target;
            if (target.tagName === 'IMG') {
                var imageSrc = target.getAttribute('src');
                addEventData({type: 'click', imageSrc: imageSrc, time: Date.now()});
            }
        });
    }

    if (imageTracker.load_tracking_enabled === '1') {
        document.querySelectorAll('img').forEach(function(img) {
            img.addEventListener('load', function() {
                var imageSrc = this.getAttribute('src');
                addEventData({type: 'load', imageSrc: imageSrc, time: Date.now()});
            });
        });
    }

    // Monitoring dynamically added images - Important for single-page applications or dynamically loaded content
    if (imageTracker.load_tracking_enabled === '1') {
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && node.tagName === 'IMG') {
                        node.addEventListener('load', function() {
                            var imageSrc = this.getAttribute('src');
                            addEventData({type: 'load', imageSrc: imageSrc, time: Date.now()});
                        });
                    }
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }

    // Final log message for front-end script
    console.log("click_javascript_clientside.js front-end script fully loaded and initialized.");
});