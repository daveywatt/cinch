jQuery(function($) {

    /* Select2 enhanced attribute initializer */
    $('#cinch-options-container').find('select[data-enhanced="true"]').select2({
        //TODO: include validation here?
    });

    /* Bootstrap tooltips */
    $('.cinch-tooltip').click(function(e){e.preventDefault()}).tooltip();

    /* Admin page access control */
    $('.cinch-access-item-toggle-parent, .cinch-access-item-toggle-child').click(function(e) {
        e.preventDefault();
        var target = ($(this).hasClass('cinch-access-item-toggle-parent') ? $(this).parents('.menu-item').find('.cinch-parent-settings') : $(this).parents('.menu-item').find('.cinch-child-settings'));

        if (!target.hasClass('cinch-menu-open')) {
            target.slideDown().addClass('cinch-menu-open');
        } else {
            target.slideUp().removeClass('cinch-menu-open');
        }

    });


});