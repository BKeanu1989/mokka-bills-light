<?php

function timeFormatted($modification = "") {
  $dateNow = date("Y-m-d H:i:s");
  $now= new DateTime("@" . strtotime($dateNow));
  if(!empty($modification)) {
    $now->modify($modification);
  }
  $now = $now->format("Y-m-d H:i:s");
  return $now;
}

// echo "fourteendays: " . timeFormatted("-14 days");
// echo "seventeendays: " . timeFormatted("-17 days");
// $test = timeFormatted();
// echo "test without param: " . $test;
// $fromStatus = "_order_sold";
// $test = get_post_meta(27911, $fromStatus, true);
// echo "test: ". $test;
function modifyTime($startDate, $modification = "") {
  // $date = date($startDate);
  $dateTime = new DateTime($startDate);
  if(!empty($modification)) {
    $dateTime->modify($modification);
  }
  $dateTime = $dateTime->format("Y-m-d H:i:s");
  return $dateTime;
}

function IdsToUpdate($someTimeAgo,$status,$customColumnNameStatus ,$postMetakey, $customTableNameStatusIDS, $customTableNameSomeTimeAgo) {
    global $wpdb;


    $wpdb->query('CREATE TEMPORARY TABLE '.$customTableNameStatusIDS.' (SELECT ID FROM wp_mokkamerch_posts WHERE post_status = "'.$status.'");');


    $wpdb->query($wpdb->prepare('CREATE TEMPORARY TABLE '.$customTableNameSomeTimeAgo.' (SELECT DISTINCT SUB_POSTMETA.post_id AS order_id, SUB_POSTMETA.meta_value as '.$customColumnNameStatus.' FROM wp_mokkamerch_postmeta AS MAIN_POSTMETA JOIN wp_mokkamerch_postmeta AS SUB_POSTMETA ON MAIN_POSTMETA.post_id = SUB_POSTMETA.post_id AND SUB_POSTMETA.meta_key = "'.$postMetakey.'" WHERE SUB_POSTMETA.meta_value < %s);',$someTimeAgo));


    $idsToUpdate = $wpdb->get_results('SELECT * FROM '.$customTableNameStatusIDS.' JOIN '.$customTableNameSomeTimeAgo.' ON '.$customTableNameStatusIDS.'.ID = '.$customTableNameSomeTimeAgo.'.order_id;', ARRAY_A);

    // $idsToUpdate = $wpdb->get_results('SELECT * FROM '.$customTableNameStatusIDS.';',ARRAY_A);
    return $idsToUpdate;

}




$now = timeFormatted("");
$fourteenDaysAgoFormatted = timeFormatted("-14 days");
$seventeenDaysAgoFormatted = timeFormatted("-17 days");
$completedOrdersToUpdate = IdsToUpdate($fourteenDaysAgoFormatted, "wc-completed", "completed_date", "_completed_date", "COMPLETED_ORDER_IDS", "COMPLETED_ORDERS_OLDER_THAN_FOURTEEN_DAYS_AGO");
$deliveryOrdersToUpdate = IdsToUpdate($seventeenDaysAgoFormatted, "wc-delivery", "order_delivery", "_order_delivery", "DELIVERY_ORDER_IDS", "DELIVERY_ORDERS_OLDER_THAN_SEVENTEEN_DAYS_AGO");

function UpdateOrderStatusWithComments($Ids,$now, $fromStatus,$toStatus, $timeModification, $customPostMetakey, $comment_author, $comment_content ) {
    global $wpdb;
    $updatedRows = 0;
    foreach ($Ids AS $key => $ID) {
      $id = $ID['ID'];
      $getTime = get_post_meta($id, $fromStatus, true);
      $calculatedTime = modifyTime($getTime, $timeModification);
        $updatingRow = $wpdb->update(
            $wpdb->prefix . "posts",
            array(
                'post_status' => $toStatus
            ),
// where
            array(
                'ID' => $id
            ),
// format for value updates
            array(
                '%s'
            ),
            // format for where
            array(
                '%d'
            )
        );
        if ($updatingRow) {
            $updatedRows++;

            if (add_post_meta($id, $customPostMetakey, $calculatedTime, true)) {
                $latest_comment_ID = $wpdb->get_var('SELECT comment_ID FROM wp_mokkamerch_omments ORDER BY comment_ID DESC LIMIT 1');
                $new_comment_ID = $latest_comment_ID + 1;
                // if add post meta successful
                $wpdb->insert(
                    $wpdb->prefix . "comments",
                    array(
                        'comment_ID' => $new_comment_ID,
                        'comment_post_ID' => $id,
                        'comment_author' => $comment_author,
                        'comment_author_email' => 'auto_update@mokka-merch.com',
                        'comment_author_url' => 'https://mokka-merch.com',
                        'comment_author_IP' => '',
                        'comment_date' => $now,
                        'comment_date_gmt' => $now,
                        'comment_content' => $comment_content,
                        'comment_karma' => 0,
                        'comment_approved' => 1,
                        'comment_agent' => 'woocommerce-mokka-auto_update',
                        'comment_type' => 'order_note',
                        'comment_parent' => 0,
                        'user_id' => 0
                    ),
                    array(
                        '%d',
                        '%d',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%d',
                        '%d',
                        '%s',
                        '%s',
                        '%d',
                        '%d'
                    )
                );
            }
        }
    }

    return $updatedRows;
}
$resultCompletedToSold = UpdateOrderStatusWithComments($completedOrdersToUpdate, $now, "_completed_date" ,"wc-sold", "+14 days", "_order_sold", "Completed To Sold", "Auto Update: von wc-completed Status auf wc-sold geändert.");
$resultDeliveryToSold = UpdateOrderStatusWithComments($deliveryOrdersToUpdate, $now, "_order_delivery" ,"wc-sold", "+17 days", "_order_sold", "Delivery To Sold", "Auto Update: von wc-delivery Status auf wc-sold geändert.");

/**
 *
 * debugged table
 * with documentation now
 *
 *
 */

global $wpdb;

/**
 * REARRANGE POSTMETA TABLE
 * NAME:
 * COMPLETED_ORDERS_WITH_ORDER_ITEM_ID_AND_NAME
 *
 * Involved Tables:
 * postmeta
 *  - several postmeta_subs
 * woocommerce_order_items
 *
 * Columns:
 * ID, order_sold_at, order_item_id, product_name, email, completed_date, delivery_date,discount
 *
 * Exclude:
 * email - service@mokka-merch.com
 */

$wpdb->query('CREATE TEMPORARY TABLE COMPLETED_ORDERS_WITH_ORDER_ITEM_ID_AND_NAME (SELECT ID, order_sold_at, order_item_id, order_item_name AS product_name, email, completed_date ,delivery_date,discount FROM (SELECT DISTINCT MAIN_POSTMETA.post_id AS ID, ORDER_SOLD_POSTMETA.meta_value AS order_sold_at, EMAIL_POSTMETA.meta_value as email, DISCOUNT_POSTMETA.meta_value AS discount, COMPLETED_DATE.meta_value AS completed_date, DELIVERY_DATE.meta_value AS delivery_date  FROM wp_mokkamerch_postmeta AS MAIN_POSTMETA JOIN wp_mokkamerch_postmeta AS ORDER_SOLD_POSTMETA ON MAIN_POSTMETA.post_id = ORDER_SOLD_POSTMETA.post_id AND ORDER_SOLD_POSTMETA.meta_key = "_order_sold" JOIN wp_mokkamerch_postmeta AS EMAIL_POSTMETA ON MAIN_POSTMETA.post_id = EMAIL_POSTMETA.post_id AND EMAIL_POSTMETA.meta_key = "_billing_email" AND EMAIL_POSTMETA.meta_value <> "service@mokka-merch.com" JOIN wp_mokkamerch_postmeta AS DISCOUNT_POSTMETA ON MAIN_POSTMETA.post_id = DISCOUNT_POSTMETA.post_id AND DISCOUNT_POSTMETA.meta_key = "_cart_discount" LEFT JOIN wp_mokkamerch_postmeta AS COMPLETED_DATE ON MAIN_POSTMETA.post_id = COMPLETED_DATE.post_id AND COMPLETED_DATE.meta_key = "_completed_date" LEFT JOIN wp_mokkamerch_postmeta AS DELIVERY_DATE ON MAIN_POSTMETA.post_id = DELIVERY_DATE.post_id AND DELIVERY_DATE.meta_key = "_order_delivery") AS ALL_COMPLETED_ORDERS JOIN wp_mokkamerch_woocommerce_order_items AS ITEMS ON ALL_COMPLETED_ORDERS.ID = ITEMS.order_id WHERE ITEMS.order_item_type = "line_item");');

/**
 * Push post_date to COMPLETED_ORDERS_WITH_ORDER_ITEM_ID_AND_NAME
 * NAME:
 * COMPLETED_ORDERS_WITH_ORDER_ITEM_ID_AND_NAME
 *
 * Involved Tables:
 * COMPLETED_ORDERS_WITH_ORDER_ITEM_ID_AND_NAME, posts
 *
 * Columns:
 * ID, order_sold_at, order_item_id, product_name, email, discount, post_date
 *
 * Variables:
 * $abrechnungsBeginn, $abrechnungsende
 *
 */
$wpdb->query('CREATE TEMPORARY TABLE ALL_ORDERS_IN_GIVEN_TIMEFRAME (SELECT COMPLETED_ORDERS_WITH_ORDER_ITEM_ID_AND_NAME.*, MAIN_POSTS.post_date FROM COMPLETED_ORDERS_WITH_ORDER_ITEM_ID_AND_NAME JOIN wp_mokkamerch_posts AS MAIN_POSTS ON COMPLETED_ORDERS_WITH_ORDER_ITEM_ID_AND_NAME.ID = MAIN_POSTS.ID AND MAIN_POSTS.post_type = "shop_order" AND MAIN_POSTS.post_status = "wc-sold");');

/**
 * REARRANGE ITEMMETA TABLE
 *
 * NAME:
 * ITEMMETA_SUMMARY
 *
 * Involved Tables:
 * woocommerce_order_itemmeta
 *
 * Columns:
 * order_item_id, product_id, variation_id, qty, total_price, single_price
 *
 * Variables:
 * //
 *
 */

$wpdb->query('CREATE TEMPORARY TABLE ITEMMETA_SUMMARY_vorbereitung (SELECT DISTINCT MAIN_ITEMMETA.order_item_id, PRODUCT_ITEMMETA.meta_value AS product_id, VARIATION_ITEMMETA.meta_value as variation_id, QTY_ITEMMETA.meta_value AS qty, (LINE_TOTAL.meta_value + LINE_TAX.meta_value) AS total_price, ((LINE_TOTAL.meta_value + LINE_TAX.meta_value) / QTY_ITEMMETA.meta_value) AS single_price FROM `wp_mokkamerch_woocommerce_order_itemmeta` AS MAIN_ITEMMETA JOIN wp_mokkamerch_woocommerce_order_itemmeta AS VARIATION_ITEMMETA ON MAIN_ITEMMETA.order_item_id = VARIATION_ITEMMETA.order_item_id AND VARIATION_ITEMMETA.meta_key = "_variation_id" JOIN wp_mokkamerch_woocommerce_order_itemmeta AS QTY_ITEMMETA ON MAIN_ITEMMETA.order_item_id = QTY_ITEMMETA.order_item_id AND QTY_ITEMMETA.meta_key = "_qty" JOIN wp_mokkamerch_woocommerce_order_itemmeta AS LINE_TOTAL ON MAIN_ITEMMETA.order_item_id = LINE_TOTAL.order_item_id AND LINE_TOTAL.meta_key = "_line_total" JOIN wp_mokkamerch_woocommerce_order_itemmeta AS LINE_TAX ON MAIN_ITEMMETA.order_item_id = LINE_TAX.order_item_id AND LINE_TAX.meta_key = "_line_tax" JOIN wp_mokkamerch_woocommerce_order_itemmeta AS PRODUCT_ITEMMETA ON MAIN_ITEMMETA.order_item_id = PRODUCT_ITEMMETA.order_item_id AND PRODUCT_ITEMMETA.meta_key = "_product_id");');

$wpdb->query('CREATE TEMPORARY TABLE ITEMMETA_SUMMARY (SELECT ITEMMETA_SUMMARY_vorbereitung.*, POSTMETA_ONDEMAND.meta_value as product_production FROM ITEMMETA_SUMMARY_vorbereitung JOIN wp_mokkamerch_postmeta AS POSTMETA_ONDEMAND ON ITEMMETA_SUMMARY_vorbereitung.product_id = POSTMETA_ONDEMAND.post_id AND POSTMETA_ONDEMAND.meta_key = "_product_production" AND POSTMETA_ONDEMAND.meta_value = "onDemand");');


/**
 * NAME:
 * ARTIST_MEMORY_DATA
 *
 * Involved Tables:
 * ITEMMETA_SUMMARY, term_relationships
 *
 * Columns:
 * order_item_id, product_id, variation_id, qty, total_price, single_price, object_id, term_taxonomy_id, term_order
 *
 * Variables:
 * //
 *
 */

$wpdb->query('CREATE TEMPORARY TABLE ARTIST_MEMORY_DATA (SELECT * FROM ITEMMETA_SUMMARY JOIN wp_mokkamerch_term_relationships AS TERM_REL ON ITEMMETA_SUMMARY.product_id = TERM_REL.object_id);');

/**
 * NAME:
 * ARTIST_SUMMARY
 *
 * Involved Tables:
 * ARTIST_MEMORY_DATA, terms
 *
 * Columns:
 * order_item_id, product_id, variation_id, qty, total_price, single_price, object_id, term_taxonomy_id, term_order, term_id, name, slug, term_group
 *
 * Variables:
 * //
 *
 */

$wpdb->query('CREATE TEMPORARY TABLE ARTIST_SUMMARY (SELECT * FROM ARTIST_MEMORY_DATA JOIN wp_mokkamerch_terms AS TERMS ON ARTIST_MEMORY_DATA.term_taxonomy_id = TERMS.term_id);');

/**
 * NAME:
 * TERM_ID_ARTISTS
 *
 * Involved Tables:
 * ARTISTS, terms
 *
 * Columns:
 * artist_name, term_id, name, object_id, term_taxonomy_id, term_order
 *
 * Variables:
 * //
 *
 */

$wpdb->query('CREATE TEMPORARY TABLE TERM_ID_ARTISTS (SELECT * FROM (SELECT ARTISTS.artist_name, TERMS.term_id, TERMS.name FROM wp_mokkamerch_artists AS ARTISTS JOIN wp_mokkamerch_terms AS TERMS ON ARTISTS.slug = TERMS.slug) AS SUB_TABLE JOIN wp_mokkamerch_term_relationships AS TERM_REL ON SUB_TABLE.term_id = TERM_REL.term_taxonomy_id );');


/**
 * NAME:
 * ITEMMETA_SUMMARY_PLUS_ORDER_ID
 *
 * Involved Tables:
 * ITEMMETA_SUMMARY, woocommerce_order_items
 *
 * Columns:
 * artist_name, term_id, name, object_id, term_taxonomy_id, term_order
 *
 * Variables:
 * //
 *
 */
$wpdb->query('CREATE TEMPORARY TABLE ITEMMETA_SUMMARY_PLUS_ORDER_ID (SELECT ITEMMETA_SUMMARY.*, ORDER_ITEMS.order_id FROM ITEMMETA_SUMMARY JOIN wp_mokkamerch_woocommerce_order_items AS ORDER_ITEMS ON ITEMMETA_SUMMARY.order_item_id = ORDER_ITEMS.order_item_id);');

/**
 * NAME:
 * ORDER_SPECIFIC_DATA
 *
 * Involved Tables:
 * ALL_ORDERS_IN_GIVEN_TIMEFRAME, ITEMMETA_SUMMARY_PLUS_ORDER_ID
 *
 * Columns:
 * product_name, order_sold_at, email,discount, post_date, order_item_id, product_id, variation_id, qty, total_price, single_price, order_id
 *
 * Variables:
 * //
 *
 */

$wpdb->query('CREATE TEMPORARY TABLE ORDER_SPECIFIC_DATA (SELECT ALL_ORDERS_IN_GIVEN_TIMEFRAME.product_name, ALL_ORDERS_IN_GIVEN_TIMEFRAME.order_sold_at, ALL_ORDERS_IN_GIVEN_TIMEFRAME.email,ALL_ORDERS_IN_GIVEN_TIMEFRAME.discount, ALL_ORDERS_IN_GIVEN_TIMEFRAME.post_date, ALL_ORDERS_IN_GIVEN_TIMEFRAME.completed_date,ALL_ORDERS_IN_GIVEN_TIMEFRAME.delivery_date, ITEMMETA_SUMMARY_PLUS_ORDER_ID.* FROM ALL_ORDERS_IN_GIVEN_TIMEFRAME JOIN ITEMMETA_SUMMARY_PLUS_ORDER_ID ON ALL_ORDERS_IN_GIVEN_TIMEFRAME.order_item_id = ITEMMETA_SUMMARY_PLUS_ORDER_ID.order_item_id);');

/**
 * REARRANGE POSTMETA TABLE
 * NAME:
 * POSTMETA_ROHARTIKEL_v2
 *
 * Involved Tables:
 * postmeta
 *
 * Columns:
 * variation_id, rohartikel, sku
 *
 * Variables:
 * //
 *
 */

$wpdb->query('CREATE TEMPORARY TABLE POSTMETA_ROHARTIKEL_v2 (SELECT DISTINCT MAIN_POSTMETA.post_id AS variation_id, POSTMETA_ROHARTIKEL.meta_value AS rohartikel, POSTMETA_SKU.meta_value AS sku FROM wp_mokkamerch_postmeta AS MAIN_POSTMETA JOIN wp_mokkamerch_postmeta AS POSTMETA_ROHARTIKEL ON MAIN_POSTMETA.post_id = POSTMETA_ROHARTIKEL.post_id AND POSTMETA_ROHARTIKEL.meta_key = "_rohartikel_default" JOIN wp_mokkamerch_postmeta AS POSTMETA_SKU ON MAIN_POSTMETA.post_id = POSTMETA_SKU.post_id AND POSTMETA_SKU.meta_key = "_sku");');

/**
 * NAME:
 * ORDER_DATA_WITH_POSTMETA
 *
 * Involved Tables:
 * ORDER_SPECIFIC_DATA, POSTMETA_ROHARTIKEL_v2
 *
 * Columns:
 * product_name, order_sold_at, email, post_date, order_item_id, product_id, variation_id, qty, total_price, single_price, order_id, rohartikel, sku
 *
 * Variables:
 * //
 *
 */

$wpdb->query('CREATE TEMPORARY TABLE ORDER_DATA_WITH_POSTMETA (SELECT ORDER_SPECIFIC_DATA.*, POSTMETA_ROHARTIKEL_v2.rohartikel, POSTMETA_ROHARTIKEL_v2.sku FROM ORDER_SPECIFIC_DATA JOIN POSTMETA_ROHARTIKEL_v2 ON ORDER_SPECIFIC_DATA.variation_id = POSTMETA_ROHARTIKEL_v2.variation_id);');

/**
 * NAME:
 * BILL_DATA
 *
 * Involved Tables:
 * ORDER_DATA_WITH_POSTMETA
 *
 * Columns:
 * product_name, order_sold_at, email, post_date, order_item_id, product_id, variation_id, qty, total_price, single_price, order_id, rohartikel, sku, marge, farbe, basis_preis, artikel_type, groesse, qualitaet
 *
 * Variables:
 * //
 *
 */

$wpdb->query('CREATE TEMPORARY TABLE BILL_DATA (SELECT ORDER_DATA_WITH_POSTMETA.*, ((ORDER_DATA_WITH_POSTMETA.single_price - ROHARTIKEL.basis_preis) * ORDER_DATA_WITH_POSTMETA.qty /1.19 ) AS marge, ROHARTIKEL.farbe, ROHARTIKEL.basis_preis, ROHARTIKEL.artikel_name AS artikel_type, ROHARTIKEL.groesse, ROHARTIKEL.qualitaet FROM ORDER_DATA_WITH_POSTMETA JOIN wp_mokkamerch_rohartikel AS ROHARTIKEL ON ORDER_DATA_WITH_POSTMETA.rohartikel = ROHARTIKEL.rohartikel_nr_extern);');

/**
 * NAME:
 * BILL_DATA_SUMMARY_JOINED_ARTISTS
 *
 * Involved Tables:
 * BILL_DATA, TERM_ID_ARTISTS
 *
 * Columns:
 * product_name, order_sold_at, email, post_date, order_item_id, product_id, variation_id, qty, total_price, single_price, order_id, rohartikel, sku, marge, farbe, basis_preis, artikel_type, groesse, qualitaet, artist_name, name
 *
 * Variables:
 * //
 *
 */

$wpdb->query('CREATE TEMPORARY TABLE BILL_DATA_SUMMARY_JOINED_ARTISTS (SELECT BILL_DATA.*, TERM_ID_ARTISTS.artist_name, TERM_ID_ARTISTS.name FROM BILL_DATA LEFT JOIN TERM_ID_ARTISTS ON BILL_DATA.product_id = TERM_ID_ARTISTS.object_id ORDER BY artist_name ASC, product_name ASC);');

/**
 *
 * get results of finished queries
 */

$table_BILL_DATA_SUMMARY = $wpdb->get_results('SELECT * FROM BILL_DATA_SUMMARY_JOINED_ARTISTS ', ARRAY_A);

/**
 *
 * Variation = 0
 * ==> woocommerce fail
 *
 */

$WOOCOMMERCE_FAILS = $wpdb->get_results('SELECT * FROM ORDER_SPECIFIC_DATA WHERE variation_id = 0;', ARRAY_A);

/**
 * NAME:
 * THUMBNAIL_TABLE
 *
 * INVOLVED TABLES:
 * postmeta
 * -- subtables
 *
 * COLUMNS:
 * post_id(off all types = pages, posts, etc ), thumbnail_id
 *
 * Get all
 *
 * EXCLUDE:
 * thumbnail_id = 0
 */

$wpdb->query('CREATE TEMPORARY TABLE THUMBNAIL_TABLE (SELECT DISTINCT MAIN_META.post_id, THUMBNAIL_META.meta_value AS thumbnail_id FROM `wp_mokkamerch_postmeta` AS MAIN_META JOIN wp_mokkamerch_postmeta AS THUMBNAIL_META ON MAIN_META.post_id = THUMBNAIL_META.post_id AND THUMBNAIL_META.meta_key = "_thumbnail_id" AND THUMBNAIL_META.meta_value <> 0);');

/**
 * NAME:
 * THUMBNAIL_TABLE_PRODUCT_TYPE
 *
 * INVOLVED TABLES:
 * THUMBNAIL_TABLE, posts
 *
 * COLUMNS:
 * post_id, thumbnail_id, post_type
 *
 *
 *
 *
 */

$wpdb->query('CREATE TEMPORARY TABLE THUMBNAIL_TABLE_PRODUCT_TYPE (SELECT THUMBNAIL_TABLE.*, POSTS.post_type FROM THUMBNAIL_TABLE JOIN wp_mokkamerch_posts AS POSTS ON THUMBNAIL_TABLE.post_id = POSTS.ID AND (POSTS.post_type = "product" OR POSTS.post_type = "product_varation"));');

/**
*
*
*
*
*
*
*/

$wpdb->query('CREATE TEMPORARY TABLE THUMBNAIL_LINKS (SELECT THUMBNAIL_TABLE.*, wp_mokkamerch_posts.guid FROM THUMBNAIL_TABLE JOIN wp_mokkamerch_posts ON THUMBNAIL_TABLE.thumbnail_id = wp_mokkamerch_posts.ID);');
$wpdb->query('CREATE TEMPORARY TABLE TEST_BILL_DATA (SELECT * FROM BILL_DATA_SUMMARY_JOINED_ARTISTS)');

$wpdb->query('CREATE TEMPORARY TABLE THUMBNAIL_LINKS_PERM (SELECT * FROM THUMBNAIL_LINKS)');

/**
*
*
*
*
*
*
*/


$wpdb->query('CREATE TEMPORARY TABLE BILL_DATA_SUMMARY_JOINED_ARTISTS_THUMB (SELECT BILL_DATA_SUMMARY_JOINED_ARTISTS.*, THUMBNAIL_LINKS.guid FROM BILL_DATA_SUMMARY_JOINED_ARTISTS JOIN THUMBNAIL_LINKS ON BILL_DATA_SUMMARY_JOINED_ARTISTS.product_id = THUMBNAIL_LINKS.post_id);');

$wpdb->query('CREATE TEMPORARY TABLE TEST_BILL_DATA_THUMB (SELECT * FROM BILL_DATA_SUMMARY_JOINED_ARTISTS_THUMB)');

/**
*
*
*
*
*
*
*/

// only once for setup
// activation
$wpdb->query('CREATE TABLE IF NOT EXISTS ' .$wpdb->prefix . 'BILL_DATA_SUMMARY_JOINED_ARTISTS_PERM_TABLE(SELECT * FROM BILL_DATA_SUMMARY_JOINED_ARTISTS_THUMB)');
$wpdb->query('CREATE TABLE IF NOT EXISTS ' .$wpdb->prefix . 'WOOCOMMERCE_FAILS_PERM_TABLE (SELECT * FROM ORDER_SPECIFIC_DATA WHERE variation_id = 0)');
