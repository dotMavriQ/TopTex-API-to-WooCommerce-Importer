### TopTex API to WooCommerce Importer
**Warning**: The TopTex-API script was initially meant to be the only script. However, due to time constraints, this project uses `curl` for simplicity. Future updates might include AJAX or alternative methods for a more robust solution. With sufficient time on my hands...it might even become modular and functional enough to be a market ready plugin.

Made quite swiftly in collaboration with a party as a Case Study/Work Test.

----

Be sure to place `importer.php` and the other `.php` file in separate folders.

Initially I was to take the code I had made and turn it into a plugin.

I ran short on time however so there are some concessions.

1. The plugin is currently hardcoded to fetch from `inventory`.
2. Some defaults not provided in the import I did set static values which can be configured in `importer.php`

### How to use the scripts in their current state:
* Run "the plugin script" to fetch the output, put it in a file and name it `output.json` (optional, you can also just curl it yourself or however you prefer)
* place `output.json` in a plugin folder that you can name `importer` which should also contain `importer.php`
* Activate and run the importer, it will fetch the info from `output.json` and import it into an installed instance of WooCommerce.
