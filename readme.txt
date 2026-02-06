=== Woo-Protect ===
Contributors: krefstudio
Tags: woocommerce, password, category, protection, security
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Protect WooCommerce product categories with password authentication. Customers must enter the correct password to view protected category products.

== Description ==

**Woo-Protect** is a powerful WooCommerce extension that allows you to protect specific product categories with password authentication. This is perfect for:

* **Exclusive Product Lines** - Restrict access to premium or VIP products
* **Wholesale Categories** - Require password for wholesale pricing tiers
* **Pre-launch Products** - Share new products with select customers only
* **Private Collections** - Create password-protected product collections
* **Member-only Categories** - Limit category access to specific groups

= Key Features =

✅ **Password Protection per Category** - Each category can have a unique password
✅ **Full Product Protection** - Protects both category pages AND individual product pages
✅ **Flexible Access Control** - Choose which categories to protect
✅ **Session-based Authentication** - Customers stay logged in for a configurable duration
✅ **Custom Lock Screen** - Fully customizable password entry page
✅ **Complete Product Hiding** - Protected products are hidden from:
   - Shop pages
   - Search results
   - Related products
   - Category archives
   - Widgets
   - Direct URL access

✅ **User-friendly Admin Interface** - Easy-to-use settings page in WooCommerce
✅ **AJAX-powered** - Smooth password verification without page reloads
✅ **Secure** - Passwords are encrypted using WordPress security functions
✅ **Mobile Responsive** - Works perfectly on all devices
✅ **Translation Ready** - Fully translatable

= How It Works =

1. Navigate to WooCommerce → Woo-Protect in your WordPress admin
2. Enable protection for one or more product categories
3. Set a unique password for each protected category
4. Customize the lock screen title and message
5. Save your settings

When customers try to access a protected category, they'll see a beautiful password form. After entering the correct password, they can view all products in that category for the duration you specify (default: 24 hours).

= Perfect For =

* **E-commerce Stores** with exclusive product lines
* **Wholesale Businesses** with tiered pricing
* **Membership Sites** offering member-only products
* **B2B Stores** with private catalogs
* **Boutique Shops** with VIP collections

= Developer Friendly =

Woo-Protect is built with clean, well-documented code following WordPress and WooCommerce best practices. It includes:

* Action and filter hooks for customization
* Template override support
* HPOS (High-Performance Order Storage) compatible
* Translation-ready with .pot file included

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Go to Plugins → Add New
3. Search for "Woo-Protect"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Go to Plugins → Add New → Upload Plugin
4. Choose the ZIP file and click "Install Now"
5. Activate the plugin

= After Activation =

1. Make sure WooCommerce is installed and activated
2. Go to WooCommerce → Woo-Protect
3. Configure your protected categories and passwords
4. Save your settings

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =

Yes, Woo-Protect is a WooCommerce extension and requires WooCommerce to be installed and activated.

= Can I protect multiple categories? =

Yes! You can protect as many categories as you want, each with its own unique password.

= How long do customers stay logged in? =

By default, customers can access protected categories for 24 hours after entering the password. You can customize this duration in the settings (1-168 hours).

= Are the passwords secure? =

Yes! All passwords are encrypted using WordPress's built-in security functions (`wp_hash_password`). They are never stored in plain text.

= Can I customize the password form? =

Yes! You can customize the title, message, and redirect URL in the settings. For advanced customization, you can override the template file in your theme.

= Will protected products appear in search results? =

No. Products from protected categories are completely hidden from search results, shop pages, related products, and widgets. Additionally, individual product pages cannot be accessed directly via URL without entering the password first.

= What happens if a customer enters the wrong password? =

They'll see an error message and can try again. There's no limit to password attempts.

= Can I use this with other WooCommerce plugins? =

Yes! Woo-Protect is designed to work seamlessly with other WooCommerce extensions.

= Is it translation ready? =

Yes! The plugin includes a .pot file and is fully translation-ready.

= Does it work with HPOS? =

Yes! Woo-Protect is compatible with WooCommerce's High-Performance Order Storage (HPOS).

== Screenshots ==

1. Admin settings page - Category list with password protection toggles
2. Lock screen customization settings
3. Frontend password form - Modern, responsive design
4. Protected category successfully unlocked
5. Admin interface showing protected categories

== Changelog ==

= 1.1.0 - 2026-02-06 =
* Added: Single product page protection - products in protected categories now require password even when accessed directly via URL
* Added: 2-column admin layout with sidebar for better organization
* Improved: Toggle switch styling and spacing in admin settings
* Improved: Admin UI with better padding and responsive design
* Security: Closed direct product access vulnerability - full category protection now enforced

= 1.0.0 - 2026-02-06 =
* Initial release
* Password protection for WooCommerce categories
* Session-based authentication
* Customizable lock screen
* AJAX-powered password verification
* Complete product hiding from shop/search
* Admin settings interface
* Translation ready
* HPOS compatible

== Upgrade Notice ==

= 1.1.0 =
Important security update! Single product pages are now protected. Products in protected categories can no longer be accessed directly via URL without password.

= 1.0.0 =
Initial release of Woo-Protect. Protect your WooCommerce categories with password authentication!

== Support ==

For support, feature requests, or bug reports, please visit [Kref Studio](https://krefstudio.com/support).

== Privacy Policy ==

Woo-Protect does not collect or store any personal data. Password verification is handled via PHP sessions, which are temporary and deleted when the browser is closed or the session expires.
