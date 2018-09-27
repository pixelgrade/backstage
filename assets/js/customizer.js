(
	function( $, exports, wp ) {

		// when the customizer is ready
		wp.customize.bind( 'ready', function() {
		    var api = wp.customize,
                changedValues = {};

            // Remove everything it is not needed
            $('#save').remove();
            $('#customize-header-actions .customize-controls-close').remove();
            $('#customize-header-actions .customize-controls-preview-toggle').remove();

            if ( ( typeof cgda.hide_info !== "undefined" && cgda.hide_info != "" ) ) {
                $('#customize-info').remove();
            }

            // Make sure that there are no loose ends.
            // api.notifications.remove( 'autosave_available' );

            let button_template = wp.template('cgda-customizer-button');
            $('#customize-save-button-wrapper').html(button_template(cgda));

            if ( typeof cgda.notice_text !== "undefined" && cgda.notice_text != "" ) {
                // Handle the button and the notice
                let notice_template = wp.template('cgda-customizer-notice'),
                    noticeType = (typeof cgda.notice_type !== "undefined") ? cgda.notice_type : 'info',
                    dismissible = ( typeof cgda.notice_dismissible === "undefined" || cgda.notice_dismissible == "" ) ? false : true;

                api.notifications.add('cgda_notice', new wp.customize.Notification(
                    'cgda_notice',
                    {
                        type: noticeType,
                        message: notice_template(cgda),
                        dismissible: dismissible
                    }
                ));

                // $('#customize-info').before(notice_template(cgda));
            }

            // Handle preventing the default behavior like prompting for unsaved changes and such.
            api.bind( 'change', function() {
                $( window ).off( 'beforeunload.customize-confirm' );
            } );
            // api.state( 'saved' ).set( true );

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
                    // if ( api.settings.changeset.autosaved || ! api.state( 'saved' ).get() ) {
                    //     queryVars.customize_autosaved = 'on';
                    // }

                    // Send all the changedValues.
                    queryVars.customized = JSON.stringify( changedValues );

                    return queryVars;
                };

                // Remember all the setting values that were changed.
                api.bind( 'change', function( setting ) {
                    changedValues[ setting.id ] = setting.get();
                } );
            }

            // Mark all settings as clean to prevent another call to requestChangesetUpdate.
            // api.each( function( setting ) {
            //     setting._dirty = false;
            // });


		} );
	}
)( jQuery, window, wp );
