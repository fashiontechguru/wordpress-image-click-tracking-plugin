// Admin-Side Specific Script
document.addEventListener('DOMContentLoaded', function() {
    // Debug log function - More relevant for admin for debugging purposes
    function debugLog(message) {
        if (window.imageClickTrackerDebug) {
            console.log(message);
        }
    }

    // Initialization message - Useful for confirming the script is loaded in the admin
    debugLog("Admin Document ready. Initializing admin-specific script.");

    // Error tracking - Essential for debugging in admin context
    debugLog("image-click-tracker.js admin initialization complete.");

    // Nonce check - Typically used in admin for verifying requests
    const nonce = imageTracker.nonce; // Assume imageTracker.nonce is set somewhere in admin

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

    // Expose openPageSafe if necessary - Likely used for admin page tab management
    function openPageSafe(evt, pageName) {
        $(".tabcontent").hide(); // Hide all tab content sections.
        $(".tablinks").removeClass("active"); // Remove 'active' class from all tabs to reset their state.
        $("#" + pageName).show(); // Show the content section of the clicked tab.
        evt.currentTarget.className += " active"; // Add 'active' class to the clicked tab for styling.
    }

    // Admin page tab and button functionality
    $('#click-load-data-refresh-button').on('click', function() {
        location.reload(); // Often used in admin pages for refresh purposes
    });

    window.openPageSafe = openPageSafe;

    // Improved initialization of tabs
    function initializeTabs() {
        // Show the default tab's content and set it as active
        $("#Recent").show(); // Assuming "Recent" tab content has an ID of "Recent"
        $("#defaultOpen").addClass("active"); // Mark the "Recent" tab button as active
        
        // Attach event listeners to tab buttons for dynamic content switching
        $(".tablinks").on('click', function(event) {
            // Extract the name of the tab from a data attribute or the button's id
            var pageName = $(this).data("page") || this.id; // Adjust based on your HTML structure
            openPageSafe(event, pageName);
        });
    }

    // Call initializeTabs to set up the tabs when the document is ready
    initializeTabs();

    // Admin-specific error tracking and final log message
    debugLog("click_javascript_admin.js admin script END OF FILE");
    console.log("click_javascript_admin.js admin script fully loaded and initialized.");

    // Debug log for completion
    console.log("Tab functionality initialized.");
});