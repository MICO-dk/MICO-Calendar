<?php 
//move to different file
/**
 * The WordPress Query class.
 * @link http://codex.wordpress.org/Function_Reference/WP_Query
 *
 */

/**
 * store the original post in a variable.
 */

global $post;
$original_post = $post;

/**
 * Query all the events related to the current post. 
 *
 * @since 1.0.0
 */
$args = array(
	//Type & Status Parameters
	'post_type'   => 'event',
	'posts_per_page' => -1,
	'post_status' => 'any',
	'meta_key'	  => 'mcal_start',
	'orderby' 	  => 'meta_value',
	'order' 	  => 'asc',
	'meta_query'  => array(
						array(
							'key' => 'mcal_related_post_id',
							'value' => get_the_ID(),
							'compare' => '='
						),
	)
	

);

$eventquery = new WP_Query( $args );
?>

<div class="eventlist-container">

	
		<p class="eventlist-global-dates">
			<strong><?php _e('Daterange', 'mico-calendar'); ?>:</strong>
			<span class="global_dates"><?php the_date_range(); ?></span>
		</p>

		<table class="widefat eventlist-table">
			<thead>
				<tr>

					<th><?php _e('Date', 'mico-calendar'); ?></th>
					<th width="51"><?php //_e('Actions', 'mico-calendar'); ?></th>
				</tr>
			</thead>
			<tbody class="eventlist-tbody">
			<?php if( $eventquery->have_posts() ): ?>
				<?php while( $eventquery->have_posts() ): $eventquery->the_post(); ?>
		
					<?php $this->display_eventslist_row(); ?>
					

				<?php endwhile; ?>
			<?php else: ?>
			<tr class="eventlist-no-rows">
				<td>
					<em><?php _e('No events yet', 'mico-calendar'); ?>.</em>
				</td>
			</tr>
			<?php endif; ?>
			</tbody>
		</table>


	<?php 
	/**
	 * Reset the post variable. For some reason reset_postdata() didnt work?. 
	 * Maybe this is a problem when doing loops inside a metabox.
	 */
	//wp_reset_postdata();
	$post = $original_post; 
	?>
	
	<p><button class="button new-event-button"><?php _e('Add event', 'mico-calendar'); ?></button></p>
	<div class="new-event-container">
		<h4><?php _e('New event', 'mico-calendar'); ?>:</h4>
		<div class="eventlist-form-wrapper">
			<?php $this->display_event_form(); ?>
			<?php $this->display_event_form_buttons(); ?>
		</div>
		
	</div>

</div>
