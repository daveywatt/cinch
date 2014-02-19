jQuery(function($) {

    /* Select2 enhanced attribute initializer */
    $('#cinch-options-container').find('select[data-enhanced="true"]').select2({
        //TODO: include validation here?
    });

    /* Bootstrap tooltips */
    $('.cinch-tooltip').click(function(e){e.preventDefault()}).tooltip();
});