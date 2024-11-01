<?php

if ($_REQUEST['do'] == 'delete' && is_admin()) {
    $data = $webweb_wp_partner_watcher_obj->delete_partner($_REQUEST['id']);
}

$data = $webweb_wp_partner_watcher_obj->get_partners();

$delete_url = $webweb_wp_partner_watcher_obj->get('delete_partner_url');
$edit_url   = $webweb_wp_partner_watcher_obj->get('edit_partner_url');

?>

<div class="webweb_wp_plugin">
    <div class="wrap">
        <h2>Partners</h2>

        <p>The list of partner links that are currently being monitored.</p>

        <div class="wrap" id="app-partners-container">
            <table class="widefat fixed">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">URL</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)) : ?>
                        <tr>
                            <td colspan="3">No records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data as $idx => $rec) : ?>
                        <tr>
                            <td><?php echo $rec['name']?></td>
                            <td><?php echo $rec['url']?> <a href="<?php echo $rec['url']?>" target="_blank">[Visit]</a></td>
                            <td>
                                <a class="app_edit_button" href="<?php echo WebWeb_WP_PartnerWatcherUtil::add_url_params($edit_url, array('id' => $idx));?>">Edit</a>
        |
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