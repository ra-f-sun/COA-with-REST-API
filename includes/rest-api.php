<?php
/**
 * Custom REST API Endpoints for COA Plugin
 * Provides optimized endpoints for fetching COA products with variations
 */

if (!defined('ABSPATH')) exit;

// Register custom REST API routes
add_action('rest_api_init', 'coa_register_rest_routes');
function coa_register_rest_routes() {
    // Endpoint: /wp-json/coa/v1/products
    register_rest_route('coa/v1', '/products', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'coa_get_products',
        'permission_callback' => '__return_true', // Public endpoint
        'args' => array(
            'page' => array(
                'default' => 1,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'default' => 50,
                'sanitize_callback' => 'absint',
            ),
            'category' => array(
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'display_enabled' => array(
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ));
    
    // Endpoint: /wp-json/coa/v1/products/{id}
    register_rest_route('coa/v1', '/products/(?P<id>\d+)', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'coa_get_single_product',
        'permission_callback' => '__return_true',
        'args' => array(
            'id' => array(
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            ),
        ),
    ));
    
    // Endpoint: /wp-json/coa/v1/categories
    register_rest_route('coa/v1', '/categories', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'coa_get_categories',
        'permission_callback' => '__return_true',
    ));
}

/**
 * Get all products with COA data (with filtering and pagination)
 */
function coa_get_products($request) {
    $page = $request->get_param('page');
    $per_page = $request->get_param('per_page');
    $category = $request->get_param('category');
    $display_enabled = $request->get_param('display_enabled');

    // First, get all simple and variable products
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => $per_page,
        'paged' => $page,
        'post_status' => 'publish',
    );
    $query = new WP_Query($args);
    $products = array();

    foreach ($query->posts as $post) {
        $product = wc_get_product($post->ID);
        if ($product->is_type('simple')) {
            $product_data = coa_format_product_data($product);

            // Category filter for simple products (just like before)
            $matches = true;
            if (!empty($category) && $product_data['coa']['category'] !== $category) {
                $matches = false;
            }
            if (!empty($display_enabled)) {
                $expected = ($display_enabled === 'yes');
                if ($product_data['coa']['display_enabled'] !== $expected) {
                    $matches = false;
                }
            }
            if ($matches) {
                $products[] = $product_data;
            }
        }
        elseif ($product->is_type('variable')) {
            $variations = coa_get_product_variations($product);
            // Now, filter variations by COA meta
            $matching_variations = array();
            foreach ($variations as $var) {
                $match_var = true;
                if (!empty($category) && $var['coa']['category'] !== $category) {
                    $match_var = false;
                }
                if (!empty($display_enabled)) {
                    $expected = ($display_enabled === 'yes');
                    if ($var['coa']['display_enabled'] !== $expected) {
                        $match_var = false;
                    }
                }
                if ($match_var) {
                    $matching_variations[] = $var;
                }
            }
            if (!empty($matching_variations)) {
                // Only include variable product if at least one variation matches
                $parent_data = coa_format_product_data($product);
                $parent_data['variations'] = $matching_variations;
                $products[] = $parent_data;
            }
        }
    }

    $response = array(
        'success' => true,
        'data' => $products,
        'pagination' => array(
            'total' => (int) $query->found_posts,
            'page' => (int) $page,
            'per_page' => (int) $per_page,
            'total_pages' => (int) $query->max_num_pages,
        ),
    );

    return new WP_REST_Response($response, 200);
}

/**
 * Get single product by ID
 */
function coa_get_single_product($request) {
    $product_id = $request->get_param('id');
    $product = wc_get_product($product_id);
    
    if (!$product) {
        return new WP_Error('not_found', 'Product not found', array('status' => 404));
    }
    
    $product_data = coa_format_product_data($product);
    
    // Add variations for variable products
    if ($product->is_type('variable')) {
        $product_data['variations'] = coa_get_product_variations($product);
    }
    
    return new WP_REST_Response(array(
        'success' => true,
        'data' => $product_data,
    ), 200);
}

/**
 * Get all COA categories
 */
function coa_get_categories($request) {
    $categories = get_option('coa_categories', array());
    
    return new WP_REST_Response(array(
        'success' => true,
        'data' => $categories,
    ), 200);
}

/**
 * Format product data with COA fields
 */
function coa_format_product_data($product) {
    $product_id = $product->get_id();
    
    // Get COA meta data
    $display_name = get_post_meta($product_id, '_coa_product_display_name', true);
    $coa_url = get_post_meta($product_id, '_coa_product_url', true);
    $display_condition = get_post_meta($product_id, '_coa_display_condition', true);
    $coa_category = get_post_meta($product_id, '_coa_product_category', true);
    
    // Get category label
    $category_label = '';
    if (!empty($coa_category)) {
        $saved_categories = get_option('coa_categories', array());
        foreach ($saved_categories as $cat) {
            if ($cat['value'] === $coa_category) {
                $category_label = $cat['label'];
                break;
            }
        }
    }
    
    return array(
        'id' => $product_id,
        'name' => $product->get_name(),
        'type' => $product->get_type(),
        'sku' => $product->get_sku(),
        'price' => $product->get_price(),
        'regular_price' => $product->get_regular_price(),
        'sale_price' => $product->get_sale_price(),
        'stock_status' => $product->get_stock_status(),
        'coa' => array(
            'display_name' => !empty($display_name) ? $display_name : $product->get_name(),
            'document_url' => $coa_url,
            'display_enabled' => $display_condition === 'yes',
            'category' => $coa_category,
            'category_label' => $category_label,
        ),
    );
}

/**
 * Get all variations for a variable product with COA data
 */
function coa_get_product_variations($product) {
    $variations = array();
    
    foreach ($product->get_available_variations() as $variation) {
        $variation_id = $variation['variation_id'];
        $variation_obj = wc_get_product($variation_id);
        
        // Get variation COA meta data
        $display_name = get_post_meta($variation_id, '_coa_product_display_name', true);
        $coa_url = get_post_meta($variation_id, '_coa_product_url', true);
        $display_condition = get_post_meta($variation_id, '_coa_display_condition', true);
        $coa_category = get_post_meta($variation_id, '_coa_product_category', true);
        
        // Get category label
        $category_label = '';
        if (!empty($coa_category)) {
            $saved_categories = get_option('coa_categories', array());
            foreach ($saved_categories as $cat) {
                if ($cat['value'] === $coa_category) {
                    $category_label = $cat['label'];
                    break;
                }
            }
        }
        
        $variations[] = array(
            'id' => $variation_id,
            'sku' => $variation_obj->get_sku(),
            'price' => $variation_obj->get_price(),
            'regular_price' => $variation_obj->get_regular_price(),
            'sale_price' => $variation_obj->get_sale_price(),
            'stock_status' => $variation_obj->get_stock_status(),
            'attributes' => $variation['attributes'],
            'coa' => array(
                'display_name' => !empty($display_name) ? $display_name : $variation_obj->get_name(),
                'document_url' => $coa_url,
                'display_enabled' => $display_condition === 'yes',
                'category' => $coa_category,
                'category_label' => $category_label,
            ),
        );
    }
    
    return $variations;
}
