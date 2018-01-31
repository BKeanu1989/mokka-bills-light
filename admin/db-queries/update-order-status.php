<?php
//
//global $wpdb;
//// tracking table
//// newTableToCompareDates = inner join onDeliveryOrders & tracking table
//// get timestamp of newTableToCompareDates
//$orderIDs_Timestamps = $wpdb->get_results('SELECT DISTINCT ' .$wpdb->prefix . 'tracking_test.order_id, ' .$wpdb->prefix . 'tracking_test.timestamp  FROM ' . $wpdb->prefix . 'posts JOIN ' . $wpdb->prefix . 'tracking_test ON ' . $wpdb->prefix . 'posts.ID = ' . $wpdb->prefix . 'tracking_test.order_id where post_status = "wc-delivery"', ARRAY_A);
//
//$dateNow = date("Y-m-d H:i:s");
//$now = new DateTime("@" . strtotime($dateNow));
//$now = $now->format("Y-m-d H:i:s");
//
//$updateOrderStatusArray = array();
//for ($i = 0; $i < count($orderIDs_Timestamps); $i++) {
//    $timestamp = $orderIDs_Timestamps[$i]['timestamp'];
//    $posOfDelimiter = strpos($timestamp, 'T');
//
//    $dateOfDelivery__String = substr($timestamp, 0, $posOfDelimiter);
//
//    $checkOrderSold = new DateTime($dateOfDelivery__String);
//    $checkOrderSold->modify('+17 days');
//    $checkOrderSoldFormatted = $checkOrderSold->format('Y-m-d');
//
//    if ($now > $checkOrderSoldFormatted) {
//        $updateOrderStatusArray[] = $orderIDs_Timestamps[$i]['order_id'];
//    }
//}
//$updatedOrderStatuses = 0;
//foreach($updateOrderStatusArray as $key => $ID) {
//    $updatingRow = $wpdb->update(
//        $wpdb->prefix . "posts",
//        array(
//            'post_status' => 'wc-sold'
//        ),
//// where
//        array(
//            'ID' => $ID
//        ),
//// format for value updates
//        array(
//            '%s'
//        ),
//        // format for where
//        array(
//            '%d'
//        )
//    );
//    if ($updatingRow) {
//        $updatedOrderStatuses++;
//    }
//// add _order_sold to postmeta
//    if (add_post_meta($ID, '_order_sold', $now, true)) {
//        $latest_comment_ID = $wpdb->get_var('SELECT comment_ID FROM ' . $wpdb->prefix .'comments ORDER BY comment_ID DESC LIMIT 1');
//        $new_comment_ID = $latest_comment_ID + 1;
//        // if add post meta successful
//        $wpdb->insert(
//            $wpdb->prefix . "comments",
//            array(
//                'comment_ID' => $new_comment_ID,
//                'comment_post_ID' => $ID,
//                'comment_author' => 'Auto updator',
//                'comment_author_email' => 'auto_update@mokka-merch.com',
//                'comment_author_url' => 'https://mokka-merch.com',
//                'comment_author_IP' => '',
//                'comment_date' => $now,
//                'comment_date_gmt' => $now,
//                'comment_content' => 'Auto Update: Status auf endgültig verkauft.',
//                'comment_karma' => 0,
//                'comment_approved' => 1,
//                'comment_agent' => 'woocommerce-mokka-auto_update',
//                'comment_type' => 'order_note',
//                'comment_parent' => 0,
//                'user_id' => 0
//            ),
//            array(
//                '%d',
//                '%d',
//                '%s',
//                '%s',
//                '%s',
//                '%s',
//                '%s',
//                '%s',
//                '%s',
//                '%d',
//                '%d',
//                '%s',
//                '%s',
//                '%d',
//                '%d'
//            )
//        );
//    }
//}
//
//
//
//echo "<h2>".$updatedOrderStatuses." Status von wc-delivery -> wc-sold aktualisiert.</h2>";
//
//// Change wc-completed to wc-sold, if wc-sold > 14 days ago
//
//$fourteenDaysAgo = new DateTime(date("Y-m-d H:i:s"));
//$fourteenDaysAgo->modify('-14 days');
//$fourteenDaysAgoFormatted = $fourteenDaysAgo->format('Y-m-d H:i:s');
//
//$wpdb->query($wpdb->prepare('CREATE TEMPORARY TABLE ORDERS_FOURTEEN_DAYS_AGO (SELECT DISTINCT SUB_POSTMETA.post_id AS order_id FROM '.$wpdb->prefix.'postmeta AS MAIN_POSTMETA JOIN '.$wpdb->prefix.'postmeta AS SUB_POSTMETA ON MAIN_POSTMETA.post_id = SUB_POSTMETA.post_id AND SUB_POSTMETA.meta_key = "_completed_date" WHERE SUB_POSTMETA.meta_value < %s)', $fourteenDaysAgoFormatted), ARRAY_A);
//$ordersCompletedOlderThanFourteenDaysAgo = $wpdb->get_results('SELECT '.$wpdb->prefix.'posts.ID FROM {$wpdb->prefix}posts JOIN ORDERS_FOURTEEN_DAYS_AGO ON '.$wpdb->prefix.'posts.ID = ORDERS_FOURTEEN_DAYS_AGO.order_id WHERE post_status = "wc-completed"', ARRAY_A);
//
//$updateOrderStatusOfCompletedOrders = array();
//for ($i = 0; $i < count($ordersCompletedOlderThanFourteenDaysAgo); $i++) {
//    $updateOrderStatusOfCompletedOrders[] = $ordersCompletedOlderThanFourteenDaysAgo[$i]['ID'];
//}
//
//
//$updatedOrderCompletedStatuses = 0;
//foreach($updateOrderStatusOfCompletedOrders as $key => $ID) {
//    $updatingRow = $wpdb->update(
//        $wpdb->prefix . "posts",
//        array(
//            'post_status' => 'wc-sold'
//        ),
//// where
//        array(
//            'ID' => $ID
//        ),
//// format for value updates
//        array(
//            '%s'
//        ),
//        // format for where
//        array(
//            '%d'
//        )
//    );
//    if ($updatingRow) {
//        $updatedOrderCompletedStatuses++;
//    }
//// add _order_sold to postmeta
//    if (add_post_meta($ID, '_order_sold', $now, true)) {
//        $latest_comment_ID = $wpdb->get_var('SELECT comment_ID FROM ' . $wpdb->prefix .'comments ORDER BY comment_ID DESC LIMIT 1');
//        $new_comment_ID = $latest_comment_ID + 1;
//        // if add post meta successful
//        $wpdb->insert(
//            $wpdb->prefix . "comments",
//            array(
//                'comment_ID' => $new_comment_ID,
//                'comment_post_ID' => $ID,
//                'comment_author' => 'Completed To Sold',
//                'comment_author_email' => 'auto_update@mokka-merch.com',
//                'comment_author_url' => 'https://mokka-merch.com',
//                'comment_author_IP' => '',
//                'comment_date' => $now,
//                'comment_date_gmt' => $now,
//                'comment_content' => 'Auto Update: von wc-completed Status auf endgültig verkauft geändert.',
//                'comment_karma' => 0,
//                'comment_approved' => 1,
//                'comment_agent' => 'woocommerce-mokka-auto_update',
//                'comment_type' => 'order_note',
//                'comment_parent' => 0,
//                'user_id' => 0
//            ),
//            array(
//                '%d',
//                '%d',
//                '%s',
//                '%s',
//                '%s',
//                '%s',
//                '%s',
//                '%s',
//                '%s',
//                '%d',
//                '%d',
//                '%s',
//                '%s',
//                '%d',
//                '%d'
//            )
//        );
//    }
//}
//echo "<h2>".$updatedOrderCompletedStatuses." Status von wc-completed -> wc-sold aktualisiert.</h2>";
//
//
///**
// *
// * FETCH all orders with post_status wc-delivery
// * join ID ON postmeta.post_id
// *
// * add delivery_date
// *
// */
//
//CREATE TEMPORARY TABLE DELIVERY_ORDERS (SELECT * FROM {$wpdb->prefix}posts WHERE post_status ="wc-delivery");
//
//SELECT DISTINCT DELIVERY_ORDERS.ID, COMPLETED_DATE.meta_value AS delivery_date FROM DELIVERY_ORDERS JOIN {$wpdb->prefix}postmeta AS MAIN_POSTMETA ON DELIVERY_ORDERS.ID = MAIN_POSTMETA.post_id JOIN {$wpdb->prefix}postmeta AS COMPLETED_DATE ON MAIN_POSTMETA.post_id = COMPLETED_DATE.post_id AND COMPLETED_DATE.meta_key = "_order_delivery";
//
//$fourteenDaysAgo = new DateTime(date("Y-m-d H:i:s"));
//$fourteenDaysAgo->modify('-17 days');
//$fourteenDaysAgoFormatted = $fourteenDaysAgo->format('Y-m-d H:i:s');
//
//// ----
//
//CREATE TEMPORARY TABLE COMPLETED_ORDERS (SELECT * FROM {$wpdb->prefix}posts WHERE post_status ="wc-completed");
//
//SELECT COMPLETED_ORDERS.ID, COMPLETED_DATE.meta_value AS completed_date FROM COMPLETED_ORDERS JOIN {$wpdb->prefix}postmeta AS MAIN_POSTMETA ON COMPLETED_ORDERS.ID = MAIN_POSTMETA.post_id JOIN {$wpdb->prefix}postmeta AS COMPLETED_DATE ON MAIN_POSTMETA.post_id = COMPLETED_DATE.post_id AND COMPLETED_DATE.meta_key = "_completed_date";
//
//CREATE TEMPORARY TABLE COMPLETED_ORDERS (SELECT * FROM {$wpdb->prefix}posts WHERE post_status ="wc-sold");
//
//SELECT DISTINCT COMPLETED_ORDERS.ID, COMPLETED_DATE.meta_value AS completed_date FROM COMPLETED_ORDERS JOIN {$wpdb->prefix}postmeta AS MAIN_POSTMETA ON COMPLETED_ORDERS.ID = MAIN_POSTMETA.post_id JOIN {$wpdb->prefix}postmeta AS COMPLETED_DATE ON MAIN_POSTMETA.post_id = COMPLETED_DATE.post_id AND COMPLETED_DATE.meta_key = "_completed_date";
//
//// TODO ORDER STATUS: wc-delivery
//// if it is set, get postmeta _order_delivery
//// fetch that date modify + 17 days
//// if its now > modified_date
////
//
//// get all sold orders where order_sold_at between range
//
//// SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = "_order_delivery" ORDER BY `{$wpdb->prefix}postmeta`.`meta_value` DESC