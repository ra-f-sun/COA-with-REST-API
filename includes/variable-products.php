<?php
/**
 * COA Fields for Variable Products
 * Displays and saves COA fields for product variations
 */

if (!defined('ABSPATH')) exit;

// Display COA fields for each variation
add_action('woocommerce_variation_options_pricing', 'coa_add_variation_fields', 10, 3);
function coa_add_variation_fields($loop, $variation_data, $variation) {
    $saved_coa_categories = get_option('coa_categories', array());
    $category_options = array('' => '-- Select COA Category --');
    foreach ($saved_coa_categories as $category_data) {
        $category_options[$category_data['value']] = $category_data['label'];
    }
    
    ?>
    <div style="border-top: 1px solid #eee; margin-top: 12px; padding-top: 12px;">
        <h4 style="margin: 0 0 12px 12px; color: #2271b1;">📋 COA Fields</h4>
    </div>
    <?php
    
    woocommerce_wp_text_input(array(
        'id' => '_coa_product_display_name_' . $loop,
        'name' => '_coa_product_display_name[' . $loop . ']',
        'label' => 'COA Display Name',
        'placeholder' => 'Leave empty to use variation name',
        'desc_tip' => true,
        'description' => 'Custom name to display in COA. If empty, variation name will be used.',
        'value' => get_post_meta($variation->ID, '_coa_product_display_name', true),
        'wrapper_class' => 'form-row form-row-full'
    ));
    
    woocommerce_wp_text_input(array(
        'id' => '_coa_product_url_' . $loop,
        'name' => '_coa_product_url[' . $loop . ']',
        'label' => 'COA Document URL',
        'placeholder' => 'https://example.com/coa-document.pdf',
        'type' => 'url',
        'desc_tip' => true,
        'description' => 'URL to the COA document (PDF or other format)',
        'value' => get_post_meta($variation->ID, '_coa_product_url', true),
        'wrapper_class' => 'form-row form-row-full'
    ));
    
    woocommerce_wp_checkbox(array(
        'id' => '_coa_display_condition_' . $loop,
        'name' => '_coa_display_condition[' . $loop . ']',
        'label' => 'Display in COA',
        'description' => 'Check to show this variation in frontend COA accordion',
        'value' => get_post_meta($variation->ID, '_coa_display_condition', true) === 'yes' ? 'yes' : 'no',
        'cbvalue' => 'yes',
        'wrapper_class' => 'form-row form-row-full'
    ));
    
    woocommerce_wp_select(array(
        'id' => '_coa_product_category_' . $loop,
        'name' => '_coa_product_category[' . $loop . ']',
        'label' => 'COA Category',
        'options' => $category_options,
        'desc_tip' => true,
        'description' => 'Select which COA category this variation belongs to',
        'value' => get_post_meta($variation->ID, '_coa_product_category', true),
        'wrapper_class' => 'form-row form-row-full'
    ));
}

// Save COA fields for variations
add_action('woocommerce_save_product_variation', 'coa_save_variation_fields', 10, 2);
function coa_save_variation_fields($variation_id, $loop) {
    if (isset($_POST['_coa_product_display_name'][$loop])) {
        update_post_meta($variation_id, '_coa_product_display_name', sanitize_text_field($_POST['_coa_product_display_name'][$loop]));
    }
    
    if (isset($_POST['_coa_product_url'][$loop])) {
        update_post_meta($variation_id, '_coa_product_url', esc_url_raw($_POST['_coa_product_url'][$loop]));
    }
    
    $display_condition = isset($_POST['_coa_display_condition'][$loop]) ? 'yes' : 'no';
    update_post_meta($variation_id, '_coa_display_condition', $display_condition);
    
    if (isset($_POST['_coa_product_category'][$loop])) {
        update_post_meta($variation_id, '_coa_product_category', sanitize_text_field($_POST['_coa_product_category'][$loop]));
    }
}
