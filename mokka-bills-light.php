<?php
/**
 *
 * Plugin Name: Mokka-Abrechnungen-LIGHT
 *
 */

function mokka_menu_page()
{
    add_menu_page(
        'Create Table',
        'Mokka Abrechnung',
        'manage_options',
        plugin_dir_path(__FILE__) . 'admin/dashboard.php',
        null,
        'dashicons-palmtree',
        20
    );

    add_submenu_page(
        plugin_dir_path(__FILE__) . 'admin/dashboard.php',
        'All Artists',
        'All Artists',
        'manage_options',
        'mokka_a_artists',
//        plugin_dir_path(__FILE__) . 'admin/all_artists.php'
        'mokka_all_artists_page'
    );

    add_submenu_page(
        plugin_dir_path(__FILE__) . 'admin/dashboard.php',
        'Single Artist',
        'Single Artist',
        'manage_options',
        'mokka_s_artist',
//        plugin_dir_path(__FILE__) . 'admin/all_artists.php'
        'mokka_single_artist_page'
    );
    add_submenu_page(
        plugin_dir_path(__FILE__) . 'admin/dashboard.php',
        'Woocommerce Fails',
        'Woocommerce Fails',
        'manage_options',
        'mokka_wc_fails_artist',
//        plugin_dir_path(__FILE__) . 'admin/all_artists.php'
        'mokka_wc_fails_page'
    );
    add_submenu_page(
        plugin_dir_path(__FILE__) . 'admin/dashboard.php',
        'Data Without Artists',
        'Data Without Artists',
        'manage_options',
        'mokka_bills_without_artists',
//        plugin_dir_path(__FILE__) . 'admin/all_artists.php'
        'mokka_bills_without_artists_page'
    );
    add_submenu_page(
        plugin_dir_path(__FILE__) . 'admin/dashboard.php',
        'Test view',
        'test view',
        'manage_options',
        'molla_bills_test_view',
//        plugin_dir_path(__FILE__) . 'admin/all_artists.php'
        'molla_bills_test_page'
    );
    add_submenu_page(
        plugin_dir_path(__FILE__) . 'admin/dashboard.php',
        'Test send all artists',
        'test send all artists',
        'manage_options',
        'mokka_bills_test_all_arts',
//        plugin_dir_path(__FILE__) . 'admin/all_artists.php'
        'mokka_test_all_artists'
    );
}
add_action('admin_menu', 'mokka_menu_page');
function mokka_test_all_artists() {
  include_once((plugin_dir_path(__FILE__).'admin/test-all-artists-month.php'));
}
function mokka_all_artists_page() {
    include_once((plugin_dir_path(__FILE__).'admin/all_artists.php'));
}
function mokka_single_artist_page() {
    include_once((plugin_dir_path(__FILE__).'admin/single_artist_summary.php'));
}
function mokka_wc_fails_page() {
    include_once((plugin_dir_path(__FILE__).'admin/wc_fails.php'));
}
function mokka_bills_without_artists_page() {
    include_once((plugin_dir_path(__FILE__).'admin/data_without_artists.php'));
}
function molla_bills_test_page() {
  include_once((plugin_dir_path(__FILE__).'admin/test_view.php'));
}

function load_custom_wp_admin_style($hook) {
    // Load only on ?page=mypluginname
    if($hook != 'mokka-abrechnung_page_mokka_s_artist') {
        return;
    }
//    wp_enqueue_style( 'custom_wp_admin_css', plugins_url('admin/css/admin-style.css', __FILE__) );
    wp_enqueue_style( 'custom_bootstrap_admin_css', plugins_url('admin/css/custom_bootstrap.min.css', __FILE__) );
}
add_action( 'admin_enqueue_scripts', 'load_custom_wp_admin_style' );
define('MOKKA_BILLS_LIGHT_PLUGIN_PATH', WP_PLUGIN_DIR . '/mokka-bills-light/');
function plugin_prefix_function() {

    $dateNow = date("Y-m-d H:i:s");
    $now= new DateTime("@" . strtotime($dateNow));
    $now->modify("+2 hours");
    $now = $now->format("Y-m-d H:i:s");

    require_once(MOKKA_BILLS_LIGHT_PLUGIN_PATH .'admin/db-queries/create_table-upon_activation.php');
}


register_activation_hook(__FILE__, 'plugin_prefix_function');
