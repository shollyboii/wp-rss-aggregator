// jQuery for 'Fetch Feed Items' Row Action in 'All Feed Sources' page
function fetch_items_row_action_callback(){
    var link = jQuery(this);
    var original_text = link.text();
    var id = link.attr('pid');
    var url = link.attr('purl');
    jQuery.post(
        url, 
        {
            'action': 'wprss_fetch_feeds_row_action',
            'id':   id
        }, 
        function(response){
            link.text('Feed items imported!');
            setTimeout( function(){
                link.text( original_text ).click( fetch_items_row_action_callback );
            }, 3500 );
        }
    );
    link.text('Please wait ...');
    link.unbind('click');
};



jQuery(window).load( function(){


    function wprssParseDate(str){
        var t = str.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
        if( t!==null ){
            var d=+t[1], m=+t[2], y=+t[3];
            var date = new Date(y,m-1,d);
            if( date.getFullYear() === y && date.getMonth() === m-1 ){
                return date;   
            }
        }
        return null;
    }


    var WPRSS_DATE_FORMAT = 'dd/mm/yy';
    var WPRSS_TIME_FORMAT = 'HH:mm:ss';
    var WPRSS_NOW = new Date();
    var WPRSS_NOW_UTC = new Date(
        WPRSS_NOW.getUTCFullYear(),
        WPRSS_NOW.getUTCMonth(),
        WPRSS_NOW.getUTCDate(),
        WPRSS_NOW.getUTCHours(),
        WPRSS_NOW.getUTCMinutes(),
        WPRSS_NOW.getUTCSeconds()
    );

    // Set datepickers
    jQuery.datepicker.setDefaults({
        dateFormat: WPRSS_DATE_FORMAT,
    });
    jQuery.timepicker.setDefaults({
        controlType: 'slider',
        timezone: 0,
        timeFormat: WPRSS_TIME_FORMAT,
    });
    jQuery('.wprss-datetimepicker').datetimepicker();
    jQuery('.wprss-datetimepicker-from-today').datetimepicker({ minDate: WPRSS_NOW_UTC });


    jQuery('.wprss-datepicker, .wprss-datepicker-from-today').focusout( function(){
        val = jQuery(this).val();
        if ( val !== '' && wprssParseDate( val ) === null ) {
            jQuery(this).addClass('wprss-date-error');
        } else jQuery(this).removeClass('wprss-date-error');
    });

	// On TAB pressed when on title input field, go to URL input field
	jQuery('input#title').on( 'keydown', function( event ) {    
        
        if ( event.which == 9 ) {
            event.preventDefault();
            jQuery('input#wprss_url').focus();
        }
    }
    );	    

	// On TAB pressed when on description textarea field, go to Publish submit button
	jQuery('textarea#wprss_description').on( 'keydown', function( event ) {
                
        if ( event.which == 9 ) {
            event.preventDefault();
            jQuery('input#publish').focus();
        }
    }
    );	        

	jQuery('.wprss_ajax_action').click( fetch_items_row_action_callback );
	
	// Make the number rollers change their value to empty string when value is 0, making
	// them use the placeholder.
	jQuery('.wprss-number-roller').on('change', function(){
		if ( jQuery(this).val() == 0 )
			jQuery(this).val('');
	});


    /* JS for admin notice - Leave a review */


    // Check to see if the Ajax Notification is visible
    if ( jQuery('#dismiss-ajax-notification').length > 0 ) {
        NOTIFICATION = jQuery('#ajax-notification');
        NOTIFICATION_DISMISS = jQuery('#dismiss-ajax-notification');
        NOTIFICATION_DISMISS.click( function(evt){
            evt.preventDefault();
            evt.stopPropagation();

            jQuery.post(ajaxurl, {
                // The name of the function to fire on the server
                action: 'wprss_hide_admin_notification',
                // The nonce value to send for the security check
                nonce: jQuery.trim( jQuery('#ajax-notification-nonce').text() )
            }, function (response) {
                // If the response was successful (that is, 1 was returned), hide the notification;
                // Otherwise, we'll change the class name of the notification
                if ( response !== '1' ) {
                    NOTIFICATION.removeClass('updated').addClass('error');
                } // end if
            });

            NOTIFICATION.fadeOut(400);
        });
    }


    if ( jQuery('#wprss_tracking_notice') ) {
        
    }


});


/**
 * WP-like collapsing settings in metabox 
 */
(function($){
    $(window).load( function(){

        // Initialize all collapsable meta settings
        $('.wprss-meta-slider').each(function(){
            // Get all required elements
            var slider = $(this);
            var viewerID = slider.attr('data-collapse-viewer');
            var viewer = $( '#' + viewerID );
            var editLink = viewer.next();
            var field = slider.find('*').first();
            var label = slider.find('label.description');
            var defaultValue = slider.attr('data-default-value');
            // Edit link opens the settings
            editLink.click(function( e ){
                // If not open already, open it
                if ( !slider.hasClass('wprss-open') )
                    slider.slideDown().addClass('wprss-open');
                e.preventDefault();
            });

            // Create the OK Button
            var okBtn = $('<a>').addClass('wprss-slider-button button-secondary').text('OK').click( function(){
                // On click, get the value of the field
                val = field.val();
                // If empty, use the default value
                if ( val === '' ) val = defaultValue;
                // Set the text of the viewer to the value
                viewer.text( val );
            });
            // Create the Cancel Button
            var cancelBtn = $('<a>').addClass('wprss-slider-button').text('Cancel');

            // Add the buttons and a break tag before the description label
            okBtn.appendTo( slider );
            cancelBtn.appendTo( slider );

            // Make both buttons close the div
            slider.find('.wprss-slider-button').click( function(){
                slider.slideUp().removeClass('wprss-open');
            });
        });

    });
})(jQuery);