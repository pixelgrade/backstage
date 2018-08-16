(
	function( $, exports, wp ) {

		// when the customizer is ready
		wp.customize.bind( 'ready', function() {
            var notice_template = wp.template('customizer-preview-for-demo-notice');
            var button_template = wp.template('customizer-preview-for-demo-button');

            $('#save').remove();
            $('#customize-header-actions .customize-controls-close').remove();
            $('#customize-header-actions .customize-controls-preview-toggle').remove();
            $('#customize-info').before(notice_template(cgda));
            $('#customize-save-button-wrapper').html(button_template(cgda));
		} );
	}
)( jQuery, window, wp );
