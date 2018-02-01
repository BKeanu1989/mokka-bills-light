<?php
class Abrechnung {
  public $orderIDs = [];
  public $orderItemIDs = [];
  // artist_ids
  public $artists = [];
  public function __construct($from, $to) {
    self::getOrdersInTimeFrame($from, $to);
  }

  public function getOrdersInTimeFrame($from, $to) {
    global $wpdb;
    $query = $wpdb->prepare("SELECT MAIN_POSTMETA.post_id, MAIN_POSTMETA.meta_value AS order_sold_at FROM wp_mokkamerch_postmeta AS MAIN_POSTMETA JOIN wp_mokkamerch_postmeta AS EMAIL_POSTMETA ON MAIN_POSTMETA.post_id = EMAIL_POSTMETA.post_id AND EMAIL_POSTMETA.meta_key = '_billing_email' AND EMAIL_POSTMETA.meta_value <> 'service@mokka-merch.com'  WHERE MAIN_POSTMETA.meta_key = '_order_sold' AND MAIN_POSTMETA.meta_value BETWEEN %s AND %s;", $from, $to);
    echo $query;
    $results = $wpdb->get_results($query, ARRAY_A);

    $orderIDs = array_map(function($orderID) {
      return $orderID["post_id"];
    }, $results);

    $this->orderIDs = $orderIDs;
    return $orderIDs;
  }

  public function getProductsInOrders($orderIDS = null) {
    global $wpdb;

    if ($orderIDS == null) {
      $orderIDS = $this->orderIDs;
    }

    $orderIDSAsString = implode(',', $orderIDS);

    $query = "SELECT * FROM {$wpdb->prefix}woocommerce_order_items where order_id IN ($orderIDSAsString) AND order_item_type = 'line_item'";
    $results = $wpdb->get_results($query, ARRAY_A);

    $orderItemIDS = array_map(function($orderItemID) {
      return $orderItemID["order_item_id"];
    }, $results);

    $this->orderItemIDs = $orderItemIDS;
    return $orderItemIDS;
  }

  public function assignArtistToProducts($orderItemIDs = null) {
    global $wpdb;

    if ($orderItemIDs == null) {
      $orderItemIDs = $this->orderItemIDs;
    }

    $orderItemIDsAsString = implode(',', $orderItemIDs);
    $query = "SELECT wp_mokkamerch_artistProducts.artist_id, wp_mokkamerch_artistProducts.artist_name,wp_mokkamerch_woocommerce_order_itemmeta.order_item_id, wp_mokkamerch_artistProducts.product_id , wp_mokkamerch_woocommerce_order_itemmeta.meta_value AS variation_id FROM wp_mokkamerch_woocommerce_order_itemmeta JOIN wp_mokkamerch_artistProducts ON wp_mokkamerch_woocommerce_order_itemmeta.meta_value = wp_mokkamerch_artistProducts.variation_id WHERE (meta_key = '_variation_id') AND order_item_id IN ($orderItemIDsAsString)";
    // echo $query;

    $results = $wpdb->get_results($query, ARRAY_A);
    // echo "<pre>";
    // print_r($results);
    // echo "</pre>";

    self::nonEmptyArtistBills($results);
    // echo "<pre>";
    // print_r($uniqueArtists);
    // echo "</pre>";
  }

  public function nonEmptyArtistBills($data) {
    $uniqueArtists = array_unique(array_column($data, 'artist_name'));
    array_map(function($artist) {
      $this->artists[$artist] = [];
      // $this->artists[$artist]["soldProducts"] = [];
      // $this->artists[$artist]["artistDetails"] = [];
    }, $uniqueArtists);

    foreach ($uniqueArtists as $key => $artistName) {
      $this->artists[$artistName]["products"] = groupSameArtistIDs($data, $artistName);
    }
  }



  public function getArtists() {
    global $wpdb;

    $query = "SELECT {$wpdb->prefix}artists.artist_name, {$wpdb->prefix}artists.init_bill_number, {$wpdb->prefix}artists.vorname, {$wpdb->prefix}artists.nachname, {$wpdb->prefix}terms.term_id FROM {$wpdb->prefix}artists JOIN {$wpdb->prefix}terms ON {$wpdb->prefix}artists.slug = {$wpdb->prefix}terms.slug";

    $results = $wpdb->get_results($query, ARRAY_A);
    // echo "<pre>";
    // print_r($results);
    // echo "</pre>";

  }

  public function getProductData($data = null) {
    global $wpdb;

    if ($data == null) {
      $data = $this->artists;
    }

    foreach ($data as $artistName => $artistData) {
      $productCount = count($artistData["products"]);
      for($i = 0; $i < $productCount; $i++) {
        // _article_type
        //_product_production
        // _thumbnail_id
        $query = $wpdb->prepare("SELECT meta_value AS productId FROM {$wpdb->prefix}postmeta WHERE post_id = %d AND meta_key = '_product_production'", $artistData["products"][$i]["product_id"]);
        $result = $wpdb->get_results($query, ARRAY_A);
        $this->artists[$artistName]["products"][$i]["productProduction"] = $result[0]["productId"];
      }
    }
  }

  public function getVariationData($data = null) {
    global $wpdb;

    if ($data = null) {
      $data = $this->artists;
    }

    foreach ($data as $artistName => $artistData) {
      $productCount = count($artistData["products"]);
      for($i = 0; $i < $productCount; $i++) {
        // attribute_pa_groesse
        // attribute_pa_farbe
        // attribute_pa_modell
        $query = $wpdb->prepare("SELECT meta_value AS productId FROM {$wpdb->prefix}postmeta WHERE post_id = %d AND meta_key = '_product_production'", $artistData["products"][$i]["variation_id"]);
        $result = $wpdb->get_results($query, ARRAY_A);
        $this->artists[$artistName]["products"][$i]["productProduction"] = $result[0]["productId"];
      }
    }


  }

}

function groupSameArtistIDs($dataArray, $singleID) {
  $array = [];
  foreach($dataArray as $key => $value) {
    if (in_array($singleID, $value)) {
      $array[] = $value;
    }
  }
  return $array;
}
