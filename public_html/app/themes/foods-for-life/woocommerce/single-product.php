<?php
/**
 * The Template for displaying all single products.
 *
 * Override this template by copying it to yourtheme/woocommerce/single-product.php
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

get_header( 'shop' );

print do_shortcode(et_pb_load_global_module( 911 ));
?>

<div class="et_pb_section specials__content et_pb_section_1 et_pb_with_background et_section_regular">
    <div class="et_pb_row et_pb_row_0 et_pb_equal_columns et_pb_row_3-4_1-4">
        <div class="et_pb_column et_pb_column_3_4 et_pb_column_0">
            <?php while ( have_posts() ) : the_post(); ?>
                <?php wc_get_template_part( 'content', 'single-product' ); ?>
            <?php endwhile; ?>

        </div> <!-- .et_pb_column -->
        <div class="et_pb_column et_pb_column_1_4 et_pb_column_1">
            <div class="et_pb_widget_area et_pb_widget_area_left clearfix et_pb_module et_pb_bg_layout_light  et_pb_sidebar_0 et_pb_sidebar_no_border">
                <?php dynamic_sidebar( 'ffl-specials-sidebar' ); ?>
            </div> <!-- .et_pb_widget_area -->
        </div> <!-- .et_pb_column -->
    </div> <!-- .et_pb_row -->
</div>

<?php get_footer( 'shop' ); ?>

