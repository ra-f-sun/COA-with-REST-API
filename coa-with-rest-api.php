<?php
/**
 * Plugin Name: COA Plugin with REST API - woo
 * Description: Custom category and fields for WooCommerce products with REST API
 * Version: 1.0.0
 * Author: Rafsun Jani
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// Define constants
define('COA_VERSION', '1.0.0');
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

// Add admin menu
add_action('admin_menu', 'coa_admin_menu', 99);
function coa_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=product',
        'COA Management',
        'COA Management',
        'manage_woocommerce',
        'coa-management',
        'coa_management_page'
    );
}

// Enqueue admin styles
add_action('admin_enqueue_scripts', 'coa_enqueue_admin_styles');
function coa_enqueue_admin_styles($hook) {
    if ($hook !== 'product_page_coa-management') {
        return;
    }
    
    wp_enqueue_style(
        'coa-admin-style',
        COA_PLUGIN_URL . 'assets/css/admin-style.css',
        array(),
        COA_VERSION
    );
}

// Admin page UI
function coa_management_page() {
    ?>
    <div class="wrap coa-management-page">
        <h1>COA Management</h1>
        
        <div class="coa-admin-box">
            <h2>System Information</h2>
            <table class="widefat coa-system-info-table">
                <tbody>
                    <tr>
                        <td style="width: 30%;"><strong>Plugin Name:</strong></td>
                        <td>COA Plugin with REST API - woo</td>
                    </tr>
                    <tr>
                        <td><strong>Plugin Version:</strong></td>
                        <td><?php echo COA_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong>WordPress Version:</strong></td>
                        <td><?php echo get_bloginfo('version'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>WooCommerce Active:</strong></td>
                        <td><?php echo class_exists('WooCommerce') ? '✓ Yes' : '✗ No'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>WooCommerce Version:</strong></td>
                        <td><?php echo class_exists('WooCommerce') ? WC()->version : 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Current User:</strong></td>
                        <td><?php echo wp_get_current_user()->display_name; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Plugin Directory:</strong></td>
                        <td><code><?php echo COA_PLUGIN_DIR; ?></code></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}
