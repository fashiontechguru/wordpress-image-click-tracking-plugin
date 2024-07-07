<?php
// Define a constant to control debugging
define('IMAGE_CLICK_TRACKER_DEBUG', true); 

// Modification for conditional check with WP_DEBUG when plugin is used for production later
// define('IMAGE_CLICK_TRACKER_DEBUG', defined('WP_DEBUG') && WP_DEBUG);

// Registers all debug related actions and hooks
function image_click_tracker_register_debug_hooks() {
    if (!IMAGE_CLICK_TRACKER_DEBUG) {
        return;
    }

    // Enable full blast error reporting when debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    ob_start(); // Start output buffering early in the process

    // Add actions for logging and debugging
    add_action('shutdown', 'image_click_tracker_log_output');
    add_action('wp_loaded', function() {
        image_click_tracker_debug_log("WordPress is fully loaded.");
    });
}

// Simplified debug log function, with stack trace for context
function image_click_tracker_debug_log($message) {
    if ( ! defined('WP_DEBUG_LOG') || ! WP_DEBUG_LOG ) {
        return;
    }
    error_log($message);
    // Optionally log stack trace for deeper context
    if (IMAGE_CLICK_TRACKER_DEBUG && apply_filters('image_click_tracker_debug_log_stack_trace', false)) {
        $exception = new Exception;
        error_log($exception->getTraceAsString());
    }
}

// Function for logging buffered output
function image_click_tracker_log_output() {
    $output = ob_get_clean(); // Capture and clean the buffer
    image_click_tracker_debug_log("Captured output: " . $output);
}



// Error handler that logs PHP errors
function image_click_tracker_error_handler($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    image_click_tracker_debug_log("Error: {$message} in {$file} on line {$line}");
}

// Exception handler that logs uncaught exceptions
function image_click_tracker_exception_handler($exception) {
    image_click_tracker_debug_log("Uncaught exception: " . $exception->getMessage());
}

// Set custom error and exception handlers
set_error_handler('image_click_tracker_error_handler');
set_exception_handler('image_click_tracker_exception_handler');

// Initialize debug functionalities
image_click_tracker_register_debug_hooks();