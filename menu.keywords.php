<?php

if ($_REQUEST['do'] == 'delete' && is_admin()) {
    $data = $webweb_wp_partner_watcher_obj->delete_keyword($_REQUEST['id']);
}

$data = $webweb_wp_partner_watcher_obj->get_keywords();

$delete_url = $webweb_wp_partner_watcher_obj->get('delete_keywords_url');
$edit_url   = $webweb_wp_partner_watcher_obj->get('edit_keywords_url');

?>
<div class="webweb_wp_plugin">
    <div class="wrap">
        <h2>Keywords</h2>

        <p>The list of keywords and phrases currently monitored.</p>

        <div class="wrap" id="app-partners-container">
            <table class="widefat fixed">
                <thead>
                    <tr>
                        <th scope="col">Keyword/Phrase</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)) : ?>
                        <tr>
                            <td colspan="2">No records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data as $idx => $keyword) : ?>
                        <tr>
                            <td><?php echo $keyword?></td>
                            <td>
                                <!--<a class="app_edit_button" href="<?php echo WebWeb_WP_PartnerWatcherUtil::add_url_params($edit_url, array('id' => $idx));?>">Edit</a> | -->

                                <a class="app_delete_button" onclick="return confirm('Are you sure?');"
                                   href="<?php echo WebWeb_WP_PartnerWatcherUtil::add_url_params($delete_url, array('id' => $idx));?>">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
