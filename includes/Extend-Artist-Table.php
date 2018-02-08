<?php

class ExtendArtistTable {
  public function __construct() {
    global $wpdb;
    $wpdb->query("ALTER TABLE {$wpdb->prefix}artists ADD term_id INT(255) NOT NULL AFTER slug, ADD extra_charge DECIMAL(5,2);");

    $termIDsForArtists = $wpdb->get_results("SELECT {$wpdb->prefix}terms.term_id, {$wpdb->prefix}terms.slug FROM {$wpdb->prefix}terms JOIN {$wpdb->prefix}artists WHERE {$wpdb->prefix}terms.slug = {$wpdb->prefix}artists.slug", ARRAY_A);

    foreach ($termIDsForArtists as $key => $row) {
      $term_id = $row["term_id"];
      $slug = $row["slug"];
      $query = $wpdb->prepare("UPDATE {$wpdb->prefix}artists SET term_id=%d WHERE {$wpdb->prefix}artists.slug = %s", $term_id, $slug);
      $wpdb->query($query);
    }
  }
}
