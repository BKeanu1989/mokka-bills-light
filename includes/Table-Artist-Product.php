<?php
class TableArtistProduct {
  // create Table

  public function __construct() {
    global $wpdb;
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}artistProducts (id INT(255) AUTO_INCREMENT PRIMARY KEY, artist_name varchar(40) NOT NULL, artist_id INT(255) NOT NULL, product_name varchar(50) NOT NULL,product_id INT(255) NOT NULL, extra_charge DECIMAL(5,2), variation_id INT(255) NOT NULL)");

    self::insertIntoTable();
  }

  /** insert data into table */
  public function insertIntoTable() {
    global $wpdb;
    $allArtists = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}artists", ARRAY_A);

    foreach ($allArtists as $key => $artist) {
      $getProducts = $wpdb->get_results("SELECT object_id as product_id FROM {$wpdb->prefix}term_relationships WHERE term_taxonomy_id = {$artist['term_id']}", ARRAY_A);
      $allProductIDs = array_map(function($singleProduct) {
        return $singleProduct["product_id"];
      }, $getProducts);

      foreach ($allProductIDs as $key => $singleProduct) {
        $variationsForSingleProduct = $wpdb->get_results("SELECT {$wpdb->prefix}posts.ID AS variation_id, PRODUCT_NAME.post_title AS product_name FROM {$wpdb->prefix}posts JOIN {$wpdb->prefix}posts as PRODUCT_NAME ON {$wpdb->prefix}posts.post_parent = PRODUCT_NAME.ID WHERE {$wpdb->prefix}posts.post_parent = {$singleProduct};", ARRAY_A);

        $variationIDs = array_map(function($singleVariation) {
          return $singleVariation["variation_id"];
        }, $variationsForSingleProduct);

        if (count($variationIDs) > 0) {
          $artistID = $artist['artist_nummer'];
          $artistName = $artist['artist_name'];
          $productName = $variationsForSingleProduct[0]["product_name"];
          // $serializedVariations = maybe_serialize($variationIDs);
          foreach ($variationIDs as $key => $singleVariationID) {
            $query = $wpdb->prepare("INSERT INTO {$wpdb->prefix}artistProducts (artist_name, artist_id, product_name, product_id, variation_id) VALUES (%s, %d, %s, %d, %d)", $artistName, $artistID, $productName, $singleProduct, $singleVariationID);
            $wpdb->query($query);
          }
        }
      }
    }
  }
}


// add rohartikelnummern
// basispreistabelle für künstler
