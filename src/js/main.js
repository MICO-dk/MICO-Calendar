jQuery(document).ready(function($) {

	/**
	 * Variables from wordpress:
	 * @variable wp_language string language code + '-' + country code. (ex: 'da-DK');
	 * @variable wp_start_of_week  int (0 or 1 depending on sunday or monday)
	 */
	wp_language_code = wp_language.split("-")[0];


	/**
	 * Add the datepicker UI to date fields
	 */
	/* Danish initialisation for the jQuery UI date picker plugin. */
	/* Written by Jan Christensen ( deletestuff@gmail.com). */

   
    
	function loadDatepicker() {
		$( ".js-datepicker" ).datepicker({ 
			dateFormat: "dd/mm/yy",
			firstDay: wp_start_of_week,
		});
	}
	loadDatepicker();
	//set language to danish
	$.datepicker.setDefaults($.datepicker.regional[wp_language_code]);



	// Hide time inputs, if all day is checked.
	
	$(document).on('change', '.mcal_all_day', function(){
		if( this.checked ) {
			$(this).closest('.eventlist-edit-table').find('.mcal_event_metabox_time_inputs').hide();
		} else {
			$(this).closest('.eventlist-edit-table').find('.mcal_event_metabox_time_inputs').show();
		}
	});



	//wrapping function so we dont have any globals hanging around
	;(function(){
		
		var EventHandler = {
			
			//the container element as jQuery object. 
			//This element must contain all handler nodes.
			eventlistContainer: $('.eventlist-container'),
			newEventContainer: $('.new-event-container'),


			// Handler class names
			eventRowClass: '.eventlist-row',
			eventRowDataClass: '.event-data-row',
			eventRowEditorClass: '.event-editing-row',
			
			formTableClass: '.eventlist-edit-table',
			formWrapperClass: '.eventlist-form-wrapper',


			buttonClassNew: '.new-event-button',
			buttonClassEdit: '.event-button-edit',
			buttonClassDelete: '.event-button-delete',
			buttonClassCancel: '.event-button-cancel',
			buttonClassSave: '.event-button-save',



			init: function() {

				/**
				 * Sets all the Event listners here and attach method(s) to them
				 */
				
				//store this object in the variable self, so that we can refer to it inside eventlisteners.
				var self = this;
				var event_id;
				this.eventlistContainer.on('click', this.buttonClassNew, function(e) {
					e.preventDefault();
					self.newEvent();
				});

				this.eventlistContainer.on('click', this.buttonClassEdit, function(e) {
					e.preventDefault();
					event_id = $(this).closest(self.eventRowClass).attr('data-id');
					self.editEvent(event_id);
				});	

				this.eventlistContainer.on('click', this.buttonClassDelete, function(e) {
					e.preventDefault();
					event_id = $(this).closest(self.eventRowClass).attr('data-id');
					self.deleteEvent(event_id);
				});

				this.eventlistContainer.on('click', this.buttonClassCancel, function(e) {
					e.preventDefault();
					self.cancelEvents();
				});

				this.eventlistContainer.on('click', this.buttonClassSave, function(e) {
					e.preventDefault();
					
					//get values from closest form
					var formWrapper = $(this).closest(self.formWrapperClass);

					// Check if this is a new post or if we're updating.
					if ( $(this).closest(self.newEventContainer).length ) {
						event_id = ''

					} else if( $(this).closest(self.eventRowEditorClass).length ) {
						event_id = $(this).closest(self.eventRowEditorClass).prev().attr('data-id');
					}
					
					eventObj = {
						'event_id': event_id,
						'all_day': formWrapper.find('input[name="mcal_all_day"]').is(':checked'),
						'attached_post_id': formWrapper.find('input[name="mcal_attached_post_id"]').val(),
						
						'start_date': formWrapper.find('input[name="mcal_start_date"]').val(),
						'start_hh': formWrapper.find('input[name="mcal_start_hh"]').val(),
						'start_mm': formWrapper.find('input[name="mcal_start_mm"]').val(),
						
						'end_date': formWrapper.find('input[name="mcal_end_date"]').val(),
						'end_hh': formWrapper.find('input[name="mcal_end_hh"]').val(),
						'end_mm': formWrapper.find('input[name="mcal_end_mm"]').val(),
					}

					self.saveEvent(eventObj);

					// clear the new event form: 
					// // note that theres a hidden field that should'nt be cleared.
					self.newEventContainer.find('input[type="checkbox"]').prop('checked', false);
					self.newEventContainer.find('.mcal_event_metabox_time_inputs').show();
					
					self.newEventContainer.find('input[type="text"]').val('');
					self.newEventContainer.find('input[type="number"]').val('');



				});

			},
			
			newEvent: function() {
		
				//close all event being edited.
				this.cancelEvents();

				//alert('you opened a new event');
				$(this.buttonClassNew).hide();
				this.newEventContainer.show();
			},

			editEvent: function(id) {
				//close all event being edited.
				this.cancelEvents();

				//alert('you edit: ' + id);
				eventElement = $(this.eventRowClass +'[data-id='+id+']');
				//eventElement.hide();
				
				eventElement.next().toggle();
				

			},
			

			cancelEvents: function() {
				//alert('you cancelled all events');
				$(this.buttonClassNew).show();
				this.newEventContainer.hide();
				$(this.eventRowDataClass).show();
				$(this.eventRowEditorClass).hide();
			},
			
			deleteEvent: function(id) {
				//alert('you deleted: ' + id);
				self = this;
				/**
				 * Delete a single event, calling a server side function.
				 */
				$.ajax({
		            url: ajaxurl,
		            type: "POST",
		            data: {
		                'action' : 'delete_event',
		                'event_id' : id,
		            },
		            beforeSend: function(jqXHR, textStatus) {
		            	$('[data-id='+ id +']').addClass('_has_transition');
		            	$('[data-id='+ id +']').addClass('_is_being_deleted');
		            },
		            success: function(data) {
						console.log(data);
						setTimeout(function() {
		            		$('[data-id='+ id +']').next('tr').remove();
		            		$('[data-id='+ id +']').remove();
		            	
		            		//Check if theres any dates left
		            		if ($('.event-data-row').length == 0) {

		            			no_rows = '<tr class="eventlist-no-rows">';
		            			no_rows += '<td><em>No events.</em></td>';
		            			no_rows += '</tr>';

		            			$('.eventlist-table').find('tbody').append(no_rows);
		            		};
		            	
		            	}, 200);
		            	
		            	
		            	
		            },
		            complete: function() {
		            	current_post_id = self.newEventContainer.find('input[name="mcal_attached_post_id"]').val();
						self.update_related_date_range(current_post_id);
		            },
		            error: function(jqXHR, textStatus, errorThrown) {
		            	$('[data-id='+ id +']').removeClass('_is_being_deleted');
		            	$('[data-id='+ id +']').removeClass('_has_transition');
		            }
		        }); //end $.ajax
			
			},
			
			saveEvent: function(eventObj) {
				//alert('you saved an event');
				self = this;
				$.ajax({
		            url: ajaxurl,
		            type: "POST",
		            data: {
		                'action' : 'insert_event',
		                'event_data' : eventObj,
		            },
		            beforeSend: function(jqXHR, textStatus) {
		            	$('.eventlist-form-wrapper').find('.spinner').show();
		            },
		            success: function(data) {
		                
		                //* insert or update new event to table. *//
		                
		                data = $($.parseHTML(data));
		                data.filter('.event-data-row').addClass('_has_transition');
		                data.filter('.event-data-row').addClass('_is_updated');

		                //find out if the id already exists
		                id = eventObj.event_id;
		               

		                //check if it exists
		                if (id != '' && $('[data-id='+ id +']').length ) {
		                	event_exists = true;
		                }



		               	//if it already exists
		                if (id != '' && $('[data-id='+ id +']').length ) {
		                	//replace that element with data
		                	
		                	//remove the updated form
		                	$('[data-id='+ id +']').next('tr').remove();
		                	$('[data-id='+ id +']').replaceWith(data);

		                	//reload the datepicker
		                	loadDatepicker();
		                	return;
		                } 
		                // else we create a new event.
		              
	                	//find the data-date attr.
	                	new_date = data.filter('.event-data-row').attr('data-date');
	                	
	                	// get all the event data rows
	                	data_rows = $('.event-data-row');
	                	no_rows = $('.eventlist-no-rows');
	                	
	                	if(data_rows.length == 0 && no_rows.length == 1) {

	                		no_rows.replaceWith(data);

	                	} else {

		                	//loop through them until the date is later than our new date, then instert before and break loop.
		                	$.each(data_rows, function(index, row) {
		                		
		                		if(new_date < $(row).attr('data-date')) {

		                			row_id = $(row).attr('data-id');
		                			$( data ).insertBefore('[data-id='+ row_id +']');
		                			
		                			//reload the datepicker
		                			loadDatepicker();

		                			//stop loope
		                			return false;
		                		
		                		} else if(index +1 == data_rows.length ) {
		                			$('.eventlist-tbody').append(data);
		                			loadDatepicker();

		                		}
		                		

		                	});

		                }

		               


		            },
		            complete: function(jqXHR, textStatus) {
		            	
		            	$('.eventlist-form-wrapper').find('.spinner').hide();
		            	//$('.eventslist-content').find('.spinner').hide();
						//$('.new-event-container').hide();
		                //$('.new-event-button').show();
		    			setTimeout(function() {
							$('.event-data-row').removeClass('_is_updated');
						}, 100);
						setTimeout(function() {
							$('.event-data-row').removeClass('_has_transition');
						}, 1000);
						
						current_post_id = self.newEventContainer.find('input[name="mcal_attached_post_id"]').val();
						self.update_related_date_range(current_post_id);
		            },
		            error: function(jqXHR, textStatus, errorThrown) {
		            	
		            }
		        });

			},

			update_related_date_range: function(id) {
				$.ajax({
		            url: ajaxurl,
		            type: "POST",
		            data: {
		                'action' : 'update_related_date_range',
		                'id' : id,
		            },
		            success: function(data) {
		            	console.log(data);
		            	$('.global_dates').text(data);
		            	
		            }
		        }); //end $.ajax
			}


		}

		EventHandler.init();

	})();









	/**
	 * fullcalendar initialization
	 * URL: http://arshaw.com/fullcalendar/docs/usage/
	 */
	
	function updateEvent(event, delta, revertFunc) {
		var end = event.end.format('YYYY-MM-DD HH:mm');
    	var end_moment = $.fullCalendar.moment(end);

    	if(event.allDay) {
    		end_moment.subtract('days', 1);
    	}
		
		end_moment = end_moment.format('YYYY-MM-DD HH:mm');

    	// update event data	
		$.ajax({
            url: ajaxurl,
            data: {
                'action' : 'update_event',
                'id'	: event.id,
                'start' : event.start.format('YYYY-MM-DD HH:mm'),
                'end' : end_moment,
                'allDay' : event.allDay
            },
            success: function(data) {
                //console.log('event updated: ' + data);
            }
        });
	}

	$('#calendar').fullCalendar({
	   
	   	//set language dynamic via wordpress?? 
	   	lang: wp_language_code,

	   	//enable drag/drop behavior
	    editable: true,

	    // first day of week. 0 = "sunday", 1 = "monday" etc..
	    firstDay: wp_start_of_week,
	    
	    // determines how many weeks to show in each month 'fixed', 'liquid' or 'variable'.
	    weekMode: 'variable',
		
		//display week numbers
	    weekNumbers: false, 
	    
	    //the format of time displayed in the calendar
	    timeFormat: 'HH:mm',

	    header: {
    		left:   'prev today next',
    		center: '',
    		right:  'title'
		},
		
		nextDayThreshold: "00:00:00",

		aspectRatio: 1.7,

		//eventColor: $('#calendar').attr('data-eventcolor'),
		// eventTextColor: '#fff',
	    events: $.parseJSON(wp_events),

	   	viewRender: function(){
	   		//add an extra class to fullcalendar (current week)
			$('.fc-today').parent().addClass('fc-current-week');
	   	},
	    
	    eventDrop: function(event, delta, revertFunc) {
			updateEvent(event, delta, revertFunc);
    	},

    	eventResize: function(event, delta, revertFunc) {
    		updateEvent(event, delta, revertFunc);
	    },
	    eventMouseover: function( event, jsEvent, view ) { 
	    	//console.log(event);
	    	$(".event-" + event._id).addClass('_is_hover');
	    },
	    eventMouseout: function( event, jsEvent, view ) { 
	    	$(".event-" + event._id).removeClass('_is_hover');
	    },
	    dragOpacity: {
			    // for agendaWeek and agendaDay
			    agenda: .5,

			    // for all other views
			    '': .8
			}

	});


}); //end of jquery no-conflict