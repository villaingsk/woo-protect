<?php
/**
 * Cache Compatibility Class
 *
 * @package Woo_Protect
 * @since 1.2.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Woo_Protect_Cache_Compatibility Class
 * 
 * Handles compatibility with cache plugins like WP Rocket, W3 Total Cache, etc.
 */
class Woo_Protect_Cache_Compatibility {

    /**
     * Constructor
     */
    public function __construct() {
        // WP Rocket compatibility
        add_filter('rocket_cache_reject_uri', array($this, 'exclude_protected_categories'));
        add_filter('rocket_cache_query_strings', array($this, 'preserve_query_strings'));
        
        // W3 Total Cache compatibility
        add_filter('w3tc_can_cache', array($this, 'w3tc_exclude_protected'), 10, 2);
        
        // WP Super Cache compatibility
        add_filter('wp_super_cache_skip_cookies', array($this, 'add_session_cookie'));
        
        // Generic cache prevention
        add_action('template_redirect', array($this, 'prevent_cache_on_protected'), 1);
    }

    /**
     * Exclude protected category URLs from WP Rocket cache
     *
     * @param array $urls
     * @return array
     */
    public function exclude_protected_categories($urls) {
        $protected_categories = $this->get_protected_category_urls();
        
        foreach ($protected_categories as $url) {
            $urls[] = $url;
        }
        
        return $urls;
    }

    /**
     * Preserve query strings for WP Rocket
     *
     * @param array $query_strings
     * @return array
     */
    public function preserve_query_strings($query_strings) {
        $query_strings[] = 'woo_protect_unlock';
        return $query_strings;
    }

    /**
     * Exclude protected pages from W3 Total Cache
     *
     * @param bool $can_cache
     * @param string $buffer
     * @return bool
     */
    public function w3tc_exclude_protected($can_cache, $buffer = '') {
        if ($this->is_protected_page()) {
            return false;
        }
        return $can_cache;
    }

    /**
     * Add session cookie to WP Super Cache skip list
     *
     * @param array $cookies
     * @return array
     */
    public function add_session_cookie($cookies) {
        $cookies[] = 'PHPSESSID';
        $cookies[] = 'woo_protect_session';
        return $cookies;
    }

    /**
     * Prevent caching on protected pages
     */
    public function prevent_cache_on_protected() {
        if ($this->is_protected_page()) {
            // Set no-cache constants
            if (!defined('DONOTCACHEPAGE')) {
                define('DONOTCACHEPAGE', true);
            }
            if (!defined('DONOTCACHEDB')) {
                define('DONOTCACHEDB', true);
            }
            if (!defined('DONOTMINIFY')) {
                define('DONOTMINIFY', true);
            }
            if (!defined('DONOTCDN')) {
                define('DONOTCDN', true);
            }
            if (!defined('DONOTCACHEOBJECT')) {
                define('DONOTCACHEOBJECT', true);
            }
        }
    }

    /**
     * Check if current page is a protected page
     *
     * @return bool
     */
    private function is_protected_page() {
        // Check if it's a protected category page
        if (is_product_category()) {
            $category = get_queried_object();
            if ($category && isset($category->term_id)) {
                return Woo_Protect_Admin_Settings::is_category_protected($category->term_id);
            }
        }

        // Check if it's a product in a protected category
        if (is_product()) {
            global $post;
            if ($post) {
                $product_categories = wp_get_post_terms($post->ID, 'product_cat', array('fields' => 'ids'));
                foreach ($product_categories as $cat_id) {
                    if (Woo_Protect_Admin_Settings::is_category_protected($cat_id)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get all protected category URLs
     *
     * @return array
     */
    private function get_protected_category_urls() {
        $urls = array();
        
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => '_woo_protect_enabled',
                    'value' => 'yes',
                ),
            ),
        ));

        if (!is_wp_error($categories) && !empty($categories)) {
            foreach ($categories as $category) {
                $cat_url = get_term_link($category);
                if (!is_wp_error($cat_url)) {
                    // Convert to relative URL for cache plugins
                    $relative_url = str_replace(home_url(), '', $cat_url);
                    $urls[] = $relative_url;
                    
                    // Also add with trailing slash variant
                    $urls[] = trailingslashit($relative_url);
                    $urls[] = untrailingslashit($relative_url);
                }
            }
        }

        return array_unique($urls);
    }
}
