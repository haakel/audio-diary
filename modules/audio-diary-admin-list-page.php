<?php
include_once 'jdf.php';
$uploads = wp_upload_dir();
$audio_files = glob($uploads['basedir'] . '/audio-diary/*.wav');
if (!is_array($audio_files)) {
    $audio_files = [];
}
usort($audio_files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});
?>
<div class="audio-diary-container audio-diary-list">
    <h1 class="audio-diary-list__title"><?php _e('Recorded Audios', 'audio-diary'); ?></h1>
    <div class="audio-diary-list__actions">
        <button id="delete-selected"
            class="audio-diary-list__button audio-diary-list__button--danger"><?php _e('Delete Selected', 'audio-diary'); ?></button>
        <button id="download-zip"
            class="audio-diary-list__button audio-diary-list__button--primary"><?php _e('Download Selected as ZIP', 'audio-diary'); ?></button>
        <button id="select-all" class="audio-diary-list__button audio-diary-list__button--secondary"
            data-select-all="true"><?php _e('Select All', 'audio-diary'); ?></button>
    </div>
    <div id="selected-count" class="audio-diary-list__count">Selected: 0</div>
    <table class="audio-diary-list__table" id="audio-table">
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
                <td><input type="checkbox" class="audio-diary-list__checkbox select-audio"
                        value="<?php echo $file_name; ?>"></td>
                <td><?php echo $file_date; ?></td>
                <td><?php echo $file_time; ?></td>
                <td>
                    <audio class="audio-diary-list__audio" controls src="<?php echo $file_url; ?>"></audio>
                </td>
                <td class="audio-diary-list__actions-cell">
                    <button
                        class="audio-diary-list__button audio-diary-list__button--small btn-download download-single"
                        data-url="<?php echo $file_url; ?>" data-name="<?php echo $file_name; ?>">Download</button>
                    <button
                        class="audio-diary-list__button audio-diary-list__button--small audio-diary-list__button--upload upload-to-drive"
                        data-name="<?php echo $file_name; ?>">Upload to Drive</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>