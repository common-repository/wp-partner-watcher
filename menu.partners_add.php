<?php

$id = !isset($_REQUEST['id']) ? null : $_REQUEST['id'];
$settings_key = $webweb_wp_partner_watcher_obj->get('plugin_partners_key');
$msg = '';

if (!empty($_POST)) {
    $data = $_REQUEST[$settings_key];

    if (!$webweb_wp_partner_watcher_obj->admin_partner($data, $id)) {
        $msg = $webweb_wp_partner_watcher_obj->message('Cannot update record.');
    } else {
        $msg = $webweb_wp_partner_watcher_obj->message('Successfully added/updated record.', 1);
    }
    
    $opts = $_REQUEST[$settings_key];
} elseif (!is_null($id)) {
    $partners = $webweb_wp_partner_watcher_obj->get_partners();
    $opts = $partners[$id];
}

?>

<div class="webweb_wp_plugin">
    <div class="wrap">
        <h2>Add/Edit Partner</h2>
        <?php echo $msg; ?>
        
        <form method="post">
            <?php settings_fields($webweb_wp_partner_watcher_obj->get('plugin_dir_name')); ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Name</th>
                    <td><input type="text" name="<?php echo $settings_key; ?>[name]" value="<?php echo $opts['name']; ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Email</th>
                    <td><input type="text" name="<?php echo $settings_key; ?>[email]" value="<?php echo $opts['email']; ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">URL</th>
                    <td><input type="text" name="<?php echo $settings_key; ?>[url]" value="<?php echo $opts['url']; ?>" />
                        <small>Example: http://domain.com</small>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save') ?>" />
            </p>
        </form>
    </div>
</div>