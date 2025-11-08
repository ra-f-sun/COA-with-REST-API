<?php
/**
 * Admin Product List Filters
 * Adds COA filter dropdown to products page
 */

if (!defined('ABSPATH')) exit;

// Add COA filter dropdown
add_action('restrict_manage_posts', 'coa_add_product_filter_dropdown');
function coa_add_product_filter_dropdown() {
    global $typenow;
    
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
    
    if ($pagenow === 'edit.php' && $typenow === 'product' && isset($_GET['coa_filter']) && !empty($_GET['coa_filter'])) {
        $filter = $_GET['coa_filter'];
        
        if ($filter === 'has_coa') {
            $query->query_vars['meta_query'] = array(
                'relation' => 'OR',
                array('key' => '_coa_product_display_name', 'compare' => 'EXISTS'),
                array('key' => '_coa_product_url', 'compare' => 'EXISTS'),
                array('key' => '_coa_product_category', 'compare' => 'EXISTS')
            );
        } elseif ($filter === 'no_coa') {
            $query->query_vars['meta_query'] = array(
                'relation' => 'AND',
                array('key' => '_coa_product_display_name', 'compare' => 'NOT EXISTS'),
                array('key' => '_coa_product_url', 'compare' => 'NOT EXISTS'),
                array('key' => '_coa_product_category', 'compare' => 'NOT EXISTS')
            );
        } elseif ($filter === 'coa_enabled') {
            $query->query_vars['meta_query'] = array(
                array('key' => '_coa_display_condition', 'value' => 'yes', 'compare' => '=')
            );
        }
    }
}
