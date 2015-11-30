<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

get_header();

print do_shortcode(et_pb_load_global_module( 913 ));

?>

<div class="et_pb_section specials__content categories__content et_pb_section_1 et_pb_with_background et_section_regular">
    <div class="et_pb_row et_pb_row_0 et_pb_equal_columns et_pb_row_3-4_1-4">
        <div class="et_pb_column et_pb_column_3_4 et_pb_column_0">
            <?php if ( have_posts() ) : ?>
                <header class="page-header">
                    <?php
                        the_archive_title( '<h1 class="page-title">', '</h1>' );
                        the_archive_description( '<div class="taxonomy-description">', '</div>' );
                    ?>
                </header><!-- .page-header -->

                <?php
                    // Start the Loop.
                    while ( have_posts() ) : the_post();

                    the_title(sprintf('<h2><a href="%s">', get_permalink()), '</a></h2>');
                    the_excerpt();

                    /*
                     * Include the post format-specific template for the content. If you want to
                     * use this in a child theme, then include a file called called content-___.php
                     * (where ___ is the post format) and that will be used instead.
                     */
                    get_template_part( 'content', get_post_format() );

                    endwhile;
                    // Previous/next page navigation.

                else :
                    // If no content, include the "No posts found" template.
                    get_template_part( 'content', 'none' );

                endif;
            ?>
        </div> <!-- .et_pb_column -->
        <div class="et_pb_column et_pb_column_1_4 et_pb_column_1">
            <div class="et_pb_widget_area et_pb_widget_area_left clearfix et_pb_module et_pb_bg_layout_light  et_pb_sidebar_0 et_pb_sidebar_no_border">
                <?php dynamic_sidebar( 'ffl-news-sidebar' ); ?>
            </div> <!-- .et_pb_widget_area -->
        </div> <!-- .et_pb_column -->
    </div> <!-- .et_pb_row -->
</div>

<?php get_footer();
