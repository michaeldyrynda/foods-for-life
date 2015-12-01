jQuery(document).ready(function($) {
    /**
     * On the Cafe page, we need to vertically center the text inside each menu group.
     * Divi has some default styles which make this a little tricky using relative
     * positioning of the element inside the main column group. Use a bit of
     * jquery which will iterate over each group, and set a top margin on
     * the inner text element, which  will give us the alignment needed.
     */
    if ($('.cafe__menu__group')) {
        $('.cafe__menu__group').each(function (item, object) {
            var text = $(this).find('.et_pb_text');

            text.css({
                'margin-top': ( $(this).outerHeight() - text.outerHeight() ) / 2 + 'px'
            });
        });
    }
});
