<?php

get_header();

$show_default_title = get_post_meta( get_the_ID(), '_et_pb_show_title', true );

$is_page_builder_used = et_pb_is_pagebuilder_used( get_the_ID() );

?>

    <div id="main-content" class="ffl-single-post">
        <?php render_heading('FFL News'); ?>
        <div class="et_pb_section et_pb_section_1 et_pb_with_background et_section_regular">
            <div class="et_pb_row et_pb_row_0 et_pb_equal_columns et_pb_row_3-4_1_4">
                <div class="et_pb_column et_pb_column_3_4 et_pb_column_0">
                    <?php while ( have_posts() ) : the_post(); ?>
                        <?php if (et_get_option('divi_integration_single_top') <> '' && et_get_option('divi_integrate_singletop_enable') == 'on') echo(et_get_option('divi_integration_single_top')); ?>

                        <article id="post-<?php the_ID(); ?>" <?php post_class( 'et_pb_post' ); ?>>
                            <?php if ( ( 'off' !== $show_default_title && $is_page_builder_used ) || ! $is_page_builder_used ) { ?>
                                <div class="et_post_meta_wrapper">
                                    <?php
                                    if ( ! post_password_required() ) :

                                        $thumb = '';

                                        $width = (int) apply_filters( 'et_pb_index_blog_image_width', 1080 );

                                        $height = (int) apply_filters( 'et_pb_index_blog_image_height', 675 );
                                        $classtext = 'et_featured_image';
                                        $titletext = get_the_title();
                                        $thumbnail = get_thumbnail( $width, $height, $classtext, $titletext, $titletext, false, 'Blogimage' );
                                        $thumb = $thumbnail["thumb"];

                                        $post_format = et_pb_post_format();

                                        if ( 'video' === $post_format && false !== ( $first_video = et_get_first_video() ) ) {
                                            printf(
                                                '<div class="et_main_video_container">
											%1$s
										</div>',
                                                $first_video
                                            );
                                        } else if ( ! in_array( $post_format, array( 'gallery', 'link', 'quote' ) ) && 'on' === et_get_option( 'divi_thumbnails', 'on' ) && '' !== $thumb ) {
                                            print_thumbnail( $thumb, $thumbnail["use_timthumb"], $titletext, $width, $height );
                                        } else if ( 'gallery' === $post_format ) {
                                            et_pb_gallery_images();
                                        }
                                        ?>

                                        <h1 class="entry-title"><?php the_title(); ?></h1>
                                        <?php et_divi_post_meta(); ?>

                                        <?php
                                        $text_color_class = et_divi_get_post_text_color();

                                        $inline_style = et_divi_get_post_bg_inline_style();

                                        switch ( $post_format ) {
                                            case 'audio' :
                                                printf(
                                                    '<div class="et_audio_content%1$s"%2$s>
												%3$s
											</div>',
                                                    esc_attr( $text_color_class ),
                                                    $inline_style,
                                                    et_pb_get_audio_player()
                                                );

                                                break;
                                            case 'quote' :
                                                printf(
                                                    '<div class="et_quote_content%2$s"%3$s>
												%1$s
											</div> <!-- .et_quote_content -->',
                                                    et_get_blockquote_in_content(),
                                                    esc_attr( $text_color_class ),
                                                    $inline_style
                                                );

                                                break;
                                            case 'link' :
                                                printf(
                                                    '<div class="et_link_content%3$s"%4$s>
												<a href="%1$s" class="et_link_main_url">%2$s</a>
											</div> <!-- .et_link_content -->',
                                                    esc_url( et_get_link_url() ),
                                                    esc_html( et_get_link_url() ),
                                                    esc_attr( $text_color_class ),
                                                    $inline_style
                                                );

                                                break;
                                        }

                                    endif;
                                    ?>
                                </div> <!-- .et_post_meta_wrapper -->
                            <?php  } ?>

                            <div class="entry-content">
                                <?php
                                the_content();

                                wp_link_pages( array( 'before' => '<div class="page-links">' . __( 'Pages:', 'Divi' ), 'after' => '</div>' ) );
                                ?>
                            </div> <!-- .entry-content -->
                            <div class="et_post_meta_wrapper">
                                <?php
                                if ( et_get_option('divi_468_enable') == 'on' ){
                                    echo '<div class="et-single-post-ad">';
                                    if ( et_get_option('divi_468_adsense') <> '' ) echo( et_get_option('divi_468_adsense') );
                                    else { ?>
                                        <a href="<?php echo esc_url(et_get_option('divi_468_url')); ?>"><img src="<?php echo esc_attr(et_get_option('divi_468_image')); ?>" alt="468" class="foursixeight" /></a>
                                    <?php 	}
                                    echo '</div> <!-- .et-single-post-ad -->';
                                }
                                ?>

                                <?php
                                if ( ( comments_open() || get_comments_number() ) && 'on' == et_get_option( 'divi_show_postcomments', 'on' ) )
                                    comments_template( '', true );
                                ?>
                            </div>
                        </article>

                        <?php if (et_get_option('divi_integration_single_bottom') <> '' && et_get_option('divi_integrate_singlebottom_enable') == 'on') echo(et_get_option('divi_integration_single_bottom')); ?>
                    <?php endwhile; ?>
                </div>
                <div class="et_pb_column et_pb_column_1_4 et_pb_column_1">
                    <?php dynamic_sidebar('ffl-news-sidebar'); ?>
                </div>
            </div> <!-- #content-area -->
        </div> <!-- .container -->
    </div> <!-- #main-content -->

<?php get_footer(); ?>
