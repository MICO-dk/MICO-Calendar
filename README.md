
#MICO Calendar

The Mcal plugin for WordPress is ment for developers who would like to include 
iCal type of functionality to the admin, while adding calendar functionality to any post type.

This plugin was made at MICO as a solution for theatre clients. 

##Features
* A dedicated post type for all events, easy to integrate with any other post types.
* General calendar functionality: "all day", "start date/time", "end date/time".
* iCal like interface for viewing events in a calendar window, with drag/drop and resize functionality.


##Documentation
All functionality is meant to work as an extention to the normal WordPress workflow


###Template tags

#### is_all_day($checkit = false, $post = 1)
Check if current post (event) has all_day checked. 
If no parameters are set, it will return boolean. 
If $checkit is set to true, it will echo the word "checked". This is useful, when used in a form.


### Use in a LOOP.


###Translation
Mcal includes a fully translationready .pot file. 

So far Mcal is translated into:
* danish


##Todo

* sort out uninstall.php. Make sure it deletes options.
* Make an advanced selectbox for the events related id box.
* Make it possible to add events on the calendar view. and possibly move events around between posts.
* Make the 2nd datebox automatically change to the same as start date - if end date is lower than start. (js-side). See: https://forum.jquery.com/topic/two-datepickers-set-default-date-of-2nd-picker-question
