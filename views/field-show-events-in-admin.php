<?php
/**
 * Represents the view for field: show_events_in_admin
 *
 * @package 	MICO_Calendar
 * @author  	Malthe Milthers <malthe@milthers.dk>
 * @license 	@TODO [description]
 * @copyright 	2014 MICO
 * @link 		MICO, http://www.mico.dk
 */

 ?>
<?php 
//get the current option value from db. 
$current = get_option($this->plugin_db_prefix . '_show_events_in_admin');
 ?>
<p>
	<input type="checkbox" name="<?php echo $this->plugin_db_prefix . '_show_events_in_admin' ?>" value="1" <?php checked( 1, $current, true ); ?>/>
	<?php _e('This will add the "event_entry" post type as a submenu to the calendar. This is useful if you need a post type to add the events to.', 'mico-calendar') ?>
</p>
