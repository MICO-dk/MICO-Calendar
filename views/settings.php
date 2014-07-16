<?php
/**
 * Represents the view for the administration settings page.
 *
 * @package 	MICO_Calendar
 * @author  	Malthe Milthers <malthe@milthers.dk>
 * @license 	@TODO [description]
 * @copyright 	2014 MICO
 * @link 		MICO, http://www.mico.dk
 */
?>
<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<form method="POST" action="options.php">
		<?php 
			//pass slug name of page, also referred to in Settings API as option group name
			settings_fields( $this->plugin_db_prefix . '_mico_calendar' );
			//pass slug name of page
			do_settings_sections( $this->plugin_slug . '-settings' );
			submit_button();
		?>
	</form>
</div> <!-- .wrap -->