<?php
/**
 * Created by PhpStorm.
 * User: Keanu1989
 * Date: 23.01.17
 * Time: 16:21
 */

global $wpdb;
$abrechnungsBeginn = $_POST['from'];
$abrechnungsEnde = $_POST['to'];
// fetch old table
// create new table from it (distinct order_item_id)
$new_BILL_DATA_SUMMARY_JOINED_ARTISTS_PERM_TABLE = $wpdb->query('CREATE TEMPORARY TABLE BILL_DATA_SUMMARY_JOINED_ARTISTS_PERM_TABLE_tmp (SELECT DISTINCT order_item_id, product_name, order_sold_at, email, product_id, variation_id, qty, total_price, single_price, order_id,rohartikel,sku,marge, farbe, basis_preis, artikel_type, groesse, qualitaet, artist_name, name FROM wp_mokkamerch_BILL_DATA_SUMMARY_JOINED_ARTISTS_PERM_TABLE)');
$countRowsOldTable = $wpdb->get_var('SELECT COUNT(*) FROM wp_mokkamerch_BILL_DATA_SUMMARY_JOINED_ARTISTS_PERM_TABLE');
// insert into new table from tmp table
// drop old table
$wpdb->query('DROP TABLE wp_mokkamerch_BILL_DATA_SUMMARY_JOINED_ARTISTS_PERM_TABLE');

$COMPLETED_ORDERS_WITH_ORDER_ITEM_ID_AND_NAME = $wpdb->query($wpdb->prepare('CREATE TEMPORARY TABLE COMPLETED_ORDERS_WITH_ORDER_ITEM_ID_AND_NAME (SELECT ID, order_sold_at, order_item_id, order_item_name AS product_name, email FROM (SELECT DISTINCT MAIN_POSTMETA.post_id AS ID, ORDER_SOLD_POSTMETA.meta_value AS order_sold_at, EMAIL_POSTMETA.meta_value as email FROM wp_mokkamerch_postmeta AS MAIN_POSTMETA JOIN wp_mokkamerch_postmeta AS ORDER_SOLD_POSTMETA ON MAIN_POSTMETA.post_id = ORDER_SOLD_POSTMETA.post_id AND ORDER_SOLD_POSTMETA.meta_key = "_order_sold" JOIN wp_mokkamerch_postmeta AS EMAIL_POSTMETA ON MAIN_POSTMETA.post_id = EMAIL_POSTMETA.post_id AND EMAIL_POSTMETA.meta_key = "_billing_email" AND EMAIL_POSTMETA.meta_value <> "service@mokka-merch.com") AS ALL_COMPLETED_ORDERS JOIN wp_mokkamerch_woocommerce_order_items AS ITEMS ON ALL_COMPLETED_ORDERS.ID = ITEMS.order_id WHERE DATE(ALL_COMPLETED_ORDERS.order_sold_at) BETWEEN %s AND %s AND ITEMS.order_item_type = "line_item");', $abrechnungsBeginn, $abrechnungsEnde));

$ALL_ORDERS_IN_GIVEN_TIMEFRAME = $wpdb->query('CREATE TEMPORARY TABLE ALL_ORDERS_IN_GIVEN_TIMEFRAME (SELECT COMPLETED_ORDERS_WITH_ORDER_ITEM_ID_AND_NAME.* FROM COMPLETED_ORDERS_WITH_ORDER_ITEM_ID_AND_NAME JOIN wp_mokkamerch_posts AS MAIN_POSTS ON COMPLETED_ORDERS_WITH_ORDER_ITEM_ID_AND_NAME.ID = MAIN_POSTS.ID AND MAIN_POSTS.post_type = "shop_order" AND MAIN_POSTS.post_status = "wc-sold");');

$ITEMMETA_SUMMARY = $wpdb->query('CREATE TEMPORARY TABLE ITEMMETA_SUMMARY (SELECT DISTINCT MAIN_ITEMMETA.order_item_id, PRODUCT_ITEMMETA.meta_value AS product_id, VARIATION_ITEMMETA.meta_value as variation_id, QTY_ITEMMETA.meta_value AS qty, (LINE_TOTAL.meta_value + LINE_TAX.meta_value) AS total_price, ((LINE_TOTAL.meta_value + LINE_TAX.meta_value) / QTY_ITEMMETA.meta_value) AS single_price FROM `wp_mokkamerch_woocommerce_order_itemmeta` AS MAIN_ITEMMETA JOIN wp_mokkamerch_woocommerce_order_itemmeta AS VARIATION_ITEMMETA ON MAIN_ITEMMETA.order_item_id = VARIATION_ITEMMETA.order_item_id AND VARIATION_ITEMMETA.meta_key = "_variation_id" JOIN wp_mokkamerch_woocommerce_order_itemmeta AS QTY_ITEMMETA ON MAIN_ITEMMETA.order_item_id = QTY_ITEMMETA.order_item_id AND QTY_ITEMMETA.meta_key = "_qty" JOIN wp_mokkamerch_woocommerce_order_itemmeta AS LINE_TOTAL ON MAIN_ITEMMETA.order_item_id = LINE_TOTAL.order_item_id AND LINE_TOTAL.meta_key = "_line_total" JOIN wp_mokkamerch_woocommerce_order_itemmeta AS LINE_TAX ON MAIN_ITEMMETA.order_item_id = LINE_TAX.order_item_id AND LINE_TAX.meta_key = "_line_tax" JOIN wp_mokkamerch_woocommerce_order_itemmeta AS PRODUCT_ITEMMETA ON MAIN_ITEMMETA.order_item_id = PRODUCT_ITEMMETA.order_item_id AND PRODUCT_ITEMMETA.meta_key = "_product_id");');


$ARTIST_MEMORY_DATA = $wpdb->query('CREATE TEMPORARY TABLE ARTIST_MEMORY_DATA (SELECT * FROM ITEMMETA_SUMMARY JOIN wp_mokkamerch_term_relationships AS TERM_REL ON ITEMMETA_SUMMARY.product_id = TERM_REL.object_id);');

$ARTIST_SUMMARY = $wpdb->query('CREATE TEMPORARY TABLE ARTIST_SUMMARY (SELECT * FROM ARTIST_MEMORY_DATA JOIN wp_mokkamerch_terms AS TERMS ON ARTIST_MEMORY_DATA.term_taxonomy_id = TERMS.term_id);');

$TERM_ID_ARTISTS = $wpdb->query('CREATE TEMPORARY TABLE TERM_ID_ARTISTS (SELECT * FROM (SELECT ARTISTS.artist_name, TERMS.term_id, TERMS.name FROM wp_mokkamerch_artists AS ARTISTS JOIN wp_mokkamerch_terms AS TERMS ON ARTISTS.slug = TERMS.slug) AS SUB_TABLE JOIN wp_mokkamerch_term_relationships AS TERM_REL ON SUB_TABLE.term_id = TERM_REL.term_taxonomy_id );');

$ITEMMETA_SUMMARY_PLUS_ORDER_ID = $wpdb->query('CREATE TEMPORARY TABLE ITEMMETA_SUMMARY_PLUS_ORDER_ID (SELECT ITEMMETA_SUMMARY.*, ORDER_ITEMS.order_id FROM ITEMMETA_SUMMARY JOIN wp_mokkamerch_woocommerce_order_items AS ORDER_ITEMS ON ITEMMETA_SUMMARY.order_item_id = ORDER_ITEMS.order_item_id);');

$ORDER_SPECIFIC_DATA = $wpdb->query('CREATE TEMPORARY TABLE ORDER_SPECIFIC_DATA (SELECT ALL_ORDERS_IN_GIVEN_TIMEFRAME.product_name, ALL_ORDERS_IN_GIVEN_TIMEFRAME.order_sold_at, ALL_ORDERS_IN_GIVEN_TIMEFRAME.email, ITEMMETA_SUMMARY_PLUS_ORDER_ID.* FROM ALL_ORDERS_IN_GIVEN_TIMEFRAME JOIN ITEMMETA_SUMMARY_PLUS_ORDER_ID ON ALL_ORDERS_IN_GIVEN_TIMEFRAME.order_item_id = ITEMMETA_SUMMARY_PLUS_ORDER_ID.order_item_id);');
$POSTMETA_ROHARTIKEL_v2 = $wpdb->query('CREATE TEMPORARY TABLE POSTMETA_ROHARTIKEL_v2 (SELECT DISTINCT MAIN_POSTMETA.post_id AS variation_id, POSTMETA_ROHARTIKEL.meta_value AS rohartikel, POSTMETA_SKU.meta_value AS sku FROM wp_mokkamerch_postmeta AS MAIN_POSTMETA JOIN wp_mokkamerch_postmeta AS POSTMETA_ROHARTIKEL ON MAIN_POSTMETA.post_id = POSTMETA_ROHARTIKEL.post_id AND POSTMETA_ROHARTIKEL.meta_key = "_rohartikel_default" JOIN wp_mokkamerch_postmeta AS POSTMETA_SKU ON MAIN_POSTMETA.post_id = POSTMETA_SKU.post_id AND POSTMETA_SKU.meta_key = "_sku");');

$ORDER_DATA_WITH_POSTMETA = $wpdb->query('CREATE TEMPORARY TABLE ORDER_DATA_WITH_POSTMETA (SELECT ORDER_SPECIFIC_DATA.*, POSTMETA_ROHARTIKEL_v2.rohartikel, POSTMETA_ROHARTIKEL_v2.sku FROM ORDER_SPECIFIC_DATA JOIN POSTMETA_ROHARTIKEL_v2 ON ORDER_SPECIFIC_DATA.variation_id = POSTMETA_ROHARTIKEL_v2.variation_id);');

$BILL_DATA = $wpdb->query('CREATE TEMPORARY TABLE BILL_DATA (SELECT ORDER_DATA_WITH_POSTMETA.*, ((ORDER_DATA_WITH_POSTMETA.single_price - ROHARTIKEL.basis_preis) * ORDER_DATA_WITH_POSTMETA.qty /1.19 ) AS marge, ROHARTIKEL.farbe, ROHARTIKEL.basis_preis, ROHARTIKEL.artikel_name AS artikel_type, ROHARTIKEL.groesse, ROHARTIKEL.qualitaet FROM ORDER_DATA_WITH_POSTMETA JOIN wp_mokkamerch_rohartikel AS ROHARTIKEL ON ORDER_DATA_WITH_POSTMETA.rohartikel = ROHARTIKEL.rohartikel_nr_extern);');

$BILL_DATA_SUMMARY = $wpdb->query('CREATE TEMPORARY TABLE BILL_DATA_SUMMARY_JOINED_ARTISTS (SELECT BILL_DATA.*, TERM_ID_ARTISTS.artist_name, TERM_ID_ARTISTS.name FROM BILL_DATA LEFT JOIN TERM_ID_ARTISTS ON BILL_DATA.product_id = TERM_ID_ARTISTS.object_id ORDER BY artist_name ASC, product_name ASC);');

$countOfInsertedCompletedSales = $wpdb->get_var('SELECT COUNT(*) FROM BILL_DATA_SUMMARY_JOINED_ARTISTS');

$table_BILL_DATA_SUMMARY = $wpdb->get_results('SELECT * FROM BILL_DATA_SUMMARY_JOINED_ARTISTS ', ARRAY_A);

$WOOCOMMERCE_FAILS = $wpdb->get_results('SELECT * FROM ORDER_SPECIFIC_DATA WHERE variation_id = 0;', ARRAY_A);

$BILL_DATA_SUMMARY_MICRO = $wpdb->query('CREATE TEMPORARY TABLE BILL_DATA_SUMMARY_MICRO (SELECT artist_name, product_name, product_id, variation_id, rohartikel, farbe, groesse, qualitaet, artikel_type, SUM(qty) AS qty, SUM(marge) AS marge FROM BILL_DATA_SUMMARY_JOINED_ARTISTS GROUP BY variation_id ORDER BY artist_name, product_name ASC,artikel_type ASC, farbe ASC, groesse ASC);');

$BILL_DATA_SUMMARY_MARGE = $wpdb->query('CREATE TEMPORARY TABLE BILL_DATA_SUMMARY_MARGE (SELECT artist_name, SUM(marge) AS total_marge FROM BILL_DATA_SUMMARY_MICRO GROUP BY artist_name)');

$BILL_DATA_SUMMARY_MARGE_MICRO = $wpdb->query('CREATE TEMPORARY TABLE BILL_DATA_SUMMARY_MARGE_MICRO (SELECT artist_name, product_name, SUM(marge) AS total_marge FROM BILL_DATA_SUMMARY_MICRO GROUP BY product_name)');

$table_BILL_DATA_SUMMARY_MARGE = $wpdb->get_results('SELECT * FROM BILL_DATA_SUMMARY_MARGE ORDER BY artist_name', ARRAY_A);

$table_BILL_DATA_SUMMARY_MARGE_MICRO = $wpdb->get_results('SELECT * FROM BILL_DATA_SUMMARY_MARGE_MICRO ORDER BY artist_name ASC, product_name ASC', ARRAY_A);

$table_BILL_DATA_SUMMARY_MICRO = $wpdb->get_results('SELECT * FROM BILL_DATA_SUMMARY_MICRO', ARRAY_A);
// insert into
$wpdb->query('INSERT INTO BILL_DATA_SUMMARY_JOINED_ARTISTS_PERM_TABLE_tmp (SELECT DISTINCT order_item_id, product_name, order_sold_at, email, product_id, variation_id, qty, total_price, single_price, order_id,rohartikel,sku,marge, farbe, basis_preis, artikel_type, groesse, qualitaet, artist_name, name FROM BILL_DATA_SUMMARY_JOINED_ARTISTS)');
$wpdb->query('CREATE TABLE wp_mokkamerch_BILL_DATA_SUMMARY_JOINED_ARTISTS_PERM_TABLE (SELECT DISTINCT order_item_id, product_name, order_sold_at, email, product_id, variation_id, qty, total_price, single_price, order_id,rohartikel,sku,marge, farbe, basis_preis, artikel_type, groesse, qualitaet, artist_name, name FROM BILL_DATA_SUMMARY_JOINED_ARTISTS_PERM_TABLE_tmp)');

$countRowsNewTable = $wpdb->get_var('SELECT COUNT(*) FROM wp_mokkamerch_BILL_DATA_SUMMARY_JOINED_ARTISTS_PERM_TABLE');
$rowsInserted = $countRowsNewTable - $countRowsOldTable;
echo "<h2>".$rowsInserted." new inserted rows. (Mokka Abrechnung)</h2>";
