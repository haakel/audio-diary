<?php
$uploads = wp_upload_dir();
$audio_files = glob($uploads['basedir'] . '/audio-diary/*.wav');
?>
<div class="wrap">
    <h1><?php _e('Recorded Audios', 'audio-diary'); ?></h1>
    <ul>
        <?php foreach ($audio_files as $file) : ?>
        <li>
            <audio controls src="<?php echo $uploads['baseurl'] . '/audio-diary/' . basename($file); ?>"></audio>
        </li>
        <?php endforeach; ?>
    </ul>
</div>