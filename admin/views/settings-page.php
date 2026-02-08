<?php
/**
 * Admin Settings Page Template
 *
 * @package Woo_Protect
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap woo-protect-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors('woo_protect_messages'); ?>

    <div class="woo-protect-layout">
        <!-- Main Content -->
        <div class="woo-protect-main">
            <form method="post" action="" id="woo-protect-settings-form">
                <?php wp_nonce_field('woo_protect_save_settings', 'woo_protect_nonce'); ?>

                <!-- Protected Categories Section -->
                <div class="woo-protect-section">
                    <h2><?php esc_html_e('Protected Categories', 'woo-protect'); ?></h2>
                    <p class="description">
                        <?php esc_html_e('Select categories to protect with password. Customers must enter the correct password to view products in these categories.', 'woo-protect'); ?>
                    </p>

                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 50px;"><?php esc_html_e('Enable', 'woo-protect'); ?></th>
                                <th><?php esc_html_e('Category', 'woo-protect'); ?></th>
                                <th><?php esc_html_e('Password', 'woo-protect'); ?></th>
                                <th style="width: 100px;"><?php esc_html_e('Products', 'woo-protect'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($categories)) : ?>
                                <?php foreach ($categories as $category) : ?>
                                    <?php
                                    $is_protected = Woo_Protect_Admin_Settings::is_category_protected($category->term_id);
                                    $current_password = Woo_Protect_Admin_Settings::get_category_password_display($category->term_id);
                                    $product_count = $category->count;
                                    ?>
                                    <tr>
                                        <td class="check-column">
                                            <label class="woo-protect-toggle">
                                                <input type="checkbox" 
                                                       name="categories[<?php echo esc_attr($category->term_id); ?>][enabled]" 
                                                       value="yes"
                                                       class="category-toggle"
                                                       data-category-id="<?php echo esc_attr($category->term_id); ?>"
                                                       <?php checked($is_protected, true); ?>>
                                                <span class="slider"></span>
                                            </label>
                                        </td>
                                        <td>
                                            <strong><?php echo esc_html($category->name); ?></strong>
                                            <?php if ($category->parent) : ?>
                                                <?php
                                                $parent = get_term($category->parent, 'product_cat');
                                                if ($parent && !is_wp_error($parent)) {
                                                    echo '<br><span class="description">' . esc_html__('Parent:', 'woo-protect') . ' ' . esc_html($parent->name) . '</span>';
                                                }
                                                ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="password-field-wrapper">
                                                <input type="password" 
                                                       name="categories[<?php echo esc_attr($category->term_id); ?>][password]" 
                                                       class="regular-text password-input"
                                                       placeholder="<?php esc_attr_e('Enter new password', 'woo-protect'); ?>"
                                                       value="<?php echo $is_protected && $current_password ? esc_attr($current_password) : ''; ?>"
                                                       data-category-id="<?php echo esc_attr($category->term_id); ?>"
                                                       <?php echo $is_protected ? '' : 'disabled'; ?>>
                                                <button type="button" class="button toggle-password" data-category-id="<?php echo esc_attr($category->term_id); ?>">
                                                    <span class="dashicons dashicons-visibility"></span>
                                                </button>
                                            </div>
                                            <?php if ($is_protected && $current_password) : ?>
                                                <p class="description" style="color: #2271b1;">
                                                    <span class="dashicons dashicons-yes-alt" style="font-size: 16px; width: 16px; height: 16px;"></span>
                                                    <?php esc_html_e('Password can be used unlimited times', 'woo-protect'); ?>
                                                </p>
                                            <?php else : ?>
                                                <p class="description">
                                                    <?php esc_html_e('Leave blank to keep existing password', 'woo-protect'); ?>
                                                </p>
                                            <?php endif; ?>
                                        </td>
                                        <td class="num">
                                            <?php echo esc_html($product_count); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="4">
                                        <?php esc_html_e('No product categories found.', 'woo-protect'); ?>
                                        <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=product_cat&post_type=product')); ?>">
                                            <?php esc_html_e('Create a category', 'woo-protect'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Lock Screen Settings Section -->
                <div class="woo-protect-section">
                    <h2><?php esc_html_e('Lock Screen Settings', 'woo-protect'); ?></h2>
                    <p class="description">
                        <?php esc_html_e('Customize the password form that customers will see when accessing protected categories.', 'woo-protect'); ?>
                    </p>

                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="lock_screen_title">
                                        <?php esc_html_e('Lock Screen Title', 'woo-protect'); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="lock_screen_title" 
                                           name="settings[lock_screen_title]" 
                                           value="<?php echo esc_attr($settings['lock_screen_title']); ?>" 
                                           class="regular-text">
                                    <p class="description">
                                        <?php esc_html_e('The title displayed on the password form.', 'woo-protect'); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="lock_screen_message">
                                        <?php esc_html_e('Lock Screen Message', 'woo-protect'); ?>
                                    </label>
                                </th>
                                <td>
                                    <textarea id="lock_screen_message" 
                                              name="settings[lock_screen_message]" 
                                              rows="4" 
                                              class="large-text"><?php echo esc_textarea($settings['lock_screen_message']); ?></textarea>
                                    <p class="description">
                                        <?php esc_html_e('The message displayed below the title.', 'woo-protect'); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="session_duration">
                                        <?php esc_html_e('Session Duration', 'woo-protect'); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="number" 
                                           id="session_duration" 
                                           name="settings[session_duration]" 
                                           value="<?php echo esc_attr($settings['session_duration']); ?>" 
                                           min="1" 
                                           max="8760"
                                           class="small-text"> 
                                    <?php esc_html_e('hours', 'woo-protect'); ?>
                                    <p class="description">
                                        <?php esc_html_e('How long customers can access protected categories after entering the password (1-8760 hours = 1 year max).', 'woo-protect'); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="redirect_url">
                                        <?php esc_html_e('Redirect URL (Optional)', 'woo-protect'); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="url" 
                                           id="redirect_url" 
                                           name="settings[redirect_url]" 
                                           value="<?php echo esc_url($settings['redirect_url']); ?>" 
                                           class="regular-text"
                                           placeholder="<?php echo esc_attr(wc_get_page_permalink('shop')); ?>">
                                    <p class="description">
                                        <?php esc_html_e('Where to redirect if user cancels password entry. Leave blank to use shop page.', 'woo-protect'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php submit_button(__('Save Settings', 'woo-protect'), 'primary', 'submit', true); ?>
            </form>
        </div>

        <!-- Sidebar -->
        <div class="woo-protect-sidebar">
            <!-- Help Section -->
            <div class="woo-protect-section woo-protect-help">
                <h2><?php esc_html_e('How It Works', 'woo-protect'); ?></h2>
                <ol>
                    <li><?php esc_html_e('Enable protection for one or more categories by toggling the switch.', 'woo-protect'); ?></li>
                    <li><?php esc_html_e('Set a unique password for each protected category.', 'woo-protect'); ?></li>
                    <li><?php esc_html_e('Products in protected categories will be hidden from shop pages, search results, and related products.', 'woo-protect'); ?></li>
                    <li><?php esc_html_e('When customers try to access a protected category, they will see a password form.', 'woo-protect'); ?></li>
                    <li><?php esc_html_e('After entering the correct password, they can view all products in that category.', 'woo-protect'); ?></li>
                    <li><?php esc_html_e('Password can be used unlimited times within the session duration period.', 'woo-protect'); ?></li>
                </ol>

                <div class="woo-protect-info-box">
                    <span class="dashicons dashicons-info"></span>
                    <strong><?php esc_html_e('Security Note:', 'woo-protect'); ?></strong>
                    <?php esc_html_e('Passwords are encrypted using WordPress security functions. Each category can have a different password. Access remains valid for the configured session duration.', 'woo-protect'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

