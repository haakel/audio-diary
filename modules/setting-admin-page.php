<?php
if (!defined('ABSPATH')) {
    exit;
}

class AudioDiarySettings {
    private $folder_id_option = 'audio_diary_google_drive_folder_id';
    private $default_folder_id = '1sBE78fuxRlcWouLs0mw12zLqTbWJg9jB';

    public function render() {
        if (isset($_POST['audio_diary_settings_submit']) && check_admin_referer('audio_diary_settings_nonce')) {
            $new_folder_id = sanitize_text_field($_POST['google_drive_folder_id']);
            update_option($this->folder_id_option, $new_folder_id);
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'audio-diary') . '</p></div>';
        }

        $folder_id = get_option($this->folder_id_option, $this->default_folder_id);
        $folder_info = $this->get_folder_info($folder_id);
        ?>

<div class="audio-diary-container audio-diary-settings-panel">
    <h1 class="audio-diary-settings-panel__title"><?php _e('Audio Diary Settings', 'audio-diary'); ?></h1>
    <p class="audio-diary-settings-panel__description">
        <?php _e('Configure the settings for your Audio Diary plugin below.', 'audio-diary'); ?></p>

    <h2 class="audio-diary-settings-panel__nav nav-tab-wrapper">
        <a href="#general" class="nav-tab nav-tab-active"><?php _e('General', 'audio-diary'); ?></a>
    </h2>

    <form method="post" action="" class="audio-diary-settings-form">
        <?php wp_nonce_field('audio_diary_settings_nonce'); ?>

        <div class="audio-diary-settings-panel__section">
            <h2 class="audio-diary-settings-panel__section-title"><?php _e('Google Drive Settings', 'audio-diary'); ?>
            </h2>
            <table class="audio-diary-settings-panel__table form-table">
                <tr>
                    <th scope="row">
                        <label
                            for="google_drive_folder_id"><?php _e('Google Drive Folder ID', 'audio-diary'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="google_drive_folder_id" name="google_drive_folder_id"
                            value="<?php echo esc_attr($folder_id); ?>" class="regular-text"
                            placeholder="<?php _e('Enter Folder ID', 'audio-diary'); ?>" />
                        <p class="audio-diary-settings-panel__description">
                            <?php _e('The ID of the Google Drive folder where audio files are stored.', 'audio-diary'); ?>
                        </p>
                        <button type="button" id="test-connection"
                            class="audio-diary-settings-panel__test-button"><?php _e('Test Connection', 'audio-diary'); ?></button>
                        <span id="connection-status" class="audio-diary-settings-panel__status"></span>
                    </td>
                </tr>
                <?php if ($folder_info): ?>
                <tr>
                    <th scope="row"><?php _e('Folder Name', 'audio-diary'); ?></th>
                    <td>
                        <span
                            class="audio-diary-settings-panel__folder-info"><?php echo esc_html($folder_info['name']); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Folder URL', 'audio-diary'); ?></th>
                    <td>
                        <a href="<?php echo esc_url($folder_info['url']); ?>"
                            target="_blank"><?php _e('Open in Google Drive', 'audio-diary'); ?></a>
                    </td>
                </tr>
                <?php else: ?>
                <tr>
                    <th scope="row"><?php _e('Folder Status', 'audio-diary'); ?></th>
                    <td>
                        <span
                            class="audio-diary-settings-panel__error"><?php _e('Folder not found or inaccessible. Verify the Folder ID and ensure it\'s shared with the Service Account.', 'audio-diary'); ?></span>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <p class="submit">
            <input type="submit" name="audio_diary_settings_submit" class="audio-diary-settings-panel__button"
                value="<?php _e('Save Changes', 'audio-diary'); ?>" />
        </p>
    </form>
</div>

<style>
.audio-diary-settings {
    max-width: 800px;
    margin: 20px 0;
}

.nav-tab-wrapper {
    margin-bottom: 20px;
}

.settings-section {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
    margin-bottom: 20px;
}

.settings-section h2 {
    margin-top: 0;
    font-size: 1.3em;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.form-table th {
    width: 200px;
    padding: 15px 10px;
}

.form-table td {
    padding: 15px 10px;
}

.folder-info {
    font-weight: bold;
    color: #23282d;
}

.error {
    color: #d63638;
}

.description {
    color: #666;
    font-style: italic;
}

.submit {
    text-align: right;
}

#test-connection {
    margin-top: 10px;
}

.connection-status {
    margin-left: 10px;
    font-weight: bold;
}

.connection-status.success {
    color: #46b450;
}

.connection-status.error {
    color: #d63638;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
    });

    $('#test-connection').on('click', function() {
        var $button = $(this);
        var folderId = $('#google_drive_folder_id').val();
        var $status = $('#connection-status');

        $button.prop('disabled', true);
        $status.text('<?php _e("Testing...", "audio-diary"); ?>').removeClass('success error');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'audio_diary_test_connection',
                folder_id: folderId,
                nonce: '<?php echo wp_create_nonce("audio_diary_test_connection_nonce"); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $status.text('<?php _e("Connection successful!", "audio-diary"); ?>')
                        .addClass('success');
                } else {
                    $status.text(response.data ||
                        '<?php _e("Connection failed.", "audio-diary"); ?>').addClass(
                        'error');
                }
            },
            error: function() {
                $status.text('<?php _e("AJAX error. Please try again.", "audio-diary"); ?>')
                    .addClass('error');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });
});
</script>
<?php
    }
    private function get_folder_info($folder_id) {
        try {
            require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';
            $client = new Google_Client();
            $client->setAuthConfig(plugin_dir_path(__FILE__) . '../config/service-account.json');
            $client->addScope(Google_Service_Drive::DRIVE_METADATA_READONLY); // تغییر Scope
            $service = new Google_Service_Drive($client);
    
            $folder = $service->files->get($folder_id, ['fields' => 'id,name,webViewLink']);
            return [
                'name' => $folder->getName(),
                'url' => $folder->getWebViewLink()
            ];
        } catch (Exception $e) {
            error_log("Failed to get folder info for $folder_id: " . $e->getMessage());
            return false;
        }
    }
}

$settings = new AudioDiarySettings();
$settings->render();
?>