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


    wp_enqueue_script(
        'coa-admin-script',
        COA_PLUGIN_URL . 'assets/js/admin-script.js',
        array('jquery'),
        COA_VERSION,
        true
    );
}


// Admin page UI
function coa_management_page() {
    // Get saved categories from database (permanent storage)
    $saved_coa_categories = get_option('coa_categories', array());
    
    // Handle form submission
    if (isset($_POST['coa_save_categories']) && check_admin_referer('coa_categories', 'coa_nonce')) {
        // Get submitted form data (temporary - from form field named 'coa_category_inputs')
        $submitted_category_data = isset($_POST['coa_category_inputs']) ? $_POST['coa_category_inputs'] : array();
        $sanitized_categories = array();
        
        // Clean and validate each submitted category
        foreach ($submitted_category_data as $category_row) {
            if (!empty($category_row['value']) && !empty($category_row['label'])) {
                $sanitized_categories[] = array(
                    'value' => sanitize_key($category_row['value']),
                    'label' => sanitize_text_field($category_row['label'])
                );
            }
        }
        
        // Save to database (permanent storage)
        update_option('coa_categories', $sanitized_categories);
        echo '<div class="updated"><p>Categories saved successfully!</p></div>';
        
        // Update display with newly saved data
        $saved_coa_categories = $sanitized_categories;
    }
    
    ob_start();
    ?>
    
    <div class="wrap coa-management-page">
        <h1>COA Management</h1>
        
        <!-- System Information -->
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
                        <td><strong>Total Categories:</strong></td>
                        <td><?php echo count($saved_coa_categories); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- COA Categories Management -->
        <div class="coa-admin-box">
            <h2>COA Categories</h2>
            <p class="description">Create custom categories (value : label format)</p>
            
            <form method="post">
                <?php wp_nonce_field('coa_categories', 'coa_nonce'); ?>
                
                <table class="widefat coa-categories-table">
                    <thead>
                        <tr>
                            <th width="40%">Value (slug)</th>
                            <th width="40%">Label (display name)</th>
                            <th width="20%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($saved_coa_categories)): ?>
                            <?php foreach ($saved_coa_categories as $row_index => $category_data): ?>
                                <tr>
                                    <td>
                                        <input type="text" 
                                               name="coa_category_inputs[<?php echo $row_index; ?>][value]" 
                                               value="<?php echo esc_attr($category_data['value']); ?>" 
                                               placeholder="e.g., raw-materials"
                                               required 
                                               style="width: 100%;">
                                    </td>
                                    <td>
                                        <input type="text" 
                                               name="coa_category_inputs[<?php echo $row_index; ?>][label]" 
                                               value="<?php echo esc_attr($category_data['label']); ?>" 
                                               placeholder="e.g., Raw Materials"
                                               required 
                                               style="width: 100%;">
                                    </td>
                                    <td>
                                        <button type="button" class="button coa-remove-row">Remove</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td>
                                    <input type="text" 
                                           name="coa_category_inputs[0][value]" 
                                           placeholder="e.g., raw-materials"
                                           required 
                                           style="width: 100%;">
                                </td>
                                <td>
                                    <input type="text" 
                                           name="coa_category_inputs[0][label]" 
                                           placeholder="e.g., Raw Materials"
                                           required 
                                           style="width: 100%;">
                                </td>
                                <td>
                                    <button type="button" class="button coa-remove-row">Remove</button>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <p style="margin-top: 15px;">
                    <button type="button" class="button" id="coa-add-category">Add Category</button>
                    <?php submit_button('Save Categories', 'primary', 'coa_save_categories', false); ?>
                </p>
            </form>
        </div>
    </div>
    
    <?php
    echo ob_get_clean();
}



// ============================================
// SIMPLE PRODUCT - COA FIELDS
// ============================================


// Display COA fields on simple products
add_action('woocommerce_product_options_general_product_data', 'coa_add_simple_product_fields');
function coa_add_simple_product_fields() {
    global $post;
    
    // Get saved categories from database
    $saved_coa_categories = get_option('coa_categories', array());
    
    // Build category options for dropdown
    $category_options = array('' => '-- Select COA Category --');
    foreach ($saved_coa_categories as $category_data) {
        $category_options[$category_data['value']] = $category_data['label'];
    }
    
    echo '<div class="options_group coa-product-fields">';
    
    // Field 1: Product Display Name for COA
    woocommerce_wp_text_input(array(
        'id' => '_coa_product_display_name',
        'label' => 'COA Display Name',
        'placeholder' => 'Leave empty to use product title',
        'desc_tip' => true,
        'description' => 'Custom name to display in COA. If empty, product title will be used.'
    ));
    
    // Field 2: Product COA URL
    woocommerce_wp_text_input(array(
        'id' => '_coa_product_url',
        'label' => 'COA Document URL',
        'placeholder' => 'https://example.com/coa-document.pdf',
        'type' => 'url',
        'desc_tip' => true,
        'description' => 'URL to the COA document (PDF or other format)'
    ));
    
    // Field 3: Product Display Condition for COA
    woocommerce_wp_checkbox(array(
        'id' => '_coa_display_condition',
        'label' => 'Display in COA',
        'description' => 'Check to show this product in frontend COA accordion'
    ));
    
    // Field 4: Product COA Category
    woocommerce_wp_select(array(
        'id' => '_coa_product_category',
        'label' => 'COA Category',
        'options' => $category_options,
        'desc_tip' => true,
        'description' => 'Select which COA category this product belongs to'
    ));
    
    echo '</div>';
}



// Save COA fields when product is saved
add_action('woocommerce_process_product_meta', 'coa_save_simple_product_fields');
function coa_save_simple_product_fields($product_id) {
    // Field 1: COA Display Name
    if (isset($_POST['_coa_product_display_name'])) {
        update_post_meta(
            $product_id, 
            '_coa_product_display_name', 
            sanitize_text_field($_POST['_coa_product_display_name'])
        );
    }
    
    // Field 2: COA Document URL
    if (isset($_POST['_coa_product_url'])) {
        update_post_meta(
            $product_id, 
            '_coa_product_url', 
            esc_url_raw($_POST['_coa_product_url'])
        );
    }
    
    // Field 3: Display Condition (checkbox) - WITH underscore prefix
    $display_condition = isset($_POST['_coa_display_condition']) ? 'yes' : 'no';
    update_post_meta($product_id, '_coa_display_condition', $display_condition);
    
    // Field 4: COA Category
    if (isset($_POST['_coa_product_category'])) {
        update_post_meta(
            $product_id, 
            '_coa_product_category', 
            sanitize_text_field($_POST['_coa_product_category'])
        );
    }
}


// Display saved COA data summary (for verification)
add_action('woocommerce_product_options_general_product_data', 'coa_show_saved_data_summary', 20);
function coa_show_saved_data_summary() {
    global $post;
    
    // Get saved values - using '_coa_display_condition' WITH underscore
    $display_name = get_post_meta($post->ID, '_coa_product_display_name', true);
    $coa_url = get_post_meta($post->ID, '_coa_product_url', true);
    $display_condition = get_post_meta($post->ID, '_coa_display_condition', true);
    $coa_category = get_post_meta($post->ID, '_coa_product_category', true);
    
    // Only show if any COA data exists
    if (!empty($display_name) || !empty($coa_url) || !empty($display_condition) || !empty($coa_category)) {
        echo '<div class="options_group" style="border-top: 1px solid #ddd; padding-top: 12px;">';
        echo '<p style="padding: 10px; background: #e7f3ff; border-left: 4px solid #2271b1; margin: 12px;">';
        echo '<strong>📋 Saved COA Data:</strong><br>';
        
        if ($display_name) {
            echo '• Display Name: <strong>' . esc_html($display_name) . '</strong><br>';
        }
        if ($coa_url) {
            echo '• Document URL: <a href="' . esc_url($coa_url) . '" target="_blank">' . esc_html($coa_url) . '</a><br>';
        }
        echo '• Display in COA: <strong>' . ($display_condition === 'yes' ? 'Yes ✓' : 'No ✗') . '</strong><br>';
        
        if ($coa_category) {
            // Get category label
            $saved_coa_categories = get_option('coa_categories', array());
            $category_label = $coa_category;
            foreach ($saved_coa_categories as $category_data) {
                if ($category_data['value'] === $coa_category) {
                    $category_label = $category_data['label'];
                    break;
                }
            }
            echo '• Category: <strong>' . esc_html($category_label) . '</strong>';
        }
        
        echo '</p>';
        echo '</div>';
    }
}


// ============================================
// ADMIN PRODUCT LIST - COA FILTER
// ============================================


// Add COA filter dropdown to products page
add_action('restrict_manage_posts', 'coa_add_product_filter_dropdown');
function coa_add_product_filter_dropdown() {
    global $typenow;
    
    // Only on products page
    if ($typenow === 'product') {
        $selected = isset($_GET['coa_filter']) ? $_GET['coa_filter'] : '';
        ?>
        <select name="coa_filter">
            <option value="">All Products</option>
            <option value="has_coa" <?php selected($selected, 'has_coa'); ?>>Has COA Data</option>
            <option value="no_coa" <?php selected($selected, 'no_coa'); ?>>No COA Data</option>
            <option value="coa_enabled" <?php selected($selected, 'coa_enabled'); ?>>COA Display Enabled</option>
        </select>
        <?php
    }
}


// Filter products based on COA data
add_filter('parse_query', 'coa_filter_products_by_coa');
function coa_filter_products_by_coa($query) {
    global $pagenow, $typenow;
    
    // Only on products admin page
    if ($pagenow === 'edit.php' && $typenow === 'product' && isset($_GET['coa_filter']) && !empty($_GET['coa_filter'])) {
        
        $filter = $_GET['coa_filter'];
        
        if ($filter === 'has_coa') {
            // Products with ANY COA data
            $query->query_vars['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key' => '_coa_product_display_name',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => '_coa_product_url',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => '_coa_product_category',
                    'compare' => 'EXISTS'
                )
            );
        } elseif ($filter === 'no_coa') {
            // Products WITHOUT any COA data
            $query->query_vars['meta_query'] = array(
                'relation' => 'AND',
                array(
                    'key' => '_coa_product_display_name',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_coa_product_url',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_coa_product_category',
                    'compare' => 'NOT EXISTS'
                )
            );
        } elseif ($filter === 'coa_enabled') {
            // Products with "Display in COA" checked - using '_coa_display_condition' WITH underscore
            $query->query_vars['meta_query'] = array(
                array(
                    'key' => '_coa_display_condition',
                    'value' => 'yes',
                    'compare' => '='
                )
            );
        }
    }
}

