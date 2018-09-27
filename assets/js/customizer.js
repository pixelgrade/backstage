;(
	function( $, exports, wp ) {

		// when the customizer is ready
		wp.customize.bind( 'ready', function() {
		    var api = wp.customize,
                changedValues = {};

            // Remove everything it is not needed
            $('#save').remove();
            $('#customize-outer-theme-controls').remove();
            $('#customize-header-actions .customize-controls-close').remove();
            $('#customize-header-actions .customize-controls-preview-toggle').remove();

            if ( ( typeof backstage.hide_info !== "undefined" && backstage.hide_info != "" ) ) {
                $('#customize-info').remove();
            }

            // Make sure that there are no loose ends.
            // api.notifications.remove( 'autosave_available' );

            let button_template = wp.template('backstage-customizer-button');
            $('#customize-save-button-wrapper').html(button_template(backstage));

            if ( typeof backstage.notice_text !== "undefined" && backstage.notice_text != "" ) {
                // Handle the button and the notice
                let notice_template = wp.template('backstage-customizer-notice'),
                    noticeType = (typeof backstage.notice_type !== "undefined") ? backstage.notice_type : 'info',
                    dismissible = ( typeof backstage.notice_dismissible === "undefined" || backstage.notice_dismissible == "" ) ? false : true;

                api.notifications.add('backstage_notice', new wp.customize.Notification(
                    'backstage_notice',
                    {
                        type: noticeType,
                        message: notice_template(backstage),
                        dismissible: dismissible
                    }
                ));
            }

            // Handle preventing the default behavior like prompting for unsaved changes and such.
            api.bind( 'change', function() {
                $( window ).off( 'beforeunload.customize-confirm' );
            } );

            // We need to modify the way the query is constructed when refreshing the preview.
            // We want to always send all the modified values since they will not come from the backend via the changeset logic.
            if ( typeof api.previewer !== "undefined" ) {
                api.previewer.query = function( options ) {
                    var queryVars = {
                        wp_customize: 'on',
                        customize_theme: api.settings.theme.stylesheet,
                        nonce: this.nonce.preview,
                        customize_changeset_uuid: api.settings.changeset.uuid
                    };

                    // Send all the changedValues.
                    queryVars.customized = JSON.stringify( changedValues );

                    return queryVars;
                };

                // Remember all the setting values that were changed.
                api.bind( 'change', function( setting ) {
                    changedValues[ setting.id ] = setting.get();
                } );
            }
		} );
	}
)( jQuery, window, wp );
