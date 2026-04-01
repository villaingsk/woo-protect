<?php
/**
 * Password Handler Class
 *
 * @package Woo_Protect
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Woo_Protect_Password_Handler Class
 */
class Woo_Protect_Password_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        // AJAX handlers for password verification
        add_action('wp_ajax_woo_protect_verify_password', array($this, 'verify_password'));
        add_action('wp_ajax_nopriv_woo_protect_verify_password', array($this, 'verify_password'));

        // Clear session on logout
        add_action('wp_logout', array($this, 'clear_session'));
    }

    /**
     * Verify password via AJAX
     */
    public function verify_password() {
        // Check nonce
        check_ajax_referer('woo_protect_public_nonce', 'nonce');

        // Get posted data
        $category_id = isset($_POST['category_id']) ? absint(wp_unslash($_POST['category_id'])) : 0;
        $password = isset($_POST['password']) ? sanitize_text_field(wp_unslash($_POST['password'])) : '';

        // Validate inputs
        if (empty($category_id) || empty($password)) {
            wp_send_json_error(array(
                'message' => __('Invalid request. Please try again.', 'woo-protect'),
            ));
        }

        // Check if category is protected
        if (!Woo_Protect_Admin_Settings::is_category_protected($category_id)) {
            wp_send_json_error(array(
                'message' => __('This category is not protected.', 'woo-protect'),
            ));
        }

        // Get stored password hash
        $stored_hash = Woo_Protect_Admin_Settings::get_category_password($category_id);

        if (empty($stored_hash)) {
            wp_send_json_error(array(
                'message' => __('Password not configured for this category.', 'woo-protect'),
            ));
        }

        // Verify password
        if (wp_check_password($password, $stored_hash)) {
            // Password correct - grant access
            $this->unlock_category($category_id);

            // Get redirect URL
            $redirect_url = get_term_link($category_id, 'product_cat');
            
            if (is_wp_error($redirect_url)) {
                $redirect_url = wc_get_page_permalink('shop');
            }

            wp_send_json_success(array(
                'message' => __('Access granted! Redirecting...', 'woo-protect'),
                'redirect' => esc_url($redirect_url),
            ));
        } else {
            // Password incorrect
            wp_send_json_error(array(
                'message' => __('Incorrect password. Please try again.', 'woo-protect'),
            ));
        }
    }

    /**
     * Unlock category by storing in session
     *
     * @param int $category_id
     */
    private function unlock_category($category_id) {
        $unlocked = self::get_sanitized_unlocked_categories();
        $unlocked[absint($category_id)] = time();

        self::persist_unlocked_categories($unlocked);
    }

    /**
     * Check if category is unlocked
     *
     * @param int $category_id
     * @return bool
     */
    public function is_category_unlocked($category_id) {
        // Simply check if category is in the unlocked array
        // No time limit - once unlocked, stays unlocked for the session
        $unlocked = $this->get_sanitized_unlocked_categories();
        return isset($unlocked[absint($category_id)]);
    }

    /**
     * Clear all unlocked categories from session
     */
    public function clear_session() {
        self::persist_unlocked_categories(array());
    }

    /**
     * Get all unlocked category IDs
     *
     * @return array
     */
    public function get_unlocked_categories() {
        // Return all unlocked categories without time limit
        return array_keys($this->get_sanitized_unlocked_categories());
    }

    /**
     * Get sanitized unlocked categories map from session.
     *
     * @return array<int,int>
     */
    public static function get_sanitized_unlocked_categories() {
        $stored = self::get_raw_unlocked_categories();

        if (!is_array($stored) || empty($stored)) {
            return array();
        }

        $sanitized = array();

        foreach ($stored as $cat_id => $timestamp) {
            $sanitized[absint($cat_id)] = absint($timestamp);
        }

        return $sanitized;
    }

    /**
     * Read unlocked categories from the active storage backend.
     *
     * Prefer WooCommerce session so the plugin does not force native PHP sessions.
     *
     * @return array
     */
    private static function get_raw_unlocked_categories() {
        if (function_exists('WC')) {
            $wc = WC();
            if ($wc && isset($wc->session) && $wc->session) {
                $stored = $wc->session->get('woo_protect_unlocked', array());
                return is_array($stored) ? $stored : array();
            }
        }

        if (isset($_SESSION['woo_protect_unlocked']) && is_array($_SESSION['woo_protect_unlocked'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            return $_SESSION['woo_protect_unlocked'];
        }

        return array();
    }

    /**
     * Persist unlocked categories to the active storage backend.
     *
     * @param array<int,int> $unlocked Unlocked categories map.
     */
    private static function persist_unlocked_categories($unlocked) {
        if (function_exists('WC')) {
            $wc = WC();
            if ($wc && isset($wc->session) && $wc->session) {
                $wc->session->set('woo_protect_unlocked', $unlocked);
                return;
            }
        }

        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = array();
        }

        $_SESSION['woo_protect_unlocked'] = $unlocked;
    }
}
