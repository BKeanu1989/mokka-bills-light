<?php
if (!current_user_can('manage_options')) {
    die;
}
?>
<div class="wrap">
    <h1><?= esc_html(get_admin_page_title()); ?></h1>
<?php
if (isset($_POST['chosen'])) {

    $abrechnungsBeginn = $_POST['from'];
    $abrechnungsEnde = $_POST['to'];

    global $wpdb;
    $table_BILL_DATA_SUMMARY = $wpdb->get_results('SELECT * FROM wp_mokkamerch_BILL_DATA_SUMMARY_JOINED_ARTISTS_PERM_TABLE', ARRAY_A);

    $WOOCOMMERCE_FAILS = $wpdb->get_results('SELECT * FROM wp_mokkamerch_WOOCOMMERCE_FAILS_PERM_TABLE;', ARRAY_A);

    // $BILL_DATA_SUMMARY_MICRO = $wpdb->query('CREATE TEMPORARY TABLE BILL_DATA_SUMMARY_MICRO (SELECT artist_name, product_name, product_id, variation_id, rohartikel, farbe, groesse, qualitaet, artikel_type, SUM(qty) AS qty, SUM(marge) AS marge, order_sold_at, order_id FROM wp_mokkamerch_BILL_DATA_SUMMARY_JOINED_ARTISTS_PERM_TABLE GROUP BY variation_id ORDER BY artist_name, product_name ASC,artikel_type ASC, farbe ASC, groesse ASC);');

    $BILL_DATA_SUMMARY_MICRO = $wpdb->query($wpdb->prepare('CREATE TEMPORARY TABLE BILL_DATA_SUMMARY_MICRO (SELECT DISTINCT order_item_id, artist_name, product_name, product_id, variation_id, rohartikel, farbe, groesse, qualitaet, artikel_type, SUM(qty) AS qty_total, qty, SUM(marge) AS marge, order_sold_at, order_id FROM wp_mokkamerch_BILL_DATA_SUMMARY_JOINED_ARTISTS_PERM_TABLE WHERE DATE(order_sold_at) BETWEEN %s AND %s GROUP BY variation_id ORDER BY artist_name, product_name ASC,artikel_type ASC, farbe ASC, groesse ASC);', $abrechnungsBeginn, $abrechnungsEnde));

    $BILL_DATA_SUMMARY_MARGE = $wpdb->query('CREATE TEMPORARY TABLE BILL_DATA_SUMMARY_MARGE (SELECT artist_name, SUM(marge) AS total_marge FROM BILL_DATA_SUMMARY_MICRO GROUP BY artist_name)');

    $BILL_DATA_SUMMARY_MARGE_MICRO = $wpdb->query('CREATE TEMPORARY TABLE BILL_DATA_SUMMARY_MARGE_MICRO (SELECT artist_name, product_name, SUM(marge) AS total_marge FROM BILL_DATA_SUMMARY_MICRO GROUP BY product_name)');

    $table_BILL_DATA_SUMMARY_MARGE = $wpdb->get_results('SELECT * FROM BILL_DATA_SUMMARY_MARGE ORDER BY artist_name', ARRAY_A);

    $table_BILL_DATA_SUMMARY_MARGE_MICRO = $wpdb->get_results('SELECT * FROM BILL_DATA_SUMMARY_MARGE_MICRO ORDER BY artist_name ASC, product_name ASC', ARRAY_A);

    $table_BILL_DATA_SUMMARY_MICRO = $wpdb->get_results('SELECT * FROM BILL_DATA_SUMMARY_MICRO', ARRAY_A);
    // $table_BILL_DATA_SUMMARY_MICRO = $wpdb->get_results($wpdb->prepare('SELECT * FROM BILL_DATA_SUMMARY_MICRO WHERE DATE(order_sold_at) BETWEEN %s AND %s', $abrechnungsBeginn, $abrechnungsEnde), ARRAY_A);


    echo "<pre>";
    echo "<h1>Woocommerce Fails:</h1>";
    echo "<p class='text-danger'>Hier im Dashboard nach der Order ID suchen und händische Arbeit leisten ...</p>";
    print_r($WOOCOMMERCE_FAILS);
    echo "</pre>";

    $allArtists = array();
    for ($i = 0; $i < count($table_BILL_DATA_SUMMARY); $i++) {
        $allArtists[$table_BILL_DATA_SUMMARY[$i]['artist_name']] = [];
    }

    $allDataWithoutArtists = array();
    $allDataOrderedByArtist = array();
    foreach ($allArtists as $artist => $emptyArray) {
        for ($i = 0; $i < count ($table_BILL_DATA_SUMMARY_MICRO); $i++) {
            if ($artist === $table_BILL_DATA_SUMMARY_MICRO[$i]['artist_name']) {
                for ($x = 0; $x < count($table_BILL_DATA_SUMMARY_MARGE); $x++) {
                    if ($table_BILL_DATA_SUMMARY_MARGE[$x]['artist_name'] == $artist) {
                        $allDataOrderedByArtist[$artist]['artist_marge'] = $table_BILL_DATA_SUMMARY_MARGE[$x]['total_marge'];
                    }
                }
                for ($y = 0; $y < count($table_BILL_DATA_SUMMARY_MARGE_MICRO); $y++) {
                    if ($table_BILL_DATA_SUMMARY_MARGE_MICRO[$y]['product_name'] == $table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']) {
                        $allDataOrderedByArtist[$artist][$table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']]['product_marge'] = $table_BILL_DATA_SUMMARY_MARGE_MICRO[$y]['total_marge'];
                    }
                }

                $allDataOrderedByArtist[$artist][$table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']] = [];
                $allDataOrderedByArtist[$artist][$table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['qty'] = $table_BILL_DATA_SUMMARY_MICRO[$i]['qty'];
                $allDataOrderedByArtist[$artist][$table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['farbe'] = $table_BILL_DATA_SUMMARY_MICRO[$i]['farbe'];
                $allDataOrderedByArtist[$artist][$table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['groesse'] = $table_BILL_DATA_SUMMARY_MICRO[$i]['groesse'];
                $allDataOrderedByArtist[$artist][$table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['qualitaet'] = $table_BILL_DATA_SUMMARY_MICRO[$i]['qualitaet'];
                $allDataOrderedByArtist[$artist][$table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['artikel_type'] = $table_BILL_DATA_SUMMARY_MICRO[$i]['artikel_type'];
                $allDataOrderedByArtist[$artist][$table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['marge'] = $table_BILL_DATA_SUMMARY_MICRO[$i]['marge'];
            } elseif (empty($table_BILL_DATA_SUMMARY_MICRO[$i]['artist_name'])) {
                $allDataWithoutArtists['empty'][$table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']] = [];
                $allDataWithoutArtists['empty'][$table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['qty'] = $table_BILL_DATA_SUMMARY_MICRO[$i]['qty'];
                $allDataWithoutArtists['empty'][$table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['farbe'] = $table_BILL_DATA_SUMMARY_MICRO[$i]['farbe'];
                $allDataWithoutArtists['empty'][$table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['groesse'] = $table_BILL_DATA_SUMMARY_MICRO[$i]['groesse'];
                $allDataWithoutArtists['empty'][$table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['qualitaet'] = $table_BILL_DATA_SUMMARY_MICRO[$i]['qualitaet'];
                $allDataWithoutArtists['empty'][$table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['artikel_type'] = $table_BILL_DATA_SUMMARY_MICRO[$i]['artikel_type'];
                $allDataWithoutArtists['empty'][$table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['marge'] = $table_BILL_DATA_SUMMARY_MICRO[$i]['marge'];
            }
        }
    }

    echo "<pre>";
    echo "<h1>Zusammenfassung Daten ohne Künstler</h1>";
    print_r($allDataWithoutArtists);
    echo "</pre>";

    echo "<h2>all data ordered by artist</h2>";
    echo "<table class='table table-hover table-striped'>";
    foreach ($allDataOrderedByArtist as $artist => $artistData) {
        echo "<tr>";
        echo "<th> ". $artist . "</th>";
        echo "</tr>";
        foreach ($artistData as $key => $value) {
            if ($key == 'artist_marge') {
                echo "<tr><td class='success'>Artist Marge: " . $value. "</td></tr>";
                continue;
            }
            echo "<tr><th>" . $key . "</th></tr>";

            foreach ($value as $key2 => $value2) {
                if ($key2 == 'product_marge') {
                    echo "<tr class='danger'><td>Produkt Marge: " . $value2 . "</td></tr>";
                    continue;
                }
                echo "<tr><th>" . $key2 . "</th></tr>";
                foreach ($value2 as $variationKey => $variationData) {
                    echo "<tr><td>" . $variationKey . "</td><td>" . $variationData . "</td><td>";
                }
            }
        }
    }
    echo "</table>";
}
?>

    <form action="" method="POST">
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
