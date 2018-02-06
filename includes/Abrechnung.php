<?php
class Abrechnung {
  //
  // public const PAYPAL_FIXED_VALUE = 0.35;
  const PAYPAL_FIXED_VALUE = 0.35;
  const PAYPAL_RATE = 1.9;
  const SERVICE_FEE = 6.9 / 100;
  const PICK_AND_PACK = 1.55;
  const MWST = 1.19;
  public $orderIDs = [];
  public $orderItemIDs = [];

  public $orders = [];
  // artist_ids
  public $artists = [];
  public function __construct($from, $to) {
    self::getOrdersInTimeFrame($from, $to);
  }

  /**
   * Get all Order IDs & date of sold order
   * @var string orderID
   * @var string order_sold_at
   * exlude: 'service@mokka-merch.com'
   *
   * sets orderIDs of instance
   *
   * @param  string $from [description]
   * @param  string $to   [description]
   * @return array       [description]
   */

  public function getOrdersInTimeFrame($from, $to) {
    global $wpdb;
    $query = $wpdb->prepare("SELECT MAIN_POSTMETA.post_id AS order_id, MAIN_POSTMETA.meta_value AS order_sold_at FROM {$wpdb->prefix}postmeta AS MAIN_POSTMETA JOIN {$wpdb->prefix}postmeta AS EMAIL_POSTMETA ON MAIN_POSTMETA.post_id = EMAIL_POSTMETA.post_id AND EMAIL_POSTMETA.meta_key = '_billing_email' AND EMAIL_POSTMETA.meta_value <> 'service@mokka-merch.com'  WHERE MAIN_POSTMETA.meta_key = '_order_sold' AND MAIN_POSTMETA.meta_value BETWEEN %s AND %s;", $from, $to);
    // echo $query;
    $results = $wpdb->get_results($query, ARRAY_A);

    $this->orderIDs = $results;
    return $results;
  }

/**
 *
 * @param  [type] $data [description]
 * @return [type]       [description]
 */

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
    // $query = "SELECT {$wpdb->prefix}woocommerce_order_items.order_id,{$wpdb->prefix}artistProducts.artist_id, {$wpdb->prefix}artistProducts.artist_name, MAIN_ORDER_ITEMMETA.order_item_id, {$wpdb->prefix}artistProducts.product_id , MAIN_ORDER_ITEMMETA.meta_value AS variation_id, LINE_TOTAL_ORDER_ITEMMETA.meta_value AS line_price, LINE_TAX_ORDER_ITEMMETA.meta_value AS line_tax
    //     FROM {$wpdb->prefix}woocommerce_order_itemmeta AS MAIN_ORDER_ITEMMETA
    //     JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS LINE_TOTAL_ORDER_ITEMMETA
    //       ON MAIN_ORDER_ITEMMETA.order_item_id = LINE_TOTAL_ORDER_ITEMMETA.order_item_id AND LINE_TOTAL_ORDER_ITEMMETA.meta_key = '_line_total'
    //     JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS LINE_TAX_ORDER_ITEMMETA
    //       ON MAIN_ORDER_ITEMMETA.order_item_id = LINE_TAX_ORDER_ITEMMETA.order_item_id AND LINE_TAX_ORDER_ITEMMETA.meta_key = '_line_tax'
    //     JOIN {$wpdb->prefix}artistProducts
    //       ON MAIN_ORDER_ITEMMETA.meta_value = {$wpdb->prefix}artistProducts.variation_id
    //     JOIN {$wpdb->prefix}woocommerce_order_items ON MAIN_ORDER_ITEMMETA.order_item_id = {$wpdb->prefix}woocommerce_order_items.order_item_id
    //     WHERE (MAIN_ORDER_ITEMMETA.meta_key = '_variation_id') AND MAIN_ORDER_ITEMMETA.order_item_id IN ($orderItemIDsAsString)";

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

    echo $query;

    $results = $wpdb->get_results($query, ARRAY_A);

    self::nonEmptyArtistBills($results);
    self::getArtistsData();
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



  public function getArtistsData( $data = null) {
    global $wpdb;

    if ($data == null) {
      $data = $this->artists;
    }

    // $query = "SELECT {$wpdb->prefix}artists.artist_name, {$wpdb->prefix}artists.init_bill_number, {$wpdb->prefix}artists.vorname, {$wpdb->prefix}artists.nachname, {$wpdb->prefix}terms.term_id, {$wpdb->prefix}artists.brutto, {$wpdb->prefix}artists.registration FROM {$wpdb->prefix}artists JOIN {$wpdb->prefix}terms ON {$wpdb->prefix}artists.slug = {$wpdb->prefix}terms.slug";

    foreach($data AS $artistName => $artistData) {
      $query = $wpdb->prepare("SELECT artist_name, init_bill_number, vorname, nachname, brutto, registration FROM {$wpdb->prefix}artists WHERE artist_name = %s", $artistName);
      $results = $wpdb->get_results($query, ARRAY_A);

      // echo "<pre>";
      // print_r($results);
      // echo "</pre>";

      $this->artists[$artistName]["artistDetails"] = [];
      $this->artists[$artistName]["artistDetails"]["artistName"] = $results[0]["artist_name"];
      $this->artists[$artistName]["artistDetails"]["initBillNumber"] = $results[0]["init_bill_number"];
      $this->artists[$artistName]["artistDetails"]["firstName"] = $results[0]["vorname"];
      $this->artists[$artistName]["artistDetails"]["lastName"] = $results[0]["nachname"];
      $this->artists[$artistName]["artistDetails"]["registration"] = $results[0]["registration"];
      $this->artists[$artistName]["artistDetails"]["brutto"] = $results[0]["brutto"];

    }
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


        // TODO 2 bestellungen in einer order

        $orderID = $this->artists[$artistName]["products"][$i]["order_id"];

        $queryForShippingCost = $wpdb->prepare("SELECT {$wpdb->prefix}woocommerce_order_itemmeta.meta_value as shipping_cost
          FROM {$wpdb->prefix}woocommerce_order_items
          JOIN {$wpdb->prefix}woocommerce_order_itemmeta ON {$wpdb->prefix}woocommerce_order_items.order_item_id = {$wpdb->prefix}woocommerce_order_itemmeta.order_item_id AND {$wpdb->prefix}woocommerce_order_itemmeta.meta_key = 'cost'
          WHERE {$wpdb->prefix}woocommerce_order_items.order_id = %s", $orderID);
          echo "<br>";
          echo $queryForShippingCost;
        $shippingCost = $wpdb->get_var($queryForShippingCost);

        $this->artists[$artistName]["products"][$i]["orderShippingCost"] = $shippingCost;

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

        self::calculateMarge($this->artists[$artistName]["products"][$i], $this->artists[$artistName]["artistDetails"]["brutto"]);
      }
    }
  }

  function setPickAndPack(&$singleProductArray) {

  }

  function calculateMarge(&$singleProductArray, $brutto) {
    // $netto = true;
    // $brutto ? $netto = false : $netto = true;
    $onDemand = true;

    if ($singleProductArray["productProduction"] == 'sieb') {
      $onDemand = false;
    }

    $onDemand ? self::calculateMargeOnDemand($singleProductArray, $brutto) : self::calculateMargeSieb($singleProductArray, $brutto);

  }

  function calculateMargeOnDemand(&$singleProductArray, $brutto) {

    $brutto ? $divider = 1 : $divider = 1.19;

    $singleProductArray["marge"] = (($singleProductArray["line_price"] + $singleProductArray["line_tax"] - $singleProductArray["basis_preis"] ) / $divider) ;
  }

  function calculateMargeSieb(&$singleProductArray, $brutto) {

    $payPalRate = self::PAYPAL_RATE;
    $payPalfixedValue = self::PAYPAL_FIXED_VALUE;
    $pickAndPack = self::PICK_AND_PACK;
    $mokkaFee = self::SERVICE_FEE;
    $mwst = self:: MWST;

    $sellingPrice = $singleProductArray["line_price"] + $singleProductArray["line_tax"];
    $shipping = $singleProductArray["orderShippingCost"];
    $berechnePayPal = $payPalfixedValue + ($payPalRate * ($shipping + $sellingPrice) / 100);

    $mokkaZwischenMarge = ceilFloat($berechnePayPal + $pickAndPack + ($mokkaFee * $sellingPrice));

    if ($brutto) {
      $mokkaMarge = $mokkaZwischenMarge * $mwst;
    } else {
      $mokkaMarge = $mokkaZwischenMarge + $singleProductArray["line_tax"];
    }

    $singleProductArray["marge"] = $sellingPrice - $mokkaMarge;

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

/**
 * always rounds an float up to the second digit
 * @param  float $number [description]
 * @return float          [description]
 */
function ceilFloat($number) {
  $roundedUP = ceil($number * 100);
  return $roundedUP / 100;
}
