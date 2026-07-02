// WP Product Compare - Scripts
(function($) {
    'use strict';

    $(document).ready(function() {
        // Optional: Add any interactive features here
        
        // Example: Highlight differences (simple visual cue)
        $('.wpc-table tbody tr').each(function() {
            var $cells = $(this).find('td');
            if ($cells.length === 2) {
                var val1 = $.trim($cells.eq(0).text());
                var val2 = $.trim($cells.eq(1).text());
                
                if (val1 !== val2 && val1 !== '-' && val2 !== '-') {
                    $(this).css('background-color', '#fff8e1');
                }
            }
        });

        // Example: Smooth scroll to table on load if coming from a compare link
        if (window.location.href.indexOf('+vs+') > -1) {
            $('html, body').animate({
                scrollTop: $('.wpc-table-wrapper').offset().top - 100
            }, 500);
        }
    });

})(jQuery);
