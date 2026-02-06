<?php
/**
 * Password Form Template
 *
 * @package Woo_Protect
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$settings = Woo_Protect::get_instance()->get_settings();
$category_name = isset($category) ? $category->name : '';
$category_id = isset($category) ? $category->term_id : 0;
?>

<div class="woocommerce woo-protect-password-page">
    <div class="woo-protect-password-container">
        <div class="woo-protect-password-card">
            <!-- Lock Icon -->
            <div class="woo-protect-lock-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>

            <!-- Title -->
            <h1 class="woo-protect-title">
                <?php echo esc_html($settings['lock_screen_title']); ?>
            </h1>

            <!-- Category Name -->
            <?php if ($category_name) : ?>
                <p class="woo-protect-category-name">
                    <?php echo esc_html($category_name); ?>
                </p>
            <?php endif; ?>

            <!-- Message -->
            <p class="woo-protect-message">
                <?php echo esc_html($settings['lock_screen_message']); ?>
            </p>

            <!-- Password Form -->
            <form id="woo-protect-password-form" class="woo-protect-form" method="post">
                <?php wp_nonce_field('woo_protect_public_nonce', 'woo_protect_nonce'); ?>
                <input type="hidden" name="category_id" value="<?php echo esc_attr($category_id); ?>">

                <div class="woo-protect-form-group">
                    <label for="woo-protect-password" class="screen-reader-text">
                        <?php esc_html_e('Password', 'woo-protect'); ?>
                    </label>
                    <input type="password" 
                           id="woo-protect-password" 
                           name="password" 
                           class="woo-protect-input" 
                           placeholder="<?php esc_attr_e('Enter password', 'woo-protect'); ?>" 
                           required
                           autocomplete="off">
                </div>

                <!-- Error Message -->
                <div id="woo-protect-error" class="woo-protect-error" style="display: none;"></div>

                <!-- Submit Button -->
                <button type="submit" class="woo-protect-submit">
                    <span class="button-text"><?php esc_html_e('Unlock Category', 'woo-protect'); ?></span>
                    <span class="button-loader" style="display: none;">
                        <svg class="spinner" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="2" x2="12" y2="6"></line>
                            <line x1="12" y1="18" x2="12" y2="22"></line>
                            <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
                            <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
                            <line x1="2" y1="12" x2="6" y2="12"></line>
                            <line x1="18" y1="12" x2="22" y2="12"></line>
                            <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
                            <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
                        </svg>
                    </span>
                </button>
            </form>

            <!-- Back Link -->
            <div class="woo-protect-back-link">
                <a href="<?php echo esc_url($settings['redirect_url'] ?: wc_get_page_permalink('shop')); ?>">
                    ← <?php esc_html_e('Back to Shop', 'woo-protect'); ?>
                </a>
            </div>
        </div>
    </div>
</div>
