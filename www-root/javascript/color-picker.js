function color_picker(element, palette) {
    jQuery(element).iris({
        palettes: palette
    });
    jQuery(document).click(function (e) {
        if (!jQuery(e.target).is('.iris-picker, .iris-picker-inner')) {
            jQuery(element).iris('hide');
        }
    });
    jQuery(element).click(function (event) {
        jQuery(element).iris('hide');
        jQuery(this).iris('show');
        return false;
    });
}
