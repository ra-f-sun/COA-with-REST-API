<?php
/**
 * Plugin Name: COA Plugin with REST API - woo
 * Description: Custom category and fields for WooCommerce products with REST API
 * Version: 1.1.1
 * Author: Rafsun Jani
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// Define constants
define('COA_VERSION', '1.1.1');
define('COA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('COA_PLUGIN_URL', plugin_dir_url(__FILE__));

// Check if WooCommerce is active
add_action('admin_init', 'coa_check_woocommerce');
function coa_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'coa_woocommerce_notice');
        deactivate_plugins(plugin_basename(__FILE__));
    }
}

function coa_woocommerce_notice() {
    echo '<div class="error"><p><strong>COA Plugin</strong> requires WooCommerce to be installed and active.</p></div>';
}

// Enqueue admin assets (CSS & JS)
add_action('admin_enqueue_scripts', 'coa_enqueue_admin_assets');
function coa_enqueue_admin_assets($hook) {
    // Only load on product pages and COA management page
    $allowed_hooks = array('product_page_coa-management', 'post.php', 'post-new.php', 'edit.php');
    
    if (!in_array($hook, $allowed_hooks)) {
        return;
    }
    
    // Check if we're on a product edit page
    global $post_type;
    if (($hook === 'post.php' || $hook === 'post-new.php' || $hook === 'edit.php') && $post_type !== 'product') {
        return;
    }
    
    // Enqueue CSS
    wp_enqueue_style(
        'coa-admin-style',
        COA_PLUGIN_URL . 'assets/css/admin-style.css',
        array(),
        COA_VERSION
    );

    // Enqueue JS
    wp_enqueue_script(
        'coa-admin-script',
        COA_PLUGIN_URL . 'assets/js/admin-script.js',
        array('jquery'),
        COA_VERSION,
        true
    );
}

// Include plugin files
require_once COA_PLUGIN_DIR . 'includes/admin-page.php';
require_once COA_PLUGIN_DIR . 'includes/simple-products.php';
require_once COA_PLUGIN_DIR . 'includes/variable-products.php';
require_once COA_PLUGIN_DIR . 'includes/admin-filters.php';
require_once COA_PLUGIN_DIR . 'includes/rest-api.php';