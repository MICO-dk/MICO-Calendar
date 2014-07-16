	<table class="widefat eventlist-edit-table">
		<tr valign="top">
			<td scope="row" width="120">
				<span class="dashicons dashicons-admin-links"></span>
				<span><?php _ex('Attached to', 'title of post', 'mico-calendar'); ?></span>
			</td>
			
			<td>
				<strong><em><?php echo get_the_title( get_related_id() ); ?></em></strong>
				<input type="hidden" name="mcal_attached_post_id" value="<?php echo get_related_id(); ?>">
			</td>
		</tr>
		<tr valign="top">
			<td scope="row" >
				<span class="dashicons dashicons-clock"></span>
				<label for="mcal_all_day" class=""><?php _e('All day', 'mico-calendar'); ?></label>
			</td>
			<td>
				<input id="mcal_all_day" class="mcal_all_day" name="mcal_all_day" type="checkbox" <?php is_all_day(true); ?>>
			</td>
		</tr>
		<?php 
		/**
		 * Clear the post variable for the input fields, 
		 * to make sure that these are not prefilled when on the related post type page.
		 */
		if( get_post_type() != 'event') {
			global $post;
			$orig_post = $post;
			$post = '';
		}
		?>
		<tr valign="top">
			<td scope="row">
				<span class="dashicons dashicons-calendar"></span>
				<span class=""><?php _ex('From', 'date', 'mico-calendar'); ?> </span>
			</td>
			<td>
				<input type="text" class="js-datepicker" name="mcal_start_date" value="<?php echo get_start_date('d/m/Y'); ?>" placeholder="02/04/2015" size="10" maxlength="10" autocomplete="off">
				<span class="mcal_event_metabox_time_inputs  <?php if(is_all_day()) {echo '_is_hidden';}; ?>">
					@ <input type="number" min="1" max="24" name="mcal_start_hh" value="<?php echo get_start_date('H'); ?>" placeholder="19" autocomplete="off"> 
					: <input type="number" min="1" max="59" name="mcal_start_mm"value="<?php echo get_start_date('i'); ?>" placeholder="00" autocomplete="off">
				</span>
			</td>
		</tr>
		<tr valign="top">
			<td scope="row">
				<span class="dashicons dashicons-calendar"></span>
				<span class=""><?php _ex('To', 'date', 'mico-calendar');  ?> </span>
			</td>
			<td>
				<input type="text" value="<?php echo get_end_date('d/m/Y'); ?>" class="js-datepicker" name="mcal_end_date" placeholder="02/04/2015" size="10" maxlength="10" autocomplete="off">
				<span class="mcal_event_metabox_time_inputs <?php if(is_all_day()) {echo '_is_hidden';}; ?>">
					@ <input type="number" min="1" max="24" value="<?php echo get_end_date('H');  ?>" name="mcal_end_hh" placeholder="19" autocomplete="off">
					: <input type="number" min="1" max="59" value="<?php echo get_end_date('i');  ?>" name="mcal_end_mm" placeholder="00" autocomplete="off">
				</span>
			</td>
		</tr>
	</table>
	<?php 
		//restore the post variable.
		if( get_post_type() != 'event') {
			$post = $orig_post;
		}
		?>