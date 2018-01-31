<?php
if (!current_user_can('manage_options')) {
    die;
}
?>
<?php
//$hello = 'hello';
//function echoMe($hello) {
//    echo $hello;
//}
?>
<div class="wrap">
    <h1><?= esc_html(get_admin_page_title()); ?></h1>
    <?php
//    $me = echoMe($hello);
//        function hello() {
//            call_user_func(echoMe($me));
//        }

global $wpdb;
<<<<<<< HEAD
$getAllArtists = $wpdb->get_results('SELECT artist_name FROM '.$wpdb->prefix.'artists ORDER BY artist_name ASC', ARRAY_A);
=======
$getAllArtists = $wpdb->get_results('SELECT artist_name FROM wp_mokkamerch_artists ORDER BY artist_name ASC', ARRAY_A);
>>>>>>> f82e3b019893cc183301d38b6fa496a41b8ea228
?>

<?php

    if (isset($_POST['chosen'])) {

        print_r($_POST);
        $chosenArtist = $_POST['chooseArtist'];
        $abrechnungsBeginn = $_POST['from'];
        $abrechnungsEnde = $_POST['to'];
        if (isset($_POST['all_time'])) $all_time_summary = $_POST['all_time'];
        if (isset($_POST['month'])) $month_summary = $_POST['month'];
        global $wpdb;

        // get plugin table
        $table_distinct_BILL_DATA_SUMMARY = $wpdb->query('CREATE TEMPORARY TABLE BILL_DATA_SUMMARY_JOINED_ARTISTS_tmp (SELECT DISTINCT order_item_id, product_name, post_date, order_sold_at, email, product_id, variation_id, qty, total_price, single_price, order_id, rohartikel, sku, marge, farbe, basis_preis, artikel_type, groesse, qualitaet, artist_name, name FROM wp_mokkamerch_BILL_DATA_SUMMARY_JOINED_ARTISTS_PERM_TABLE )');

        /**
         *
         *  Monthly - SQL Table
         *
         */
        // create tmp table
        // artist data in given timeframe
        if (isset($_POST['monthly'])) {

            echo "<h1>Monthly Summary: " .$chosenArtist . "</h1>";
            $create_tmp_table_BILL_DATA_SUMMARY_SINGLE_ARTIST = $wpdb->query($wpdb->prepare('CREATE TEMPORARY TABLE BILL_DATA_SUMMARY_SINGLE_ARTIST_tmp (SELECT * FROM BILL_DATA_SUMMARY_JOINED_ARTISTS_tmp WHERE DATE(post_date) BETWEEN %s AND %s AND artist_name = %s);', $abrechnungsBeginn, $abrechnungsEnde, $chosenArtist));

            $tmp_table_BILL_DATA_SUMMARY_SINGLE_ARTIST = $wpdb->get_results('SELECT * FROM BILL_DATA_SUMMARY_SINGLE_ARTIST_tmp', ARRAY_A);

            $BILL_DATA_SUMMARY_MICRO = $wpdb->query('CREATE TEMPORARY TABLE BILL_DATA_SUMMARY_MICRO (SELECT artist_name, product_name, product_id, variation_id, rohartikel, farbe, groesse, qualitaet, artikel_type, SUM(qty) AS qty, SUM(marge) AS marge FROM BILL_DATA_SUMMARY_SINGLE_ARTIST_tmp GROUP BY variation_id ORDER BY artist_name, product_name ASC,artikel_type ASC, farbe ASC, groesse ASC);');

            $BILL_DATA_SUMMARY_MARGE = $wpdb->query('CREATE TEMPORARY TABLE BILL_DATA_SUMMARY_MARGE (SELECT artist_name, SUM(marge) AS total_marge FROM BILL_DATA_SUMMARY_MICRO GROUP BY artist_name)');

            $BILL_DATA_SUMMARY_MARGE_MICRO = $wpdb->query('CREATE TEMPORARY TABLE BILL_DATA_SUMMARY_MARGE_MICRO (SELECT artist_name, product_name, SUM(marge) AS total_marge FROM BILL_DATA_SUMMARY_MICRO GROUP BY product_name)');

            $table_BILL_DATA_SUMMARY_MARGE = $wpdb->get_results('SELECT * FROM BILL_DATA_SUMMARY_MARGE ORDER BY artist_name', ARRAY_A);

            $table_BILL_DATA_SUMMARY_MARGE_MICRO = $wpdb->get_results('SELECT * FROM BILL_DATA_SUMMARY_MARGE_MICRO ORDER BY artist_name ASC, product_name ASC', ARRAY_A);

            $table_BILL_DATA_SUMMARY_MICRO = $wpdb->get_results('SELECT * FROM BILL_DATA_SUMMARY_MICRO', ARRAY_A);
           // echo "<pre>";
           // print_r($tmp_table_BILL_DATA_SUMMARY_SINGLE_ARTIST);
           // print_r(json_encode($tmp_table_BILL_DATA_SUMMARY_SINGLE_ARTIST));
           // echo "new Query: ";
           // echo "Abrechnungsbeginn: $abrechnungsBeginn";
           // echo "abrechnungsEnde: $abrechnungsEnde";
           // echo "Chosen Artist: $chosenArtist";
           // $newQuery = $wpdb->get_results($wpdb->prepare('SELECT * FROM wp_mokkamerch_BILL_DATA_SUMMARY_JOINED_ARTISTS_PERM_TABLE WHERE DATE(order_sold_at) BETWEEN %s AND %s AND artist_name = %s', $abrechnungsBeginn, $abrechnungsEnde, $chosenArtist), ARRAY_A);
           // $newQuery = $wpdb->get_results($wpdb->prepare('SELECT * FROM wp_mokkamerch_BILL_DATA_SUMMARY_JOINED_ARTISTS_PERM_TABLE WHERE artist_name = %s AND DATE(order_sold_at) BETWEEN %s AND %s ORDER BY order_sold_at ASC', $chosenArtist, $abrechnungsBeginn, $abrechnungsEnde), ARRAY_A);
           $newQuery = $wpdb->get_results($wpdb->prepare('SELECT * FROM wp_mokkamerch_BILL_DATA_SUMMARY_JOINED_ARTISTS_PERM_TABLE WHERE artist_name = %s AND (DATE(order_sold_at) >=%s AND DATE(order_sold_at) < %s) ORDER BY order_sold_at ASC', $chosenArtist, $abrechnungsBeginn, $abrechnungsEnde), ARRAY_A);
           // use that syntax
           // SELECT * FROM `wp_mokkamerch_BILL_DATA_SUMMARY_JOINED_ARTISTS_PERM_TABLE` WHERE (order_sold_at >= '2017-09-01' AND order_sold_at < '2017-09-30')
           $artistDetails = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$wpdb->prefix.'artists WHERE artist_name = %s', $chosenArtist ), ARRAY_A);
           $artistID = $artistDetails["artist_nummer"];
           $artistID = sprintf("%04d", $artistID);
           $artistRegistration = $artistDetails["registration"];
           $artistInitBillNumber = $artistDetails["init_bill_number"];
           $brutto = $artistDetails["brutto"];
           $firstName = $artistDetails["vorname"];
           $lastName = $artistDetails["nachname"];
           $artist = $artistDetails["artist_name"];

           // echo "ARTIST ID: $artistID";
           print_r($newQuery);
           // $newQuery = utf8_encode($newQuery);
           // $json_encode = json_encode($newQuery, JSON_UNESCAPED_UNICODE);
           $json_encode = json_encode($newQuery, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
           // echo "<pre>";
           // print_r($json_encode);
           // echo "</pre>";
           // var_dump($json_encode);

           // Get cURL resource
           $curl = curl_init();
           // Set some options - we are passing in a useragent too here
           curl_setopt_array($curl, array(
             CURLOPT_HTTPHEADER, array(
               'Content-Type: application/json',
               'Content-Length: ' . strlen($json_encode)),
               CURLOPT_RETURNTRANSFER => 1,
               CURLOPT_URL => 'http://localhost:3000/create-json',
               // CURLOPT_USERAGENT => 'Codular Sample cURL Request',
               CURLOPT_POST => 1,
               CURLOPT_POSTFIELDS => array('data' => $json_encode, 'abrechnungsBeginn' => $abrechnungsBeginn, 'abrechnungsEnde' => $abrechnungsEnde, 'artistID' => $artistID, 'registration' => $artistRegistration, 'initBillNumber' => $artistInitBillNumber, 'brutto' => $brutto, 'firstName' => $firstName, 'lastName' => $lastName),
           ));
           // Send the request & save response to $resp
           $resp = curl_exec($curl);
           // Close request to clear up some resources
           curl_close($curl);
           $curl = curl_init();
           curl_setopt_array($curl, array(
               CURLOPT_RETURNTRANSFER => 1,
               CURLOPT_URL => 'http://localhost:3000/pdf-create',
               // CURLOPT_USERAGENT => 'Codular Sample cURL Request',
           ));
           $resp = curl_exec($curl);
           curl_close($curl);

           // $curl = curl_init();
           // $query = http_build_query(['artist' => $artist, 'date' => substr($abrechnungsBeginn, 0, 7)]);
           // curl_setopt_array($curl, array(
           //     CURLOPT_RETURNTRANSFER => 1,
           //     CURLOPT_URL => "http://localhost:3000/download-pdf?".$query,
           //     // CURLOPT_USERAGENT => 'Codular Sample cURL Request',
           // ));
           // $resp = curl_exec($curl);
           // curl_close($curl);

           // $fp = fopen('artist.json', 'w');
           // fwrite($fp, $json_encode);
           // fclose($fp);
           // echo "</pre>";
            /**
             *
             * Monthly - Array
             *
             */

             // use that syntax
             // SELECT * FROM `wp_mokkamerch_BILL_DATA_SUMMARY_JOINED_ARTISTS_PERM_TABLE` WHERE (order_sold_at >= '2017-09-01' AND order_sold_at < '2017-09-30')

            $allArtists = array();
            for ($i = 0; $i < count($tmp_table_BILL_DATA_SUMMARY_SINGLE_ARTIST); $i++) {
                $allArtists[$tmp_table_BILL_DATA_SUMMARY_SINGLE_ARTIST[$i]['artist_name']] = [];
            }

            $allDataWithoutArtists = array();
            $artistDataTimeframe = array();
            foreach ($allArtists as $artist => $emptyArray) {
                for ($i = 0; $i < count ($table_BILL_DATA_SUMMARY_MICRO); $i++) {
                    if ($artist === $table_BILL_DATA_SUMMARY_MICRO[$i]['artist_name']) {
                        for ($x = 0; $x < count($table_BILL_DATA_SUMMARY_MARGE); $x++) {
                            if ($table_BILL_DATA_SUMMARY_MARGE[$x]['artist_name'] == $artist) {
                                $artistDataTimeframe[$artist]['artist_marge'] = $table_BILL_DATA_SUMMARY_MARGE[$x]['total_marge'];
                            }
                        }
                        for ($y = 0; $y < count($table_BILL_DATA_SUMMARY_MARGE_MICRO); $y++) {
                            if ($table_BILL_DATA_SUMMARY_MARGE_MICRO[$y]['product_name'] == $table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']) {
                                $artistDataTimeframe[$artist][$table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']]['product_marge'] = $table_BILL_DATA_SUMMARY_MARGE_MICRO[$y]['total_marge'];
                            }
                        }

                        $artistDataTimeframe[$artist][$table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']] = [];
                        $artistDataTimeframe[$artist][$table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['qty'] = $table_BILL_DATA_SUMMARY_MICRO[$i]['qty'];
                        $artistDataTimeframe[$artist][$table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['farbe'] = $table_BILL_DATA_SUMMARY_MICRO[$i]['farbe'];
                        $artistDataTimeframe[$artist][$table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['groesse'] = $table_BILL_DATA_SUMMARY_MICRO[$i]['groesse'];
                        $artistDataTimeframe[$artist][$table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['qualitaet'] = $table_BILL_DATA_SUMMARY_MICRO[$i]['qualitaet'];
                        $artistDataTimeframe[$artist][$table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['artikel_type'] = $table_BILL_DATA_SUMMARY_MICRO[$i]['artikel_type'];
                        $artistDataTimeframe[$artist][$table_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['marge'] = $table_BILL_DATA_SUMMARY_MICRO[$i]['marge'];
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


            /**
             *
             * Monthly - HTML Table
             *
             */

            echo "<table class='table table-hover table-striped'>";

            foreach ($artistDataTimeframe as $artist => $artistData) {
                echo "<tr>";
                echo "<th class='artist'> ". $artist . "</th>";
                echo "</tr>";
                foreach ($artistData as $key => $value) {
                    if ($key == 'artist_marge') {
                        echo "<tr><td class='artist_marge success'>Artist Marge: " . $value. "</td></tr>";
                        continue;
                    }
                    echo "<tr><th class='product'>" . $key . "</th></tr>";

                    foreach ($value as $key2 => $value2) {
                        if ($key2 == 'product_marge') {
                            echo "<tr class='product_marge danger'><td>Produkt Marge: " . $value2 . "</td></tr>";
                            continue;
                        }
                        echo "<tr><th class='product_variation'>" . $key2 . "</th></tr>";
                        foreach ($value2 as $variationKey => $variationData) {
                            echo "<tr><td>" . $variationKey . "</td><td>" . $variationData . "</td></tr>";
                        }
                    }
                }
            }
            echo "</table>";
        }

        /**
         *
         *  All Time - SQL Table
         *
         */

        // create tmp table
        // all time artist_data
        if (isset($_POST['all_time'])) {
            echo "<h1>All time Summary: " .$chosenArtist . "</h1>";

            $create_tmp_table_ALL_BILL_DATA_SUMMARY_SINGLE_ARTIST = $wpdb->query($wpdb->prepare('CREATE TEMPORARY TABLE ALL_BILL_DATA_SUMMARY_SINGLE_ARTIST_tmp (SELECT * FROM BILL_DATA_SUMMARY_JOINED_ARTISTS_tmp WHERE artist_name = %s);', $chosenArtist));

            $tmp_table_BILL_DATA_SUMMARY_SINGLE_ARTIST = $wpdb->get_results('SELECT * FROM ALL_BILL_DATA_SUMMARY_SINGLE_ARTIST_tmp', ARRAY_A);

            $ALL_BILL_DATA_SUMMARY_MICRO = $wpdb->query('CREATE TEMPORARY TABLE ALL_BILL_DATA_SUMMARY_MICRO (SELECT artist_name, product_name, product_id, variation_id, rohartikel, farbe, groesse, qualitaet, artikel_type, SUM(qty) AS qty, SUM(marge) AS marge FROM ALL_BILL_DATA_SUMMARY_SINGLE_ARTIST_tmp GROUP BY variation_id ORDER BY artist_name, product_name ASC,artikel_type ASC, farbe ASC, groesse ASC);');

            $ALL_BILL_DATA_SUMMARY_MARGE = $wpdb->query('CREATE TEMPORARY TABLE ALL_BILL_DATA_SUMMARY_MARGE (SELECT artist_name, SUM(marge) AS total_marge FROM ALL_BILL_DATA_SUMMARY_MICRO GROUP BY artist_name)');

            $ALL_BILL_DATA_SUMMARY_MARGE_MICRO = $wpdb->query('CREATE TEMPORARY TABLE ALL_BILL_DATA_SUMMARY_MARGE_MICRO (SELECT artist_name, product_name, SUM(marge) AS total_marge FROM ALL_BILL_DATA_SUMMARY_MICRO GROUP BY product_name)');

            $table_ALL_BILL_DATA_SUMMARY_MARGE = $wpdb->get_results('SELECT * FROM ALL_BILL_DATA_SUMMARY_MARGE ORDER BY artist_name', ARRAY_A);

            $table_ALL_BILL_DATA_SUMMARY_MARGE_MICRO = $wpdb->get_results('SELECT * FROM ALL_BILL_DATA_SUMMARY_MARGE_MICRO ORDER BY artist_name ASC, product_name ASC', ARRAY_A);

            $table_ALL_BILL_DATA_SUMMARY_MICRO = $wpdb->get_results('SELECT * FROM ALL_BILL_DATA_SUMMARY_MICRO', ARRAY_A);

            /**
             *
             * All Time - Array
             *
             */

            $allArtists = array();
            for ($i = 0; $i < count($tmp_table_BILL_DATA_SUMMARY_SINGLE_ARTIST); $i++) {
                $allArtists[$tmp_table_BILL_DATA_SUMMARY_SINGLE_ARTIST[$i]['artist_name']] = [];
            }

            $allDataWithoutArtists = array();
            $allTimeArtistData = array();
            foreach ($allArtists as $artist => $emptyArray) {
                for ($i = 0; $i < count($table_ALL_BILL_DATA_SUMMARY_MICRO); $i++) {
                    if ($artist === $table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['artist_name']) {
                        for ($x = 0; $x < count($table_ALL_BILL_DATA_SUMMARY_MARGE); $x++) {
                            if ($table_ALL_BILL_DATA_SUMMARY_MARGE[$x]['artist_name'] == $artist) {
                                $allTimeArtistData[$artist]['artist_marge'] = $table_ALL_BILL_DATA_SUMMARY_MARGE[$x]['total_marge'];
                            }
                        }
                        for ($y = 0; $y < count($table_ALL_BILL_DATA_SUMMARY_MARGE_MICRO); $y++) {
                            if ($table_ALL_BILL_DATA_SUMMARY_MARGE_MICRO[$y]['product_name'] == $table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['product_name']) {
                                $allTimeArtistData[$artist][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['product_name']]['product_marge'] = $table_ALL_BILL_DATA_SUMMARY_MARGE_MICRO[$y]['total_marge'];
                            }
                        }

                        $allTimeArtistData[$artist][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']] = [];
                        $allTimeArtistData[$artist][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['qty'] = $table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['qty'];
                        $allTimeArtistData[$artist][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['farbe'] = $table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['farbe'];
                        $allTimeArtistData[$artist][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['groesse'] = $table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['groesse'];
                        $allTimeArtistData[$artist][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['qualitaet'] = $table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['qualitaet'];
                        $allTimeArtistData[$artist][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['artikel_type'] = $table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['artikel_type'];
                        $allTimeArtistData[$artist][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['marge'] = $table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['marge'];
                    } elseif (empty($table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['artist_name'])) {
                        $allDataWithoutArtists['empty'][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']] = [];
                        $allDataWithoutArtists['empty'][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['qty'] = $table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['qty'];
                        $allDataWithoutArtists['empty'][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['farbe'] = $table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['farbe'];
                        $allDataWithoutArtists['empty'][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['groesse'] = $table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['groesse'];
                        $allDataWithoutArtists['empty'][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['qualitaet'] = $table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['qualitaet'];
                        $allDataWithoutArtists['empty'][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['artikel_type'] = $table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['artikel_type'];
                        $allDataWithoutArtists['empty'][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['product_name']][$table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['variation_id']]['marge'] = $table_ALL_BILL_DATA_SUMMARY_MICRO[$i]['marge'];
                    }
                }
            }

            /**
             *
             *  All Time - HTML Table
             *
             */

            echo "<table class='table table-hover table-striped'>";
            foreach ($allTimeArtistData as $artist => $artistData) {
                echo "<tr>";
                echo "<th class='artist'> " . $artist . "</th>";
                echo "</tr>";
                foreach ($artistData as $key => $value) {
                    if ($key == 'artist_marge') {
                        echo "<tr><td class='artist_marge success'>Artist Marge: " . $value . "</td></tr>";
                        continue;
                    }
                    echo "<tr><th>" . $key . "</th></tr>";

                    foreach ($value as $key2 => $value2) {
                        if ($key2 == 'product_marge') {
                            echo "<tr class='product_marge danger'><td>Produkt Marge: " . $value2 . "</td></tr>";
                            continue;
                        }
                        echo "<tr><th>" . $key2 . "</th></tr>";
                        foreach ($value2 as $variationKey => $variationData) {
                            echo "<tr><td class='$variationKey'>" . $variationKey . "</td><td class='$variationData'>" . $variationData . "</td></tr>";
                        }
                    }
                }
            }
            echo "</table>";
        }

//allArtists
//$allDataOrderedByArtist
//$table_BILL_DATA_SUMMARY_MICRO
//$table_BILL_DATA_SUMMARY_MARGE
//$table_BILL_DATA_SUMMARY_MARGE_MICRO



//        echo "<pre>";
//        echo "<h1>table_BILL_DATA_SUMMARY_MICRO:</h1>";
//        print_r($table_BILL_DATA_SUMMARY_MICRO);
//        echo "</pre>";

//        echo "<pre>";
//        echo "<h1>table_BILL_DATA_SUMMARY:</h1>";
//        print_r($tmp_table_BILL_DATA_SUMMARY_SINGLE_ARTIST);
//        echo "</pre>";

        if (isset($WOOCOMMERCE_FAILS)) {
            echo "<pre>";
            echo "<h1>Woocommerce Fails:</h1>";
            echo "<p class='text-danger'>Hier im Dashboard nach der Order ID suchen und händische Arbeit leisten ...</p>";
            print_r($WOOCOMMERCE_FAILS);
            echo "</pre>";
        }
        /**
         *
         * Array & Table for artist, in given timeframe
         *
         */

        echo "<pre>";
        echo "<h1>Zusammenfassung Daten ohne Künstler</h1>";
        // print_r($allDataWithoutArtists);
        echo "</pre>";

    }
    ?>
    <?php

    if (isset($_POST['all_time']) && isset($_POST['monthly'])) {

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
<?php     } ?>
<form action="" method="POST">
    <select name="chooseArtist" id="chooseArtist">
        <?php
        foreach ($getAllArtists as $key => $value) { ?>
            <option value="<?php echo $value['artist_name']; ?>" <?php if(isset($_POST['chosen']) && $value['artist_name'] == $chosenArtist) echo 'selected' ?>><?php echo $value['artist_name']; ?></option>
        <?php } ?>
    </select>
    <label>
        <input type="checkbox" name="all_time" value="all_time_summary" onchange="toggleCheckbox()">
        All time summary
    </label>
    <label>
        <input type="checkbox" name="monthly" value="monthly_summary" <?php if(isset($_POST['monthly'])) echo "checked" ?>>
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
<script>
    function toggleCheckbox() {
        var checkboxAllTime = document.querySelector('input[type="checkbox"][name="all_time"]');
        var allTimeChecked = checkboxAllTime.checked;
        var inputTextField = document.querySelectorAll('input[type="text"]');
        if (allTimeChecked) {
            console.log(allTimeChecked);
            console.log(inputTextField);
            for (var i = 0; i < inputTextField.length; i++) {
                inputTextField[i].disabled = true;
            }
            inputTextField.disabled = true;
        } else {
            for (var i = 0; i < inputTextField.length; i++) {
                inputTextField[i].disabled = false;
            }
        }
    }

    // fetch('http://localhost:3000/download-pdf?artist=Sayonara&date=2017-10', {
    //   mode: 'no-cors'
    // }).then((response) => {
    //   return response.blob();
    // }).then(blob => URL.createObjectURL(blob))
    // .then(url => {
    //     window.open(url, '_blank');
    //     URL.revokeObjectURL(url);
    // });
</script>
