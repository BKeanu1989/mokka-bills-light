<?php
/**
 *
 * Plugin Name: Mokka-Abrechnungen-LIGHT
 *
 */

define('MOKKA_BILLS_LIGHT_PLUGIN_PATH', WP_PLUGIN_DIR . '/mokka-bills-light/');

function mokka_bill_menu_page()
{
    add_menu_page(
        'Create Table',
        'Mokka Abrechnung Light',
        'manage_options',
        plugin_dir_path(__FILE__) . 'admin/dashboard.php',
        null,
        'dashicons-palmtree',
        20
    );
}
add_action('admin_menu', 'mokka_bill_menu_page');

// function mokka_bills_light_function() {

//     $dateNow = date("Y-m-d H:i:s");
//     $now= new DateTime("@" . strtotime($dateNow));
//     $now->modify("+2 hours");
//     $now = $now->format("Y-m-d H:i:s");

//     require_once(MOKKA_BILLS_LIGHT_PLUGIN_PATH .'admin/db-queries/create_table-upon_activation.php');
// }

// register_activation_hook(__FILE__, 'mokka_bills_light_function');
