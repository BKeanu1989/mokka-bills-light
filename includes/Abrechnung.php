<?php
class Abrechnung {
  //
  public $orderIDs = [];

  public $orderItemIDs = [];

  public $orders = [];
  // artist_ids
  public $artists = [];
  public function __construct($from, $to) {
    self::getOrdersInTimeFrame($from, $to);
  }

  public function getOrdersInTimeFrame($from, $to) {
    global $wpdb;
    $query = $wpdb->prepare("SELECT MAIN_POSTMETA.post_id AS order_id, MAIN_POSTMETA.meta_value AS order_sold_at FROM {$wpdb->prefix}postmeta AS MAIN_POSTMETA JOIN {$wpdb->prefix}postmeta AS EMAIL_POSTMETA ON MAIN_POSTMETA.post_id = EMAIL_POSTMETA.post_id AND EMAIL_POSTMETA.meta_key = '_billing_email' AND EMAIL_POSTMETA.meta_value <> 'service@mokka-merch.com'  WHERE MAIN_POSTMETA.meta_key = '_order_sold' AND MAIN_POSTMETA.meta_value BETWEEN %s AND %s;", $from, $to);
    // echo $query;
    $results = $wpdb->get_results($query, ARRAY_A);

    $this->orderIDs = $results;
    return $results;
  }

  public function getProductsInOrders($data = null) {
    global $wpdb;

    if ($data == null) {
      $data = $this->orderIDs;
    }

    $orderIDs = array_map(function($orderID) {
      return $orderID["order_id"];
    }, $data);

    $orderIDSAsString = implode(',', $orderIDs);

    $query = "SELECT {$wpdb->prefix}woocommerce_order_items.order_item_id, {$wpdb->prefix}woocommerce_order_items.order_item_name AS product_name, {$wpdb->prefix}woocommerce_order_items.order_id, {$wpdb->prefix}postmeta.meta_value as order_sold_at FROM {$wpdb->prefix}woocommerce_order_items
              JOIN {$wpdb->prefix}postmeta ON {$wpdb->prefix}woocommerce_order_items.order_id = {$wpdb->prefix}postmeta.post_id AND {$wpdb->prefix}postmeta.meta_key = '_order_sold'
              where order_id
              IN ($orderIDSAsString)
              AND order_item_type = 'line_item';";


    // echo $query;
    $results = $wpdb->get_results($query, ARRAY_A);
    $this->orders = $results;
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
    $query = "SELECT {$wpdb->prefix}woocommerce_order_items.order_id,{$wpdb->prefix}artistProducts.artist_id, {$wpdb->prefix}artistProducts.artist_name, MAIN_ORDER_ITEMMETA.order_item_id, {$wpdb->prefix}artistProducts.product_id , MAIN_ORDER_ITEMMETA.meta_value AS variation_id, LINE_TOTAL_ORDER_ITEMMETA.meta_value AS line_price, LINE_TAX_ORDER_ITEMMETA.meta_value AS line_tax
        FROM {$wpdb->prefix}woocommerce_order_itemmeta AS MAIN_ORDER_ITEMMETA
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS LINE_TOTAL_ORDER_ITEMMETA
          ON MAIN_ORDER_ITEMMETA.order_item_id = LINE_TOTAL_ORDER_ITEMMETA.order_item_id AND LINE_TOTAL_ORDER_ITEMMETA.meta_key = '_line_total'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS LINE_TAX_ORDER_ITEMMETA
          ON MAIN_ORDER_ITEMMETA.order_item_id = LINE_TAX_ORDER_ITEMMETA.order_item_id AND LINE_TAX_ORDER_ITEMMETA.meta_key = '_line_tax'
        JOIN {$wpdb->prefix}artistProducts
          ON MAIN_ORDER_ITEMMETA.meta_value = {$wpdb->prefix}artistProducts.variation_id
        JOIN {$wpdb->prefix}woocommerce_order_items ON MAIN_ORDER_ITEMMETA.order_item_id = {$wpdb->prefix}woocommerce_order_items.order_item_id
        WHERE (MAIN_ORDER_ITEMMETA.meta_key = '_variation_id') AND MAIN_ORDER_ITEMMETA.order_item_id IN ($orderItemIDsAsString)";
    // echo $query;

    $results = $wpdb->get_results($query, ARRAY_A);

    self::nonEmptyArtistBills($results);

  }

  public function nonEmptyArtistBills($data) {
    $uniqueArtists = array_unique(array_column($data, 'artist_name'));
    array_map(function($artist) {
      $this->artists[$artist] = [];
    }, $uniqueArtists);

    foreach ($uniqueArtists as $key => $artistName) {
      $this->artists[$artistName]["products"] = groupSameArtistIDs($data, $artistName);
    }
  }



  public function getArtists() {
    global $wpdb;

    $query = "SELECT {$wpdb->prefix}artists.artist_name, {$wpdb->prefix}artists.init_bill_number, {$wpdb->prefix}artists.vorname, {$wpdb->prefix}artists.nachname, {$wpdb->prefix}terms.term_id FROM {$wpdb->prefix}artists JOIN {$wpdb->prefix}terms ON {$wpdb->prefix}artists.slug = {$wpdb->prefix}terms.slug";

    $results = $wpdb->get_results($query, ARRAY_A);

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

        $productID = $artistData["products"][$i]["product_id"];
        $orderID = $artistData["products"][$i]["order_id"];
        $query = $wpdb->prepare("SELECT meta_value AS productId FROM {$wpdb->prefix}postmeta WHERE post_id = %d AND meta_key = '_product_production'", $productID);
        $result = $wpdb->get_results($query, ARRAY_A);
        $this->artists[$artistName]["products"][$i]["productProduction"] = $result[0]["productId"];

        // find value of order_id in $this->orderIDs
        // to add order_sold_at
        $key = array_search($orderID, array_column($this->orderIDs, 'order_id'));
        $this->artists[$artistName]["products"][$i]["order_sold_at"] = $this->orderIDs[$key]["order_sold_at"];

      }
    }
  }

  public function getVariationData($data = null) {
    global $wpdb;

    if ($data == null) {
      $data = $this->artists;
    }

    foreach ($data as $artistName => $artistData) {
      $productCount = count($artistData["products"]);
      for($i = 0; $i < $productCount; $i++) {
        $variationID = $artistData["products"][$i]["variation_id"];
        $query = $wpdb->prepare("SELECT MAIN_POSTMETA.meta_value AS variation_id, ROHARTIKEL_META.meta_value as rohartikelnr FROM {$wpdb->prefix}postmeta AS MAIN_POSTMETA
          JOIN {$wpdb->prefix}postmeta AS ROHARTIKEL_META ON MAIN_POSTMETA.post_id = ROHARTIKEL_META.post_id AND ROHARTIKEL_META.meta_key = '_rohartikel_default'
          WHERE MAIN_POSTMETA.post_id = %d ORDER BY MAIN_POSTMETA.meta_id LIMIT 1", $variationID);
          // echo $query;
        $result = $wpdb->get_results($query, ARRAY_A);
        $rohartikelNummer = $result[0]["rohartikelnr"];
        $this->artists[$artistName]["products"][$i]["rohartikelnr"] = $rohartikelNummer;

        $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}rohartikel WHERE rohartikel_nr_extern = %d", $rohartikelNummer);
        $rohartikelData = $wpdb->get_results($query,ARRAY_A);
        $this->artists[$artistName]["products"][$i]["color"] = $rohartikelData[0]["farbe"];
        $this->artists[$artistName]["products"][$i]["size"] = $rohartikelData[0]["groesse"];
        $this->artists[$artistName]["products"][$i]["quality"] = $rohartikelData[0]["qualitaet"];
        $this->artists[$artistName]["products"][$i]["article_type"] = $rohartikelData[0]["artikel_name"];
        $this->artists[$artistName]["products"][$i]["basis_preis"] = $rohartikelData[0]["basis_preis"];
      }
    }
  }

  public function addMarge(&$data = null) {
    if ($data == null) {
      $data = $this->artists;
    }

    foreach ($data as $artistName => $artistData) {
      $productCount = count($artistData["products"]);
      for($i = 0; $i < $productCount; $i++) {

        calculateMarge($this->artists[$artistName]["products"][$i]);
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

function calculateMarge(&$singleProductArray) {
  $netto = false;
  $onDemand = true;
  if (isset($singleProductArray["netto"])) {
    $netto = $singleProductArray["netto"];
  }



  if ($singleProductArray["productProduction"] == 'sieb') {
    $onDemand = false;
  }
  $onDemand ? calculateMargeOnDemand($singleProductArray) : calculateMargeSieb($singleProductArray);

}

function calculateMargeOnDemand(&$singleProductArray) {
  $singleProductArray["marge"] = (($singleProductArray["line_price"] + $singleProductArray["line_tax"] - $singleProductArray["basis_preis"] ) / 1.19) ;
}

function calculateMargeSieb(&$singleProductArray) {

}
