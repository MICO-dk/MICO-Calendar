<div class="wrap">
	<h2>
		<?php _e('Calendar', 'mico-calendar'); ?>
		
		<?php $uses_event_entry = get_option($this->plugin_db_prefix . '_show_events_in_admin') == 1 ? true : false; ?>
		<?php if($uses_event_entry): ?>	
			<?php $event_entry = get_post_type_object('event_entry' ); ?>
			<a href="/wp-admin/post-new.php?post_type=event_entry" class="add-new-h2"><?php echo $event_entry->labels->add_new; ?></a>
		<?php endif; ?>
	</h2>
	<div id="calendar" class="mcal-calendar-view"></div>
</div> <!-- .wrap -->