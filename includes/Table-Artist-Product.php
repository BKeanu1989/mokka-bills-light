<?php
class TableArtistProduct {
  // create Table

  public function __construct() {
    global $wpdb;
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}artistProducts (id INT(255) AUTO_INCREMENT PRIMARY KEY, artist_name varchar(40) NOT NULL, artist_id INT(255) NOT NULL, product_id INT(255) NOT NULL, variation_id INT(255) NOT NULL");
  }

  /** insert data into table */
  public function insertIntoTable() {
    global $wpdb;
    $allArtists = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}artists", ARRAY_A);

    foreach ($allArtists as $key => $artist) {
      print_r($artist);
      $getProducts = $wpdb->get_results("SELECT object_id as product_id FROM {$wpdb->prefix}term_relationships WHERE term_taxonomy_id = {$artist['term_id']}", ARRAY_A);
      $allProductIDs = array_map(function($singleProduct) {
        return $singleProduct["product_id"];
      }, $getProducts);

      foreach ($allProductIDs as $key => $singleProduct) {
        $variationsForSingleProduct = $wpdb->get_results("SELECT ID AS variation_id FROM wp_mokkamerch_posts WHERE post_parent = {$singleProduct}", ARRAY_A);

        $variationIDs = array_map(function($singleVariation) {
          return $singleVariation["variation_id"];
        }, $variationsForSingleProduct);

        $artistID = $artist['artist_nummer'];
        $artistName = $artist['artist_name'];
        // $serializedVariations = maybe_serialize($variationIDs);
        foreach ($variationIDs as $key => $singleVariationID) {
          $query = $wpdb->prepare("INSERT INTO {$wpdb->prefix}artistProducts (artist_name, artist_id, product_id, variation_id) VALUES (%s, %d, %d, %s)", $artistName, $artistID, $singleProduct,$singleVariationID);
          $wpdb->query($query);
        }
      }
    }
  }
}


// add rohartikelnummern
// basispreistabelle für künstler
