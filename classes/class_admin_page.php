<?php 

class Audio_Diary_Admin_Page {

    /**
	 * Instance
	 *
	 * @access private
	 * @var object Class object.
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * Initiator
	 *
	 * @return object Initialized object of class.
	 * @since 1.0.0
	 */
	
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
	
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_item'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_save_audio', array($this, 'save_audio'));
    }

    public function add_menu_item() {
        add_menu_page(
            'Audio Diary',         // عنوان صفحه
            'Audio Diary',         // عنوان منو
            'manage_options',      // سطح دسترسی
            'audio-diary',         // اسلاگ صفحه
            array($this, 'render_page'), // تابعی که محتوای صفحه را نمایش می‌دهد
            'dashicons-media-audio', // آیکون منو
            6    
        );

        add_submenu_page(
            'audio-diary',
            __('Recorded Audios', 'audio-diary'),
            __('Recorded Audios', 'audio-diary'),
            'manage_options',
            'audio-diary-list',
            array($this, 'render_list_page')
        );
    }

    public function enqueue_scripts() {
        $script_url = plugins_url( 'js/audio-diary.js', AUDIO_DIARY_ASSETS_PATH );
        wp_enqueue_script('audio-diary-script', $script_url, array('jquery'), '1.0', true);
    }

    public function render_record_page() {
        ?>
<div class="wrap">
    <h1><?php _e('Audio Diary', 'audio-diary'); ?></h1>
    <button id="start-recording"><?php _e('Start Recording', 'audio-diary'); ?></button>
    <button id="stop-recording" disabled><?php _e('Stop Recording', 'audio-diary'); ?></button>
    <audio id="audio-player" controls></audio>
</div>
<?php
    }

    public function render_list_page() {
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
<?php
    }

    public function save_audio() {
        check_admin_referer('audio_diary_nonce');

        $audio_data = $_POST['audio_data'];
        $audio_data = base64_decode(str_replace('data:audio/wav;base64,', '', $audio_data));

        $uploads = wp_upload_dir();
        $audio_dir = $uploads['basedir'] . '/audio-diary/';
        if (!file_exists($audio_dir)) {
            mkdir($audio_dir, 0755, true);
        }

        $filename = $audio_dir . uniqid() . '.wav';
        file_put_contents($filename, $audio_data);

        wp_send_json_success();
    }
}

if (class_exists('Audio_Diary')) {
    $audio_diary = new Audio_Diary();
}

Audio_Diary_Admin_Page::get_instance();