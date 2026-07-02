/**
 * WP Versus Comparison Frontend Script
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Copy URL functionality with better UX
        $('.wpvc-copy-btn').on('click', function(e) {
            e.preventDefault();
            var $input = $(this).siblings('input');
            var originalText = $(this).text();
            
            $input.select();
            document.execCommand('copy');
            
            $(this).text('Copied!');
            setTimeout(function() {
                $('.wpvc-copy-btn').text(originalText);
            }, 2000);
        });

        // Add smooth scroll to specifications
        $('.wpvc-button[href*="#specs"]').on('click', function(e) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: $('.wpvc-specifications').offset().top - 100
            }, 800);
        });

        // Highlight different specs on hover
        $('.wpvc-spec-row.wpvc-different').hover(
            function() {
                $(this).css('background-color', '#fff3cd');
            },
            function() {
                $(this).css('background-color', '');
            }
        );

        // Add comparison link generator to single post pages
        addComparisonLinksToPosts();
    });

    /**
     * Add "Compare" buttons to single post pages
     * This allows users to easily start a comparison from any product page
     */
    function addComparisonLinksToPosts() {
        // Check if we're on a single post/page/custom post type
        if ($('.wpvc-comparison-container').length > 0) {
            return; // Already on comparison page
        }

        var postId = $('body.post-id-' + window.wpvc_current_post_id).length ? 
                     window.wpvc_current_post_id : null;

        if (!postId) {
            // Try to get post ID from other sources
            var bodyClass = $('body').attr('class');
            var match = bodyClass.match(/postid-(\d+)/);
            if (match && match[1]) {
                postId = match[1];
            }
        }

        if (postId) {
            // You can inject a "Compare this item" button here
            // Example: Add after post content
            console.log('Post ID detected:', postId);
            // Implementation depends on your theme structure
        }
    }

    /**
     * AJAX function to load comparison data (future enhancement)
     */
    function loadComparisonData(item1Id, item2Id) {
        $.ajax({
            url: wpvc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wpvc_load_comparison',
                item_1_id: item1Id,
                item_2_id: item2Id,
                nonce: wpvc_ajax.nonce
            },
            success: function(response) {
                console.log('Comparison data loaded:', response);
            },
            error: function(xhr, status, error) {
                console.error('Error loading comparison:', error);
            }
        });
    }

})(jQuery);
