<?php
if (!current_user_can('manage_options')) {
    die;
}
?>
<div class="wrap">
    <h1><?= esc_html(get_admin_page_title()); ?></h1>

<?php
if (isset($_POST['chosen'])) {

    global $wpdb;
    print_r($_POST);

    include_once((plugin_dir_path(__FILE__).'db-queries/update-order-status.php'));
    include_once((plugin_dir_path(__FILE__).'db-queries/insert_new.php'));
//    $tmp_table_BILL_DATA_SUMMARY = $wpdb->get_results('SELECT * FROM BILL_DATA_SUMMARY_JOINED_ARTISTS_PERM_TABLE_tmp', ARRAY_A);
//    echo "<pre>";
//    print_r($tmp_table_BILL_DATA_SUMMARY);
//    echo "</pre>";
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
