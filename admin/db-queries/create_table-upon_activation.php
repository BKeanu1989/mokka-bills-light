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
