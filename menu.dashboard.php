<?php 
$opts = $webweb_wp_partner_watcher_obj->get_options();
?>

<div class="webweb_wp_plugin">
    <div class="wrap">
        <h2><?php echo __('Dashboard', 'webweb_member_status') ?></h2>

        <p>Please check the <a href="<?php echo $webweb_wp_partner_watcher_obj->get('plugin_admin_url_prefix');?>/menu.support.php">Help</a> section if you need instructions how to use this plugin.</p>

        <table class="app_table">
            <tr>
                <td>Plugin Status</td>
                <td><?php echo empty($opts['status']) ? $webweb_wp_partner_watcher_obj->msg('Disabled') : $webweb_wp_partner_watcher_obj->msg('Enabled', 1);?></td>
            </tr>
            <tr>
                <td>Notification Email</td>
                <td><?php echo $opts['notification_email'];?></td>
            </tr>
            <tr>
                <td>Notification Threshold</td>
                <td><?php echo $opts['notification_threshold'];?> Keyword(s)</td>
            </tr>
            <tr>
                <td>Cron Scheduled</td>
                <td><?php
                echo $webweb_wp_partner_watcher_obj->is_cron_scheduled() ? 'Yes' : 'No';
                echo ' (' . $webweb_wp_partner_watcher_obj->get('plugin_cron_freq') . ', after 11:30pm)';

                ?></td>
            </tr>
        </table>
    </div>
</div>
