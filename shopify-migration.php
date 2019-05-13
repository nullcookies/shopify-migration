<?php
/**
 * Plugin Name: Shopify Migration
 * Plugin URI:  https://davidroddick.com/
 * Description: Migrate blog posts from Shopify to WordPress
 * Version:     0.01
 * Author:      David Roddick
 * Author URI:  https://davidroddick
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: davidroddick
 */
defined( 'ABSPATH' ) or die( 'No access to this page' );

set_time_limit(0);
ignore_user_abort(1);
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 'on');

require_once( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php');
require_once( plugin_dir_path( __FILE__ ) . 'inc/Shopify_Migration.php');

function shopify_migration_options_page_html() {
    if ( !current_user_can('manage_options') ) {
        return;
    }

    $all_users = get_users();
    //echo "<pre>"; print_r($all_users); echo "</pre>";

    if ( isset( $_POST['submit'] ) ) {
        $data['url'] = $_POST['shopify-url'];
        $data['api_key'] = $_POST['shopify-api-key'];
        $data['password'] = $_POST['shopify-password'];
        $data['blog'] = $_POST['shopify-blog'];
        $data['limit'] = (int) $_POST['posts-limit'];
        $data['author'] = (int) $_POST['author'];
    
        //echo "<pre>"; print_r($data); echo "</pre>";
        //exit;
        $shop = new Shopify_Migration( $data );
        $blog = $shop->export_posts_from_shopify();
        $shop->import_posts_to_wordpress($blog);
    }
    ?>
    <div class="wrap">
        <h1><?= esc_html(get_admin_page_title()); ?></h1>
        <form action="" method="post">
            <table>
                <tr><td>API Key:</td><td><input name='shopify-api-key' type='text' required /></td></tr>
                <tr><td>Password:</td><td><input name='shopify-password' type='text' required /></td></tr>
                <tr><td>Shop URL:</td><td><input name='shopify-url' type='text' required /></td></tr>
                <tr><td>Blog ID:</td><td><input name='shopify-blog' type='text' required /></td></tr>
                <tr><td>Posts limit (max 250):</td><td><input name='posts-limit' type='text' value=50 required /></td></tr>
                <tr><td>Assign to user:</td>
                    <td><select name='author'>
                    <?php
                        foreach ($all_users as $u) {
                            echo "<option value='$u->ID'>$u->display_name</option>";
                        }
                    ?></select></td>
                </tr>
            </table>
            <?php
            settings_fields('shopify_migration_options');
            do_settings_sections('shopify_migration');
            submit_button('Run Migration');
            ?> 
        </form>
    </div>
    <?php
}

function shopify_migration_options_page() {
    add_submenu_page(
        'tools.php',
        'Shopify Migration Options',
        'Shopify Migration',
        'manage_options',
        'shopify_migration',
        'shopify_migration_options_page_html'
    );
}
add_action('admin_menu', 'shopify_migration_options_page');
