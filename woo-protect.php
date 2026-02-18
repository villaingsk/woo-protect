<?php
/**
 * Plugin Name: Woo-Protect
 * Plugin URI: https://krefstudio.com/woo-protect
 * Description: Protect WooCommerce product categories with password authentication. Customers must enter the correct password to view protected category products.
 * Version: 1.2.1
 * Author: Kref Studio
 * Author URI: https://krefstudio.com
 * Text Domain: woo-protect
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.5
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WOO_PROTECT_VERSION', '1.2.1');
define('WOO_PROTECT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WOO_PROTECT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WOO_PROTECT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Check if WooCommerce is active
 */
function woo_protect_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'woo_protect_woocommerce_missing_notice');
        deactivate_plugins(plugin_basename(__FILE__));
        return false;
    }
    return true;
}

/**
 * Display admin notice if WooCommerce is not active
 */
function woo_protect_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <?php 
            echo wp_kses_post(
                sprintf(
                    /* translators: %s: WooCommerce installation URL */
                    __('<strong>Woo-Protect</strong> requires WooCommerce to be installed and active. Please <a href="%s">install WooCommerce</a> first.', 'woo-protect'),
                    admin_url('plugin-install.php?s=woocommerce&tab=search&type=term')
                )
            );
            ?>
        </p>
    </div>
    <?php
}

/**
 * Initialize the plugin
 */
function woo_protect_init() {
    // Check WooCommerce dependency
    if (!woo_protect_check_woocommerce()) {
        return;
    }

    // Load plugin text domain
    load_plugin_textdomain('woo-protect', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Include required files
    require_once WOO_PROTECT_PLUGIN_DIR . 'includes/class-woo-protect.php';
    require_once WOO_PROTECT_PLUGIN_DIR . 'includes/class-admin-settings.php';
    require_once WOO_PROTECT_PLUGIN_DIR . 'includes/class-category-protection.php';
    require_once WOO_PROTECT_PLUGIN_DIR . 'includes/class-password-handler.php';
    require_once WOO_PROTECT_PLUGIN_DIR . 'includes/class-cache-compatibility.php';

    // Initialize the main plugin class
    Woo_Protect::get_instance();
    
    // Initialize cache compatibility
    new Woo_Protect_Cache_Compatibility();
}
add_action('plugins_loaded', 'woo_protect_init');

/**
 * Plugin activation hook
 */
function woo_protect_activate() {
    // Check WooCommerce is active
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            wp_kses_post(__('Woo-Protect requires WooCommerce to be installed and active. Please install WooCommerce first.', 'woo-protect')),
            'Plugin Activation Error',
            array('back_link' => true)
        );
    }

    // Set default options
    $default_options = array(
        'lock_screen_title' => __('Protected Category', 'woo-protect'),
        'lock_screen_message' => __('This category is password protected. Please enter the password to continue.', 'woo-protect'),
        'session_duration' => 24,
        'redirect_url' => '',
    );

    if (!get_option('woo_protect_settings')) {
        add_option('woo_protect_settings', $default_options);
    }

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'woo_protect_activate');

/**
 * Plugin deactivation hook
 */
function woo_protect_deactivate() {
    // Clear all sessions
    if (session_id()) {
        session_destroy();
    }

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'woo_protect_deactivate');

/**
 * Add settings link on plugin page
 */
function woo_protect_plugin_action_links($links) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        admin_url('admin.php?page=woo-protect-settings'),
        __('Settings', 'woo-protect')
    );
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . WOO_PROTECT_PLUGIN_BASENAME, 'woo_protect_plugin_action_links');
