<?php
if (!current_user_can('manage_options')) {
    die;
}
?>
<div class="wrap">
    <h1><?= esc_html(get_admin_page_title()); ?></h1>
    <?php
      global $wpdb;
      $getAllArtists = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'artists ORDER BY artist_name ASC', ARRAY_A);
      // print_r($getAllArtists);
    ?>
<?php
    if (isset($_POST['chosen'])) {
      global $wpdb;
      // print_r($_POST);
      $abrechnungsBeginn = $_POST['from'];
      $abrechnungsEnde = $_POST['to'];
      $array = [];
      $array["data"] = [];

        foreach ($getAllArtists as $key => $artist) {
          // echo "array: ". $getAllArtists[$key]["artist_name"] .": ". $artist["artist_name"];
          $artistName = $artist["artist_name"];
          $newQuery = [];
          $newQuery["products"] = $wpdb->get_results($wpdb->prepare('SELECT * FROM wp_mokkamerch_BILL_DATA_SUMMARY_JOINED_ARTISTS_PERM_TABLE WHERE artist_name = %s AND DATE(order_sold_at) BETWEEN %s AND %s ORDER BY order_sold_at ASC', $artistName, $abrechnungsBeginn, $abrechnungsEnde), ARRAY_A);
          $newQuery["artistDetails"] = [];
          $newQuery["artistDetails"]["artistName"] = $artistName;
          $newQuery["artistDetails"]["artistNummer"] = sprintf("%04d",$artist["artist_nummer"]);
          $newQuery["artistDetails"]["registration"] = $artist["registration"];
          $newQuery["artistDetails"]["init_bill_number"] = $artist["init_bill_number"];
          $newQuery["artistDetails"]["brutto"] = $artist["brutto"];
          $newQuery["artistDetails"]["vorname"] = $artist["vorname"];
          $newQuery["artistDetails"]["nachname"] = $artist["nachname"];
          $array["data"][$artistName] = $newQuery;
          // $json_encode = json_encode($newQuery, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        }

$array["abrechnungsBeginn"] = $abrechnungsBeginn;
$array["abrechnungsEnde"] = $abrechnungsEnde;
// $data_string = json_encode($array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT);
$data_string = json_encode($array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
// print_r($data_string);
$ch = curl_init('http://localhost:3000/create-json');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
'Content-Type: application/json',
'Content-Length: ' . strlen($data_string))
);

$result = curl_exec($ch);
        // $json_encode = json_encode($array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        // echo "<pre>";
        // // print_r($array);
        // print_r($json_encode);
        // echo "</pre>";
        // $test = [];
        // $test['test'] = "test";
        // $test = json_encode($test, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        // $curl = curl_init();
        // curl_setopt_array($curl, array(
        //  CURLOPT_HTTPHEADER, array(
        //    'Content-Type: application/json',
        //    'Content-Length: ' . strlen($test)),
        //    CURLOPT_RETURNTRANSFER => 1,
        //    CURLOPT_URL => 'http://localhost:3000/create-json',
        //    // CURLOPT_USERAGENT => 'Codular Sample cURL Request',
        //    CURLOPT_POST => 1,
        //    CURLOPT_POSTFIELDS => array('data' => $test, 'abrechnungsBeginn' => $abrechnungsBeginn, 'abrechnungsEnde' => $abrechnungsEnde),
        // ));
        // $resp = curl_exec($curl);
        // curl_close($curl);
        // $curl = curl_init();
        // curl_setopt_array($curl, array(
        //    CURLOPT_RETURNTRANSFER => 1,
        //    CURLOPT_URL => 'http://localhost:3000/pdf-create',
        // ));
        // $resp = curl_exec($curl);
        // curl_close($curl);
  }
    ?>
</div>
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
</script>
