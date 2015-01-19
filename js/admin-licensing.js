jQuery( document ).ready( function($) {
	var licenseManager = window.wprss.licenseManager;

	manage_license = function() {
		var button = $(this),
			activating = button.hasClass('button-activate-license'),
			button_orig_label = button.attr('value'),
			addon = button.attr('name').split('_', 3)[1], // Name has form "wprss_ftp_license_deactivate"; grab the "ftp" part.
			license = $('#wprss-' + addon + '-license-key').val(),
			nonce = $('#wprss_' + addon + '_license_nonce').val(),
			promise;

		button.attr('disabled', true);
		button.attr('value', activating ? wprss_admin_licensing.activating : wprss_admin_licensing.deactivating);

		if (activating) {
			promise = licenseManager.activateLicense(addon, license, nonce);
		} else {
			promise = licenseManager.deactivateLicense(addon, license, nonce);
		}

		promise.then(function( response ) {
			var td = button.parent();

			// Inject the new HTML we got to update the UI and hook up the onClick handler.
			if (response.html !== undefined) {
				td.empty();
				td.append(response.html);
				td.children('.button-activate-license').click(manage_license);
				td.children('.button-deactivate-license').click(manage_license);
			}

			// There was an error.
			if (response.error !== undefined) {
				console.log('There was an error: ' + response.error);
			}
		},
		function ( error ) {
			console.log('Error: ', error);
			button.attr('disabled', false);
			button.attr('value', button_orig_label);
		});

	};

	$('.button-activate-license').click(manage_license);
	$('.button-deactivate-license').click(manage_license);

});