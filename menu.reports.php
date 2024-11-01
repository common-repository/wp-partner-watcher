<?php

$report_file = !isset($_REQUEST['report_file']) ? null : $_REQUEST['report_file'];
$report_files = $webweb_wp_partner_watcher_obj->get_report_files();

?>
<div class="webweb_wp_plugin">
    <div class="wrap">
        <h2>Reports</h2>

        <p></p>

        <form method="post">
            <?php settings_fields($webweb_wp_partner_watcher_obj->get('plugin_dir_name')); ?>

            <?php if (!empty($report_files)) : ?>
                Choose Report:
                
                <?php
                    $options = array_combine($report_files, $report_files);
                    echo WebWeb_WP_PartnerWatcherUtil::html_select('report_file', $options, $report_file);
                ?>
                
                <input type="submit" class="button-primary" value="<?php _e('Load Report') ?>" />
            <?php else : ?>
                No reports found.
            <?php endif; ?>

            <?php
            if (!empty($report_file)) {
                echo "<p><pre>" . $webweb_wp_partner_watcher_obj->get_report($report_file) . "</pre></p>";
            }
            ?>
        </form>
    </div>
</div>