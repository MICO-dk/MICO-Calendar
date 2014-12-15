<?php
/**
 *
 * @package 	MICO_Calendar
 * @author  	Malthe Milthers <malthe@milthers.dk>
 * @license 	@TODO [description]
 * @copyright 	2014 MICO
 * @link 		MICO, http://www.mico.dk
 *
 * @wordpress-plugin
 * Plugin Name: 	MICO Calendar
 * Plugin URI:		@TODO
 * Description: 	Creates a calendarview, and allows for all posttypes to be included in the calendar, by adding an event-metabox to their edit page.
 * Version: 		1.1.0
 * Author: 			Malthe Milthers
 * Author URI: 		http://www.malthemilthers.com
 * Text Domain: 	mico-calendar
 * License: 		@TODO
 * GitHub URI:		@TODO
 */
 

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


/*
 * Load the plugins class.
 */
require_once( plugin_dir_path( __FILE__ ) . 'class-mico-calendar.php' );



/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * 
 * When the plugin is deleted, the uninstall.php file is loaded. 
 * This is better than calling the hook, because it wont run any arbitrary code outside of functions
 */
register_activation_hook( __FILE__, array( 'MICO_Calendar', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MICO_Calendar', 'deactivate' ) );


/*
 * Run the one and only instance of the plugins main class.
 */
add_action( 'plugins_loaded', array( 'MICO_Calendar', 'get_instance' ) );



/** General Template tags **/


/**
 * Check if current post is all day
 *
 * This can be used as a boolean to find out if current event is all day. 
 * It can also be used in forms to add the word "checked" to display the correct value.
 * 
 * @return boolean or string depending on the value of $checkit
 */
function is_all_day($checkit = false, $post = NULL) {
	$post = get_post($post);

	//check if the post exists, return if it doesnt.
	if($post === NULL) {
		return;
	}

	// load the data from database
	$all_day = get_post_meta( $post->ID, 'mcal_all_day', true );
	
	//echo the string "checked" if checkit is set to true.
	if($all_day == 1 && $checkit) {
		echo 'checked';
	}

	// return boolean.
	if($all_day == 1 ) {
		return true;
	} else {
		return false;
	}
}



/**
 * Retrieve the start date of an mcal_event
 *
 * @since  1.0.0
 *
 * @return string Start date for the event
 * @var string
 */
function get_start_date($d = '', $post = NULL) {
	$post = get_post( $post );

	//check if the post exists
	if($post === NULL) {
		return;
	}

	$start_date = get_post_meta( $post->ID, 'mcal_start', true );

	//get_post_meta returns empty string, if theres nothing to return
	if($start_date == '') {
		return;
	}

	if ( $d == '' ) {
		$the_date = mysql2date( get_option( 'date_format' ), $start_date );
	} else {
		$the_date = mysql2date( $d, $start_date );
	}
	return $the_date;
}


/**
 * Retrieve the end date of an mcal_event
 *
 * @since  1.0.0
 *
 * @return string End date for the event
 * @var string
 */
function get_end_date($d = '', $post = NULL) {
	$post = get_post( $post );

	//check if the post exists
	if($post === NULL) {
		return;
	}

	//retrieve the start date meta:
	$end_date = get_post_meta( $post->ID, 'mcal_end', true );

	//get_post_meta returns empty string, if theres nothing to return
	if($end_date == '') {
		return;
	}

	if ( $d == '' ) {
		$the_date = mysql2date( get_option( 'date_format' ), $end_date );
	} else {
		$the_date = mysql2date( $d, $end_date );
	}
	return $the_date;
}

/**
 * Retrieve am intelligent datestring.
 *
 * This will echo "startdate - enddate" as a string, while intelligently 
 * removing the year from startdate if its the same year as the enddate, 
 * and also will only show one date if both dates are the same.
 *
 * @since  1.0.0
 *
 * @return  string "Startdate - Enddate" 
 */

function the_date_range($post = NULL) {
	$post = get_post( $post );

	//check if the post exists
	if($post === NULL) {
		return;
	}

	// Retrieve dates without time
	$start_date = get_start_date('Y-m-d', $post);
	$end_date = get_end_date('Y-m-d', $post);

	$start_date_year = get_start_date('Y', $post);
	$end_date_year = get_end_date('Y', $post);


	// If the events start and end date are the same, or if the end date is empty
	if ($start_date == $end_date or strlen($end_date) < 4 ) {
		// only return the start date.
		echo get_start_date('', $post);
		return;
	}

	if (get_option('mcal_force_year') != 1 && $start_date_year == $end_date_year) {
		// remove the year from the start date
		$start_format = str_replace(array(', Y', 'y', 'o', 'Y'), array('', '', '', ''), get_option( 'date_format' ) ) ;
	} else {
		$start_format = get_option( 'date_format');
	}
	
	echo get_start_date($start_format, $post) . ' - ' . get_end_date('', $post);
	return;
}



/**
 * Get the related id. 
 *
 * @return int the id of the related post. this is the post that the event is attached to. If it doesnt exist, it returns its own ID.
 */
function get_related_id($post = NULL) {
	$post = get_post( $post );
	//check if the post exists
	if($post === NULL) {
		return;
	}

	$related_id = get_post_meta( $post->ID, 'mcal_related_post_id', true );

	//check if the related id exists
	$related_post = get_post($related_id);

	if($related_post && $related_id != '') {
		return $related_id;
	} else {
		return get_the_id();
	}
}


/**
 * Build a timestamp from date hour and minute values, and format it as ISO. 
 *
 * @since  1.0.0
 */
function build_timestamp($date, $hh, $mm) {
	//check if formats is correct
	$date = sanitize_text_field($date);
	if ( !preg_match('@^\d{2}/\d{2}/\d{4}$@',$date) ){ 
		return false; 
	}
	if(strlen($hh) == 1) { $hh = '0' . $hh; }
	if(strlen($mm) == 1) { $mm = '0' . $mm; }

	$hh = preg_match('@^\d{2}$@', $hh ) ? sanitize_text_field($hh) : '00';
	$mm = preg_match('@^\d{2}$@', $mm ) ? sanitize_text_field($mm) : '00';

	//build start timestamp:
	$ts = $date . ' ' . $hh . ':' . $mm;
	$ts = DateTime::createFromFormat('d/m/Y H:i', $ts);
	$ts = $ts->format('Y-m-d H:i');

	return $ts;
}


/**
 * get All events for a related post (ie. event_entry)
 *
 * @since  1.1.0
 */

function get_event_query($post = NULL) {
	$post = get_post( $post );
	//check if the post exists
	if($post === NULL) {
		return;
	}
	$args = array(
		'post_type'   => 'event',
		'posts_per_page'         => -1,
		'orderby' => 'meta_value',
	    'meta_key' => ' mcal_start',
	    'order' => 'ASC',
	    'meta_query' => array(
	            array(
	                'key' => 'mcal_related_post_id',
	                'value' => $post->ID,
	                'compare' => '=='
	            ),
	        ),
	);
		
	$query = new WP_Query( $args );
	return $query;
}












/**************************** Might be useful later *************************/


/**
 * Retrives the current admin colorscheme as set in user settings.
 */
// add_action('admin_head', function(){
// 	function get_admin_colorscheme(){
// 		global $_wp_admin_css_colors;
// 		$scheme_name = get_user_meta(get_current_user_id(), 'admin_color', true);
// 		return $_wp_admin_css_colors[$scheme_name];
// 	}
// 	/**
// 	 * usage:
// 	 * $active_colorscheme = get_admin_colorscheme();
// 	 *
// 	 * note: must be called after admin_head
// 	 */
// });
