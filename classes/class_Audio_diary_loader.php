<?php 
if ( ! defined( 'ABSPATH' ) ) {
echo "what the hell are you doing here?";
exit;
}

class Audio_Diary_loader{
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
    
    /**
	 * Constructor
	 */
	public function __construct() {
		$this->load_dependencies();
	}
    /**
	 * Load the required dependencies for this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		// require_once AUDIO_DIARY_CLASSES_PATH . '';
		// require_once AUDIO_DIARY_CLASSES_PATH . '';

		// The class responsible for the settings page
		require_once AUDIO_DIARY_CLASSES_PATH . 'class_admin_page.php';

		// The class responsible for defining main options of the plugin.

		// require_once AUDIO_DIARY_CLASSES_PATH . '';

		// The class responsible for defining REST Routs API of the plugin.
		// require_once AUDIO_DIARY_CLASSES_PATH . '';

		// The class responsible for defining all actions for edd.
        
		// require_once AUDIO_DIARY_MODULES_PATH . '';
}

}

Audio_Diary_loader::get_instance();