(
	function( $, exports, wp ) {

		// when the customizer is ready
		wp.customize.bind( 'ready', function() {
		    // Handle the button and the notice
            let notice_template = wp.template('cgda-customizer-preview-notice'),
                button_template = wp.template('cgda-customizer-preview-button');

            $('#save').remove();
            $('#customize-header-actions .customize-controls-close').remove();
            $('#customize-header-actions .customize-controls-preview-toggle').remove();
            $('#customize-info').before(notice_template(cgda));
            $('#customize-save-button-wrapper').html(button_template(cgda));

            // Handle preventing the default behavior like prompting for unsaved changes and such.
            wp.customize.bind( 'change', function() {
                $( window ).off( 'beforeunload.customize-confirm' );
            } );
            // api.state( 'saved' ).set( true );
		} );
	}
)( jQuery, window, wp );
