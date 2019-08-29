#MICO Calendar

NOTE: this plugin is in 1.0.0 because its now beeing used in production for MICO's Clients. However - lots of features and bugs are still being worked on. Consider yourself warned :) !

The Mico Calendar plugin for WordPress is ment for developers who would like to include 
iCal-like functionality to the admin, while adding calendar functionality to any post type of choice


##Features
* A dedicated post type for all the dates called 'event' (we may change that to date later, but for now we keep backwards compatibility) thats easy to integrate with any other post types.
* An optional post type for all event_entries, that you can add dates (events) to.
* General calendar functionality: "all day", "start date/time", "end date/time".
* iCal like interface for viewing events in a calendar window, with drag/drop and resize functionality.


## Template tags
These template tags are intended to feel as natural as any other wordpress template tags. Notice that this WILL conflict with other plugins using the same function names.


### is_all_day()
This function returns either a boolean value, or - if $chekit is set to true - echos the word checked, if the current event has all day checked.
```PHP
<?php is_all_day($checkit, $post) ?>
```

- **$checkit** (optional)  
*boolean* - wether or not to output the word 'checked', when all day is checked.

- **$post** (optional)   
*integer* - the id that specifies the post from which to check the all day value.


### get_start_date()
This function returns the starts date of the current post. If the posttype is an event, it will return the start date of the single event. However, if the post is a related post type, it will return the start date of the first event added to it.
```PHP
<?php get_start_date($d, $post) ?>
```
- **$d** (optional)  
*string* - The requested output format, should be a PHP date format string. e.g. 'Y-m-d'. Defaults to the date setting in wordpress.

- **$post** (optional)   
*integer* - the id that specifies the post from which to check the all day value.

### get_end_date()
This function returns the end date of the current post. If the posttype is an event, it will return the end date of the single event. However, if the post is a related post type, it will return the end date of the last event added to it.
```PHP
<?php get_end_date($d, $post) ?>
```
- **$d** (optional)  
*string* - The requested output format, should be a PHP date format string. e.g. 'Y-m-d'. Defaults to the date setting in wordpress.

- **$post** (optional)   
*integer* - the id that specifies the post from which to check the all day value.


### the_date_range()
This function echos the daterange as a string, intelligently formatting the strings. e.g. "3. September - 5. September 2014". Note that if both dates are in the same year, the year will be removed from the start date. This same behavior might be added to the month variable.

```PHP
<?php the_date_range($post) ?>
```
- **$post** (optional)   
*integer* - the id that specifies the post from which to check the all day value.


### get_related_id()
This function returns the id of whatever post the event belongs to. If the related post doesnt exist it falls back to `get_the_id()` of current post.
```PHP
<?php get_related_id($post) ?>
```
- **$post** (optional)   
*integer* - the id that specifies the post from which to check the all day value.


### build_timestamp()
This function returns a full timestamp in the ISO format 'Y-m-d H:i', from broken pieces of date info (date, hour, minute).
```PHP
<?php build_timestamp($date, $hh, $mm) ?>
```
- **$date** (required)   
*string* - dateinput. MUST be formated as follows: "dd/mm/yyyy".

- **$hh** (required)
*string* - hour number as string. e.g. '04', '13', '19'.

- **$mm** (required)
*string* - minute number as string. e.g. '04', '13', '19'.


### Use in a LOOP
When using this in a loop all the template tags should work fine. If you want to loop through the events you need to loop through the post type 'event', with a normal WP_Query. At this point you might want to use a meta_query to sort the event or post by the start date, and only show future dates. The WP_Query could look something like this:
```PHP	
		$today = new DateTime();
		$args = array(
			'post_type' => 'your-post-type',
			'orderby' => 'meta_value',
			'meta_key' => ' mcal_start',
			'order' => 'ASC',
			'meta_query' => array(
					array(
						'key' => 'mcal_end',
						'value' => $today->format('Y-m-d H:i'),
						'compare' => '>='
					)
				),
		);
		$query = new WP_Query( $args );

		...theloop
```


### Adding custom fields
You can add your own fields to the events via a few actions. Note that these fields MUST be prefixed with "mcal_cf_" in order to work. Otherwise Ajax wont be able to recognize them for saving. See example below

```PHP
/**
 * Add markup for custom fields
 * Add this to functions.php or admin functions plugin
 */
add_action( 'mcal_meta_box_event_form', 'mytheme_add_mcal_field_markup', 10, 2 );
function mytheme_add_mcal_field_markup($event_id, $related_id) {
	
	$link = get_post_meta( get_the_id(), 'mcal_cf_link', true );

	$contact = get_post_meta( get_the_id(), 'mcal_cf_contact', true );
	
	$args = array(
		'posts_per_page'   => -1,
		'orderby'          => 'title',
		'order'            => 'ASC',
		'post_type'        => 'employee',
		'post_status'      => 'publish',
	);
	$posts_array = get_posts( $args );

	?>

	<tr valign="top">
		<td scope="row" >
			<span class="dashicons dashicons-admin-users"></span>
			<label for="mcal_cf_contact" class=""><?php _e('Main contact', 'mico-calendar'); ?></label>
		</td>
		<td>
			<select name="mcal_cf_contact" id="mcal_cf_contact">
				<option value="">Select option</option>
				<?php foreach ($posts_array as $post): ?>
					<option value="<?php echo $post->ID ?>" <?php selected( $contact, $post->ID, true ) ?>><?php echo $post->post_title ?></option>
				<?php endforeach ?>
			</select>
			
		</td>
	</tr>


	<tr valign="top">
		<td scope="row" >
			<span class="dashicons dashicons-admin-site"></span>
			<label for="mcal_cf_link" class=""><?php _e('Link', 'mico-calendar'); ?></label>
		</td>
		<td>
			<input id="mcal_cf_link" class="mcal_cf_link" name="mcal_cf_link" type="text" value="<?php echo $link; ?>">
		</td>
	</tr>

	<?php
}

```


```PHP
/**
 * Make sure custom fields are saved when editing the event post meta box
 * Add this to functions.php or admin functions plugin
 */
add_action( 'mcal_save_meta_box_data', 'mytheme_save_mcal_data', 10, 3 );
function mytheme_save_mcal_data($post_id, $prefix, $data) {

	if ( isset( $data['mcal_cf_link'] ) ) {
		update_post_meta( $post_id, 'mcal_cf_link', esc_url_raw( $data['mcal_cf_link']) );
	}

	if ( isset( $data['mcal_cf_contact'] ) ) {
		update_post_meta( $post_id, 'mcal_cf_contact', sanitize_text_field( $data['mcal_cf_contact'] ) );
	}

}
```

```PHP
/**
 * Make sure custom fields are saved when editing the event via widget in connected post
 * Add this to functions.php or admin functions plugin
 */
add_action( 'mcal_update_post_meta_on_insert', 'mytheme_update_post_meta_on_insert', 10, 2 );
function mytheme_update_post_meta_on_insert($new_event_id, $data) {

	if (isset($data['mcal_cf_link'])) {
		update_post_meta( $new_event_id, 'mcal_cf_link', esc_url_raw( $data['mcal_cf_link'] ) );
	}

	if (isset($data['mcal_cf_contact'])) {
		update_post_meta( $new_event_id, 'mcal_cf_contact', sanitize_text_field( $data['mcal_cf_contact'] ) );
	}
	
	/**
	 * NOTE ABOUT CHECKBOXES. Dont wrap the updtae func in an isset, since 
	 * checkboxes arent set when they havent been checked
	 */
	 // $checkbox = isset($data['my_checkbox'])? 1 : 0;
	 // update_post_meta( $new_event_id, 'my_checkbox', $checkbox);	
	
}
```


###Translations
The Mico Calendar includes a fully translationready .pot file.

So far it has been translated into:
* danish


##Todo
* sort out uninstall.php. Make sure it deletes options.
* Make an advanced selectbox for the events related id box.
* Make it possible to add events on the calendar view. and possibly move events around between posts.
* Make the 2nd datebox automatically change to the same as start date - if end date is lower than start. (js-side). See: https://forum.jquery.com/topic/two-datepickers-set-default-date-of-2nd-picker-question
