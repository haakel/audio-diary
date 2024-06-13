<?php 
if ( ! defined( 'ABSPATH' ) ) {
    echo "what the hell are you doing here?";
    exit;
}

class Audio_Diary_Admin_Page {
    /**
     * Instance
     *
     * @access private
     * @var object Class object.
     */
    private static $instance;

    /**
     * Initiator
     *
     * @return object Initialized object of class.
     */
    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        // You can add actions here if needed, for example:
         add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        $script_url = plugins_url('assets/js/audio-diary.js', AUDIO_DIARY_PATH);
        wp_enqueue_script('audio-diary-script', $script_url, array('jquery'), '1.0', true);

        wp_localize_script(
            'audio-diary-script',
            'audioDiaryJsObject',
            [
                'ajax_url'        => admin_url('admin-ajax.php'),
                'nonce'           => wp_create_nonce('audio_diary_nonce'),
            ]
        );
    }

    /**
     * Render record page
     */
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

    /**
     * Render list page
     */
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
}

// Initialize the class instance
Audio_Diary_Admin_Page::get_instance();