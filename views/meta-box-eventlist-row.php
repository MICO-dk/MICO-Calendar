<tr class="eventlist-row event-data-row" data-id="<?php echo get_the_id(); ?>" data-date="<?php echo get_start_date('Y-m-d H:i'); ?>" >
	<td>
		<span class="dashicons dashicons-calendar"></span>
		<?php the_date_range(); ?>
		<?php if( !is_all_day() ) { echo ' @ ' . get_start_date('H:i'); }?>
	</td>
	<td class="event-actions">
		<span class="dashicons dashicons-edit event-button-edit"></span>
		<span>&nbsp;</span>
		<span class="dashicons dashicons-trash event-button-delete"></span>
	</td>
</tr>
<tr class="eventlist-row event-editing-row" >
	<td colspan="2">
		<h4><?php _e('Update event', 'mico-calendar'); ?>:</h4>
		<div class="eventlist-form-wrapper">
			<?php $this->display_event_form(); ?>
			<?php $this->display_event_form_buttons(); ?>
		</div>
	</td>
</tr>