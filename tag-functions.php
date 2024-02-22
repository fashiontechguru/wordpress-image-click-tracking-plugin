<?php

// Retrieve tags associated with images and posts

// Function to retrieve tags associated with an image
function get_tags_for_image($image_url) {
    // Your implementation to retrieve tags for an image
}

// Function to retrieve tags associated with a post
function get_tags_for_post($post_id) {
    $postTags = array();
    // Retrieve tags associated with the post
    $post_tags = get_the_tags($post_id);
    // Check if tags exist
    if ($post_tags) {
        // Extract tag names
        foreach ($post_tags as $tag) {
            $postTags[] = $tag->name;
        }
    }
    return $postTags;
}