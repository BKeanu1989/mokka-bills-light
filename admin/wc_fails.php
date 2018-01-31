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
    ?>

    <?php

    if (isset($_POST['chosen'])) {

        print_r($_POST);
        $abrechnungsBeginn = $_POST['from'];
        $abrechnungsEnde = $_POST['to'];
        if (isset($_POST['all_time'])) $all_time_summary = $_POST['all_time'];
        if (isset($_POST['month'])) $month_summary = $_POST['month'];
        global $wpdb;

        // get plugin table

        /**
         *
         *  Monthly - SQL Table
         *
         */
        // create tmp table
        // artist data in given timeframe
        if (isset($_POST['monthly'])) {

            echo "<h1>Monthly Summary: Woocommerce Fails</h1>";
            $wc_fails_monthly = $wpdb->get_results($wpdb->prepare('SELECT * FROM '.$wpdb->prefix.'WOOCOMMERCE_FAILS_PERM_TABLE WHERE DATE(order_sold_at) BETWEEN %s AND %s', $abrechnungsBeginn, $abrechnungsEnde),ARRAY_A);


            echo "<pre>";
            print_r($wc_fails_monthly);
            echo "</pre>";
        } elseif (isset($_POST['all_time'])) {
            echo "<h1>All time Summary</h1>";
            $wc_fails = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'WOOCOMMERCE_FAILS_PERM_TABLE', ARRAY_A);
            echo "<pre>";
            print_r($wc_fails);
            echo "</pre>";
        }




  } ?>

    <form action="" method="POST">
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