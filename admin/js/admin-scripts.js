/**
 * Woo-Protect Admin Scripts
 *
 * @package Woo_Protect
 * @since 1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Toggle password visibility
        $('.toggle-password').on('click', function(e) {
            e.preventDefault();
            
            const categoryId = $(this).data('category-id');
            const passwordInput = $(`.password-input[data-category-id="${categoryId}"]`);
            const icon = $(this).find('.dashicons');
            
            if (passwordInput.attr('type') === 'password') {
                passwordInput.attr('type', 'text');
                icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
            } else {
                passwordInput.attr('type', 'password');
                icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
            }
        });

        // Enable/disable password input based on toggle
        $('.category-toggle').on('change', function() {
            const categoryId = $(this).data('category-id');
            const passwordInput = $(`.password-input[data-category-id="${categoryId}"]`);
            
            if ($(this).is(':checked')) {
                passwordInput.prop('disabled', false).focus();
            } else {
                passwordInput.prop('disabled', true).val('');
            }
        });

        // Form submission with AJAX
        $('#woo-protect-settings-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitButton = $form.find('input[type="submit"]');
            const originalText = $submitButton.val();
            
            // Disable submit button
            $submitButton.prop('disabled', true).val('Saving...');
            
            // Collect form data
            const formData = new FormData(this);
            const settings = {};
            const categories = {};
            
            // Parse settings
            $('[name^="settings["]').each(function() {
                const name = $(this).attr('name').match(/settings\[([^\]]+)\]/)[1];
                settings[name] = $(this).val();
            });
            
            // Parse categories
            $('.category-toggle:checked').each(function() {
                const categoryId = $(this).data('category-id');
                const password = $(`.password-input[data-category-id="${categoryId}"]`).val();
                
                categories[categoryId] = {
                    enabled: 'yes',
                    password: password
                };
            });
            
            // Send AJAX request
            $.ajax({
                url: wooProtectAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'woo_protect_save_settings',
                    nonce: wooProtectAdmin.nonce,
                    settings: settings,
                    categories: categories
                },
                success: function(response) {
                    if (response.success) {
                        showNotice('success', response.data.message);
                        
                        // Clear password fields (they're now saved)
                        $('.password-input').val('');
                    } else {
                        showNotice('error', response.data.message || wooProtectAdmin.strings.saveError);
                    }
                },
                error: function() {
                    showNotice('error', wooProtectAdmin.strings.saveError);
                },
                complete: function() {
                    // Re-enable submit button
                    $submitButton.prop('disabled', false).val(originalText);
                }
            });
        });

        // Show notice
        function showNotice(type, message) {
            // Remove existing notices
            $('.woo-protect-notice').remove();
            
            // Create new notice
            const $notice = $('<div>')
                .addClass('woo-protect-notice')
                .addClass(type)
                .text(message);
            
            // Insert after title
            $('.woo-protect-settings h1').after($notice);
            
            // Scroll to notice
            $('html, body').animate({
                scrollTop: $notice.offset().top - 100
            }, 300);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }

        // Confirm before leaving if form has changes
        let formChanged = false;
        
        $('#woo-protect-settings-form :input').on('change', function() {
            formChanged = true;
        });
        
        $('#woo-protect-settings-form').on('submit', function() {
            formChanged = false;
        });
        
        $(window).on('beforeunload', function() {
            if (formChanged) {
                return 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
    });

})(jQuery);
