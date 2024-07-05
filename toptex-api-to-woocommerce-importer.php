<?php
/*
Plugin Name: TopTex API to WooCommerce Importer
Description: Import product data from TopTex API into WooCommerce. This plugin is currently in alpha and works as a proof of concept. It's thus hardcoded to import from Inventory for the time being.
Version: 1.0
Author: dotMavriQ
*/

// Hook for adding admin menus
add_action('admin_menu', 'toptex_importer_admin_menu');

function toptex_importer_admin_menu()
{
    add_menu_page('TopTex Importer', 'TopTex Importer', 'manage_options', 'toptex_importer', 'toptex_importer_page');
}

function toptex_importer_page()
{
    ?>
    <div class="wrap">
        <h2>TopTex API to WooCommerce Importer</h2>
        <form method="post" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">API Key</th>
                    <td><input type="text" name="api_key"
                            value="<?php echo isset($_POST['api_key']) ? esc_attr($_POST['api_key']) : ''; ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Username</th>
                    <td><input type="text" name="username"
                            value="<?php echo isset($_POST['username']) ? esc_attr($_POST['username']) : ''; ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Password</th>
                    <td><input type="password" name="password"
                            value="<?php echo isset($_POST['password']) ? esc_attr($_POST['password']) : ''; ?>" /></td>
                </tr>
            </table>
            <?php submit_button('Import from TopTex API'); ?>
        </form>
        <?php
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['api_key'], $_POST['username'], $_POST['password'])) {
            toptex_import_from_api($_POST['api_key'], $_POST['username'], $_POST['password']);
        }
        ?>
    </div>
    <?php
}

function toptex_import_from_api($api_key, $username, $password)
{
    $token_request = curl_init("https://api.toptex.io/v3/authenticate");
    $token_post_data = json_encode(array("username" => $username, "password" => $password));
    curl_setopt($token_request, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($token_request, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: ' . $api_key));
    curl_setopt($token_request, CURLOPT_POST, true);
    curl_setopt($token_request, CURLOPT_POSTFIELDS, $token_post_data);

    $response = curl_exec($token_request);
    $response_data = json_decode($response, true);
    $token = $response_data['token'] ?? 'No token retrieved';

    curl_close($token_request);

    $api_request = curl_init("https://api.toptex.io/v3/products/inventory?catalog_reference=GI6400B");
    curl_setopt($api_request, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'x-api-key: ' . $api_key, 'x-toptex-authorization: Bearer ' . $token));
    curl_setopt($api_request, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($api_request);
    curl_close($api_request);

    // Decode JSON for pretty printing
    $formatted_result = json_encode(json_decode($result), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    // Output inside a textarea for user-friendly display
    echo '<textarea readonly style="width: 60%; height: 150px; background-color: #f1f1f1; border-radius: 5px; font-family: monospace; padding: 10px; overflow-y: auto; resize: none;">';
    echo htmlspecialchars($formatted_result);
    echo '</textarea>';
}


?>