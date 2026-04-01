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

        // Filter product exposure via REST endpoints.
        add_filter('rest_product_query', array($this, 'filter_rest_product_query'), 10, 2);
        add_filter('woocommerce_rest_product_object_query', array($this, 'filter_rest_product_query'), 10, 2);
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

        if (!$this->is_product_query($query)) {
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
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required to exclude protected product categories from storefront queries.
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

        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required to exclude protected product categories from WooCommerce product queries.
        $tax_query[] = array(
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => $hidden_categories,
            'operator' => 'NOT IN',
        );

        return $tax_query;
    }

    /**
     * Filter product REST API queries to avoid exposing hidden products.
     *
     * @param array           $args    Query arguments.
     * @param WP_REST_Request $request Request object.
     * @return array
     */
    public function filter_rest_product_query($args, $request) {
        unset($request);

        $hidden_categories = $this->get_hidden_category_ids();

        if (empty($hidden_categories)) {
            return $args;
        }

        if (!isset($args['tax_query']) || !is_array($args['tax_query'])) {
            $args['tax_query'] = array();
        }

        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required to exclude protected product categories from product REST queries.
        $args['tax_query'][] = array(
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => $hidden_categories,
            'operator' => 'NOT IN',
        );

        return $args;
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
            if (!has_term($hidden_categories, 'product_cat', $post_id)) {
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
        return Woo_Protect_Admin_Settings::get_protected_category_ids();
    }

    /**
     * Get unlocked category IDs from session
     *
     * @return array
     */
    private function get_unlocked_category_ids() {
        $stored = Woo_Protect_Password_Handler::get_sanitized_unlocked_categories();

        if (empty($stored)) {
            return array();
        }

        $unlocked = array();
        foreach ($stored as $cat_id => $timestamp) {
            $unlocked[absint($cat_id)] = absint($timestamp);
        }
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
     * Determine whether the current query should be filtered as a product listing.
     *
     * @param WP_Query $query Query instance.
     * @return bool
     */
    private function is_product_query($query) {
        $post_type = $query->get('post_type');

        if ('product' === $post_type) {
            return true;
        }

        if (is_array($post_type) && in_array('product', $post_type, true)) {
            return true;
        }

        return (
            $query->is_post_type_archive('product') ||
            $query->is_tax('product_cat') ||
            $query->is_tax('product_tag') ||
            $query->is_search()
        );
    }

    /**
     * Check whether the public lock-screen assets are needed on the current request.
     *
     * @return bool
     */
    public static function should_enqueue_public_assets() {
        if (is_admin() || wp_doing_ajax()) {
            return false;
        }

        return self::get_requested_protected_category() instanceof WP_Term;
    }

    /**
     * Get the protected category involved in the current request, if any.
     *
     * @return WP_Term|null
     */
    private static function get_requested_protected_category() {
        if (is_product_category()) {
            $category = get_queried_object();

            if (
                $category instanceof WP_Term &&
                Woo_Protect_Admin_Settings::is_category_protected($category->term_id)
            ) {
                return $category;
            }
        }

        if (is_product()) {
            $product_id = get_queried_object_id();

            if ($product_id > 0) {
                $product_categories = wp_get_post_terms($product_id, 'product_cat');

                if (!is_wp_error($product_categories)) {
                    foreach ($product_categories as $category) {
                        if (
                            $category instanceof WP_Term &&
                            Woo_Protect_Admin_Settings::is_category_protected($category->term_id)
                        ) {
                            return $category;
                        }
                    }
                }
            }
        }

        return null;
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
