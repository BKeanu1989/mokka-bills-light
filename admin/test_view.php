<?php
if (!current_user_can('manage_options')) {
    die;
}
?>
<div class="wrap">
    <h1><?= esc_html(get_admin_page_title()); ?></h1>
    <?php
//    $me = echoMe($hello);
//        function hello() {
//            call_user_func(echoMe($me));
//        }

global $wpdb;

function totalMarge($tableArray) {
  // print_r($tableArray);
  $marge = 0;
  for ($i = 0; $i < count($tableArray); $i++) {
    if ($tableArray[$i]['marge_sum']) {
       $marge += $tableArray[$i]['marge_sum'];
    }
  }
  return $marge;
}

function artistSummary($artist_name, $from, $to) {
  global $wpdb;
  $artistSummary = $wpdb->get_results($wpdb->prepare('SELECT artist_name, product_name, SUM(qty) AS qty_sum,farbe, groesse, qualitaet, artikel_type, SUM(marge) AS marge_sum FROM '.$wpdb->prefix.'BILL_DATA_SUMMARY_JOINED_ARTISTS_PERM_TABLE where DATE(order_sold_at) BETWEEN %s AND %s AND artist_name = %s GROUP BY product_name', $from, $to, $artist_name),ARRAY_A);
  $artistSummary['total_marge'] = totalMarge($artistSummary);
  return $artistSummary;
}



if(isset($_POST['chosen'])) {
  $chosenArtist = $_POST['chooseArtist'];
  echo "Artist name: " . $chosenArtist;
  $from = $_POST['from'];
  echo "from: " . $from;
  $to = $_POST['to'];
  echo "to: " . $to;

  $artistData = artistSummary($chosenArtist, $from, $to);

  echo "<pre>";
  print_r($artistData);
  echo "</pre>";

  echo json_encode($artistData);
}

$getAllArtists = $wpdb->get_results('SELECT artist_name FROM {$wpdb->prefix}artists ORDER BY artist_name ASC', ARRAY_A);
?>
<form action="" method="POST">
    <select name="chooseArtist" id="chooseArtist">
        <?php
        foreach ($getAllArtists as $key => $value) { ?>
            <option value="<?php echo $value['artist_name']; ?>" <?php if(isset($_POST['chosen']) && $value['artist_name'] == $chosenArtist) echo 'selected' ?>><?php echo $value['artist_name']; ?></option>
        <?php } ?>
    </select>
    <label>
        <input type="checkbox" name="all_time" value="all_time_summary">
        All time summary
    </label>
    <label>
        <input type="checkbox" name="monthly" value="monthly_summary">
        Monthly summary
    </label>
    <div class="form-group">
        <label for="from">Daten ziehen von:</label>
        <input type="text" id="from" name="from" class="form-control" value="<?php if (isset($_POST['chosen'])) echo $_POST['from'] ?>">
    </div>
    <div class="form-group">
        <label for="to">Daten ziehen bis:</label>
        <input type="text" id="to" name="to" class="form-control" value="<?php if (isset($_POST['chosen'])) echo $_POST['to'] ?>">
    </div>
    <input type="submit" name="chosen">
</form>
</div>
