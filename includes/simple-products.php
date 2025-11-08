<?php
/**
 * COA Fields for Simple Products
 * Displays and saves COA fields for simple products only
 */

if (!defined('ABSPATH')) exit;

// Display COA fields on simple products ONLY
add_action('woocommerce_product_options_general_product_data', 'coa_add_simple_product_fields');
function coa_add_simple_product_fields() {
    global $post, $product_object;
    
    if (!$product_object) {
        $product_object = wc_get_product($post->ID);
    }
    
    // Only show for simple products (exclude variable products)
    if ($product_object && $product_object->is_type('variable')) {
        return;
    }
    
    $saved_coa_categories = get_option('coa_categories', array());
    $category_options = array('' => '-- Select COA Category --');
    foreach ($saved_coa_categories as $category_data) {
        $category_options[$category_data['value']] = $category_data['label'];
    }
    
    echo '<div class="options_group coa-product-fields">';
    
    woocommerce_wp_text_input(array(
        'id' => '_coa_product_display_name',
        'label' => 'COA Display Name',
        'placeholder' => 'Leave empty to use product title',
        'desc_tip' => true,
        'description' => 'Custom name to display in COA. If empty, product title will be used.'
    ));
    
    woocommerce_wp_text_input(array(
        'id' => '_coa_product_url',
        'label' => 'COA Document URL',
        'placeholder' => 'https://example.com/coa-document.pdf',
        'type' => 'url',
        'desc_tip' => true,
        'description' => 'URL to the COA document (PDF or other format)'
    ));
    
    woocommerce_wp_checkbox(array(
        'id' => '_coa_display_condition',
        'label' => 'Display in COA',
        'description' => 'Check to show this product in frontend COA accordion'
    ));
    
    woocommerce_wp_select(array(
        'id' => '_coa_product_category',
        'label' => 'COA Category',
        'options' => $category_options,
        'desc_tip' => true,
        'description' => 'Select which COA category this product belongs to'
    ));
    
    echo '</div>';
}

// Save COA fields for simple products
add_action('woocommerce_process_product_meta', 'coa_save_simple_product_fields');
function coa_save_simple_product_fields($product_id) {
    $product = wc_get_product($product_id);
    
    if ($product && $product->is_type('variable')) {
        return;
    }
    
    if (isset($_POST['_coa_product_display_name'])) {
        update_post_meta($product_id, '_coa_product_display_name', sanitize_text_field($_POST['_coa_product_display_name']));
    }
    
    if (isset($_POST['_coa_product_url'])) {
        update_post_meta($product_id, '_coa_product_url', esc_url_raw($_POST['_coa_product_url']));
    }
    
    $display_condition = isset($_POST['_coa_display_condition']) ? 'yes' : 'no';
    update_post_meta($product_id, '_coa_display_condition', $display_condition);
    
    if (isset($_POST['_coa_product_category'])) {
        update_post_meta($product_id, '_coa_product_category', sanitize_text_field($_POST['_coa_product_category']));
    }
}

// Display saved COA data summary
add_action('woocommerce_product_options_general_product_data', 'coa_show_saved_data_summary', 20);
function coa_show_saved_data_summary() {
    global $post, $product_object;
    
    if (!$product_object) {
        $product_object = wc_get_product($post->ID);
    }
    
    if ($product_object && $product_object->is_type('variable')) {
        return;
    }
    
    $display_name = get_post_meta($post->ID, '_coa_product_display_name', true);
    $coa_url = get_post_meta($post->ID, '_coa_product_url', true);
    $display_condition = get_post_meta($post->ID, '_coa_display_condition', true);
    $coa_category = get_post_meta($post->ID, '_coa_product_category', true);
    
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
