<?php
$settings_key = $webweb_wp_partner_watcher_obj->get('plugin_settings_key');
$opts = $webweb_wp_partner_watcher_obj->get_options();
?>
<div class="webweb_wp_plugin">
    <div class="wrap">
        <h2>Settings</h2>

        <form method="post" action="options.php">
            <?php settings_fields($webweb_wp_partner_watcher_obj->get('plugin_dir_name')); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Status</th>
                    <td>
                        <label for="radio1"> 
                            <input type="radio" id="radio1" name="<?php echo $settings_key; ?>[status]"
                                value="1" <?php echo empty($opts['status']) ? '' : 'checked="checked"'; ?> /> Enabled
                        </label>
                        <br/>
                        <label for="radio2">
                            <input type="radio" name="<?php echo $settings_key; ?>[status]"  id="radio2"
                                value="0" <?php echo!empty($opts['status']) ? '' : 'checked="checked"'; ?> /> Disabled
                        </label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Notification Email</th>
                    <td><input type="text" name="<?php echo $settings_key; ?>[notification_email]" value="<?php echo $opts['notification_email']; ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Notification Threshold</th>
                    <td><input type="text" name="<?php echo $settings_key; ?>[notification_threshold]" value="<?php echo $opts['notification_threshold']; ?>" />
                        <small>How many keywords to match before sending an alert.</small>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save') ?>" />
            </p>
        </form>
    </div>
</div>