<?php
include_once 'jdf.php'; // اضافه کردن کتابخانه jdf

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
    <div class="action-buttons">
        <button id="delete-selected" class="btn btn-danger"><?php _e('Delete Selected', 'audio-diary'); ?></button>
        <button id="download-zip"
            class="btn btn-primary"><?php _e('Download Selected as ZIP', 'audio-diary'); ?></button>
        <button id="select-all" class="btn btn-secondary" data-select-all="true">Select All</button>
    </div>
    <div id="selected-count" class="selected-count">Selected: 0</div>
    <table id="audio-table">
        <thead>
            <tr>
                <th><?php _e('Select', 'audio-diary'); ?></th>
                <th><?php _e('Date', 'audio-diary'); ?></th>
                <th><?php _e('Time', 'audio-diary'); ?></th>
                <th><?php _e('Audio', 'audio-diary'); ?></th>
                <th><?php _e('Actions', 'audio-diary'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($audio_files as $file) : 
                $file_date = jdate("Y-m-d", filemtime($file));
                $file_time = jdate("H:i:s", filemtime($file));
                $file_url = $uploads['baseurl'] . '/audio-diary/' . basename($file);
                $file_name = basename($file);
            ?>
            <tr data-file="<?php echo $file_name; ?>">
                <td><input type="checkbox" class="select-audio" value="<?php echo $file_name; ?>"></td>
                <td><?php echo $file_date; ?></td>
                <td><?php echo $file_time; ?></td>
                <td>
                    <audio controls src="<?php echo $file_url; ?>"></audio>
                </td>
                <td class="action-cell">
                    <button class="btn btn-small btn-download download-single" data-url="<?php echo $file_url; ?>"
                        data-name="<?php echo $file_name; ?>">Download</button>
                    <button class="btn btn-small btn-upload upload-to-drive"
                        data-name="<?php echo $file_name; ?>">Upload to Drive</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>