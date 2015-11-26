<?php
/**
 * Template Name: Catering Form
 */

// Handle form post
if (isset($_POST['order_details'])) {
    $errors = $items = array();
    parse_str($_POST['order_details'], $input);

    // Validate items
    if (! isset($input['items']) || count($input['items']) == 0) {
        $errors[] = 'You have not specified any items in your order';
    } else {
        foreach ($input['items'] as $item_id => $quantity) {
            $post = get_post($item_id);
            $post->custom_fields = get_catering_fields($post);

            if (isset($post->custom_fields['minimum']) && ( $quantity < (int) $post->custom_fields['minimum'] )) {
                $errors[] = printf('The minimum quantity for %s is %d.', $post->post_title, (int) $post->custom_fields['minimum']);
            }

            $items[] = array(
                'description' => $post->post_title,
                'quantity'    => $quantity,
                'price'       => $post->custom_fields['price'],
                'line_total'  => number_format($quantity * $post->custom_fields['price'], 2),
            );
        }
    }

    // Validate email
    if (filter_var($input['order_email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors[] = sprintf('The specified email %s is invalid.', htmlspecialchars($input['order_email']));
    }

    // Validate order date
    if (! strtotime($input['order_delivery_date'])) {
        $errors[] = 'The specified delivery date is invalid.';
    } elseif (strtotime($input['order_delivery_date']) < time()) {
        $errors[] = 'The specified delivery date is in the past.';
    } elseif (strtotime($input['order_delivery_date']) < strtotime('+2 days')) {
        $errors[] = 'For short notice catering, please call us on 08 8227 1300 and we can process your order over the phone.';
    }

    if (count($errors) > 0) {
        print json_encode(array(
            'success' => false,
            'errors'  => $errors,
        ));
    } else {
        if (getenv('ORDER_FORM_RECIPIENT')) {
            $message = get_order_email($input, $items);

            mail(
                getenv('ORDER_FORM_RECIPIENT'),
                '[FFL Website] Catering Order Received',
                $message,
                join(PHP_EOL, array(
                    'From: ' . getenv('ORDER_FORM_RECIPIENT'),
                    'Reply-To: ' . getenv('ORDER_FORM_RECIPIENT'),
                    'Return-Path: ' . getenv('ORDER_FORM_RECIPIENT'),
                    'CC: ' . $input['order_email'],
                ))
            );
        }

        print json_encode(array( 'success' => true, ));
    }

    die();
}

get_header();

$is_page_builder_used = et_pb_is_pagebuilder_used( get_the_ID() );

// Get all categories that are a child of the Catering category
$categories = get_catering_posts();

?>

<div id="main-content" xmlns="http://www.w3.org/1999/html">

        <?php while ( have_posts() ) : the_post(); ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

                <div class="entry-content">
                    <?php the_content(); ?>

                    <div class="et_pb_section catering__menu et_pb_section_99 et_pb_with_background et_section_regular">
                        <form action="/catering/" id="order-form" method="post">
                            <div class="et_pb_row et_pb_row_99 et_pb_row_4col table-responsive">
                                <div id="submit-message">
                                    <span class="title"></span>
                                    <span class="content"></span>
                                </div>

                                <table class="order">
                                    <col style="width: 45%;" />
                                    <col style="width: 20%;" />
                                    <col style="width: 25%;" />
                                    <col style="width: 10%;" />
                                    <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Minimum Order</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr id="no-items">
                                        <td colspan="4">There are currently no items in your order</td>
                                    </tr>
                                    </tbody>
                                    <tfoot>
                                    <tr>
                                        <td colspan="2" class="terms">
                                            <strong>FREE DELIVERY</strong> <em>on every order of $100.00 or more (Adelaide CBD Only).<br />
                                                Delivery charges will apply on orders under $100.00 and/or delivery outside the CBD square mile. Pick-up is also available.</em>
                                        </td>
                                        <td><strong>Total</strong> <em>inc. GST</em></td>
                                        <th>$<span id="order-total"></span></th>
                                    </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="et_pb_row et_pb_row_100 et_pb_row_4col" id="order-details">
                                <div class="order_input_container clearfix">
                                    <div class="order_input double">
                                        <label for="order_name">Your Name</label>
                                        <input id="order_name" name="order_name" type="text" required>
                                    </div>
                                    <div class="order_input">
                                        <label for="order_phone">Your Contact Number</label>
                                        <input id="order_phone" name="order_phone" type="text" required>
                                    </div>
                                    <div class="order_input">
                                        <label for="order_email">Your Email Address</label>
                                        <input id="order_email" name="order_email" type="text" required>
                                    </div>
                                    <div class="order_input double">
                                        <label for="order_delivery">Your Delivery Address</label>
                                        <input id="order_delivery" name="order_delivery" type="text" required>
                                    </div>
                                    <div class="order_input">
                                        <label for="order_delivery_date">Delivery Date</label>
                                        <input id="order_delivery_date" name="order_delivery_date" type="date" required">
                                    </div>
                                    <div class="order_input full">
                                        <label for="order-comments">Additional Comments</label>
                                        <textarea id="order-comments" name="order_comments" rows="10"></textarea>
                                    </div>
                                </div>
                                <div class="et_pb_promo et_pb_module">
                                    <button class="et_pb_promo_button et_pb_button" id="submit-order" type="button" disabled>Confirm Order</button>
                                </div>
                            </div>
                        </form>

                        <?php foreach ($categories as $category): ?>
                            <div class="et_pb_row et_pb_row_99 et_pb_row_4col">

                                <h2><?= $category->name ?></h2>
                                <?php foreach ($category->posts as $post): ?>
                                    <div class="catering__menu-item">
                                        <img src="<?= $post->featured_image ?>" class="catering__menu-item__image" />
                                        <div class="item-description">
                                            <h3><?= $post->post_title ?></h3>
                                            <p><?= $post->post_content ?></p>

                                            <?php if (isset($post->custom_fields['serving'])): ?>
                                                <div class="serving">
                                                    <span class="serving__title">Serving:</span>&nbsp;<span class="serving__description"><?= $post->custom_fields['serving'] ?></span>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (isset($post->custom_fields['price'])): ?>
                                                <div class="price">
                                                    <span class="price__amount">$<?= sprintf('%.2f', $post->custom_fields['price']) ?></span><?php if (isset($post->custom_fields['price_portion'])): ?>&nbsp;<span class="price__portion"><?= $post->custom_fields['price_portion'] ?></span><?php endif; ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (isset($post->custom_fields['minimum_order'])): ?>
                                                <div class="minimum">
                                                    <span class="minimum__order">Minimum order: <?= $post->custom_fields['minimum_order'] ?></span>
                                                </div>
                                            <?php endif; ?>

                                            <div class="form__fields">
                                                <input type="number" class="order_item" data-item="<?= $post->ID ?>" data-description="<?= $post->post_title ?>" <?= sprintf('data-minimum="%1$d" data-minimum-description="%2$s" min="%1$d"', isset($post->custom_fields['minimum_order']) ? (int) $post->custom_fields['minimum_order'] : 1, isset($post->custom_fields['minimum_order']) ? $post->custom_fields['minimum_order'] : '1 serve') ?>" data-price="<?= $post->custom_fields['price'] ?>" placeholder="0">&nbsp;
                                                <button type="button">Add to Order</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>


                </div> <!-- .entry-content -->

            </article> <!-- .et_pb_post -->

        <?php endwhile; ?>

    </div> <!-- #main-content -->

<script>
    (function($) {
        var order_total = 0,
            items = []; // array of objects

        set_order_total();

        $('.form__fields button').click(function (e) {
            var item = $(this).parent().find('.order_item');

            var id          = item.data('item'),
                quantity    = item.val(),
                description = item.data('description'),
                price       = item.data('price'),
                minimum     = item.data('minimum'),
                min_desc    = item.data('minimumDescription');

            update_cart(id, quantity, description, price, minimum, min_desc);
        });

        $('table.order button.delete').live('click', function (e) {
            var row  = $(this).parent().parent(),
                item = row.find('.quantity');

            var id       = item.data('item'),
                quantity = item.val(),
                price    = item.data('price');

            update_total(calculate_price(quantity, price), true);

            row.remove();
            remove_item(id);

            if (items.length == 0) {
                $('#no-items').show();
            }
        });

        $('table.order button.update').live('click', function (e) {
            var row  = $(this).parent().parent(),
                item = row.find('.quantity');

            var id       = item.data('item'),
                quantity = item.val(),
                price    = item.data('price'),
                minimum  = item.data('minimum');

            if (quantity >= minimum) {
                var item_total = calculate_price(quantity, price);

                item.val(quantity);
                item.parent().parent().find('.line-total').html('$' + item_total);

                update_items(id, quantity);
            }
        });

        $('table.order .quantity').live('change', function (e) {
            var item = $(this),
                id = item.data('item'),
                quantity = item.val(),
                price = item.data('price'),
                minimum = item.data('minimum'),
                row = item.parent().parent();

            if (quantity >= minimum) {
                var item_total = calculate_price(quantity, price);

                row.find('.line-total').html('$' + item_total);
                update_items(id, quantity);
            } else {
                row.remove();
                remove_item(id);
            }
        });

        $('#order-details .order_input > input[required]').keyup(function () {
            var filled = true;

            $('#order-details .order_input > input[required]').each (function () {
                if ($(this).val() == '') {
                    filled = false;
                }
            });

            if (filled) {
                $('#submit-order').removeAttr('disabled');
            } else {
                $('#submit-order').attr('disabled', 'disabled');
            }
        });

        $('#submit-order').click(function (e) {
            e.preventDefault();
            e.stopPropagation();

            var form = $('#order-form'),
                url  = form.attr('action');

            $.ajax({
                method: 'POST',
                url: url,
                data: { order_details: form.serialize() },
                dataType: 'json',
                beforeSend: function () {
                    $('#submit-message').hide();
                    $('#submit-message').removeClass('error');
                    $('#submit-message').removeClass('success');
                    $('#submit-message .title').html('');
                    $('#submit-message .content').html('');
                },
                success: function (data) {
                    if (data.success) {
                        $('#submit-message').addClass('success');
                        $('#submit-message .title').html('Your order was submitted!')
                        $('#submit-message .content').html('We will be in contact with you to confirm your order and process payment shortly.')

                        items = [];

                        $('table.order .order_item').each(function () {
                            $(this).remove();
                        });

                        $('#order-details input').each(function () {
                            $(this).val('');
                        });

                        $('.order_item[type=number]').each(function () {
                            $(this).val('');
                        });

                        $('#order-comments').val('');

                        $('#no-items').show();
                        $('#submit-order').attr('disabled', 'disabled');
                        update_total();

                        $('html, body').animate({ scrollTop: $('#order-form').offset().top }, 'slow');
                    } else {
                        $('#submit-message').addClass('error');
                        $('#submit-message .title').html('Please check your input and try again');
                        $('#submit-message .content').html('<ul id="submit-errors"></ul>');

                        for (var i = 0; i < data.errors.length; i++) {
                            $('#submit-message .content #submit-errors').append(
                                '<li>' + data.errors[i] + '</li>'
                            );
                        }

                        $('html, body').animate({ scrollTop: $('#order-form').offset().top - 15 }, 'slow');
                    }

                    $('#submit-message').show();
                }
            });
        });

        function update_cart(id, quantity, description, price, minimum, min_desc) {
            var item_total  = calculate_price(quantity, price);
            var order_table = $('table.order');

            if (quantity >= minimum) {
                if (items.length == 0) {
                    $('#no-items').hide();
                }

                var exists = item_exists(id);

                if (typeof exists !== 'undefined') {
                    $('table.order .quantity').each(function () {
                        var item = $(this);

                        if (item.data('item') == id) {
                            item.val(quantity);
                            item.parent().parent().find('.line-total').html('$' + item_total);
                        }
                    });
                } else {
                    order_table.find('tbody').append(
                        '<tr class="order_item">' +
                            '<td>' + description + '</td>' +
                            '<td>' + min_desc + '</td>' +
                            '<td>' +
                                '<input type="number" class="quantity" name="items[' + id + ']" min="' + minimum +'" data-minimum="' + minimum + '" data-price="' + price + '" data-item="' + id + '" value="' + quantity + '">' +
                                '<button type="button" class="update">Update</button>' +
                                '<button type="button" class="delete">Delete</button>' +
                            '</td>' +
                            '<td class="line-total">$' + item_total + '</td>' +
                        '</tr>'
                    );
                }

                update_items(id, quantity, description, price);
            }
        }

        function set_order_total() {
            $('#order-total').text(order_total);
        }

        function update_total() {
            order_total = 0;

            for (var i = 0; i < items.length; i++) {
                order_total = (parseFloat(order_total) + parseFloat(items[i].quantity * items[i].price)).toFixed(2);
            }

            set_order_total();
        }

        function calculate_price(quantity, price) {
            return (quantity * price).toFixed(2);
        }

        function update_items(id, quantity, description, price) {
            var exists = item_exists(id);

            if (typeof exists !== 'undefined') {
                items[exists].quantity = quantity;
            } else {
                items.push({
                    id:          id,
                    description: description,
                    quantity:    quantity,
                    price:       price
                });
            }

            update_total();
        }

        function item_exists(id) {
            for (var i = 0; i < items.length; i++) {
                if (items[i].id == id) {
                    return i;
                }
            }
        }

        function remove_item(id) {
            items = items.filter(function (item) {
                return item.id !== id;
            });

            update_total();
        }
    })( jQuery );

</script>

<?php get_footer(); ?>
