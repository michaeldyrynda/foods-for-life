<?php
/**
 * Template Name: Catering Form
 */

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
                        <div class="et_pb_row et_pb_row_99 et_pb_row_4col">
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

                        <?php foreach ($categories as $category): ?>
                            <div class="et_pb_row et_pb_row_99 et_pb_row_4col">

                                <h2><?= $category->name ?></h2>
                                <?php foreach ($category->posts as $post): ?>
                                    <div class="catering__menu-item">
                                        <img src="<?= $post->featured_image ?>" class="catering__menu-item__image" />
                                        <div class="item-description">
                                            <h3><?= $post->post_title ?></h3>
                                            <?= $post->post_content ?>

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

        set_order_total(order_total);

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
                        '<tr>' +
                            '<td>' + description + '</td>' +
                            '<td>' + min_desc + '</td>' +
                            '<td>' +
                                '<input type="number" class="quantity" name="item[' + id + '][quantity]" min="' + minimum +'" data-minimum="' + minimum + '" data-price="' + price + '" data-item="' + id + '" value="' + quantity + '">&nbsp;' +
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
