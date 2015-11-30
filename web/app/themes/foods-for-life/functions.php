<?php

remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40, 0);

/**
 * We have no need to add items to cart for now, so we override
 * the default add-to-cart/simple template with an empty file.
 */
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30, 0);

add_action('woocommerce_after_single_product', 'specials_render_button');

function foods_for_life_scripts() {
    wp_enqueue_script('ffl-scripts', get_stylesheet_directory_uri() . '/js/ffl.js', array( 'jquery', ));
}

add_action('wp_enqueue_scripts', 'foods_for_life_scripts');

function get_catering_posts() {
    $categories = get_categories(array( 'child_of' => 16, ));

    // For each category in the catering category, fetch its posts
    foreach ($categories as $category_key => $category) {
        // Ensure that we return all posts for this category
        $posts = get_posts(array( 'category' => $category->cat_ID, 'posts_per_page' => -1 ));

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

function build_swift_message($subject, $recipient_name, $recipient_email, $body_html, $body_text, $items_csv = null) {
    $message = Swift_message::newInstance()
        ->setSubject($subject)
        ->setFrom(array( getenv('ORDER_FORM_SENDER_EMAIL') => getenv('ORDER_FORM_SENDER_NAME'), ))
        ->setTo(array( $recipient_email => $recipient_name, ))
        ->setBody($body_text)
        ->addPart($body_html, 'text/html');

    if (! is_null($items_csv)) {
        $message->attach(Swift_Attachment::fromPath($items_csv), 'text/csv');
    }

    return $message;
}

function send_confirmation_email($input, $items) {
    list($body_html, $body_text) = get_confirmation_email_body($input, $items);

    $mailer  = get_mailer();
    $message = build_swift_message(
        '[Foods For Life] Catering Order Confirmation',
        $input['order_name'],
        $input['order_email'],
        $body_html,
        $body_text
    );

    return $mailer->send($message);
}

function send_order_email($input, $items) {
    list($body_html, $body_text) = get_confirmation_email_body($input, $items, true);

    $order_items_csv = get_order_items_csv($items);

    $mailer  = get_mailer();
    $message = build_swift_message(
        '[Foods For Life] Catering Order Received',
        getenv('ORDER_FORM_RECIPIENT_NAME'),
        getenv('ORDER_FORM_RECIPIENT_EMAIL'),
        $body_html,
        $body_text,
        $order_items_csv
    );

    if ($mailer->send($message)) {
        unlink($order_items_csv);

        return true;
    }

    return false;
}

function get_order_items_csv($items) {
    $filename = sprintf('%s.csv', tempnam('/tmp', 'ffl-order_'));
    $handle   = fopen($filename, 'w');

    fputcsv($handle, array( 'Description', 'Quantity', 'Price', 'Line Total', ));

    foreach ($items as $item) {
        fputcsv($handle, $item);
    }

    fclose($handle);

    return $filename;
}

function get_order_delivery_datetime($input) {
    return array(
        date('d/m/Y', strtotime($input['order_delivery_date'])),
        date('h:i A', strtotime($input['order_delivery_time'])),
    );
}

function get_confirmation_email_body($input, $items, $order_email = false) {
    return array(
        get_confirmation_email_body_html($input, $items, $order_email),
        get_confirmation_email_body_text($input, $items, $order_email),
    );
}

function get_confirmation_email_body_html($input, $items, $order_email = false) {
    $total         = get_order_total($items);
    $message       = null;
    $current_date  = date('d/m/Y H:i:s');

    list($delivery_date, $delivery_time) = get_order_delivery_datetime($input);

    $input['order_comments'] = stripslashes($input['order_comments']);

    $message .= '<strong style="font-family: Helvetica, Arial, sans-serif;">';

    if ($order_email) {
        $message .= 'A new order was received from the Foods For Life website.';
    } else {
        $message .= 'Your order has been received. We will be in contact with you to finalise your order shortly.';
    }

    $message .= '</strong><br /><br />';

    $message .= <<<MESSAGE
<table cellpadding="4" cellspacing="1" style="border: 1px solid #f5f5f5; border-collapse: collapse;">
<tr>
    <td style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; width: 25%; font-weight: bold;">Order Received</td>
    <td style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; width: 25%;">{$current_date}</td>
    <td style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; width: 25%; font-weight: bold;">Order Name</td>
    <td style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; width: 25%;">{$input['order_name']}</td>
</tr>
<tr>
    <td style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; width: 25%; font-weight: bold;">Contact Number</td>
    <td style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; width: 25%;">{$input['order_phone']}</td>
    <td style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; width: 25%; font-weight: bold;">Contact Email</td>
    <td style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; width: 25%;">{$input['order_email']}</td>
</tr>
<tr>
    <td style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; width: 25%; font-weight: bold;">Delivery Address</td>
    <td style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; width: 25%;">{$input['order_delivery']}</td>
    <td style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; width: 25%; font-weight: bold;">Delivery Date</td>
    <td style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; width: 25%;">{$delivery_date} {$delivery_time}</td>
</tr>
<tr>
    <td colspan="4" style="border: 1px solid #f5f5f5;">&nbsp;</td>
</tr>
<tr>
    <td colspan="4" style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; font-weight: bold;">Comments</td>
</tr>
<tr>
    <td colspan="4" style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif;">{$input['order_comments']}</td>
</tr>
<tr>
    <td colspan="4" style="border: 1px solid #f5f5f5;">&nbsp;</td>
</tr>
<tr>
    <td colspan="4" style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; font-weight: bold;">Order Items</td>
</tr>
<tr>
    <td colspan="4" style="border: 1px solid #f5f5f5;" >&nbsp;</td>
</tr>
MESSAGE;

    $message .= '<tr>';
    $message .= '<td style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; font-weight: bold; width: 25%;">Description</td>';
    $message .= '<td style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; font-weight: bold; width: 25%;">Quantity</td>';
    $message .= '<td style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; font-weight: bold; width: 25%;">Price</td>';
    $message .= '<td style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; font-weight: bold; width: 25%;">Line Total</td>';
    $message .= '</tr>';

    foreach ($items as $item) {
        $message .= sprintf(
            '<tr>
                <td style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; width: 25%%;">%s</td>
                <td style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; width: 25%%;">%s</td>
                <td style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; font-weight: bold; width: 25%%;">$%.2f</td>
                <td style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; font-weight: bold; width: 25%%;">$%.2f</td>
            </tr>',
            $item['description'],
            $item['quantity'],
            $item['price'],
            $item['line_total']
        );
    }

    $message .= sprintf(
        '<tr>
            <td colspan="2" style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; width: 50%%;">Total</td>
            <td style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; font-weight: bold; width: 25%%;">Total</td>
            <td style="border: 1px solid #f5f5f5; font-family: Helvetica, Arial, sans-serif; font-weight: bold; width: 25%%;">$%.2f</td>
        </tr>',
        $total
    );

    return $message;
}

function get_order_total($items) {
    return array_reduce($items, 'sum', 0);
}

function sum($carry, $item) {
    return $carry += $item['line_total'];
}

function get_confirmation_email_body_text($input, $items, $order_email = false) {
    $total         = get_order_total($items);
    $message       = null;
    $current_date  = date('d/m/Y H:i:s');

    list($delivery_date, $delivery_time) = get_order_delivery_datetime($input);

    if ($order_email) {
        $message .= '*A new order was received from the Foods For Life website*' . PHP_EOL . PHP_EOL;
    } else {
        $message .= '*Your order has been received. We will be in contact with you to finalise your order shortly.*';
    }

    $message .= <<<MESSAGE
Order Received:       {$current_date}
Order Name:           {$input['order_name']}
Order Contact Number: {$input['order_phone']}
Order Contact Email:  {$input['order_email']}
Order Address:        {$input['order_delivery']}
Order Delivery Date:  {$delivery_date}
Order Delivery Time:  {$delivery_time}
Additional Comments:  {$input['order_comments']}
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
    }

    $message .= sprintf('%s  | %s%s', str_pad(' ', $length_desc + $length_qty + $length_price + $length_total),
        number_format($total, 2), PHP_EOL);

    return $message;
}

function get_mailer() {
    return Swift_Mailer::newInstance(Swift_MailTransport::newInstance());
}
