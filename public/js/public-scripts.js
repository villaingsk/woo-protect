/**
 * Woo-Protect Public Scripts
 *
 * @package Woo_Protect
 * @since 1.0.0
 */

(function ($) {
    'use strict';

    $(document).ready(function () {

        // Password form submission
        $('#woo-protect-password-form').on('submit', function (e) {
            e.preventDefault();

            const $form = $(this);
            const $submitButton = $form.find('.woo-protect-submit');
            const $buttonText = $submitButton.find('.button-text');
            const $buttonLoader = $submitButton.find('.button-loader');
            const $errorDiv = $('#woo-protect-error');
            const $passwordInput = $('#woo-protect-password');

            // Get form data
            const categoryId = $form.find('input[name="category_id"]').val();
            const password = $passwordInput.val();
            const nonce = $form.find('input[name="woo_protect_nonce"]').val();

            // Validate
            if (!password) {
                showError('Please enter a password.');
                $passwordInput.focus();
                return;
            }

            // Hide error
            $errorDiv.hide();

            // Disable form
            $submitButton.prop('disabled', true);
            $passwordInput.prop('disabled', true);
            $buttonText.hide();
            $buttonLoader.show();

            // Send AJAX request
            $.ajax({
                url: wooProtectPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'woo_protect_verify_password',
                    nonce: nonce,
                    category_id: categoryId,
                    password: password
                },
                success: function (response) {
                    if (response.success) {
                        // Success - show success state
                        $form.addClass('success');
                        $buttonText.text('✓ Access Granted!').show();
                        $buttonLoader.hide();

                        // Redirect after short delay
                        setTimeout(function () {
                            window.location.href = response.data.redirect;
                        }, 500);
                    } else {
                        // Error - show error message
                        showError(response.data.message || wooProtectPublic.strings.wrongPassword);
                        resetForm();
                        $passwordInput.select();
                    }
                },
                error: function () {
                    showError(wooProtectPublic.strings.errorOccurred);
                    resetForm();
                }
            });

            // Show error message
            function showError(message) {
                $errorDiv.text(message).fadeIn(300);

                // Shake animation
                $form.addClass('shake');
                setTimeout(function () {
                    $form.removeClass('shake');
                }, 500);
            }

            // Reset form to initial state
            function resetForm() {
                $submitButton.prop('disabled', false);
                $passwordInput.prop('disabled', false);
                $buttonText.text($buttonText.data('original-text') || 'Unlock Category').show();
                $buttonLoader.hide();
            }
        });

        // Store original button text
        const $buttonText = $('.woo-protect-submit .button-text');
        $buttonText.data('original-text', $buttonText.text());

        // Auto-focus password input
        $('#woo-protect-password').focus();

        // Clear error on input
        $('#woo-protect-password').on('input', function () {
            $('#woo-protect-error').fadeOut(200);
        });

        // Enter key support
        $('#woo-protect-password').on('keypress', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#woo-protect-password-form').submit();
            }
        });
    });

})(jQuery);

// Add shake animation CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
        20%, 40%, 60%, 80% { transform: translateX(10px); }
    }
    .shake {
        animation: shake 0.5s;
    }
`;
document.head.appendChild(style);
