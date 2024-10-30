<?php
/*
  Plugin Name: ChannelSale: WooCommerce Plugin to Sync Multi-Marketplace Product Listings
  Description: ChannelSale provides multi-channel software solutions to WordPress users to fully integrate WordPress backend with Amazon, eBay, Walmart, Google Shopping, Facebook, Jet, Houzz & 200+ more major shopping sites globally.
  Version:     4.0.2
  Author:      ChannelSale
 */

/* plugin activation code */

function channelsale_admin_options_setup() {
    add_action('admin_menu', 'channelsale_add_page');
}

add_action('init', 'channelsale_admin_options_setup');

function channelsale_database_setup() {
    global $wpdb;
    $table_name = $wpdb->prefix . "channelsale_users";

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {

        $sql = 'CREATE TABLE ' . $table_name . ' (
    key_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id bigint(20) UNSIGNED NOT NULL,
  description varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  permissions varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  consumer_key char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  consumer_secret char(43) COLLATE utf8mb4_unicode_ci NOT NULL,
  nonces longtext COLLATE utf8mb4_unicode_ci,
  truncated_key char(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  last_access datetime DEFAULT NULL,
    PRIMARY KEY (key_id))';

        //reference to upgrade.php file
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }
}

function channelsale_install() {
    // trigger our function that registers the custom post type
    channelsale_admin_options_setup();

    //initiate database setup
    channelsale_database_setup();

    // clear the permalinks after the post type has been registered
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'channelsale_install');
/* plugin activation code */

/* plugin deactivation code */

function channelsale_deactivation() {
    // unregister the post type, so the rules are no longer in memory
    remove_menu_page("channelsale_add_page");

    // clear the permalinks to remove our post type's rules from the database
    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, 'channelsale_deactivation');
/* plugin activation code */

/* creating admin menu page to display login form */

// action function for above hook
function channelsale_add_page() {
    //create menu
    add_menu_page(__('ChannelSale', 'menu-channelsale'), __('ChannelSale', 'menu-channelsale'), 'manage_options', 'mt-top-level-handle', 'mt_toplevel_page', '', 6);
}

/* creating admin menu page to display login form */

/* method to render login form */

//form rendering method connected to the admin menu page
function mt_toplevel_page() {
    echo "<h2>" . __('ChannelSale account login details', 'menu-channelsale') . "</h2>";
    ?>
    <div class = "wrap">
        <a class="page-title-action" style="" href="https://www.channelsale.com/support/contact-us.aspx" target="_blank"><?php _e('New Customer', 'cltd_example') ?></a>
    </div>
    <?php
    if (isset($_POST["submit_add"])) {

        $username = sanitize_text_field(htmlspecialchars(filter_input(INPUT_POST, 'username')));
        $pwd = filter_input(INPUT_POST, 'pwd');
        $store_url = sanitize_text_field(htmlspecialchars(filter_input(INPUT_POST, 'site_url'))) . '/wp-json/wc/v2';

        if (empty($username)) {
            $message = "Please enter username";
        } else if (empty($pwd)) {
            $message = "Please enter password";
        } else {
            global $wpdb;

            if (function_exists('openssl_random_pseudo_bytes')) {
                $ck_key = bin2hex(openssl_random_pseudo_bytes(20));
            } else {
                $ck_key = sha1(wp_rand());
            }

            $consumer_key = 'ck_' . $ck_key;
            $final_consumer_key = hash_hmac('sha256', $consumer_key, 'wc-api');

            if (function_exists('openssl_random_pseudo_bytes')) {
                $cs_key = bin2hex(openssl_random_pseudo_bytes(20));
            } else {
                $cs_key = sha1(wp_rand());
            }

            $final_consumer_secret = 'cs_' . $cs_key;

            $final_truncated_key = substr($consumer_key, -7);

            $current_user_id = get_current_user_id();

            $wpdb->query("insert into wp_channelsale_users(user_id,permissions,consumer_key,consumer_secret,truncated_key)"
                    . "values($current_user_id,'read_write','$final_consumer_key','$final_consumer_secret','$final_truncated_key')");

            $merchant_name = "ChannelSale";

            $url = "https://login.channelsale.com/callback.aspx?"
                    . "Channel=$merchant_name"
                    . "&user=$username"
                    . "&pass=$pwd"
                    . "&consumer_key=$final_consumer_key"
                    . "&consumer_secret=$final_consumer_secret"
                    . "&truncated_key=$final_truncated_key"
                    . "&store_url=$store_url";

            $response = wp_remote_get($url);

            $smessage = "You have successfully connected to ChannelSale. <a target='_blank' href='https://login.channelsale.com/login.aspx'>Click here to login</a>";
        }
    }
    ?>

    <p id="message" style="color:green;"><?php echo $smessage; ?></p>
    <p id="error" style="color:red;"><?php echo $message; ?></p>

    <form id="form" method="POST" enctype="multipart/form-data">
        <div class="metabox-holder" id="poststuff">
            <div id="post-body">
                <div id="post-body-content">
                    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
                        <tbody>
                            <tr class="form-field">
                                <th valign="top" scope="row">
                                    <label for="rid"><?php _e('Username:', 'cltd_example') ?></label>
                                </th>
                                <td>
                                    <input required id="username" name="username" type="text" style="width: 95%" value="" size="50" class="code" placeholder="<?php _e('Username', 'cltd_example') ?>">
                                </td>
                            </tr>
                            <tr class="form-field">
                                <th valign="top" scope="row">
                                    <label for="rid"><?php _e('Password:', 'cltd_example') ?></label>
                                </th>
                                <td>
                                    <input required id="pwd" name="pwd" type="password" style="width: 95%" size="50" class="code" placeholder="<?php _e('Password', 'cltd_example') ?>">
                                </td>
                            </tr>
                            <tr class="form-field">
                                <td>
                                    <input type="hidden" value="<?php echo get_site_url(); ?>" id="site_url" name="site_url"/>
                                    <button type="submit" class="button-primary" name="submit_add">Save Changes</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form>
    <?php
}

/* method to render login form */

/******************************************************************************End of file***************************************************************************/
