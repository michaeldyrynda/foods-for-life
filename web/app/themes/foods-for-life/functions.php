<?php

function get_catering_posts() {
    $categories = get_categories(array( 'child_of' => 16, ));

    // For each category in the catering category, fetch its posts
    foreach ($categories as $category_key => $category) {
        $posts = get_posts(array( 'category' => $category->cat_ID, ));

        // For each post, fetch its custom fields
        foreach ($posts as $post_key => $post) {
            $posts[$post_key]->featured_image = wp_get_attachment_url(get_post_thumbnail_id($post->ID));
            $posts[$post_key]->custom_fields  = array();

            // Filter only the custom fields we need to render the page
            foreach (get_post_custom($post->ID) as $custom_key => $custom_value) {
                if (in_array($custom_key, array( 'price', 'price_portion', 'serving', 'minimum_order', ))) {
                    $post->custom_fields[$custom_key] = array_shift($custom_value);
                }
            }
        }

        $categories[$category_key]->posts = $posts;
    }

    return $categories;
}
