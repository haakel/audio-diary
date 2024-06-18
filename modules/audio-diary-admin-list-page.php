<?php
$uploads = wp_upload_dir();
$audio_files = glob($uploads['basedir'] . '/audio-diary/*.wav');

usort($audio_files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

?>
<div class="wrap">
    <h1><?php _e('Recorded Audios', 'audio-diary'); ?></h1>
    <table>
        <thead>
            <tr>
                <th><?php _e('Date', 'audio-diary'); ?></th>
                <th><?php _e('Time', 'audio-diary'); ?></th>
                <th><?php _e('Audio', 'audio-diary'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($audio_files as $file) : 
                $file_date = date("Y-m-d", filemtime($file));
                $file_time = date("H:i:s", filemtime($file));
                $file_url = $uploads['baseurl'] . '/audio-diary/' . basename($file);
            ?>
            <tr>
                <td><?php echo $file_date; ?></td>
                <td><?php echo $file_time; ?></td>
                <td>
                    <audio controls src="<?php echo $file_url; ?>"></audio>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>