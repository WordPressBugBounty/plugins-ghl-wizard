;(function($) {

	$(document).ready( function() {

		$('#hlwpw-tag-box').select2();
		$('#hlwpw-required-tag-box').select2();
		$('#hlwpw-and-required-tag-box').select2();
		$('#lcw-ld-auto-enrollment-tags').select2();
		$('#hlwpw-campaign-box').select2();
		$('#hlwpw-wokflow-box').select2();
		$('#lcw-calendar-id').select2();
		$('#lcw-calendar-time-slot').select2();
		$('#hlwpw_selected_existing_tag').select2();
		$('#membership-redirect-to-box').select2();
		$('#hlwpw-order-status-action-area').find('.hlwpw-status-tag-box').select2();
		$('.lcw-menu-visibility-tags').select2();
		$('.lcw-menu-visibility-memberships').select2();

		const $calendarId = $('#lcw-calendar-id');
		const $startDate = $('#lcw-calendar-start-date');
		const $endDate = $('#lcw-calendar-end-date');
		const $slotSelect = $('#lcw-calendar-time-slot');
		const $slotButton = $('#lcw-get-time-slots');
		const $slotMessage = $('#lcw-calendar-timeslot-message');

		function setSlotMessage(message, isError) {
			$slotMessage.text(message || '').toggleClass('lcw-error', !!isError);
		}

		function resetSlotOptions(message) {
			$slotSelect.empty().append(new Option('- Select a time slot -', ''));
			$slotSelect.prop('disabled', true).trigger('change');
			setSlotMessage(message, false);
		}

		function validateSlotRange() {
			const calendarId = $calendarId.val();
			const startDate = $startDate.val();
			const endDate = $endDate.val();

			if (!calendarId || !startDate || !endDate) {
				return 'Select a calendar, start date, and end date.';
			}

			const start = new Date(startDate + 'T00:00:00');
			const end = new Date(endDate + 'T00:00:00');

			if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
				return 'Enter valid dates.';
			}

			if (end < start) {
				return 'End date cannot be earlier than start date.';
			}

			const dayDiff = Math.round((end - start) / 86400000);
			if (dayDiff > 31) {
				return 'Date range cannot exceed 31 days.';
			}

			return '';
		}

		if (!$slotSelect.val()) {
			$slotSelect.prop('disabled', true);
		}

		$calendarId.on('change', function() {
			resetSlotOptions('');
		});

		$startDate.add($endDate).on('change', function() {
			resetSlotOptions('');
		});

		$slotButton.on('click', function() {
			const validationMessage = validateSlotRange();
			if (validationMessage) {
				setSlotMessage(validationMessage, true);
				return;
			}

			const selectedSlot = $slotSelect.val();
			$slotButton.prop('disabled', true);
			resetSlotOptions('Loading time slots...');

			$.ajax({
				url: lcw_admin_ajax.ajax_url,
				type: 'POST',
				data: {
					action: 'lcw_get_calendar_timeslots',
					nonce: lcw_admin_ajax.nonce,
					calendar_id: $calendarId.val(),
					start_date: $startDate.val(),
					end_date: $endDate.val()
				}
			}).done(function(response) {
				if (!response.success || !response.data || !response.data.slots) {
					setSlotMessage('Unable to fetch calendar time slots.', true);
					return;
				}

				if (!response.data.slots.length) {
					resetSlotOptions('No time slots found for the selected date range.');
					return;
				}

				$slotSelect.empty().append(new Option('- Select a time slot -', ''));
				response.data.slots.forEach(function(slot) {
					$slotSelect.append(new Option(slot.label, slot.value));
				});
				if (selectedSlot) {
					$slotSelect.val(selectedSlot);
				}
				$slotSelect.prop('disabled', false).trigger('change');
				setSlotMessage('');
			}).fail(function(xhr) {
				const message = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
					? xhr.responseJSON.data.message
					: 'Unable to fetch calendar time slots.';
				setSlotMessage(message, true);
			}).always(function() {
				$slotButton.prop('disabled', false);
			});
		});

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
