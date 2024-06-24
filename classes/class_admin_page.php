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
        add_action('wp_ajax_delete_audio', array($this, 'delete_audio'));
        add_action('wp_ajax_delete_selected_audios', array($this, 'delete_selected_audios'));
        add_action('wp_ajax_download_zip', array($this,'handle_download_zip'));
        $this->create_audio_folder();
    }

    function delete_old_zip_files() {
        $uploads = wp_upload_dir();
        $files = glob($uploads['basedir'] . '/audio-diary-selected-*.zip');
        $time_limit = 3600; // 1 hour in seconds
    
        foreach ($files as $file) {
            if (filemtime($file) < (time() - $time_limit)) {
                unlink($file);
            }
        }
    }

    function handle_download_zip() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
            return;
        }
    
        if (!isset($_POST['files']) || !is_array($_POST['files'])) {
            wp_send_json_error(['message' => 'Invalid request']);
            return;
        }
    
        $selected_files = $_POST['files'];
        $uploads = wp_upload_dir();
        $zip = new ZipArchive();
        $zip_filename = 'audio-diary-selected-' . time() . '.zip';
        $zip_filepath = $uploads['basedir'] . '/' . $zip_filename;
    
        if ($zip->open($zip_filepath, ZipArchive::CREATE) !== TRUE) {
            wp_send_json_error(['message' => 'Cannot create zip file']);
            return;
        }
    
        foreach ($selected_files as $file) {
            $file_path = $uploads['basedir'] . '/audio-diary/' . $file;
            if (file_exists($file_path)) {
                $zip->addFile($file_path, $file);
            }
        }
    
        $zip->close();
    
        $zip_url = $uploads['baseurl'] . '/' . $zip_filename;
    
        wp_send_json_success(['zip_url' => $zip_url]);
    }
    
    
    function delete_audio() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }
    
        if (empty($_POST['file_name'])) {
            wp_send_json_error('No file specified');
            return;
        }
    
        $file_name = sanitize_file_name($_POST['file_name']);
        $uploads = wp_upload_dir();
        $file_path = $uploads['basedir'] . '/audio-diary/' . $file_name;
    
        if (file_exists($file_path) && unlink($file_path)) {
            wp_send_json_success('File deleted');
        } else {
            $error = file_exists($file_path) ? 'Failed to delete file' : 'File does not exist';
            wp_send_json_error($error);
        }
    }
    
    function delete_selected_audios() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }
    
        if (empty($_POST['files']) || !is_array($_POST['files'])) {
            wp_send_json_error('No files specified');
            return;
        }
    
        $uploads = wp_upload_dir();
        $base_path = $uploads['basedir'] . '/audio-diary/';
        $errors = [];
    
        foreach ($_POST['files'] as $file_name) {
            $file_name = sanitize_file_name($file_name);
            $file_path = $base_path . $file_name;
    
            if (file_exists($file_path)) {
                if (!unlink($file_path)) {
                    $errors[] = $file_name;
                }
            } else {
                $errors[] = $file_name;
            }
        }
    
        if (empty($errors)) {
            wp_send_json_success('All files deleted');
        } else {
            wp_send_json_error('Failed to delete files: ' . implode(', ', $errors));
        }
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

    $date_time = date('Y-m-d H-i-s');
    $file_name = 'memory-' . $date_time . '.wav';
    $file_path = $upload_path . $file_name;

    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        wp_send_json_success(['message' => 'File uploaded', 'file_name' => $file_name]);
    } else {
        wp_send_json_error('File upload failed');
    }
}



}
Audio_Diary_Admin_Page::get_instance();