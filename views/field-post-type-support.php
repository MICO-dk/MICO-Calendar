<?php
/**
 * Represents the view for field: post_type_support
 *
 * @package 	MICO_Calendar
 * @author  	Malthe Milthers <malthe@milthers.dk>
 * @license 	@TODO [description]
 * @copyright 	2014 MICO
 * @link 		MICO, http://www.mico.dk
 */

//get all the registered post types within WordPress
$post_types = get_post_types(array('public' => true), 'objects');

//remove attachments and our event post type from the array
unset($post_types['attachment']);
unset($post_types['event']);

//get the current option value from db. 
$current_options = get_option($this->plugin_db_prefix . '_post_type_support');

?>
<?php foreach ($post_types as $post_type) : ?>
	<?php  
		//check if post type has been checked already.
	    $current = is_array($current_options) ? in_array($post_type->name, $current_options) : false ;
	?>
	<p>
		<input type="checkbox" id="<?php echo $this->plugin_db_prefix . '_post_type_support_' . $post_type->name; ?>" name="<?php echo $this->plugin_db_prefix . '_post_type_support[]' ?>" value="<?php echo $post_type->name; ?>" <?php checked( true, $current, true ); ?>/>
		<label for="<?php echo $this->plugin_db_prefix . '_post_type_support_' . $post_type->name; ?>"><?php echo $post_type->labels->name;?></label>
	</p>

<?php endforeach ?>