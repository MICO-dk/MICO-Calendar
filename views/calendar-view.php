<div class="wrap">
	<h2>
		<?php _e('Calendar', 'mico-calendar'); ?>
		<?php $event_entry = get_post_type_object('event_entry' ); ?>
		<a href="/wp-admin/post-new.php?post_type=event_entry" class="add-new-h2"><?php echo $event_entry->labels->add_new; ?></a>
	</h2>
	<div id="calendar" class="mcal-calendar-view"></div>
</div> <!-- .wrap -->