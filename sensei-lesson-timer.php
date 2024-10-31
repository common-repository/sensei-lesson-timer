<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also src all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @wordpress-plugin
 * Plugin Name:       Element Lesson Timer for Sensei
 * Plugin URI:        https://www.elementplugins.com
 * Description:       Extends Sensei LMS to support Classroom events
 * Version:           2.0.2
 * Author:            ElementLMS
 * Author URI:        https://www.elementlms.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 5.3
 * Tested up to:      5.8.1
 * Requires PHP:      7.4
 * Text Domain:       element-lesson-timer-for-sensei
 * Domain Path:       /i18n/languages/
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ELMS_LTS_VERSION', '2.0.2' );

define( 'ELMS_LTS_FILE', __FILE__ );

if ( !defined( 'ELMS_LTS_DIR' ) ) {
	define( 'ELMS_LTS_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
}
if ( !defined( 'ELMS_LTS_SRC' ) ) {
	define( 'ELMS_LTS_SRC', ELMS_LTS_DIR . '/src' );
}
if ( !defined( 'ELMS_LTS_VENDOR' ) ) {
	define( 'ELMS_LTS_VENDOR', ELMS_LTS_DIR . '/vendor' );
}
if ( !defined( 'ELMS_LTS_URL' ) ) {
	define( 'ELMS_LTS_URL', plugins_url( basename( ELMS_LTS_DIR ) ) );
}
if ( !defined( 'ELMS_DB_PREFIX' ) ) {
	define( 'ELMS_DB_PREFIX', 'scs_' );
}

if ( !class_exists( 'Sensei_Lesson_Timer' ) ) {

	class Sensei_Lesson_Timer {

		private static $_instance;

		// Just a object-level flag to control between functions itf we are showing the timer or not.
		public $_process_timer = false;

		// Object-level post-type settings array, to be shared with Ajax method.
		public $post_type_setting_args = array();

		public $version	= ELMS_LTS_VERSION;

		// Contains the reference path to the plugin root directory. Used when other included plugin files
		// need to include files relative to the plugin root.
		public $plugin_dir;

		// Contains the reference url to the plugin root directory. Used when other included plugin files
		// need to refernece CSS/JS via URL
		public $plugin_url;

		// Container array for variable to be passed to out JavaScript logic
		public $localize_data = array();

		// These are the post_types registered by Sensei. We start there. There is a filter where the user can add custom post types.
		public $sensei_post_types = array( 'lesson' );

		/**
		 * Class Constructor
		 *
		 * @param none
		 * @return void
		 * @since 1.0
		 */
		function __construct() {

			$this->plugin_dir = ELMS_LTS_DIR;
			$this->plugin_url = ELMS_LTS_URL;

			add_action( 'init', array( $this, 'init' ) );
			add_action( 'template_redirect', array( $this, 'template_redirect' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
			add_action( 'get_footer', array( $this, 'get_footer' ) );

			add_filter( 'sensei_settings_tabs', array( $this, 'sensei_settings_tabs' ) );
			add_filter( 'sensei_settings_fields', array( $this, 'sensei_settings_fields' ) );

			add_action( 'admin_print_scripts', array( $this, 'admin_enqueue_scripts' ), 11 );
			add_action( 'admin_print_styles', array( $this, 'admin_enqueue_styles' ), 11 );

			add_action( 'wp_ajax_sensei_timer_post_types', array( $this, 'ajax_get_post_types' ), 11 );
		}

		/**
		  * Override class function for 'this'.
		  *
		  * This function handles out Singleton logic in
		  *
		  * @param string $myArgument With a *description* of this argument, these may also
		  *    span multiple lines.
		  *
		  * @return void
		  */
		static function this() {
			return self::$_instance;
		}

		/**
		 * Function to returns the current instance of the class object
		 *
		 * @param none
		 * @return current instance of $this
		 * @since 1.0
		 */
		public static function get_instance() {
			if ( ! self::$_instance ) {
				self::$_instance = new self();
			}
		    return self::$_instance;
		}

		/**
		 * Action hook handler function
		 *
		 * This function is called when WordPress fires the init action.
		 *
		 * Initialized part of this plugin like loading text domains, settings, etc.
		 *
		 * @param none
		 * @return none
		 * @since 1.0
		 */
		function init() {
			if (is_admin()) return;

			load_plugin_textdomain( 'element-lesson-timer-for-sensei', false, ELMS_LTS_DIR .'/i18n/languages/' );
		}

		/**
		 * Action hook handler function
		 *
		 * This function is called when WordPress fires the wp_enqueue_scripts action.
		 *
		 * This function registers scripts and stylesheets needed as part of this plugin's functionality.
		 *
		 * @param none
		 * @return none
		 * @since 1.0
		 */
		function wp_enqueue_scripts() {
			if (is_admin()) return;

			wp_register_script(
				'sensei-lesson-timer-js',
				$this->plugin_url .'/assets/js/sensei-lesson-timer.js',
				array( 'jquery' ),
				$this->version,
				true
			);
		}

		/**
		 * Action hook handler function
		 *
		 * This function is called when WordPress fires the template_redirect action for front-end.
		 *
		 * This function loads the $this->localized_data varaiable which will be passed out in the wp_footer action
		 * handler.
		 *
		 * @param none
		 * @return none
		 * @since 1.0
		 */
		function template_redirect() {
			global $woothemes_sensei;

			if (is_admin()) return;

			if ( ( !is_single() ) && ( !is_page() ) ) return;

			$queried_object = get_queried_object();

			$post_types = $this->sensei_post_types;
			if ( isset( $woothemes_sensei->settings->settings[ 'slt_setting_post_types' ] ) ) {
				$post_types = $woothemes_sensei->settings->settings[ 'slt_setting_post_types' ];
				if ( !empty( $post_types ) ) {
					foreach( $post_types as $idx => $post_type ) {
						$post_type = trim( $post_type );
						if ( empty( $post_type ) ) unset( $post_types[$idx] );
					}
					if ( !empty( $post_types ) ) {
						$post_types = array_values( $post_types );
					}
				}
			}

			// If we don't find the queried object post type in our collection then abort showing the timer.
			if ( ( !isset( $queried_object->post_type ) ) || ( array_search( $queried_object->post_type, $post_types ) === false ) ) {
				return;
			}

			if ($queried_object->post_type == 'lesson') {
				$lesson_id = $queried_object->ID;
				$course_id = absint( get_post_meta( $lesson_id, '_lesson_course', true ) );
				if (empty($course_id)) return;

				$started_course = Sensei_Course::is_user_enrolled( $course_id, get_current_user_id() );
				// If the user has not start course then don't show the timer.
				if ($started_course === false) return;

				$user_lesson_status = WooThemes_Sensei_Utils::user_lesson_status( $queried_object->ID, get_current_user_id() );
				if ( !empty( $user_lesson_status ) ) {
					$lesson_complete = WooThemes_Sensei_Utils::user_completed_lesson( $user_lesson_status );

					// If the user has completed the Lesson then don't show the timer.
					if ($lesson_complete === true) return;
				}
			}

			$disable_for_roles = array();
			if ( isset( $woothemes_sensei->settings->settings[ 'slt_setting_disable_by_roles' ] ) ) {
				$disable_for_roles = $woothemes_sensei->settings->settings[ 'slt_setting_disable_by_roles' ];
				// Don't trust Sensei to return a truely empty array. Instead it will return array( 0 => '') which is not empty;
				if (!empty( $disable_for_roles ) ) {
					foreach( $disable_for_roles as $idx => $role ) {
						$role = trim( $role );
						if (empty($role)) unset( $disable_for_roles[$idx] );
					}
					if ( !empty( $disable_for_roles ) ) {
						$disable_for_roles = array_values( $disable_for_roles );
					}
				}
			}
			$disable_for_roles = apply_filters( 'slt_setting_disable_for_roles', $disable_for_roles, $queried_object );
			if ( !empty( $disable_for_roles ) ) {
				foreach( $disable_for_roles as $idx => $role ) {
					if ( current_user_can( $role ) ) return;
				}
			}

			$this->localize_data['lesson_length'] = intval( get_post_meta( $queried_object->ID, "_lesson_length", true ) );

			$this->localize_data['unload_message'] = '';
			if ( isset( $woothemes_sensei->settings->settings[ 'slt_setting_warning_message' ] ) ) {
				$this->localize_data['unload_message'] = $woothemes_sensei->settings->settings[ 'slt_setting_warning_message' ];
			}

			$this->localize_data['auto_complete'] = false;
			if ( isset( $woothemes_sensei->settings->settings[ 'slt_setting_auto_complete' ] ) ) {
				$this->localize_data['auto_complete'] = $woothemes_sensei->settings->settings[ 'slt_setting_auto_complete' ];
			}

			$this->localize_data['pause_on_unfocus'] = true;
			if ( isset( $woothemes_sensei->settings->settings[ 'slt_setting_pause_on_unfocus' ] ) ) {
				$this->localize_data['pause_on_unfocus'] = $woothemes_sensei->settings->settings[ 'slt_setting_pause_on_unfocus' ];
			}

			$this->localize_data['placement'] = 'outside-right';
			if ( isset( $woothemes_sensei->settings->settings[ 'slt_setting_placement' ] ) ) {
				$this->localize_data['placement'] = $woothemes_sensei->settings->settings[ 'slt_setting_placement' ];
			}

			$this->localize_data['size'] = '1em';
			if ( isset( $woothemes_sensei->settings->settings[ 'slt_setting_size' ] ) ) {
				$this->localize_data['size'] = $woothemes_sensei->settings->settings[ 'slt_setting_size' ];
			}

			if ( ( $this->localize_data['placement'] == 'outside-left' ) || ( $this->localize_data['placement'] == 'outside-right' ) ) {
				$this->localize_data['form_element_outside_spacer'] = ' ';
			} else if ( ( $this->localize_data['placement'] == 'inside-left' ) || ( $this->localize_data['placement'] == 'inside-right' ) ) {
				$this->localize_data['form_element_inside_spacer']	= ' - ';
			}

			$this->localize_data['form_elements'] = array();
			if (array_search('lesson', $post_types) !== false) {
				// The standard Sensei Lesson form element. This is when a Lesson does not have a Quiz
				$this->localize_data['form_elements']['submit'] = 'form.lesson_button_form input.complete[name="quiz_complete"]';

				// Optional form element for when the Sensei Lesson has a Quiz
				// Need to use Sensei textdomain so that strings match for JS selector.
				$title_attr = __( 'View the Lesson Quiz', 'woothemes-sensei' );
				$this->localize_data['form_elements']['view_quiz'] = 'footer p a.button[title="'. $title_attr .'"]';
			}

			// Add a CSS cursor to the button while the time is active to indicate to the users the button is disabled
			$this->localize_data['form_element_cursor'] = 'not-allowed';

			// Add a title attribute to the button while the time is active to indicate to the users on hover the button is disabled.
			$this->localize_data['form_element_title'] = '';

			// Add a class to the button while the time is active to indicate to the users on hover the button is disabled.
			$this->localize_data['form_element_class'] = '';

			$this->localize_data['debug_js'] = true;
			$this->localize_data['show_timer'] = true;

			// See notes_filters.txt for full details on this filter and its related options
			$this->localize_data = apply_filters('sensei_lesson_timer_settings', $this->localize_data, $queried_object);
			if ( ( !isset( $this->localize_data['show_timer'] ) ) || ( !$this->localize_data['show_timer'] ) ) {
				return;
			}
			if ( ( !isset( $this->localize_data['lesson_length'] ) ) || ( !$this->localize_data['lesson_length'] ) ) {
				return;
			}
			if ( ( !isset( $this->localize_data['form_elements'] ) ) || ( !$this->localize_data['form_elements'] ) ) {
				return;
			}

			$this->_process_timer = $this->localize_data['show_timer'];
		}

		/**
		 * Action hook handler function
		 *
		 * This function is called when WordPress fires the get_footer action.
		 *
		 * This function checks our class variable (_process_timer) to see if it was loaded for
		 * the page. If YES it will pass the localize_data array out for JavaScript. If NO it will
		 * deregister the JavaScript and Stylesheet references established in
		 * wp_enqueue_scripts() class method.
		 *
		 * @param none
		 * @return none
		 * @since 1.0
		 */
		function get_footer() {

			if ($this->_process_timer == true) {
				wp_enqueue_script( 'sensei-lesson-timer-js' );
				wp_localize_script( 'sensei-lesson-timer-js', 'sensei_lesson_time_plugin_data', $this->localize_data );
			}
		}

		/**
		 * Action hook handler function
		 *
		 * This function is called when WordPress fires Sensei sensei_settings_tabs action.
		 *
		 * This function add new tab sections for our plugin to the Sensei Settings page.
		 *
		 * @param $sections - Array of current registered Sensei sections
		 * @return $sections - A modified version of the same array now including our
		 * custom sections.
		 * @since 1.0
		 */
		function sensei_settings_tabs( $sections ) {
			if (!isset( $sections['sensei-lesson-timer'] ) ) {
				$sections['sensei-lesson-timer'] = array(
					'name'        => __( 'Lesson Timer', 'element-lesson-timer-for-sensei' ),
					'description' => __( 'Sensei Lesson Timer Settings.', 'element-lesson-timer-for-sensei' )
				);
			}

			return $sections;
		}

		/**
		 * Action hook handler function
		 *
		 * This function is called when WordPress fires Sensei sensei_settings_fields action.
		 *
		 * This function add settings fields to the registered section added via sensei_settings_tabs.
		 *
		 * @since 1.0
		 * @param $fields - Array of current registered Sensei fields
		 * @return $fields - A modified version of the same array now including our
		 * custom fields.
		 */
		function sensei_settings_fields( $fields ) {


			$post_types_options = array(
				'lesson' => __( 'Lessons', 'element-lesson-timer-for-sensei' ),
			);

			// Store this for retreival via AJAX (ajax_get_post_types).
			$this->post_type_setting_args = array(
				'key' => 'slt_setting_post_types',
				'data' => array(
					'name'        => __( 'Add Lesson Timer to these Sensei post types', 'element-lesson-timer-for-sensei' ),
					'description' => __( 'The Lesson Timer will be displayed on the these Sensei post types', 'element-lesson-timer-for-sensei' ),
					'section'     => 'sensei-lesson-timer',
					'type'        => 'multicheck',
					// don't get post-type values here, since this hook is too early,
					// and Sensei CPTs are not registered. (we'll populate with Ajax)
					'options'     => $post_types_options,
					'defaults'    => array( 'lesson' ),
				),
			);

			$fields[ $this->post_type_setting_args['key'] ] = $this->post_type_setting_args['data'];

			$user_roles = $this->get_user_roles();
			$user_roles = apply_filters( 'slt_setting_disable_by_roles_options', $user_roles );
			if (!empty($user_roles)) {
				$fields['slt_setting_disable_by_roles'] = array(
					'name'        => __( 'Disable Lesson Timer by Role', 'element-lesson-timer-for-sensei' ),
					'description' => __( 'The Lesson Timer can be disable by specific user roles. By default the Lesson Time is shown to all user roles.', 'element-lesson-timer-for-sensei' ),
					'section'     => 'sensei-lesson-timer',
					'type'        => 'multicheck',
					'options'     => $user_roles,
					'defaults'    => array( ),
				);
			}

			$fields['slt_setting_auto_complete'] = array(
				'name'        => __('Auto Complete', 'element-lesson-timer-for-sensei'),
				'description' => __('Auto-Complete the Lesson when the timer reaches zero', 'element-lesson-timer-for-sensei'),
				'section'     => 'sensei-lesson-timer',
				'type'        => 'checkbox',
				'default'     => false,
			);

			$fields['slt_setting_pause_on_unfocus'] = array(
				'name'        => __('Pause Timer', 'element-lesson-timer-for-sensei'),
				'description' => __('Pause the Lesson Timer when the browser is not being viewed. This can help prevent the user from switching to another browser window while the timer counts down. This is only effective for modern browsers.', 'element-lesson-timer-for-sensei'),
				'section'     => 'sensei-lesson-timer',
				'type'        => 'checkbox',
				'default'     => true,
			);

			$fields['slt_setting_placement'] = array(
				'name'        => __('Timer Placement', 'element-lesson-timer-for-sensei'),
				'description' => __("Controls where the Lesson Timer will be displayed in relation to the 'Complete Lesson' button.", 'element-lesson-timer-for-sensei'),
				'section'     => 'sensei-lesson-timer',
				'type'        => 'select',
				'default'     => 'outside-right',
				'required'    => 0,
				'options'     => array(
					'outside-right'   => __('Disable Button, Timer after button', 'element-lesson-timer-for-sensei'),
					'outside-replace' => __('Hide Button, Show Timer', 'element-lesson-timer-for-sensei'),
					'inside-right'    => __('Add Timer after Button Text', 'element-lesson-timer-for-sensei'),
				),
			);

			$fields['slt_setting_size'] = array(
				'name'        => __('Timer Size', 'element-lesson-timer-for-sensei'),
				'description' => __("Controls the Lesson Timer size. 1 is the default (smallest size)", 'element-lesson-timer-for-sensei'),
				'section'     => 'sensei-lesson-timer',
				'type'        => 'select',
				'default'     => '1em',
				'required'    => 0,
				'options'     => array(
					'1em'   => __('1', 'element-lesson-timer-for-sensei'),
					'2em'   => __('2', 'element-lesson-timer-for-sensei'),
					'3em'   => __('3', 'element-lesson-timer-for-sensei'),
					'4em'   => __('4', 'element-lesson-timer-for-sensei'),
					'5em'   => __('5', 'element-lesson-timer-for-sensei'),
					'6em'   => __('6', 'element-lesson-timer-for-sensei'),
					'7em'   => __('7', 'element-lesson-timer-for-sensei'),
					'8em'   => __('8', 'element-lesson-timer-for-sensei'),
					'9em'   => __('9', 'element-lesson-timer-for-sensei'),
					'10em'   => __('10', 'element-lesson-timer-for-sensei'),
				),
			);

			$fields['slt_setting_warning_message'] = array(
				'name'        => __( 'Warning Message', 'element-lesson-timer-for-sensei' ),
				'description' => __('Message shown when the user attempts to leave the page where an active time is running. Leave blank to disable the warning message. This message will show in most modern browsers except Firefox.', 'element-lesson-timer-for-sensei'),
				'section'     => 'sensei-lesson-timer',
				'type'        => 'textarea',
				'default'     => '',
				'required'    => 0
			);

			return $fields;
		}

		/**
		 * This function registers the scripts needed as part of this plugin's admin functionality.
		 *
		 * It lists 'woothemes-sensei-settings-api' as a dependency, and as such, will not be enqueued unless
		 * the Sensei script is enqueued (which only happens on the Sensei settings screen.)
		 *
		 * @since  1.1.3
		 * @return void
		 */
		public function admin_enqueue_scripts() {
			wp_enqueue_script(
				'sensei-lesson-timer-admin-js',
				$this->plugin_url .'/assets/js/sensei-lesson-timer-admin.js',
				array( 'sensei-settings' ),
				$this->version,
				true
			);
			wp_localize_script( 'sensei-lesson-timer-admin-js', 'SenseiTimerAdminNonce', wp_create_nonce( __FILE__ ) );
		}

		/**
		 * This function registers the stylesheets needed as part of this plugin's admin functionality.
		 *
		 * It lists 'woothemes-sensei-settings-api' as a dependency, and as such, will not be enqueued unless
		 * the Sensei stylesheet is enqueued (which only happens on the Sensei settings screen.)
		 *
		 * @since  1.1.3
		 * @return void
		 */
		public function admin_enqueue_styles() {
			wp_enqueue_style(
				'sensei-lesson-timer-admin-css',
				$this->plugin_url .'/assets/css/sensei-lesson-timer-admin.css',
				array( 'sensei-settings-api' ),
				$this->version
			);
		}

		/**
		 * wp_ajax callback, hooked to the 'wp_ajax_sensei_timer_post_types' action.
		 *
		 * @since  1.1.3
		 * @return void
		 */
		public function ajax_get_post_types() {
			if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], __FILE__ ) ) {
				wp_send_json_error( 'nonce failed' );
			}

			$post_types = $this->get_post_types();

			$args = $this->post_type_setting_args;
			$args['data']['options'] = $post_types;

			ob_start();

			Sensei()->settings->form_field_multicheck( $args );

			// grab the data from the output buffer and send it.
			wp_send_json_success( ob_get_clean() );
		}

		/**
		 * A utility function to load the post_type name label for the supported post_types of this plugin (lesson)
		 *
		 * @param none
		 * @return array of post_type items keyed by post_type slug and value if the name label
		 * @since 1.0
		 */
		function get_post_types() {
			$post_types = array();

			if (!empty($this->sensei_post_types)) {
				foreach($this->sensei_post_types as $post_type_slug) {
					$post_type = get_post_type_object( $post_type_slug );

					if ( $post_type ) {
						$post_types[$post_type_slug] = $post_type->labels->name;
					}
				}
			}
			return $post_types;
		}

		/**
		 * A utility function to obtain a list of the user roles within the WordPress
		 * environment.
		 *
		 * @param none
		 * @return none - The function sets a class variable $roles
		 * @since 1.0
		 */
		function get_user_roles() {

			if (!function_exists('get_editable_roles')) {
				include (ABSPATH .'wp-admin/includes/user.php');
			}

			$roles = get_editable_roles();
			if (empty($roles)) return;

			$user_roles = array();
			foreach($roles as $role_slug => $role) {
				$user_roles[$role_slug] = $role['name'];
			}

			return $user_roles;
		}
	}
}

// Start the plugin
add_action('plugins_loaded', function() {

	$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );

	// Because plugin could be sensei/woothemes-sensei.php or woothemes-sensei/woothemes-sensei.php.
	$is_active = array_filter( $active_plugins, function( $plugin ) {
		return false !== strpos( $plugin, '/woothemes-sensei.php' ) || false !== strpos( $plugin, '/sensei-lms.php' );
	} );

	if ( ! empty( $is_active ) ) {
		Sensei_Lesson_Timer::get_instance();
	} else {
		trigger_error( __( 'Sensei Lesson Timer requires the Sensei plugin! Please install and activate Sensei (https://github.com/Automattic/sensei).', 'element-lesson-timer-for-sensei' ), E_USER_WARNING );
	}
});
