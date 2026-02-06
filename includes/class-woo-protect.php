<?php
/**
 * Main Woo-Protect Class
 *
 * @package Woo_Protect
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Woo_Protect Class
 */
class Woo_Protect {

    /**
     * Single instance of the class
     *
     * @var Woo_Protect
     */
    private static $instance = null;

    /**
     * Admin settings instance
     *
     * @var Woo_Protect_Admin_Settings
     */
    public $admin_settings;

    /**
     * Category protection instance
     *
     * @var Woo_Protect_Category_Protection
     */
    public $category_protection;

    /**
     * Password handler instance
     *
     * @var Woo_Protect_Password_Handler
     */
    public $password_handler;

    /**
     * Get single instance
     *
     * @return Woo_Protect
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->init_classes();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Start session if not already started
        add_action('init', array($this, 'start_session'), 1);

        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'public_enqueue_scripts'));

        // Add WooCommerce compatibility
        add_action('before_woocommerce_init', array($this, 'declare_compatibility'));
    }

    /**
     * Initialize plugin classes
     */
    private function init_classes() {
        // Initialize admin settings
        if (is_admin()) {
            $this->admin_settings = new Woo_Protect_Admin_Settings();
        }

        // Initialize category protection
        $this->category_protection = new Woo_Protect_Category_Protection();

        // Initialize password handler
        $this->password_handler = new Woo_Protect_Password_Handler();
    }

    /**
     * Start PHP session
     */
    public function start_session() {
        if (!session_id() && !headers_sent()) {
            session_start();
        }
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook
     */
    public function admin_enqueue_scripts($hook) {
        // Only load on our settings page
        if ('woocommerce_page_woo-protect-settings' !== $hook) {
            return;
        }

        // Enqueue admin CSS
        wp_enqueue_style(
            'woo-protect-admin',
            WOO_PROTECT_PLUGIN_URL . 'admin/css/admin-styles.css',
            array(),
            WOO_PROTECT_VERSION
        );

        // Enqueue admin JS
        wp_enqueue_script(
            'woo-protect-admin',
            WOO_PROTECT_PLUGIN_URL . 'admin/js/admin-scripts.js',
            array('jquery'),
            WOO_PROTECT_VERSION,
            true
        );

        // Localize script
        wp_localize_script('woo-protect-admin', 'wooProtectAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('woo_protect_admin_nonce'),
            'strings' => array(
                'saveSuccess' => __('Settings saved successfully!', 'woo-protect'),
                'saveError' => __('Error saving settings. Please try again.', 'woo-protect'),
            ),
        ));
    }

    /**
     * Enqueue public scripts and styles
     */
    public function public_enqueue_scripts() {
        // Enqueue public CSS
        wp_enqueue_style(
            'woo-protect-public',
            WOO_PROTECT_PLUGIN_URL . 'public/css/public-styles.css',
            array(),
            WOO_PROTECT_VERSION
        );

        // Enqueue public JS
        wp_enqueue_script(
            'woo-protect-public',
            WOO_PROTECT_PLUGIN_URL . 'public/js/public-scripts.js',
            array('jquery'),
            WOO_PROTECT_VERSION,
            true
        );

        // Localize script
        wp_localize_script('woo-protect-public', 'wooProtectPublic', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('woo_protect_public_nonce'),
            'strings' => array(
                'verifying' => __('Verifying password...', 'woo-protect'),
                'wrongPassword' => __('Incorrect password. Please try again.', 'woo-protect'),
                'errorOccurred' => __('An error occurred. Please try again.', 'woo-protect'),
            ),
        ));
    }

    /**
     * Declare WooCommerce HPOS compatibility
     */
    public function declare_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                WOO_PROTECT_PLUGIN_BASENAME,
                true
            );
        }
    }

    /**
     * Get plugin settings
     *
     * @return array
     */
    public function get_settings() {
        $defaults = array(
            'lock_screen_title' => __('Protected Category', 'woo-protect'),
            'lock_screen_message' => __('This category is password protected. Please enter the password to continue.', 'woo-protect'),
            'session_duration' => 24,
            'redirect_url' => '',
        );

        $settings = get_option('woo_protect_settings', array());
        return wp_parse_args($settings, $defaults);
    }

    /**
     * Get protected categories
     *
     * @return array Array of category IDs
     */
    public function get_protected_categories() {
        return get_option('woo_protect_categories', array());
    }
}
