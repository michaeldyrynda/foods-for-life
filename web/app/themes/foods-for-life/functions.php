<?php

remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40, 0);

/**
 * We have no need to add items to cart for now, so we override
 * the default add-to-cart/simple template with an empty file.
 */
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30, 0);

add_action('woocommerce_before_main_content', 'specials_render_heading');
add_action('woocommerce_after_single_product', 'specials_render_button');

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
            $post->custom_fields = get_catering_fields($post);
        }

        $categories[$category_key]->posts = $posts;
    }

    return $categories;
}


function get_catering_fields(&$post) {
    $catering_fields = array();
    foreach (get_post_custom($post->ID) as $custom_key => $custom_value) {
        if (in_array($custom_key, array( 'price', 'price_portion', 'serving', 'minimum_order', ))) {
            $catering_fields[$custom_key] = array_shift($custom_value);
        }
    }

    return $catering_fields;
}


function get_order_email($input, $items) {
    $total         = 0;
    $current_date  = date('d/m/Y H:i:s');
    $delivery_date = date('d/m/Y', strtotime($input['order_delivery_date']));
    $message = <<<MESSAGE
Order Received:       {$current_date}
Order Name:           {$input['order_name']}
Order Contact Number: {$input['order_phone']}
Order Contact Email:  {$input['order_email']}
Order Address:        {$input['order_delivery']}
Order Delivery Date:  {$delivery_date}
--------
Order Items:
MESSAGE;

    $length_desc = $length_qty = $length_price = $length_total = 0;

    foreach ($items as $item) {
        if (strlen($item['description']) > $length_desc) {
            $length_desc = strlen($item['description']);
        }

        if (strlen($item['quantity']) > $length_qty) {
            $length_qty = strlen($item['quantity']);
        }

        if (strlen($item['price']) > $length_price) {
            $length_price = strlen($item['price']);
        }

        if (strlen($item['line_total']) > $length_total) {
            $length_total = strlen($item['line_total']);
        }
    }

    $message      .= sprintf('%s | Qty  | Price ($) | Total ($)', str_pad('Item', $length_desc));
    $length_qty   += 2;
    $length_price += 5;

    foreach ($items as $item) {
        $message .= sprintf(
            '%s | %s | %s | %s %s',
            str_pad($item['description'], $length_desc),
            str_pad($item['quantity'], $length_qty),
            str_pad($item['price'], $length_price),
            str_pad($item['line_total'], $length_total),
            PHP_EOL
        );

        $total += $item['line_total'];
    }

    $message .= sprintf('%s  | %s%s', str_pad(' ', $length_desc + $length_qty + $length_price + $length_total),
        number_format($total, 2), PHP_EOL);

    return $message;
}


function specials_render_heading() {
    print <<<HEADING
<div class="et_pb_section et_pb_fullwidth_section page__heading specials et_pb_section_0 et_section_regular">
    <section class="et_pb_fullwidth_header et_pb_module et_pb_bg_layout_light et_pb_text_align_left health-foods__heading et_pb_fullwidth_header_0">
	    <div class="et_pb_fullwidth_header_container left">
		    <div class="header-content-container center">
			    <div class="header-content">
				    <h1>Specials</h1>
                </div>
            </div>
        </div>
        <div class="et_pb_fullwidth_header_overlay"></div>
        <div class="et_pb_fullwidth_header_scroll"></div>
    </section>
</div>
HEADING;

}

function specials_render_contact() {
    print <<<CONTACT
<div class="specials__contact clearfix">
    <span class="secondary">Visit us</span> at <strong>58A Gawler Place, Adelaide</strong> - <span class="secondary">Call us</span> on <strong>08 8227 1300</strong> to place a phone order.
</div>
CONTACT;
}

function specials_render_button() {
    print <<<BUTTON
<div class="et_pb_promo et_pb_module">
    <a class="et_pb_promo_button et_pb_button" href="/specials">View all specials</a>
</div>
BUTTON;

}
