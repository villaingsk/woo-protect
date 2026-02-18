<?php
/**
 * Admin Settings Class
 *
 * @package Woo_Protect
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Woo_Protect_Admin_Settings Class
 */
class Woo_Protect_Admin_Settings {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'), 99);
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_woo_protect_save_settings', array($this, 'ajax_save_settings'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Woo-Protect Settings', 'woo-protect'),
            __('Woo-Protect', 'woo-protect'),
            'manage_woocommerce',
            'woo-protect-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('woo_protect_settings_group', 'woo_protect_settings');
        register_setting('woo_protect_settings_group', 'woo_protect_categories');
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'woo-protect'));
        }

        // Get current settings
        $settings = Woo_Protect::get_instance()->get_settings();
        $protected_categories = Woo_Protect::get_instance()->get_protected_categories();

        // Get all product categories
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ));

        // Include template
        include WOO_PROTECT_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    /**
     * AJAX save settings
     */
    public function ajax_save_settings() {
        // Check nonce
        check_ajax_referer('woo_protect_admin_nonce', 'nonce');

        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'woo-protect')));
        }

        // Get posted data
        $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
        $categories = isset($_POST['categories']) ? $_POST['categories'] : array();

        // Sanitize settings
        $sanitized_settings = array(
            'lock_screen_title' => sanitize_text_field($settings['lock_screen_title'] ?? ''),
            'lock_screen_message' => sanitize_textarea_field($settings['lock_screen_message'] ?? ''),
            'session_duration' => absint($settings['session_duration'] ?? 24),
            'redirect_url' => esc_url_raw($settings['redirect_url'] ?? ''),
        );

        // Sanitize categories
        $sanitized_categories = array();
        if (is_array($categories)) {
            foreach ($categories as $cat_id => $cat_data) {
                $cat_id = absint($cat_id);
                if ($cat_id > 0 && isset($cat_data['enabled']) && $cat_data['enabled'] === 'yes') {
                    $password = sanitize_text_field($cat_data['password'] ?? '');
                    
                    // Only update password if a new one is provided
                    if (!empty($password)) {
                        // Hash the password for verification
                        $hashed_password = wp_hash_password($password);
                        
                        $sanitized_categories[$cat_id] = array(
                            'enabled' => 'yes',
                            'password' => $hashed_password,
                        );

                        // Save to term meta
                        update_term_meta($cat_id, '_woo_protect_enabled', 'yes');
                        update_term_meta($cat_id, '_woo_protect_password', $hashed_password);
                        // Store plain text password for display purposes only
                        update_term_meta($cat_id, '_woo_protect_password_display', $password);
                    } else {
                        // Keep existing password if field is empty
                        $existing_password = get_term_meta($cat_id, '_woo_protect_password', true);
                        if (!empty($existing_password)) {
                            $sanitized_categories[$cat_id] = array(
                                'enabled' => 'yes',
                                'password' => $existing_password,
                            );
                            update_term_meta($cat_id, '_woo_protect_enabled', 'yes');
                        }
                    }
                } else {
                    // Remove protection
                    delete_term_meta($cat_id, '_woo_protect_enabled');
                    delete_term_meta($cat_id, '_woo_protect_password');
                    delete_term_meta($cat_id, '_woo_protect_password_display');
                }
            }
        }

        // Save settings
        update_option('woo_protect_settings', $sanitized_settings);
        update_option('woo_protect_categories', $sanitized_categories);

        wp_send_json_success(array(
            'message' => __('Settings saved successfully!', 'woo-protect'),
        ));
    }

    /**
     * Get category password (hashed)
     *
     * @param int $category_id Category ID
     * @return string|false
     */
    public static function get_category_password($category_id) {
        return get_term_meta($category_id, '_woo_protect_password', true);
    }

    /**
     * Check if category is protected
     *
     * @param int $category_id Category ID
     * @return bool
     */
    public static function is_category_protected($category_id) {
        $enabled = get_term_meta($category_id, '_woo_protect_enabled', true);
        $password = get_term_meta($category_id, '_woo_protect_password', true);
        
        return ($enabled === 'yes' && !empty($password));
    }

    /**
     * Get category password for display (plain text)
     *
     * @param int $category_id Category ID
     * @return string|false
     */
    public static function get_category_password_display($category_id) {
        return get_term_meta($category_id, '_woo_protect_password_display', true);
    }
}
