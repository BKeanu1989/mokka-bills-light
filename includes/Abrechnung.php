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
    $query = $wpdb->prepare("SELECT MAIN_POSTMETA.post_id AS orderID, MAIN_POSTMETA.meta_value AS order_sold_at FROM {$wpdb->prefix}postmeta AS MAIN_POSTMETA JOIN {$wpdb->prefix}postmeta AS EMAIL_POSTMETA ON MAIN_POSTMETA.post_id = EMAIL_POSTMETA.post_id AND EMAIL_POSTMETA.meta_key = '_billing_email' AND EMAIL_POSTMETA.meta_value <> 'service@mokka-merch.com'  WHERE MAIN_POSTMETA.meta_key = '_order_sold' AND MAIN_POSTMETA.meta_value BETWEEN %s AND %s;", $from, $to);
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
      return $orderID["orderID"];
    }, $data);

    $orderIDSAsString = implode(',', $orderIDs);

    $query = "SELECT {$wpdb->prefix}woocommerce_order_items.order_item_id AS orderItemID, {$wpdb->prefix}woocommerce_order_items.order_item_name AS product_name, {$wpdb->prefix}woocommerce_order_items.order_id, {$wpdb->prefix}postmeta.meta_value as order_sold_at FROM {$wpdb->prefix}woocommerce_order_items
              JOIN {$wpdb->prefix}postmeta ON {$wpdb->prefix}woocommerce_order_items.order_id = {$wpdb->prefix}postmeta.post_id AND {$wpdb->prefix}postmeta.meta_key = '_order_sold'
              where order_id
              IN ($orderIDSAsString)
              AND order_item_type = 'line_item';";


    // echo $query;
    $results = $wpdb->get_results($query, ARRAY_A);
    $this->orders = $results;
    $orderItemIDS = array_map(function($orderItemID) {
      return $orderItemID["orderItemID"];
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

        $query = "SELECT {$wpdb->prefix}woocommerce_order_items.order_id AS orderID, {$wpdb->prefix}woocommerce_order_items.order_item_name AS productName ,{$wpdb->prefix}artistProducts.artist_id AS artistID, {$wpdb->prefix}artistProducts.artist_name AS artistName, MAIN_ORDER_ITEMMETA.order_item_id AS orderItemID, {$wpdb->prefix}artistProducts.product_id as productID, MAIN_ORDER_ITEMMETA.meta_value AS variation_id, LINE_TOTAL_ORDER_ITEMMETA.meta_value AS line_price, LINE_TAX_ORDER_ITEMMETA.meta_value AS line_tax
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
    $uniqueArtists = array_unique(array_column($data, 'artistName'));
    array_map(function($artist) {
      $this->artists[$artist] = [];
    }, $uniqueArtists);

    foreach ($uniqueArtists as $key => $artistName) {
      $this->artists[$artistName]["products"] = groupSameArtistIDs($data, $artistName);
    }
  }

/**
 * Add artistDetails to Abrechnung->artists
 * @param  [type] $data default = $this->parents
 * @var string artistName
 * @var int initBillNumber
 * @var string firstName
 * @var string lastName
 * @var date registration
 * @var boolean brutto
 * @var decimal extraCharge
 * @return [type]       [description]
 */

  public function getArtistsData( $data = null) {
    global $wpdb;

    if ($data == null) {
      $data = $this->artists;
    }

    foreach($data AS $artistName => $artistData) {
      $query = $wpdb->prepare("SELECT artist_name, init_bill_number, vorname, nachname, brutto, registration, extra_charge FROM {$wpdb->prefix}artists WHERE artist_name = %s", $artistName);
      $results = $wpdb->get_row($query, ARRAY_A);

      $this->artists[$artistName]["artistDetails"] = [];
      $this->artists[$artistName]["artistDetails"]["artistName"] = $results["artist_name"];
      $this->artists[$artistName]["artistDetails"]["initBillNumber"] = $results["init_bill_number"];
      $this->artists[$artistName]["artistDetails"]["firstName"] = $results["vorname"];
      $this->artists[$artistName]["artistDetails"]["lastName"] = $results["nachname"];
      $this->artists[$artistName]["artistDetails"]["registration"] = $results["registration"];
      $this->artists[$artistName]["artistDetails"]["brutto"] = $results["brutto"];
      $this->artists[$artistName]["artistDetails"]["extraCharge"] = $results["extra_charge"];

    }
  }

  /**
   * add productProduction (onDemand / Sieb) & oderShippingCost & extra_charge
   * @var string productProduction
   * @var float orderShippingCost
   * @var float extraCharge
   * @var int orderID
   * @param  [type] $data [description]
   * @return [type]       [description]
   */

  public function getProductData($data = null) {
    global $wpdb;

    if ($data == null) {
      $data = $this->artists;
    }

    foreach ($data as $artistName => $artistData) {
      $productCount = count($artistData["products"]);
      for($i = 0; $i < $productCount; $i++) {
        $productID = $artistData["products"][$i]["productID"];
        $query = $wpdb->prepare("SELECT meta_value AS productProduction FROM {$wpdb->prefix}postmeta WHERE post_id = %d AND meta_key = '_product_production'", $productID);
        $result = $wpdb->get_row($query, ARRAY_A);
        $this->artists[$artistName]["products"][$i]["productProduction"] = $result["productProduction"];

        $orderID = $artistData["products"][$i]["orderID"];
        // $orderID = $this->artists[$artistName]["products"][$i]["orderID"];

        // find value of order_id in $this->orderIDs
        // to add order_sold_at
        $key = array_search($orderID, array_column($this->orderIDs, 'order_id'));
        $this->artists[$artistName]["products"][$i]["orderSoldAt"] = $this->orderIDs[$key]["order_sold_at"];


        $queryForShippingCost = $wpdb->prepare("SELECT {$wpdb->prefix}woocommerce_order_itemmeta.meta_key,  {$wpdb->prefix}woocommerce_order_itemmeta.meta_value
        FROM {$wpdb->prefix}woocommerce_order_items
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta ON {$wpdb->prefix}woocommerce_order_items.order_item_id = {$wpdb->prefix}woocommerce_order_itemmeta.order_item_id AND ({$wpdb->prefix}woocommerce_order_itemmeta.meta_key = 'cost' OR {$wpdb->prefix}woocommerce_order_itemmeta.meta_key = 'shipping_tax_amount')
        WHERE {$wpdb->prefix}woocommerce_order_items.order_id = %d", $orderID);

        $shippingData = $wpdb->get_results($queryForShippingCost, ARRAY_A);

        $shippingNetto = $shippingData[0]["meta_value"] ? $shippingData[0]["meta_value"] : 0;
        $shippingTax = $shippingData[1]["meta_value"] ? $shippingData[1]["meta_value"] : 0;
        $shippingCost =  $shippingNetto + $shippingTax;

        // $shippingNetto = $shippingData[]
        $this->artists[$artistName]["products"][$i]["orderShippingCost"] = $shippingCost;

        // TODO assumes extra_charge is product dependend (NOT variation dependend)

        $queryForExtraCharge = $wpdb->prepare("SELECT extra_charge FROM {$wpdb->prefix}artistProducts WHERE product_id = %d LIMIT 1", $productID);
        echo "queryForExtraCharge: " . $queryForExtraCharge;
        $extraChargeResults = $wpdb->get_row($queryForExtraCharge, ARRAY_A);

        $this->artists[$artistName]["products"][$i]["extraCharge"] = $extraChargeResults["extra_charge"];

      }
    }
  }


/**
 * get Variation Data from table rohartikel via rohartikel from postmeta
 * @var table postmeta, rohartikel
 * @param  [type] $data [description]
 * @return [type]       [description]
 */
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
        $result = $wpdb->get_row($query, ARRAY_A);
        $rohartikelNummer = $result["rohartikelnr"];
        $this->artists[$artistName]["products"][$i]["rohartikelnr"] = $rohartikelNummer;

        $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}rohartikel WHERE rohartikel_nr_extern = %d", $rohartikelNummer);
        $rohartikelData = $wpdb->get_row($query,ARRAY_A);
        $this->artists[$artistName]["products"][$i]["color"] = $rohartikelData["farbe"];
        $this->artists[$artistName]["products"][$i]["size"] = $rohartikelData["groesse"];
        $this->artists[$artistName]["products"][$i]["quality"] = $rohartikelData["qualitaet"];
        $this->artists[$artistName]["products"][$i]["articleType"] = $rohartikelData["artikel_name"];
        $this->artists[$artistName]["products"][$i]["basicPrice"] = $rohartikelData["basis_preis"];
      }
    }
  }

  /**
   * add Marge to Data: (this->artists)
   * apply different calculations depending on $brutto & productProduction
   *
   * @param [type] $data pass by reference
   */


  public function addMarge(&$data = null) {
    if ($data == null) {
      $data = $this->artists;
    }

    foreach ($data as $artistName => $artistData) {
      $productCount = count($artistData["products"]);
      for($i = 0; $i < $productCount; $i++) {

        self::calculateMarge($this->artists[$artistName]["products"][$i], $this->artists[$artistName]["artistDetails"]["brutto"], $this->artists[$artistName]["artistDetails"]["extraCharge"]);
      }
    }
  }

  public function calculateMarge(&$singleProductArray, $brutto, $artistExtraCharge) {
    // $netto = true;
    // $brutto ? $netto = false : $netto = true;
    $onDemand = true;

    if ($singleProductArray["productProduction"] == 'sieb') {
      $onDemand = false;
    }

    $onDemand ? self::calculateMargeOnDemand($singleProductArray, $brutto, $artistExtraCharge) : self::calculateMargeSieb($singleProductArray, $brutto, $artistExtraCharge);

  }

  public function calculateMargeOnDemand(&$singleProductArray, $brutto, $artistExtraCharge) {

    $brutto ? $divider = 1 : $divider = 1.19;
    $totalExtraCharge = $artistExtraCharge + $singleProductArray["extraCharge"];
    echo "{$singleProductArray["productName"]}: {$totalExtraCharge}";
    $singleProductArray["marge"] = (($singleProductArray["line_price"] + $singleProductArray["line_tax"] - $singleProductArray["basicPrice"] - $totalExtraCharge ) / $divider) ;
  }

  public function calculateMargeSieb(&$singleProductArray, $brutto, $artistExtraCharge) {

    $payPalRate = self::PAYPAL_RATE;
    $payPalfixedValue = self::PAYPAL_FIXED_VALUE;
    $pickAndPack = self::PICK_AND_PACK;
    $mokkaFee = self::SERVICE_FEE;
    $mwst = self:: MWST;

    $sellingPrice = $singleProductArray["line_price"] + $singleProductArray["line_tax"];
    $shipping = $singleProductArray["orderShippingCost"];
    $payPalCosts = $payPalfixedValue + ($payPalRate * ($shipping + $sellingPrice) / 100);

    $mokkaZwischenMarge = ceilFloat($payPalCosts + $pickAndPack + ($mokkaFee * $sellingPrice));
    $totalExtraCharge = $artistExtraCharge + $singleProductArray["extraCharge"];


    if ($brutto) {
      $mokkaMarge = $mokkaZwischenMarge * $mwst;
    } else {
      $mokkaMarge = $mokkaZwischenMarge + $singleProductArray["line_tax"];
    }
    $singleProductArray["marge"] = $sellingPrice - $mokkaMarge - $totalExtraCharge;
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
