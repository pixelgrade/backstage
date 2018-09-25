(
	function( $, exports, wp ) {

		// when the customizer is ready
		wp.customize.bind( 'ready', function() {
            // Remove everything it is not needed
            $('#save').remove();
            $('#customize-header-actions .customize-controls-close').remove();
            $('#customize-header-actions .customize-controls-preview-toggle').remove();

            // Make sure that there are no loose ends.
            // api.notifications.remove( 'autosave_available' );

            let button_template = wp.template('cgda-customizer-button');
            $('#customize-save-button-wrapper').html(button_template(cgda));

            if ( typeof cgda.notice_text !== "undefined" && cgda.notice_text != "" ) {
                // Handle the button and the notice
                let notice_template = wp.template('cgda-customizer-notice'),
                    noticeType = (typeof cgda.notice_type !== "undefined") ? cgda.notice_type : 'info';

                wp.customize.notifications.add('cgda_notice', new wp.customize.Notification(
                    'cgda_notice',
                    {
                        type: cgda.notice_type,
                        message: notice_template(cgda)
                    }
                ));

                // $('#customize-info').before(notice_template(cgda));
            }

            // Handle preventing the default behavior like prompting for unsaved changes and such.
            wp.customize.bind( 'change', function() {
                $( window ).off( 'beforeunload.customize-confirm' );
            } );
            // api.state( 'saved' ).set( true );
		} );
	}
)( jQuery, window, wp );
