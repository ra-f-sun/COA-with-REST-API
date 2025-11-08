<?php
/**
 * COA Management Admin Page
 * Handles category management interface
 */

if (!defined('ABSPATH')) exit;

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

// Admin page UI
function coa_management_page() {
    $saved_coa_categories = get_option('coa_categories', array());
    
    // Handle form submission
    if (isset($_POST['coa_save_categories']) && check_admin_referer('coa_categories', 'coa_nonce')) {
        $submitted_category_data = isset($_POST['coa_category_inputs']) ? $_POST['coa_category_inputs'] : array();
        $sanitized_categories = array();
        
        foreach ($submitted_category_data as $category_row) {
            if (!empty($category_row['value']) && !empty($category_row['label'])) {
                $sanitized_categories[] = array(
                    'value' => sanitize_key($category_row['value']),
                    'label' => sanitize_text_field($category_row['label'])
                );
            }
        }
        
        update_option('coa_categories', $sanitized_categories);
        echo '<div class="updated"><p>Categories saved successfully!</p></div>';
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
