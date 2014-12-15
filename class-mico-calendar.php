<?php
/**
 * MICO Calendar
 *
 * @package 	MICO_Calendar
 * @author  	Malthe Milthers <malthe@milthers.dk>
 * @license 	@TODO [description]
 * @copyright 	2014 MICO
 * @link 		MICO, http://www.mico.dk
 */




/**
 * The main class
 */
class MICO_Calendar {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 * @var     string
	 */
	const VERSION = '1.0.0';


	/**
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file and the name of the main plugin folder. 
	 *
	 * @since    1.0.0
	 * @var      string
	 */
	protected $plugin_slug = 'mico-calendar';

	/**
	 * Unique prefix for all database entries
	 * @since 		1.0.0
	 * @var 		string
	 */
	
	protected $plugin_db_prefix = 'mcal';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 * @var      object
	 */
	protected static $instance = null;


	/**
	 * Slug of the plugin screen.
	 *
	 * This is use to display a link to the settings page, on the plugins page (beside the activate/deactivate links). 
	 *
	 * @since    1.0.0
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Stor all events
	 */
	protected $all_events = null;

	/**
	 * Initialize the plugin by setting localization and loading scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		


		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		//populate the $all_events variable with the events from wordpress
		add_action( 'wp_loaded', array($this, 'populate_events'));

		// Load styles and scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_fullcalendar_scripts' ) );

		// Event post type: Register post type (event)
		add_action( 'init', array( $this, 'register_post_type' ) );
		
		// Add a custom "view event" link, since this is not displayed by default, when post type is a submenu item. 
		add_action( 'admin_bar_menu', array($this,'add_view_link_to_toolbar'), 999 );

		// Event post type: hide date filter on the post type listing
			//add_action('load-edit.php', array($this, 'hide_date_filter') );
		// Event post type: Add metabox to the post type (event)
		add_action( 'add_meta_boxes', array( $this, 'add_event_meta_box') );
		// Event post type: Save the event meta box data
		add_action( 'save_post_event', array( $this, 'save_event_meta_box_data') );


		// Eventslist meta box: Add metabox to post types supported via the settings page.
		add_action( 'add_meta_boxes', array( $this, 'add_eventslist_meta_box') );
		// Eventslist meta box: Save the eventslist meta box data
		add_action( 'save_post', array( $this, 'save_eventslist_meta_box_data') );
		


		// Settings page: Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_settings_menu' ) );
		// Settings page: Add an action link (with activate and delete) pointing to the settings page. NOTE: the hook must have this format: 'plugin_action_links_PLUGINFOLDER/PLUGINFILE.php'
		add_filter( 'plugin_action_links_'. $this->plugin_slug .'/'. $this->plugin_slug .'.php', array( $this, 'add_action_links' ) );
		// Settings page: Add and register the settings section and fields
		add_action('admin_init', array( $this, 'add_plugin_settings' ));


		// Plugins menu: add the main calendar view. Priority must be a lower number than 9, in order for this item to appear
		add_action( 'admin_menu', array( $this, 'add_plugin_toplevel_menu' ), 1 );
		// Plugins menu: add a "fake" submenu for "new event". This menu disappears, when the post type is added under a different menu. Same goes for potental taxonomies
		//add_action('admin_menu', array($this, 'add_plugin_new_event_submenu') );
		// Plugins menu: Fix closed admin menu, when editing single post. admin_head seems to be the only hook that works.
		//add_action( 'admin_head', array($this, 'fix_plugin_menu') );


		// FullCalendar AJAX: Load events as JSON
		add_action( 'wp_ajax_events_json', array($this, 'events_json_callback') );
		// FullCalendar AJAX: Update event 
		add_action( 'wp_ajax_update_event', array($this, 'update_event_callback') );

		// Insert event via ajax 
		add_action( 'wp_ajax_insert_event', array($this, 'insert_event_callback') );
		// Delete event via ajax
		add_action( 'wp_ajax_delete_event', array($this, 'delete_event_callback') );
		// update the date range on the related post.
		add_action( 'wp_ajax_update_related_date_range', array($this, 'update_related_date_range_callback') );


		//Trash related events when the main post is trashed
		add_action('trashed_post', array($this, 'trash_related_events') );
		//untrash related events when the main post is untrashed
		add_action('untrash_post', array($this, 'untrash_related_events') );
		//delet related events when the main post is deleted
		add_action('delete_post', array($this, 'delete_related_events') );
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since 		1.0.0
	 * @return 		string 		Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return the instance of this class.
	 *
	 * @since 		1.0.0 
	 * @return		object		A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( self::$instance == null ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since 		1.0.0
	 *
	 * @param 		boolean 		$network_wide 	True if WPMU superadmin uses
	 *                                   			"Network Activate" action, false if
	 *                                       		WPMU is disabled or plugin is
	 *                                         		activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();

					restore_current_blog();
				}

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

					restore_current_blog();

				}

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;
		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs WHERE archived = '0' AND spam = '0' AND deleted = '0'";
		return $wpdb->get_col( $sql );

	}


	/*------------------------------------
	 *	Actual plugin functionality starts here. 
	 * -----------------------------------/


	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {
		// @TODO: Define activation functionality here
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		// @TODO: Define deactivation functionality here
	}


	/**
	 * Populate our variable with all the events, 
	 * 
	 * This allows us to pass them to js with localize_script in a later action.
	 *
	 * @since  1.0.0
	 */
	public function populate_events() {
		$this->all_events = $this->get_events_json();
		//var_dump(get_the_title());
		//var_dump($this->all_events);

	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {
		$domain = $this->plugin_slug;

		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		$fullpath = dirname( basename( plugins_url() ) ) . '/' . basename(dirname(__FILE__))  . '/languages/';
	
		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, $fullpath );		
	
	}

	/**
	 * Register and enqueue the styles.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'assets/css/mico-calendar.css', __FILE__ ), array(), self::VERSION );
		wp_enqueue_style( 'jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css' );
	}

	/**
	 * Register and enqueues the JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'assets/js/main.min.js', __FILE__ ), array( 'jquery', 'jquery-ui-datepicker' ), self::VERSION, true );

		// send the start of week option to js
		wp_localize_script( $this->plugin_slug . '-plugin-script', 'wp_start_of_week', get_option('start_of_week') );

		// send the language code option to js.
		wp_localize_script( $this->plugin_slug . '-plugin-script', 'wp_language', get_bloginfo('language') );

		// Pass all the events as json (string) to the main script via the variable wp_events
		wp_localize_script( $this->plugin_slug . '-plugin-script', 'wp_events', $this->all_events);

	}

	/**
	 * Register and enqueues files related to FullCalendar.
	 *
	 * @since 		1.0.0
	 * @link 		FullCalendar, http://arshaw.com/fullcalendar/
	 */
	public function enqueue_fullcalendar_scripts() {
		wp_enqueue_script( 'moment', plugins_url('assets/fullcalendar/lib/moment.min.js', __FILE__), array('jquery'), '1.0.0', true );
		wp_enqueue_script( 'fullcalendar-editable', plugins_url('assets/fullcalendar/lib/jquery-ui.custom.min.js', __FILE__), array('jquery', 'moment'), '1.0.0', true );
		wp_enqueue_script( 'fullcalendar-script', plugins_url('assets/fullcalendar/fullcalendar.min.js', __FILE__), array('jquery', 'moment'), '1.0.0', true );
		wp_enqueue_script( 'fullcalendar-lang-script', plugins_url('assets/fullcalendar/lang-all.js', __FILE__), array('fullcalendar-script'), self::VERSION, true );
	}
	
	/**
	 * Register the plugins post types
	 *
	 * @since  1.0.0
	 */
	public function register_post_type() {

		if ( !post_type_exists( 'event' ) ) :
	
			//get show_in_admin option
			$show_dates_in_admin = get_option($this->plugin_db_prefix . '_show_dates_in_admin') == 1 ? 'mico-calendar' : false;

			$labels = array(
				'name'               => _x( 'Dates', 'post type general name', $this->plugin_slug ),
				'singular_name'      => _x( 'Date', 'post type singular name', $this->plugin_slug ),
				'menu_name'          => _x( 'Dates', 'admin menu', $this->plugin_slug ),
				'name_admin_bar'     => _x( 'Dates', 'add new on admin bar', $this->plugin_slug ),
				'add_new'            => _x( 'Add New', 'date', $this->plugin_slug ),
				'add_new_item'       => __( 'Add New Date', $this->plugin_slug ),
				'new_item'           => __( 'New Date', $this->plugin_slug ),
				'edit_item'          => __( 'Edit Date', $this->plugin_slug ),
				'view_item'          => __( 'View Date', $this->plugin_slug ),
				'all_items'          => __( 'All Dates', $this->plugin_slug ),
				'search_items'       => __( 'Search Dates', $this->plugin_slug ),
				'parent_item_colon'  => __( 'Parent Date:', $this->plugin_slug ),
				'not_found'          => __( 'No dates found.', $this->plugin_slug ),
				'not_found_in_trash' => __( 'No dates found in trash.', $this->plugin_slug )		
			);
			$args = array(
				'labels'             => $labels,
				'public'             => false,
				'publicly_queryable' => false,
				'exclude_from_search'=> true,
				'show_ui'            => true,
				'show_in_menu'       => $show_dates_in_admin,
				'query_var'          => false,
				'rewrite'            => array( 'slug' => _x( 'date', 'URL slug', $this->plugin_slug ) ),
				'capability_type'    => 'post',
				'has_archive'        => false,
				'hierarchical'       => false,
				'menu_position'      => null,
				'supports'           => array('title'),
				//'menu_icon'          => 'dashicons-calendar'
			);
			register_post_type( 'event', $args );

		endif;

		if ( !post_type_exists( 'event_entry' ) ) :

			//get show_in_admin option
			$show_in_admin = get_option($this->plugin_db_prefix . '_show_events_in_admin') == 1 ? 'mico-calendar' : false;

			$labels = array(
				'name'               => _x( 'Events', 'post type general name', $this->plugin_slug ),
				'singular_name'      => _x( 'Event', 'post type singular name', $this->plugin_slug ),
				'menu_name'          => _x( 'Events', 'admin menu', $this->plugin_slug ),
				'name_admin_bar'     => _x( 'Event', 'add new on admin bar', $this->plugin_slug ),
				'add_new'            => _x( 'Add New', 'event', $this->plugin_slug ),
				'add_new_item'       => __( 'Add New Event', $this->plugin_slug ),
				'new_item'           => __( 'New Event', $this->plugin_slug ),
				'edit_item'          => __( 'Edit Event', $this->plugin_slug ),
				'view_item'          => __( 'View Event', $this->plugin_slug ),
				'all_items'          => __( 'All Events', $this->plugin_slug ),
				'search_items'       => __( 'Search Events', $this->plugin_slug ),
				'parent_item_colon'  => __( 'Parent Event:', $this->plugin_slug ),
				'not_found'          => __( 'No events found.', $this->plugin_slug ),
				'not_found_in_trash' => __( 'No events found in trash.', $this->plugin_slug )		
			);
			$args = array(
				'labels'             => $labels,
				'public'             => true,
				'publicly_queryable' => true,
				'exclude_from_search'=> false,
				'show_ui'            => true,
				'show_in_menu'       => $show_in_admin,
				'query_var'          => true,
				'rewrite'            => array( 'slug' => _x( 'calendar', 'URL slug', $this->plugin_slug ) ),
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => null,
				'supports'           => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'revisions'),
				//'menu_icon'          => 'dashicons-calendar'
			);
			register_post_type( 'event_entry', $args );

		endif;

	}

	/**
	 * Manually add a 'view event' link to the toolbar
	 *
	 * Wordpress doesnt add this when post type is placed as a submenu for some reason.
	 * @param [type] $wp_admin_bar [description]
	 */
	public function add_view_link_to_toolbar( $wp_admin_bar ){
		
		if( is_admin() ) {
			$screen = get_current_screen();

			if( $screen->post_type == 'event_entry' && $screen->base == 'post') {
			
				$post_type_object = get_post_type_object( 'event_entry' );

				$args = array(
					'id' => 'view',
					'title' => $post_type_object->labels->view_item,
					'parent' => false,
					'href' => get_the_permalink(),

			 		);
				$wp_admin_bar->add_node( $args );

			}
		} elseif(is_single() && get_post_type() == 'event_entry') {

			$post_type_object = get_post_type_object( 'event_entry' );

				$args = array(
					'id' => 'edit',
					'title' => $post_type_object->labels->edit_item,
					'parent' => false,
					'href' => get_edit_post_link(),

			 		);
				$wp_admin_bar->add_node( $args );

		}
	}


	/**
	 * 
	 */

	public function hide_date_filter() {
		$screen = get_current_screen();
		if( $screen->post_type == 'event' ) {
			add_filter('months_dropdown_results', '__return_empty_array');
		}
	}

	/**
	 * Add meta box to the event post type
	 *
	 * @since  	1.0.0
	 */
	public function add_event_meta_box() {
		add_meta_box(
			//$id, HTML id-attribute
			'event',
			//$title
			__( 'Event info', $this->plugin_slug ),
			//$callback
			array( $this, 'display_event_meta_box' ),
			//$post_type
			'event',
			//$context
			'normal',
			//$priority
			'default',
			//$callback_args, adds additional arsg to $post, which is passed by default.
			null
		);
	}

	/**
	 * Render the event meta box.
	 *
	 * @since 		1.0.0
	 * @param 		int 	$post_id 	The object for the current post/page.
	 */
	public function display_event_meta_box($post) {
		// Add a nonce field so we can check for it later.
		wp_nonce_field( $this->plugin_db_prefix . '_eventinfo_meta_box', $this->plugin_db_prefix . '_eventinfo_meta_box_nonce' );
		include_once( 'views/meta-box-event.php' );
	}


	/**
	 * Save the data from the event meta box
	 * @since 		1.0.0
	 * @param 		int 	$post_id 	The ID of the post being saved.
	 */
	public function save_event_meta_box_data($post_id) {

		// Checks save status
	    $is_autosave = wp_is_post_autosave( $post_id );
	    $is_revision = wp_is_post_revision( $post_id );
	    $is_valid_nonce = ( isset( $_POST[ $this->plugin_db_prefix . '_eventinfo_meta_box_nonce' ] ) && wp_verify_nonce( $_POST[$this->plugin_db_prefix. '_eventinfo_meta_box_nonce'], $this->plugin_db_prefix . '_eventinfo_meta_box' ) ) ? 'true' : 'false';
	 	$is_correct_permission = current_user_can( 'edit_posts', $post_id ) ? 'true' : 'false';
    
	    // Exits script depending on save status
	    if ( $is_autosave || $is_revision || !$is_valid_nonce || !$is_correct_permission ) {
	        return;
	    }
	
		/* OK, it's safe for us to save the data now. */
	
		// all day.
		$all_day = isset($_POST[ $this->plugin_db_prefix. '_all_day']) ? 1 : 0;
		update_post_meta( $post_id, $this->plugin_db_prefix . '_all_day', $all_day );

		// Start timestamp.
		if ( isset( $_POST[ $this->plugin_db_prefix . '_start_date'] ) ) {
			$start = build_timestamp($_POST[ $this->plugin_db_prefix . '_start_date'], $_POST[ $this->plugin_db_prefix . '_start_hh'], $_POST[$this->plugin_db_prefix . '_start_mm']);
			if ($start) {
				update_post_meta( $post_id, $this->plugin_db_prefix . '_start', $start);
			}
			
		}

		// End timestamp
		if ( isset( $_POST[$this->plugin_db_prefix . '_end_date'] ) ) {
			$end = build_timestamp($_POST[ $this->plugin_db_prefix . '_end_date'], $_POST[ $this->plugin_db_prefix . '_end_hh'], $_POST[ $this->plugin_db_prefix . '_end_mm']);
			// make sure that the end point is after the start point.
			if($end && $start <= $end) {
				// We're fine. update end point
				update_post_meta( $post_id, $this->plugin_db_prefix . '_end', $end);
			} else {
				// Make end = start
				update_post_meta( $post_id, $this->plugin_db_prefix . '_end', $start);
			}
		}

		// Check if related postid is set
		if ( isset( $_POST[$this->plugin_db_prefix . '_attached_post_id'] ) ) {
			$related_post_id = $_POST[$this->plugin_db_prefix . '_attached_post_id'];
			$find_post = get_post( $related_post_id );
			// check that the post exists.
			if (!is_null($find_post)) {
				//exists, update post meta.
				$related_post_type = get_post_type($related_post_id);
				update_post_meta( $post_id, $this->plugin_db_prefix . '_related_post_id', sanitize_text_field($related_post_id));
				update_post_meta( $post_id, $this->plugin_db_prefix . '_related_post_type', sanitize_text_field($related_post_type));

				//update events based data for this post
				$this->update_related_post_event_data($related_post_id);

			} else {
				// set it to NULL (no relation)
				update_post_meta( $post_id, $this->plugin_db_prefix . '_related_post_id', NULL);			
			}
		
		}


	} // END save_event_meta_box_data()



	/**
	 * Add a list of related events to the post types chosen via the settings page. 
	 *
	 * @since  1.0.0
	 */
	
	public function add_eventslist_meta_box() {
		
		$post_types = get_option($this->plugin_db_prefix . '_post_type_support');
		
		if(is_array($post_types)) {
			$post_types[] = 'event_entry';
		} else {
			$post_types = array('event_entry');
		}
		if($post_types) :
			foreach ( $post_types as $post_type ) {
				add_meta_box(
					//$id, HTML id-attribute
					$this->plugin_slug . '-eventslist',
					//$title
					__( 'Calendar', $this->plugin_slug ),
					//$callback
					array($this, 'display_eventslist_meta_box'),
					//$post_type
					$post_type,
					//$context
					'normal',
					//$priority
					'default',
					//$callback_args, adds additional arsg to $post, which is passed by default.
					null
				);
				add_filter( 'postbox_classes_'.$post_type.'_'.$this->plugin_slug . '-eventslist', array($this, 'add_eventslist_class') );
			}
		endif;
	}

	/**
	 * Add a class to the eventslist meta box
	 */

	public function add_eventslist_class( $classes = array() ) {
        $add_classes = array( 'seamless_meta_box' );
        foreach ( $add_classes as $class ) {
                if ( !in_array( $class, $classes ) ) {
                        $classes[] = sanitize_html_class( $class );
                }
        } // End of foreach loop
	    return $classes;
	}

	/**
	 * Render the eventslist meta box.
	 *
	 * @since  	1.0.0
	 * @param  	object 	$post 	The object for the current post/page.
	 */

	public function display_eventslist_meta_box($post) {
		// Add a nonce field so we can check for it later.
		wp_nonce_field( $this->plugin_db_prefix . '_eventlist_meta_box', $this->plugin_db_prefix . '_eventlist_meta_box_nonce' );
		include_once( 'views/meta-box-eventslist.php' );
	}


	/**
	 * Save data from the eventslist meta box
	 *
	 * @since  1.0.0
	 */

	public function save_eventslist_meta_box_data($post_id) {
		// Checks save status
	    $is_autosave = wp_is_post_autosave( $post_id );
	    $is_revision = wp_is_post_revision( $post_id );
	    $is_valid_nonce = ( isset( $_POST[ $this->plugin_db_prefix . '_eventslist_meta_box_nonce' ] ) && wp_verify_nonce( $_POST[$this->plugin_db_prefix. '_eventslist_meta_box_nonce'], $this->plugin_db_prefix . '_eventslist_meta_box' ) ) ? 'true' : 'false';
	 	$is_correct_permission = current_user_can( 'edit_posts', $post_id ) ? 'true' : 'false';
    
	    // Exits script depending on save status
	    if ( $is_autosave || $is_revision || !$is_valid_nonce || !$is_correct_permission ) {
	        return;
	    }
	
		/* OK, it's safe for us to save the data now. */

		$this->update_related_post_event_data($post_id);
		

	}

	/**
	 * Updates the related post, to have a start and end date in sync with its related events. 
	 *
	 * This should be run every time an event is saved or the related item is saved.
	 *
	 * @since  1.0.0
	 */

	public function update_related_post_event_data($post_id) {

		$args = array(
			//Type & Status Parameters
			'post_type'   => 'event',
			'post_status' => 'any',
			'meta_key'	  => 'mcal_start',
			'orderby' 	  => 'meta_value',
			'order' 	  => 'asc',
			'meta_query'  => array(
								array(
									'key' => 'mcal_related_post_id',
									'value' => $post_id,
									'compare' => 'LIKE'
								),
			)
		);
		$query = new WP_Query( $args );
		
		// if there are any events added to this post
		if ($query->have_posts()) {

			//first event id
			$first_event_id = $query->posts[0]->ID;

			$last_event = end($query->posts);
			$last_event_id = $last_event->ID;

			//update start meta
			$first_event_start = get_post_meta( $first_event_id, 'mcal_start', true );
			update_post_meta( $post_id, $this->plugin_db_prefix . '_start', $first_event_start);
			
			//update end meta
			$last_event_end = get_post_meta( $last_event_id, 'mcal_end', true );
			update_post_meta( $post_id, $this->plugin_db_prefix . '_end', $last_event_end );

		} else {
			delete_post_meta($post_id, $this->plugin_db_prefix . '_end');
			delete_post_meta($post_id, $this->plugin_db_prefix . '_start');
		}


	}




	/**
	 * Register the administration menus for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_settings_menu() {
		/*
		 * Add a settings page for this plugin to the Settings menu.
		 *
		 */
		$this->plugin_screen_hook_suffix = add_options_page( 
			//$page_title
			__('Mico Calendar Settings', $this->plugin_slug),
			//$menu_title
			__('Mico Calendar', $this->plugin_slug),
			//$capability
			'manage_options',
			//$menu_slug
			$this->plugin_slug. '-settings',
			//$callback
			array( $this, 'display_plugin_admin_page' )
		);
		
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		include_once( 'views/settings.php' );
	}


	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    	1.0.0
	 * @param  		array 	$links 		an array of links to desplay on the plugin page
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '-settings' . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}

	/**
	 * Add the plugin settings sections and fields. 
	 * NOTE: as long as we only have one section, we dont need a title and description for the section.
	 *
	 * @since  1.0.0
	 */
	
	public function add_plugin_settings() {
		
		// First, we register a section. This is necessary since all future options must belong to one.
	    add_settings_section(
	        // ID used to identify this section and with which to register options
	        $this->plugin_slug . '-settings',
			// Title to be displayed on the administration page. we dont need this right now
	       	null,
	        // Callback used to render the description of the section. we dont need this right now
	        null,
	        // Page on which to add this section of options
	        $this->plugin_slug . '-settings'
	    );

	    // Add the post type checkbox fields.
		add_settings_field( 
		    // ID used to identify the field throughout the plugin
			$this->plugin_db_prefix . '_post_type_support',
		    // The label to the left of the option interface element
		    'Post Types',
		    // The name of the function responsible for rendering the option interface
		    array($this, 'display_post_type_support_field'),
		    // The page on which this option will be displayed
		    $this->plugin_slug . '-settings',
		    // The name of the section to which this field belongs
		    $this->plugin_slug . '-settings',
		    // The array of arguments to pass to the callback. In this case, just a description.
		    array('')
		);
		 
		// show events field
		add_settings_field( 
		    // ID used to identify the field throughout the plugin
			$this->plugin_db_prefix . '_show_events_in_admin',
		    // The label to the left of the option interface element
		    __('Show events admin menu', 'mico-calendar'),
		    // The name of the function responsible for rendering the option interface
		    array($this, 'display_show_events_in_admin_field'),
		    // The page on which this option will be displayed
		    $this->plugin_slug . '-settings',
		    // The name of the section to which this field belongs
		    $this->plugin_slug . '-settings',
		    // The array of arguments to pass to the callback. In this case, just a description.
		    array('')
		);

		// show events field
		add_settings_field( 
		    // ID used to identify the field throughout the plugin
			$this->plugin_db_prefix . '_show_dates_in_admin',
		    // The label to the left of the option interface element
		    __('Show dates admin menu', 'mico-calendar'),
		    // The name of the function responsible for rendering the option interface
		    array($this, 'display_show_dates_in_admin_field'),
		    // The page on which this option will be displayed
		    $this->plugin_slug . '-settings',
		    // The name of the section to which this field belongs
		    $this->plugin_slug . '-settings',
		    // The array of arguments to pass to the callback. In this case, just a description.
		    array('')
		);



		// Force year in get_date_range()
		add_settings_field( 
		    // ID used to identify the field throughout the plugin
			$this->plugin_db_prefix . '_force_year',
		    // The label to the left of the option interface element
		    __('Force year in date range', 'mico-calendar'),
		    // The name of the function responsible for rendering the option interface
		    array($this, 'display_force_year_field'),
		    // The page on which this option will be displayed
		    $this->plugin_slug . '-settings',
		    // The name of the section to which this field belongs
		    $this->plugin_slug . '-settings',
		    // The array of arguments to pass to the callback. In this case, just a description.
		    array('')
		);

		// Finally, we register the fields with WordPress
		register_setting(
		    //group name. security. Must match the settingsfield() on form page
		    $this->plugin_db_prefix . '_mico_calendar',
		    //name of field
		    $this->plugin_db_prefix . '_post_type_support'
		);
		register_setting(
		    //group name. security. Must match the settingsfield() on form page
		    $this->plugin_db_prefix . '_mico_calendar',
		    //name of field
		    $this->plugin_db_prefix . '_show_events_in_admin'
		);
		register_setting(
		    //group name. security. Must match the settingsfield() on form page
		    $this->plugin_db_prefix . '_mico_calendar',
		    //name of field
		    $this->plugin_db_prefix . '_show_dates_in_admin'
		);
		register_setting(
		    //group name. security. Must match the settingsfield() on form page
		    $this->plugin_db_prefix . '_mico_calendar',
		    //name of field
		    $this->plugin_db_prefix . '_force_year'
		);

	}

	/**
	 * Render the post_type_support field
	 *
	 * @since    1.0.0
	 * @param    $args 		Optional arguments passed by the add_settings_field function.
	 */
	public function display_post_type_support_field($args) {
		include_once( 'views/field-post-type-support.php' );
	}

	/**
	 * Render the show_events_in_admin field
	 *
	 * @since    1.0.0
	 * @param    $args 		Optional arguments passed by the add_settings_field function.
	 */
	public function display_show_events_in_admin_field($args) {
		include_once( 'views/field-show-events-in-admin.php' );
	}

	/**
	 * Render the show_dates_in_admin field
	 *
	 * @since    1.0.0
	 * @param    $args 		Optional arguments passed by the add_settings_field function.
	 */
	public function display_show_dates_in_admin_field($args) {
		include_once( 'views/field-show-dates-in-admin.php' );
	}

	/**
	 * Render the force_year field
	 *
	 * @since    1.0.0
	 * @param    $args 		Optional arguments passed by the add_settings_field function.
	 */
	public function display_force_year_field($args) {
		include_once( 'views/field-force-year.php' );
	}


	/**
	 * Add the plugin toplevel menu
	 *
	 * @since    1.0.0
	 */
	
	public function add_plugin_toplevel_menu() {
		add_menu_page( 
	    	//$page_title
	    	_x('Calendar', 'admin page title', $this->plugin_slug),
	    	//$menu_title
	    	_x('Calendar', 'admin menu title', $this->plugin_slug),
	    	//$capability
	    	'edit_posts',
	    	//$menu_slug
	    	$this->plugin_slug,
	    	//$function
	    	array($this, 'display_plugin_calendar_view'),
	    	//$icon_url
	    	'dashicons-calendar',
	    	//position
	    	6 
    	);

	}
	/**
	 * Display calendar view
	 * @return [type] [description]
	 */
	function display_plugin_calendar_view() {
		include_once( 'views/calendar-view.php' );
	}

	/**
	 * Add a fake "new event" menu under our plugin menu
	 * 
	 * The reason why this is "fake" is because its actually just linking to the page, 
	 * rather then being the normal page, registred with the post type.
	 *
	 * @since  1.0.0 
	 */
	
	public function add_plugin_new_event_submenu() {
		add_submenu_page( 
			//$parent_slug
	        $this->plugin_slug,
	        //$page_title
	        __( 'Add New Event', $this->plugin_slug ),
	        //$menu_title
	        _x( 'Add New', 'event', $this->plugin_slug ),
	        //$capability
	        'edit_posts',
	        //menu_slug
	        'post-new.php?post_type=event',
	        //$function -leave this empty, as this is just a link
	        ''
	    );
	}

	/**
	 * Fix closed admin menu, when editing single post.
	 *
	 * For some reaason the wordpress css classes for single posts doesnt work when we place
	 * the post type related pages under another toplevel menu. This fixes that.
	 *
	 * @since  1.0.0
	 */
	
	public function fix_plugin_menu() {

		global $menu, $parent_file, $self;

		if($parent_file == 'edit.php?post_type=event') {
			foreach( $menu as $key => $item ) {
				if( $item[2] == $this->plugin_slug ) {
					$menu[$key][4] .= " wp-has-current-submenu wp-menu-open";
				}
			}	
		}

	}

	/**
	 * Update single event.
	 *
	 * This happens when an event is drag&dropped or resized in the calendar view.
	 *
	 * @since  1.0.0
	 */
	
	public function update_event_callback() {
		if ( isset($_GET['id']) && isset($_GET['start']) && isset($_GET['end']) ) {
			//update event
			update_post_meta($_GET['id'], $this->plugin_db_prefix . '_start', $_GET['start']);
			update_post_meta($_GET['id'], $this->plugin_db_prefix  . '_end', $_GET['end']);
		}
		$related_id = get_related_id($_GET['id']);
		$this->update_related_post_event_data($related_id);
		die();
	}

	/**
	 * Load all events as JSON
	 *
	 * This callback is recieving $_GET['start'] and $_GET['end'] from javascript
	 * 
	 * @since  1.0.0
	 */
	public function events_json_callback() {

		//Setup WP_Query to fetch all events within the called ranged 
		//($_GET['start'] and $_GET['end'])
		$args = array(
			'post_type' => 'event',
			'post_status' => 'any',
			'posts_per_page' => -1,
			
			// this is commented out because we get better performance from returning all events in one go.
			// performance might prove this to be a problem, so we leave this piece of commented out code, as a possible fix for later performance problems. 
			// 'meta_query' => array(
			// 		array(
			// 			'key' => $this->plugin_db_prefix . '_end',
			// 			'value' => $_GET['start'],
			// 			'compare' => '>='
			// 		),
			// 		array(
			// 			'key' => $this->plugin_db_prefix . '_start',
			// 			'value' => $_GET['end'],
			// 			'compare' => '<='
			// 		)
			// 	),
		
		);
		$query = new WP_Query( $args );

		//create an empty array to populate
		$events_json = array();

		//populate json array
		if ( $query->have_posts() ): while ( $query->have_posts() ) : $query->the_post();
				
				$related_post_id = get_mcal_related_id();
				$related_post_type = get_post_type($related_post_id);
				

				/**
				 * Make the end date of "all-day" events inclusive.
				 * FullCalendar behaves according to google cal and iCalendar standards: excluding the end date, when all-day is set.
				 * This seems wierd to our usecase., so we set the end date to the day after, in order to get the correct display in our calendar view.
				 * Notice, however, that we dont change that actual data stored i db, as we might want to change this behavior later.
				 */
				
				if(is_all_day()) {
					$end_date = date('Y-m-d H:i:s', strtotime(get_mcal_end_date('Y-m-d H:i')  . ' + 1 day'));
				} else {
					$end_date = get_mcal_end_date('Y-m-d H:i');
				}

				$events_json[] = array(
					'id' => get_the_id(),
					'title' => get_the_title(),
					'start' => get_mcal_start_date('Y-m-d H:i'),
					'end' => $end_date,
					'url' => get_edit_post_link(get_the_id(), ''),
					'allDay' => is_all_day(),
					'className' => array('post-type-'.$related_post_type, 'mcal-event', 'event-'.get_the_id() ),
				);
			
		endwhile; endif;

		//echo the JSON-formatted array to js. 
		echo json_encode($events_json);

		//always die after wordpress based ajax functions. 
		die();

	}

	/**
	 * Retrieve all the events as json. 
	 * @return [type] [description]
	 */
	public function get_events_json() {
		$args = array(
			'post_type' => 'event',
			'post_status' => 'publish',
			'posts_per_page' => -1,
		);
		$query = new WP_Query( $args );

		//create an empty array to populate
		$events_json = array();

		//populate json array
		if ( $query->have_posts() ): while ( $query->have_posts() ) : $query->the_post();
				
				$related_post_id = get_related_id();
				$related_post_type = get_post_type($related_post_id);
				

				/**
				 * Make the end date of "all-day" events inclusive.
				 * FullCalendar behaves according to google cal and iCalendar standards: excluding the end date, when all-day is set.
				 * This seems wierd to our usecase., so we set the end date to the day after, in order to get the correct display in our calendar view.
				 * Notice, however, that we dont change that actual data stored i db, as we might want to change this behavior later.
				 */
				
				if(is_all_day()) {
					$end_date = date('Y-m-d H:i:s', strtotime(get_end_date('Y-m-d H:i')  . ' + 1 day'));
				} else {
					$end_date = get_end_date('Y-m-d H:i');
				}
				$related = get_post($related_post_id);
				$events_json[] = array(
					'id' => get_the_id(),
					'title' => $related->post_title,
					'start' => get_start_date('Y-m-d H:i'),
					'end' => $end_date,
					'url' => get_edit_post_link($related_post_id, ''),
					'allDay' => is_all_day(),
					'className' => array('post-type-'.$related_post_type, 'mcal-event', 'event-'.get_the_id() ),
				);
			
		endwhile; endif;
		
		//echo the JSON-formatted array to js.
		
		return json_encode($events_json);
	}

	/**
	 * Insert a new event via ajax
	 *
	 * @since  1.0.0
	 */
	public function insert_event_callback() {
		
		//get the event data from js. as php POST array
		$new_event_data = $_POST['event_data'];

		$id = isset($_POST['event_data']['event_id']) ? $_POST['event_data']['event_id'] : '';

		// we have to check for the string 'true', as this is what js passes to us.
		if( isset($_POST['event_data']['all_day']) && $_POST['event_data']['all_day'] == 'true') {
			$all_day = 1;
		} else {
			$all_day = 0;
		}

		if ( isset( $_POST['event_data']['start_date'] ) ) {
			$start = build_timestamp($_POST['event_data']['start_date'], $_POST['event_data']['start_hh'], $_POST['event_data']['start_mm']);
		} else {
			$start = '';
		}

		if ( isset( $_POST['event_data']['end_date'] ) ) {
			$end = build_timestamp($_POST['event_data']['end_date'], $_POST['event_data']['end_hh'], $_POST['event_data']['end_mm']);
			// make sure that the end point is after the start point.
			if($end && $start <= $end) {
				// We're fine. update end point
				$end = $end;
			} else {
				//set end to the same as start.
				$end = $start;
			}
		}

		if ( isset( $_POST['event_data']['attached_post_id'] ) ) {
			$related_post_id = $_POST['event_data']['attached_post_id'];
			$find_post = get_post( $related_post_id );
			// check that the post exists.
			if (!is_null($find_post)) {
				//exists, update post meta.
				$related_post_type = get_post_type($related_post_id);
				$relate_post_id = sanitize_text_field($related_post_id);

			} else {
				// set it to NULL (no relation)
				$relate_post_id = NULL;
			}
		
		}

		$title = get_the_title($relate_post_id);

		$today = new DateTime();

		// Insert a new post. IMPORTANT: ID must be blank in order to insert a new post, otherwise it will update a post with that id.
		$post = array(
			'ID' => $id,
			'post_title' => $title,
			'post_status' => 'publish',
			'post_type' => 'event',
			);

		$new_event_id = wp_insert_post( $post, true );

		//update meta fields for the new event post:

		update_post_meta( $new_event_id, $this->plugin_db_prefix . '_all_day', $all_day );
		update_post_meta( $new_event_id, $this->plugin_db_prefix . '_start', $start);
		update_post_meta( $new_event_id, $this->plugin_db_prefix . '_end', $end);

		update_post_meta( $new_event_id, $this->plugin_db_prefix . '_related_post_id', $related_post_id);
		update_post_meta( $new_event_id, $this->plugin_db_prefix . '_related_post_type', $related_post_type);

		//update events based data for this post
		if(!is_null($relate_post_id)) {
			$this->update_related_post_event_data($related_post_id);
		}

		//return html for js to insert
		global $post;
		$post = get_post($new_event_id);
		echo $this->display_eventslist_row();
		
		//always die on ajax events.
		die();
	}


	/**
	 * Delete a single event based on a passed id
	 *
	 * @since  1.0.0
	 * 
	 */
	public function delete_event_callback() {
		
		//first get the related id
		$related_id = get_related_id($_POST['event_id']);
		
		//delete the event. related id is not longer available via template tag.
		wp_delete_post( $_POST['event_id'] );
		
		//update the related posts daterange.
		$this->update_related_post_event_data($related_id);
		die();
	}

	/**
	 * Update the daterange displayed on the related post in admin
	 *
	 * @since  1.0.0
	 */
	public function update_related_date_range_callback() {
		$post_id = $_POST['id'];
		the_date_range($post_id);
		die();
	}

	/**
	 * Display a single row for an event in a table.
	 * 
	 * @since 1.0.0
	 */
	public function display_eventslist_row() {
		include 'views/meta-box-eventlist-row.php';
	}


	/**
	 * Display the form for a single event
	 *
	 * @since 1.0.0
	 */

	public function display_event_form() {
		include 'views/meta-box-eventlist-eventform.php';
	}

	/**
	 * Display the form buttons
	 *
	 * @since  1.0.0
	 */
	public function display_event_form_buttons() {
		include 'views/meta-box-eventform-buttons.php';
	}	

	/**
	 * Trash related events, when a post is trashed
	 *
	 * @since  1.0.0
	 */
	
	public function trash_related_events($pid) {
		$args = array(
			'post_type' => 'event',
			'post_status' => array('any','trash','auto-draft'),
			'posts_per_page' => -1,
		);
		$query = new WP_Query( $args );
		
		while ( $query->have_posts() ) : $query->the_post();
			if(get_post_meta( get_the_id(), 'mcal_related_post_id', true ) == $pid ) {
				wp_trash_post( get_the_id() );
			}
		endwhile;
	}

	/**
	 * Untrash related events, when post is restored from trash
	 */
	public function untrash_related_events($pid) {
		$args = array(
			'post_type' => 'event',
			'post_status' => 'trash',
			'posts_per_page' => -1,
		);
		$query = new WP_Query( $args );
		
		while ( $query->have_posts() ) : $query->the_post();
			if(get_post_meta( get_the_id(), 'mcal_related_post_id', true ) == $pid ) {
				wp_untrash_post( get_the_id() );
			}
		endwhile;
	}

	/**
	 * Delete related events when post is permanently deleted
	 *
	 * @since  1.0.0
	 */
	public function delete_related_events($pid) {
		$args = array(
			'post_type' => 'event',
			//notice that we shouldn't use 'any', when using array. 'any' does not include 'trash' and 'auto-draft'
			'post_status' => array('publish','trash','auto-draft', 'pending', 'draft', 'future', 'private', 'inherit'),
			'posts_per_page' => -1,
		);
		$query = new WP_Query( $args );
		
		while ( $query->have_posts() ) : $query->the_post();
			if(get_post_meta( get_the_id(), 'mcal_related_post_id', true ) == $pid ) {
				wp_delete_post( get_the_id() );
			}
		endwhile;
	}

} //END class MICO_Calendar