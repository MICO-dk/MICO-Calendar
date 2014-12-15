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
$current = get_option($this->plugin_db_prefix . '_disable_event_archive');
 ?>
<p>
	<input type="checkbox" name="<?php echo $this->plugin_db_prefix . '_disable_event_archive' ?>" value="1" <?php checked( 1, $current, true ); ?>/>
	<?php _e('This is useful if you need a page with the same slug as the archive page.', 'mico-calendar') ?>
</p>
