<?php
$uploads = wp_upload_dir();
$audio_files = glob($uploads['basedir'] . '/audio-diary/*.wav');

if (!is_array($audio_files)) {
    $audio_files = [];
}

usort($audio_files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});
?>
<div class="wrap audio-diary-admin-list-page">
    <h1><?php _e('Recorded Audios', 'audio-diary'); ?></h1>
    <button id="delete-selected"><?php _e('Delete Selected', 'audio-diary'); ?></button>
    <button id="download-zip"><?php _e('Download Selected as ZIP', 'audio-diary'); ?></button>
    <button id="select-all" data-select-all="true">Select All</button>
    <table>
        <thead>
            <tr>
                <th><?php _e('Select', 'audio-diary'); ?></th>
                <th><?php _e('Date', 'audio-diary'); ?></th>
                <th><?php _e('Time', 'audio-diary'); ?></th>
                <th><?php _e('Audio', 'audio-diary'); ?></th>
                <th><?php _e('Delete', 'audio-diary'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($audio_files as $file) : 
                $file_date = date("Y-m-d", filemtime($file));
                $file_time = date("H:i:s", filemtime($file));
                $file_url = $uploads['baseurl'] . '/audio-diary/' . basename($file);
                $file_name = basename($file);
            ?>
            <tr>
                <td><input type="checkbox" class="select-audio" value="<?php echo $file_name; ?>"></td>
                <td><?php echo $file_date; ?></td>
                <td><?php echo $file_time; ?></td>
                <td>
                    <audio controls src="<?php echo $file_url; ?>"></audio>
                </td>
                <td>
                    <button class="delete-audio"
                        data-file="<?php echo $file_name; ?>"><?php _e('Delete', 'audio-diary'); ?></button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>