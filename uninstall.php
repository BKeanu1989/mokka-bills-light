<?php
// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS wp_mokkamerch_BILL_DATA_SUMMARY_JOINED_ARTISTS_PERM_TABLE");
$wpdb->query("DROP TABLE IF EXISTS wp_mokkamerch_WOOCOMMERCE_FAILS_PERM_TABLE");
