#MICO Calendar

NOTE: this plugin is in 1.0.0 because its now beeing used in production for MICO's Clients. However - lots of features and bugs are still being worked on. Consider yourself warned :) !

The Mico Calendar plugin for WordPress is ment for developers who would like to include 
iCal-like functionality to the admin, while adding calendar functionality to any post type og choice

This plugin was made at MICO as a solution for theatre clients. 


##Features
* A dedicated post type for all events, easy to integrate with any other post types.
* General calendar functionality: "all day", "start date/time", "end date/time".
* iCal like interface for viewing events in a calendar window, with drag/drop and resize functionality.



## Template tags
These template tags are intended to feel as natural as any other wordpress template tags. Notice that this WILL conflict with other plugins using the same function names.


### is_all_day()
This function returns either a boolean value, or - if $chekit is set to true - echos the word checked, if the current event has all day checked.
```PHP
<?php is_all_day($checkit, $post) ?>
``

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


### the_date_range
This function echos the daterange as a string, intelligently formatting the strings. e.g. "3. September - 5. September 2014". Note that if both dates are in the same year, the year will be removed from the start date. This same behavior might be added to the month variable.

```PHP
<?php the_date_range($post) ?>
```
- **$post** (optional)   
*integer* - the id that specifies the post from which to check the all day value.


### get_related_id
This function returns the id of whatever post the event belongs to. If the related post doesnt exist it falls back to `get_the_id()` of current post.
```PHP
<?php get_related_id($post) ?>
```
- **$post** (optional)   
*integer* - the id that specifies the post from which to check the all day value.


### build_timestamp
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
When using this in a loop all the template tags should work fine. If you wanna loop through the events you need to loop through the post type 'event', with a normal WP_Query.



###Translations
The Mico Calendar includes a fully translationready .pot file.

So far it has been translated into:
* danish


##Todo
* sort out uninstall.php. Make sure it deletes options.
* Make an advanced selectbox for the events related id box.
* Make it possible to add events on the calendar view. and possibly move events around between posts.
* Make the 2nd datebox automatically change to the same as start date - if end date is lower than start. (js-side). See: https://forum.jquery.com/topic/two-datepickers-set-default-date-of-2nd-picker-question
