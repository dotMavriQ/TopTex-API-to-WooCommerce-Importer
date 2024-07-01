<?php
/*
Plugin Name: TopTex API to WooCommerce Importer
Description: Import product data from TopTex API into WooCommerce. This plugin is currently in alpha and works as a proof of concept. It's thus hardcoded to import from Inventory for the time being.
Version: 1.0
Author: dotMavriQ
*/

// Hook for adding admin menus
add_action('admin_menu', 'toptex_importer_admin_menu');

// Action function for the above hook
function toptex_importer_admin_menu()
{
    add_menu_page('TopTex Importer', 'TopTex Importer', 'manage_options', 'toptex_importer', 'toptex_importer_page');
}

// Function to display the admin page
function toptex_importer_page()
{
    ?>
    <div class="wrap">
        <h2>TopTex API to WooCommerce Importer</h2>
        <div class="notice notice-warning">
            <p><strong>Disclaimer:</strong> This plugin is currently in alpha and works as a proof of concept. It's thus
                hardcoded to import from Inventory for the time being.</p>
        </div>
        <form method="post" action="">
            <?php settings_fields('toptex-importer-options'); ?>
            <?php do_settings_sections('toptex_importer'); ?>
            <?php wp_nonce_field('toptex-import-action', 'toptex_nonce'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">API Key</th>
                    <td><input type="text" name="toptex_api_key" value="<?php echo get_option('toptex_api_key'); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Username</th>
                    <td><input type="text" name="toptex_username" value="<?php echo get_option('toptex_username'); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Password</th>
                    <td><input type="password" name="toptex_password"
                            value="<?php echo get_option('toptex_password'); ?>" /></td>
                </tr>
            </table>
            <input type="hidden" name="toptex_import_action" value="import" />
            <?php submit_button('Import from TopTex API', 'primary', 'submit_toptex_import'); ?>
        </form>
    </div>
    <?php
}

// Register and define the settings
add_action('admin_init', 'toptex_importer_admin_settings');
function toptex_importer_admin_settings()
{
    register_setting('toptex-importer-options', 'toptex_api_key');
    register_setting('toptex-importer-options', 'toptex_username');
    register_setting('toptex-importer-options', 'toptex_password');
}

// Enqueue styles
function toptex_importer_enqueue_styles()
{
    if (is_admin()) {
        wp_enqueue_style('toptex-admin-style', plugins_url('css/admin-style.css', __FILE__));
    }
}
add_action('admin_enqueue_scripts', 'toptex_importer_enqueue_styles');

// Handle form submission
add_action('admin_init', 'toptex_handle_import');
function toptex_handle_import()
{
    // Check if the form was submitted
    if (isset($_POST['submit_toptex_import'])) {
        // Now check if the nonce is set and is valid
        if (isset($_POST['toptex_nonce']) && wp_verify_nonce($_POST['toptex_nonce'], 'toptex_import_action')) {
            if (current_user_can('manage_options')) {
                $api_key = sanitize_text_field(get_option('toptex_api_key'));
                $username = sanitize_text_field(get_option('toptex_username'));
                $password = sanitize_text_field(get_option('toptex_password'));
                toptex_perform_import($api_key, $username, $password);
            }
        } else {
            add_settings_error('toptex_importer', 'invalid_nonce', 'Sorry, your nonce did not verify.', 'error');
        }
    }
}


// Perform the API request and import the data
function toptex_perform_import($api_key, $username, $password)
{
    $token = toptex_get_token($username, $password, $api_key);
    $url = "https://api.toptex.io/v3/products/inventory?catalog_reference=GI6400B"; // Adjust as necessary

    $response = wp_remote_get(
        $url,
        array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'x-toptex-authorization' => 'Bearer ' . $token
            )
        )
    );

    if (is_wp_error($response)) {
        add_settings_error('toptex_importer', 'api_error', 'Failed to fetch data from the API: ' . $response->get_error_message(), 'error');
        return;
    }

    $items = json_decode(wp_remote_retrieve_body($response), true)['items'];
    foreach ($items as $item) {
        toptex_update_or_create_product($item);
    }
}

// Function to handle authentication and token retrieval
function toptex_get_token($username, $password, $api_key)
{
    $auth_url = "https://api.toptex.io/v3/authenticate"; // Adjust as necessary
    $body = json_encode(array('username' => $username, 'password' => $password));
    $response = wp_remote_post(
        $auth_url,
        array(
            'headers' => array('Content-Type' => 'application/json', 'x-api-key' => $api_key),
            'body' => $body
        )
    );

    if (is_wp_error($response)) {
        add_settings_error('toptex_importer', 'auth_error', 'Authentication failed: ' . $response->get_error_message(), 'error');
        return '';
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    return $response_body['token'] ?? '';
}

// Update or create product in WooCommerce
function toptex_update_or_create_product($item)
{
    $product_id = wc_get_product_id_by_sku($item['sku']);
    $product = $product_id ? wc_get_product($product_id) : new WC_Product_Simple();

    // Set product data
    $product->set_name($item['designation']);
    $product->set_sku($item['sku']);
    $product->set_regular_price(10); // Example price
    $product->set_description('A wonderful product'); // Example description

    // Save the product
    $product->save();
}
?>