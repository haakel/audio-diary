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

// تنظیمات صفحه‌بندی
$per_page = 10;
$total_files = count($audio_files);
$total_pages = max(1, ceil($total_files / $per_page)); // حداقل 1 صفحه
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

// اگر صفحه جاری بزرگ‌تر از تعداد صفحات باشد، به آخرین صفحه هدایت شو
if ($current_page > $total_pages) {
    $redirect_url = add_query_arg('paged', $total_pages, admin_url('admin.php?page=audio-diary-list'));
    wp_safe_redirect($redirect_url);
    exit;
}

$offset = ($current_page - 1) * $per_page;
$paged_files = array_slice($audio_files, $offset, $per_page);
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
    <table class="audio-diary-list__table" id="audio-table" data-total-files="<?php echo esc_attr($total_files); ?>">
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
            <?php foreach ($paged_files as $file) : 
                $file_date = jdate("Y-m-d", filemtime($file));
                $file_time = jdate("H:i:s", filemtime($file));
                $file_url = $uploads['baseurl'] . '/audio-diary/' . basename($file);
                $file_name = basename($file);
            ?>
            <tr data-file="<?php echo esc_attr($file_name); ?>">
                <td><input type="checkbox" class="audio-diary-list__checkbox select-audio"
                        value="<?php echo esc_attr($file_name); ?>"></td>
                <td><?php echo esc_html($file_date); ?></td>
                <td><?php echo esc_html($file_time); ?></td>
                <td>
                    <audio class="audio-diary-list__audio" controls src="<?php echo esc_url($file_url); ?>"></audio>
                </td>
                <td class="audio-diary-list__actions-cell">
                    <button
                        class="audio-diary-list__button audio-diary-list__button--small btn-download download-single"
                        data-url="<?php echo esc_url($file_url); ?>"
                        data-name="<?php echo esc_attr($file_name); ?>">Download</button>
                    <button
                        class="audio-diary-list__button audio-diary-list__button--small audio-diary-list__button--upload upload-to-drive"
                        data-name="<?php echo esc_attr($file_name); ?>">Upload to Drive</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1) : ?>
    <div class="audio-diary-list__pagination">
        <?php
        $base_url = admin_url('admin.php?page=audio-diary-list');
        if ($current_page > 1) :
            $prev_url = add_query_arg('paged', $current_page - 1, $base_url);
        ?>
        <a href="<?php echo esc_url($prev_url); ?>"
            class="audio-diary-list__pagination-link audio-diary-list__pagination-link--prev"><?php _e('Previous', 'audio-diary'); ?></a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++) : 
            $page_url = add_query_arg('paged', $i, $base_url);
        ?>
        <a href="<?php echo esc_url($page_url); ?>"
            class="audio-diary-list__pagination-link <?php echo $i === $current_page ? 'audio-diary-list__pagination-link--active' : ''; ?>">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>

        <?php if ($current_page < $total_pages) :
            $next_url = add_query_arg('paged', $current_page + 1, $base_url);
        ?>
        <a href="<?php echo esc_url($next_url); ?>"
            class="audio-diary-list__pagination-link audio-diary-list__pagination-link--next"><?php _e('Next', 'audio-diary'); ?></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>