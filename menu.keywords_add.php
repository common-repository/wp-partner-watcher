<?php

$id = !isset($_REQUEST['id']) ? null : $_REQUEST['id'];
$settings_key = 'keywords';
$msg = '';

if (!empty($_POST)) {
    if (!$webweb_wp_partner_watcher_obj->admin_keyword($_REQUEST[$settings_key], $id)) {
        $msg = $webweb_wp_partner_watcher_obj->message('Cannot update record.');
    } else {
        $msg = $webweb_wp_partner_watcher_obj->message('Successfully added/updated record.', 1);
    }
    
    $opts = $_REQUEST[$settings_key];
} /*elseif (!is_null($id)) {
    $partners = $webweb_wp_partner_watcher_obj->get_keywords();
    $opts = $partners[$id];
}*/

?>

<div class="webweb_wp_plugin">
    <div class="wrap">
        <h2>Add/Edit Keywords</h2>
        <?php echo $msg; ?>

        <form method="post">
            <?php settings_fields($webweb_wp_partner_watcher_obj->get('plugin_dir_name')); ?>

            <p>
            You can separate keyword/keyword phrases by comma and/or by putting them on a separate line.
            </p>

            <table class="form-table">
                <tr valign="top">
                    <td><textarea name="keywords"></textarea></td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save') ?>" />
            </p>
        </form>
    </div>
</div>