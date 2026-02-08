<?php
/**
 * Category Protection Class
 *
 * @package Woo_Protect
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Woo_Protect_Category_Protection Class
 */
class Woo_Protect_Category_Protection {

    /**
     * Constructor
     */
    public function __construct() {
        // Intercept category page access
        add_action('template_redirect', array($this, 'check_category_access'), 1);

        // Filter product queries
        add_action('pre_get_posts', array($this, 'filter_product_query'));

        // Hide protected products from widgets
        add_filter('woocommerce_product_query_tax_query', array($this, 'filter_product_tax_query'), 10, 2);

        // Filter related products
        add_filter('woocommerce_related_products', array($this, 'filter_related_products'), 10, 3);
    }

    /**
     * Check category access on category pages and single product pages
     */
    public function check_category_access() {
        // Check product category pages
        if (is_product_category()) {
            $category = get_queried_object();
            
            if (!$category || !isset($category->term_id)) {
                return;
            }

            // Check if category is protected
            if (!Woo_Protect_Admin_Settings::is_category_protected($category->term_id)) {
                return;
            }

            // Prevent caching of protected pages (security measure)
            nocache_headers();
            if (!headers_sent()) {
                header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
                header('Pragma: no-cache');
                header('Expires: 0');
            }

            // Check if user has unlocked this category
            if ($this->is_category_unlocked($category->term_id)) {
                return;
            }

            // Display password form
            $this->display_password_form($category);
            exit;
        }

        // Check single product pages
        if (is_product()) {
            global $post;
            
            if (!$post) {
                return;
            }

            // Get product categories
            $product_categories = wp_get_post_terms($post->ID, 'product_cat', array('fields' => 'ids'));
            
            if (empty($product_categories)) {
                return;
            }

            // Check if any category is protected
            foreach ($product_categories as $cat_id) {
                if (Woo_Protect_Admin_Settings::is_category_protected($cat_id)) {
                    // Prevent caching of protected product pages
                    nocache_headers();
                    if (!headers_sent()) {
                        header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
                        header('Pragma: no-cache');
                        header('Expires: 0');
                    }

                    // Check if user has unlocked this category
                    if (!$this->is_category_unlocked($cat_id)) {
                        // Get category object for display
                        $category = get_term($cat_id, 'product_cat');
                        
                        if ($category && !is_wp_error($category)) {
                            // Display password form
                            $this->display_password_form($category);
                            exit;
                        }
                    }
                }
            }
        }
    }

    /**
     * Filter product query to hide protected products
     *
     * @param WP_Query $query
     */
    public function filter_product_query($query) {
        // Only filter main query on frontend
        if (is_admin() || !$query->is_main_query()) {
            return;
        }

        // Only filter product queries
        if (!isset($query->query_vars['post_type']) || $query->query_vars['post_type'] !== 'product') {
            return;
        }

        $hidden_categories = $this->get_hidden_category_ids();

        if (empty($hidden_categories)) {
            return;
        }

        // Get existing tax query
        $tax_query = $query->get('tax_query');
        if (!is_array($tax_query)) {
            $tax_query = array();
        }

        // Add our exclusion
        $tax_query[] = array(
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => $hidden_categories,
            'operator' => 'NOT IN',
        );

        $query->set('tax_query', $tax_query);
    }

    /**
     * Filter product tax query for WooCommerce
     *
     * @param array $tax_query
     * @param WP_Query $query
     * @return array
     */
    public function filter_product_tax_query($tax_query, $query) {
        $hidden_categories = $this->get_hidden_category_ids();

        if (empty($hidden_categories)) {
            return $tax_query;
        }

        $tax_query[] = array(
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => $hidden_categories,
            'operator' => 'NOT IN',
        );

        return $tax_query;
    }

    /**
     * Filter related products
     *
     * @param array $related_posts
     * @param int $product_id
     * @param array $args
     * @return array
     */
    public function filter_related_products($related_posts, $product_id, $args) {
        if (empty($related_posts)) {
            return $related_posts;
        }

        $hidden_categories = $this->get_hidden_category_ids();

        if (empty($hidden_categories)) {
            return $related_posts;
        }

        // Filter out products from hidden categories
        $filtered_posts = array();
        foreach ($related_posts as $post_id) {
            $product_categories = wp_get_post_terms($post_id, 'product_cat', array('fields' => 'ids'));
            
            // Check if product has any hidden category
            $has_hidden = array_intersect($product_categories, $hidden_categories);
            
            if (empty($has_hidden)) {
                $filtered_posts[] = $post_id;
            }
        }

        return $filtered_posts;
    }

    /**
     * Get hidden category IDs (protected but not unlocked)
     *
     * @return array
     */
    private function get_hidden_category_ids() {
        $protected_categories = $this->get_protected_category_ids();
        $unlocked_categories = $this->get_unlocked_category_ids();

        return array_diff($protected_categories, $unlocked_categories);
    }

    /**
     * Get all protected category IDs
     *
     * @return array
     */
    private function get_protected_category_ids() {
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => '_woo_protect_enabled',
                    'value' => 'yes',
                ),
            ),
            'fields' => 'ids',
        ));

        return is_array($categories) ? $categories : array();
    }

    /**
     * Get unlocked category IDs from session
     *
     * @return array
     */
    private function get_unlocked_category_ids() {
        if (!isset($_SESSION['woo_protect_unlocked'])) {
            return array();
        }

        $unlocked = $_SESSION['woo_protect_unlocked'];
        $settings = Woo_Protect::get_instance()->get_settings();
        $session_duration = absint($settings['session_duration']) * HOUR_IN_SECONDS;
        $current_time = time();

        $valid_unlocked = array();

        foreach ($unlocked as $cat_id => $timestamp) {
            // Check if session is still valid
            if (($current_time - $timestamp) < $session_duration) {
                $valid_unlocked[] = absint($cat_id);
            }
        }

        return $valid_unlocked;
    }

    /**
     * Check if category is unlocked
     *
     * @param int $category_id
     * @return bool
     */
    private function is_category_unlocked($category_id) {
        $unlocked = $this->get_unlocked_category_ids();
        return in_array(absint($category_id), $unlocked, true);
    }

    /**
     * Display password form
     *
     * @param WP_Term $category
     */
    private function display_password_form($category) {
        $settings = Woo_Protect::get_instance()->get_settings();
        
        // Get header
        get_header();

        // Include password form template
        include WOO_PROTECT_PLUGIN_DIR . 'public/templates/password-form.php';

        // Get footer
        get_footer();
    }
}
