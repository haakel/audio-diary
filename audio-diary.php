<?php
/*
Plugin Name: Audio diary
Description: پلاگین دفترچه خاطرات.
Author: haakel
Version: 2.1.1
*/

if (!defined('ABSPATH')) {
    echo "what the hell are you doing here?";
    exit;
}

// فعال کردن کرون جاب در هنگام فعال‌سازی افزونه
register_activation_hook(__FILE__, 'audio_diary_activate');
function audio_diary_activate() {
    if (!wp_next_scheduled('delete_old_zip_files_cron')) {
        wp_schedule_event(time(), 'hourly', 'delete_old_zip_files_cron');
    }
}

// غیرفعال کردن کرون جاب در هنگام غیرفعال‌سازی افزونه
register_deactivation_hook(__FILE__, 'audio_diary_deactivate');
function audio_diary_deactivate() {
    $timestamp = wp_next_scheduled('delete_old_zip_files_cron');
    wp_unschedule_event($timestamp, 'delete_old_zip_files_cron');
}

// تعریف اکشن برای حذف فایل‌های زیپ قدیمی
add_action('delete_old_zip_files_cron', 'delete_old_zip_files');
function delete_old_zip_files() {
    $uploads = wp_upload_dir();
    $files = glob($uploads['basedir'] . '/audio-diary-selected-*.zip');
    $time_limit = 3600; // 1 ساعت به ثانیه

    foreach ($files as $file) {
        if (filemtime($file) < (time() - $time_limit)) {
            unlink($file);
        }
    }
}

class Audio_diary {
    private static $instance;

    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->define_constants();
        $this->Audio_diary_loader();
        add_shortcode('audio_diary', array($this, 'audio_diary_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_shortcode_scripts')); // اضافه کردن هوک برای frontend
    }

    public function define_constants() {
        define('AUDIO_DIARY_VERSION', '1.0.0');
        define('AUDIO_DIARY_FILE', __FILE__);
        define('AUDIO_DIARY_PATH', plugin_dir_path(AUDIO_DIARY_FILE));
        define('AUDIO_DIARY_BASE', plugin_basename(AUDIO_DIARY_FILE));
        define('AUDIO_DIARY_SLUG', 'audio-diary-settings');
        define('AUDIO_DIARY_SETTINGS_LINK', admin_url('admin.php?page=' . AUDIO_DIARY_SLUG));
        define('AUDIO_DIARY_CLASSES_PATH', AUDIO_DIARY_PATH . 'classes/');
        define('AUDIO_DIARY_IMAGES', AUDIO_DIARY_PATH . 'build/images');
        define('AUDIO_DIARY_MODULES_PATH', AUDIO_DIARY_PATH . 'modules/');
        define('AUDIO_DIARY_ASSETS_PATH', AUDIO_DIARY_PATH . 'assets/');
        define('AUDIO_DIARY_URL', plugins_url('/', AUDIO_DIARY_FILE));
    }

    public function audio_diary_shortcode() {
        ob_start();
        ?>
<div class="audio-diary-container audio-diary-recorder">
    <h1><?php _e('Audio Diary', 'audio-diary'); ?></h1>
    <button class="audio-diary-recorder__button" id="recording-button"></button>
    <audio class="audio-diary-recorder__player" id="audio-player" controls></audio>
    <canvas class="audio-diary-recorder__visualizer" id="visualizer" width="640" height="100"></canvas>
</div>
<?php
        return ob_get_clean();
    }

    public function enqueue_shortcode_scripts() {
        if (is_singular() && has_shortcode(get_the_content(), 'audio_diary')) {
            wp_enqueue_style('audio-diary-style', AUDIO_DIARY_URL . 'assets/css/style.css');
            wp_enqueue_style('audio-diary-toast-style', AUDIO_DIARY_URL . 'assets/css/jquery.toast.css');
    
            wp_enqueue_script('audio-diary-toast-script', AUDIO_DIARY_URL . 'assets/js/jquery.toast.js', array('jquery'), '1.0', true);
            wp_enqueue_script('audio-diary-script', AUDIO_DIARY_URL . 'assets/js/audio-diary.js', array('jquery', 'audio-diary-toast-script'), '1.0', true);
    
            wp_localize_script('audio-diary-script', 'audio_diary_vars', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
            ));
        }
    }

    public function Audio_diary_loader() {
        require AUDIO_DIARY_CLASSES_PATH . 'class_Audio_diary_loader.php';
    }
}

Audio_diary::get_instance();