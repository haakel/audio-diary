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
        add_action('wp_ajax_delete_audio_files', 'delete_audio_files');
        $this->create_audio_folder();
    }
  
    function delete_audio_files() {
        check_ajax_referer('audio-diary-nonce', '_ajax_nonce');
    
        if (!isset($_POST['files']) || !is_array($_POST['files'])) {
            wp_send_json_error('Invalid request');
        }
    
        $uploads = wp_upload_dir();
        $audio_dir = $uploads['basedir'] . '/audio-diary/';
        $deleted = [];
    
        foreach ($_POST['files'] as $file) {
            $file_path = $audio_dir . basename($file);
            if (file_exists($file_path) && unlink($file_path)) {
                $deleted[] = $file;
            }
        }
    
        if (empty($deleted)) {
            wp_send_json_error('No files deleted');
        }
    
        wp_send_json_success($deleted);
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
        wp_enqueue_style('audio-diary-style', plugin_dir_url(__FILE__) . '../assets/css/style.css');
        wp_enqueue_style('audio-diary-toast-style', plugin_dir_url(__FILE__) . '../assets/css/jquery.toast.css');
        wp_enqueue_script('audio-diary-script',  plugin_dir_url(__FILE__) ."../assets/js/audio-diary.js", array('jquery'), '1.0', true);
        wp_enqueue_script('audio-diary-toast-script',  plugin_dir_url(__FILE__) ."../assets/js/jquery.toast.js", array('jquery'), '1.0', true);

    }

    public function render_page() {
        include_once AUDIO_DIARY_MODULES_PATH."audio-diary-admin-page.php";
    }

    public function render_list_page() {
        include_once AUDIO_DIARY_MODULES_PATH."audio-diary-admin-list-page.php";
    }

    private function create_audio_folder() {
        $uploads = wp_upload_dir();
        $audio_dir = $uploads['basedir'] . '/audio-diary';

        if (!file_exists($audio_dir)) {
            wp_mkdir_p($audio_dir);
        }
}
    public function save_audio() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
        return;
    }

    if (empty($_FILES['audio_data'])) {
        wp_send_json_error('No file uploaded');
        return;
    }

    $file = $_FILES['audio_data'];
    $uploads = wp_upload_dir();
    $upload_path = $uploads['basedir'] . '/audio-diary/';

    $file_name = 'audio-' . time() . '.wav';
    $file_path = $upload_path . $file_name;

    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        wp_send_json_success('File uploaded');
    } else {
        wp_send_json_error('File upload failed');
    }
}

}
Audio_Diary_Admin_Page::get_instance();