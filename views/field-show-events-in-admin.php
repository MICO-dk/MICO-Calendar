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
	<?php _e('This will add the "events" post type as a submenu to the calendar. This can be useful when debugging.', 'mico-calendar') ?>
</p>
