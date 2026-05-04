;(function($) {

	$(document).ready( function() {

		$('#hlwpw-tag-box').select2();
		$('#hlwpw-required-tag-box').select2();
		$('#hlwpw-and-required-tag-box').select2();
		$('#lcw-ld-auto-enrollment-tags').select2();
		$('#hlwpw-campaign-box').select2();
		$('#hlwpw-wokflow-box').select2();
		$('#hlwpw_selected_existing_tag').select2();
		$('#membership-redirect-to-box').select2();
		$('#hlwpw-order-status-action-area').find('.hlwpw-status-tag-box').select2();
		$('.lcw-menu-visibility-tags').select2();
		$('.lcw-menu-visibility-memberships').select2();

		$(document).on('change', '[name^="lcw_menu_logged_in"]', function() {
			if (!this.checked) {
				return;
			}

			const $settings = $(this).closest('.menu-item-settings');
			$settings.find('[name^="lcw_menu_logged_out"]').prop('checked', false);
		});

		$(document).on('change', '[name^="lcw_menu_logged_out"]', function() {
			if (!this.checked) {
				return;
			}

			const $settings = $(this).closest('.menu-item-settings');
			$settings.find('[name^="lcw_menu_logged_in"]').prop('checked', false);
			$settings.find('[name^="lcw_menu_membership_any"]').prop('checked', false);
			$settings.find('.lcw-menu-visibility-tags, .lcw-menu-visibility-memberships').val(null).trigger('change');
		});

		$(document).on('change', '[name^="lcw_menu_membership_any"]', function() {
			if (!this.checked) {
				return;
			}

			const $settings = $(this).closest('.menu-item-settings');
			$settings.find('[name^="lcw_menu_logged_out"]').prop('checked', false);
			$settings.find('.lcw-menu-visibility-memberships').val(null).trigger('change');
		});

		$(document).on('change', '.lcw-menu-visibility-memberships', function() {
			if (!$(this).val() || !$(this).val().length) {
				return;
			}

			const $settings = $(this).closest('.menu-item-settings');
			$settings.find('[name^="lcw_menu_membership_any"]').prop('checked', false);
			$settings.find('[name^="lcw_menu_logged_out"]').prop('checked', false);
		});
	});

})(jQuery);
