<?php

class Audio_Diary_Admin_Page {

    private static $instance;

    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_item'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_save_audio', array($this, 'save_audio')); 
        add_action('wp_ajax_nopriv_save_audio', array($this, 'save_audio')); 
        add_action('wp_ajax_delete_audio', array($this, 'delete_audio'));
        add_action('wp_ajax_delete_selected_audios', array($this, 'delete_selected_audios'));
        add_action('wp_ajax_download_zip', array($this, 'handle_download_zip'));
        add_action('wp_ajax_upload_to_google_drive', array($this, 'audio_diary_upload_to_google_drive'));
        add_action('wp_ajax_audio_diary_delete_selected_audios', array($this, 'audio_diary_delete_selected_audios'));
        add_action('wp_ajax_audio_diary_test_connection', array($this, 'test_connection'));
        $this->create_audio_folder();
    }

    public function test_connection() {
        check_ajax_referer('audio_diary_test_connection_nonce', 'nonce');
        if (!isset($_POST['folder_id']) || empty($_POST['folder_id'])) {
            wp_send_json_error(__('Invalid Folder ID.', 'audio-diary'));
        }

        $folder_id = sanitize_text_field($_POST['folder_id']);
        require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';
        $client = new Google_Client();
        $client->setAuthConfig(plugin_dir_path(__FILE__) . '../config/service-account.json');
        $client->addScope(Google_Service_Drive::DRIVE_METADATA_READONLY);
        $service = new Google_Service_Drive($client);

        try {
            $folder = $service->files->get($folder_id, ['fields' => 'id,name']);
            error_log("Test connection successful for folder ID: $folder_id, Name: " . $folder->getName());
            wp_send_json_success();
        } catch (Google_Service_Exception $e) {
            error_log("Test connection failed for $folder_id: " . $e->getMessage());
            if ($e->getCode() == 404) {
                wp_send_json_error(__('Folder not found. Check the Folder ID.', 'audio-diary'));
            } elseif ($e->getCode() == 403) {
                wp_send_json_error(__('Permission denied. Ensure the Service Account has access to this folder.', 'audio-diary'));
            } else {
                wp_send_json_error(__('Failed to connect: ' . $e->getMessage(), 'audio-diary'));
            }
        }
    }

    function audio_diary_upload_to_google_drive() {
        $file_name = isset($_POST['file']) ? sanitize_file_name($_POST['file']) : '';
        if (!$file_name) {
            error_log('Upload Error: No file specified.');
            wp_send_json_error('No file specified.');
        }

        $uploads = wp_upload_dir();
        $file_path = $uploads['basedir'] . '/audio-diary/' . $file_name;

        if (!file_exists($file_path)) {
            error_log("Upload Error: File not found on server - $file_path");
            wp_send_json_error('File not found on server.');
        }

        require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';
        $client = new Google_Client();
        $client->setAuthConfig(plugin_dir_path(__FILE__) . '../config/service-account.json');
        $client->addScope(Google_Service_Drive::DRIVE); // تغییر به DRIVE برای دسترسی کامل
        $service = new Google_Service_Drive($client);

        $target_folder_id = get_option('audio_diary_google_drive_folder_id', '1sBE78fuxRlcWouLs0mw12zLqTbWJg9jB');
        $drive_file_id = get_option('google_drive_' . $file_name);
        error_log("Checking file: $file_name, Drive ID: " . ($drive_file_id ? $drive_file_id : 'None'));

        if ($drive_file_id) {
            try {
                $file = $service->files->get($drive_file_id, ['fields' => 'id,name,parents,trashed']);
                $parents = $file->getParents();
                $trashed = $file->getTrashed();
                error_log("File $file_name exists in Google Drive with ID: $drive_file_id, Name: " . $file->getName() . ", Parents: " . implode(',', $parents) . ", Trashed: " . ($trashed ? 'Yes' : 'No'));

                if (in_array($target_folder_id, $parents) && !$trashed) {
                    wp_send_json_error('File already exists in the target Google Drive folder.');
                } else {
                    error_log("File $file_name is not in target folder or is trashed, proceeding to upload.");
                    delete_option('google_drive_' . $file_name);
                }
            } catch (Google_Service_Exception $e) {
                error_log("Google Drive check failed for $file_name: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
                if ($e->getCode() == 404) {
                    delete_option('google_drive_' . $file_name);
                    error_log("Deleted option for $file_name as it was not found in Google Drive.");
                } else {
                    wp_send_json_error('Error checking file in Google Drive: ' . $e->getMessage());
                }
            }
        } else {
            error_log("No Drive ID found for $file_name, proceeding to upload.");
        }

        error_log("Uploading $file_name to Google Drive...");
        $file = new Google_Service_Drive_DriveFile();
        $file->setName($file_name);
        $file->setParents([$target_folder_id]);

        $result = $service->files->create($file, [
            'data' => file_get_contents($file_path),
            'mimeType' => 'audio/wav',
            'uploadType' => 'multipart'
        ]);

        if ($result) {
            update_option('google_drive_' . $file_name, $result->id);
            error_log("Upload successful for $file_name, new ID: " . $result->id);
            wp_send_json_success(['file_id' => $result->id]);
        } else {
            error_log("Upload failed for $file_name");
            wp_send_json_error('Upload failed.');
        }
    }

    function audio_diary_delete_selected_audios() {
        $files = isset($_POST['files']) ? (array)$_POST['files'] : [];
        $delete_from_drive = isset($_POST['delete_from_drive']) && $_POST['delete_from_drive'] === 'true';
    
        if (empty($files)) {
            wp_send_json_error('No files selected.');
        }
    
        $uploads = wp_upload_dir();
        $deleted_local = 0;
        $deleted_drive = 0;
        $drive_errors = [];
    
        if ($delete_from_drive) {
            require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';
            $client = new Google_Client();
            $client->setAuthConfig(plugin_dir_path(__FILE__) . '../config/service-account.json');
            $client->addScope(Google_Service_Drive::DRIVE);
            $service = new Google_Service_Drive($client);
        }
    
        foreach ($files as $file) {
            $file_path = $uploads['basedir'] . '/audio-diary/' . sanitize_file_name($file);
            $drive_file_id = get_option('google_drive_' . $file);
    
            if ($delete_from_drive && $drive_file_id) {
                try {
                    $service->files->delete($drive_file_id);
                    delete_option('google_drive_' . $file);
                    $deleted_drive++;
                    error_log("Successfully deleted file $drive_file_id from Google Drive for $file.");
                } catch (Google_Service_Exception $e) {
                    $drive_errors[] = "Failed to delete $file from Google Drive: " . $e->getMessage() . " (Code: " . $e->getCode() . ")";
                    error_log($drive_errors[count($drive_errors) - 1]);
                }
            }
    
            if (file_exists($file_path) && unlink($file_path)) {
                $deleted_local++;
                error_log("Successfully deleted local file: $file_path");
            } else {
                error_log("No local file to delete or failed: $file_path");
            }
        }
    
        if ($deleted_local > 0 || $deleted_drive > 0) {
            $response = ['success' => true, 'deleted_local' => $deleted_local, 'deleted_drive' => $deleted_drive];
            if (!empty($drive_errors)) {
                $response['data'] = ['drive_errors' => implode("; ", $drive_errors)];
            }
            wp_send_json($response);
        } else {
            wp_send_json_error('Failed to delete any files.');
        }
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
        $files = isset($_POST['files']) ? (array)$_POST['files'] : [];
        if (empty($files)) {
            wp_send_json_error('No files selected.');
        }

        $uploads = wp_upload_dir();
        $zip_name = 'audio-diary-' . time() . '.zip';
        $zip_path = $uploads['basedir'] . '/' . $zip_name;
        $zip = new ZipArchive();

        if ($zip->open($zip_path, ZipArchive::CREATE) === TRUE) {
            foreach ($files as $file) {
                $file_path = $uploads['basedir'] . '/audio-diary/' . sanitize_file_name($file);
                if (file_exists($file_path)) {
                    $zip->addFile($file_path, $file);
                }
            }
            $zip->close();

            $zip_url = $uploads['baseurl'] . '/' . $zip_name;
            wp_send_json_success(['zip_url' => $zip_url]);
        } else {
            wp_send_json_error('Failed to create ZIP file.');
        }
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
        $files = isset($_POST['files']) ? (array)$_POST['files'] : [];
        if (empty($files)) {
            wp_send_json_error('No files selected.');
        }

        $uploads = wp_upload_dir();
        $deleted = 0;

        foreach ($files as $file) {
            $file_path = $uploads['basedir'] . '/audio-diary/' . sanitize_file_name($file);
            if (file_exists($file_path) && unlink($file_path)) {
                $deleted++;
            }
        }

        if ($deleted > 0) {
            wp_send_json_success('Deleted ' . $deleted . ' files.');
        } else {
            wp_send_json_error('Failed to delete files.');
        }
    }

    public function add_menu_item() {
        add_menu_page(
            'Audio Diary',
            'Audio Diary',
            'manage_options',
            'audio-diary',
            array($this, 'render_page'),
            'dashicons-media-audio',
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
        add_submenu_page(
            'audio-diary',
            __('setting', 'audio-diary'),
            __('setting', 'audio-diary'),
            'manage_options',
            'setting',
            array($this, 'setting_page')
        );
    }

    public function enqueue_scripts() {
        wp_enqueue_style('audio-diary-style', plugin_dir_url(__FILE__) . '../assets/css/style.css');
        wp_enqueue_style('audio-diary-toast-style', plugin_dir_url(__FILE__) . '../assets/css/jquery.toast.css');
        wp_enqueue_script('audio-diary-script', plugin_dir_url(__FILE__) . "../assets/js/audio-diary.js", array('jquery'), '1.0', true);
        wp_enqueue_script('audio-diary-toast-script', plugin_dir_url(__FILE__) . "../assets/js/jquery.toast.js", array('jquery'), '1.0', true);
    }

    public function render_page() {
        include_once AUDIO_DIARY_MODULES_PATH . "audio-diary-admin-page.php";
    }

    public function render_list_page() {
        include_once AUDIO_DIARY_MODULES_PATH . "audio-diary-admin-list-page.php";
    }

    public function setting_page() {
        include_once AUDIO_DIARY_MODULES_PATH . "setting-admin-page.php";
    }

    private function create_audio_folder() {
        $uploads = wp_upload_dir();
        $audio_dir = $uploads['basedir'] . '/audio-diary';

        if (!file_exists($audio_dir)) {
            wp_mkdir_p($audio_dir);
        }
    }

    public function save_audio() {
        // if (!current_user_can('manage_options')) {
        //     wp_send_json_error('Unauthorized');
        //     return;
        // }

        if (empty($_FILES['audio_data'])) {
            wp_send_json_error('No file uploaded');
            return;
        }

        $file = $_FILES['audio_data'];
        $uploads = wp_upload_dir();
        $upload_path = $uploads['basedir'] . '/audio-diary/';

        $date_time = date('Y-m-d-H-i-s');
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