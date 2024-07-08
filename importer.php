<?php
// import-button.php

// Ensure WordPress context (if direct access to this script)
require_once ('wp-load.php');

if (current_user_can('manage_options')) {
    $json_data = $_POST['json_data'] ?? '';
    $data = json_decode($json_data, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
        // Loop through each product data and create WooCommerce products
        foreach ($data as $product_data) {
            // Assume $product_data contains necessary fields
            $product_id = wc_create_product($product_data);
            if ($product_id) {
                // Success handling, maybe log or output success message
            } else {
                // Error handling
            }
        }
    } else {
        // Handle JSON errors or invalid data
        die('Invalid JSON data provided.');
    }
} else {
    die('Not authorized.');
}

// Redirect back to plugin page or output some result
wp_redirect(admin_url('admin.php?page=toptex_importer'));
exit;
?>
