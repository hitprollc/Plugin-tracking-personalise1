/**
 * Public JavaScript for Plugin Tracking Personalise
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Animate progress bar on load
        if ($('.ptp-progress-fill').length) {
            var progressBar = $('.ptp-progress-fill');
            var targetWidth = progressBar.css('width');
            progressBar.css('width', '0');
            
            setTimeout(function() {
                progressBar.css('width', targetWidth);
            }, 300);
        }

        // Add smooth scroll to timeline events
        $('.ptp-timeline-event').each(function(index) {
            var $event = $(this);
            setTimeout(function() {
                $event.addClass('visible');
            }, 100 * index);
        });

        // Form validation
        $('.ptp-lookup-form').on('submit', function(e) {
            var trackingNumber = $('#ptp_tracking_number').val().trim();
            
            if (!trackingNumber) {
                e.preventDefault();
                alert('Veuillez entrer un numéro de suivi.');
                $('#ptp_tracking_number').focus();
                return false;
            }

            // Validate email if required
            var emailField = $('#ptp_email');
            if (emailField.length) {
                var email = emailField.val().trim();
                if (!email) {
                    e.preventDefault();
                    alert('Veuillez entrer votre adresse email.');
                    emailField.focus();
                    return false;
                }

                // Basic email validation
                var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(email)) {
                    e.preventDefault();
                    alert('Veuillez entrer une adresse email valide.');
                    emailField.focus();
                    return false;
                }
            }
        });

        // Input formatting - uppercase for tracking number
        $('#ptp_tracking_number').on('input', function() {
            $(this).val($(this).val().toUpperCase());
        });

        // Add loading state to submit button
        $('.ptp-lookup-form').on('submit', function() {
            var $btn = $(this).find('.ptp-submit-btn');
            $btn.prop('disabled', true).text('Recherche en cours...');
        });
    });

})(jQuery);
