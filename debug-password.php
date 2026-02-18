<?php
/**
 * Debug script to check password storage
 * Place this file in wp-content/plugins/woo-protect/ and access via browser
 */

// Load WordPress
require_once('../../../wp-load.php');
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo "<h1>Woo-Protect Password Debug</h1>";

// Get all product categories
$categories = get_terms(array(
    'taxonomy' => 'product_cat',
    'hide_empty' => false,
));

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Category ID</th><th>Category Name</th><th>Protected</th><th>Password Hash</th><th>Password Display</th></tr>";

foreach ($categories as $category) {
    $is_protected = get_term_meta($category->term_id, '_woo_protect_enabled', true);
    $password_hash = get_term_meta($category->term_id, '_woo_protect_password', true);
    $password_display = get_term_meta($category->term_id, '_woo_protect_password_display', true);
    
    echo "<tr>";
    echo "<td>" . esc_html($category->term_id) . "</td>";
    echo "<td>" . esc_html($category->name) . "</td>";
    echo "<td>" . ($is_protected === 'yes' ? 'YES' : 'NO') . "</td>";
    echo "<td>" . (empty($password_hash) ? '<em>empty</em>' : esc_html(substr($password_hash, 0, 30)) . '...') . "</td>";
    echo "<td>" . (empty($password_display) ? '<em>empty</em>' : '<strong>' . esc_html($password_display) . '</strong>') . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h2>Instructions:</h2>";
echo "<ol>";
echo "<li>Go to WooCommerce → Woo-Protect</li>";
echo "<li>Enable protection for a category</li>";
echo "<li>Enter a password (e.g., 'test123')</li>";
echo "<li>Click Save Settings</li>";
echo "<li>Refresh this page to see if password is stored</li>";
echo "</ol>";
