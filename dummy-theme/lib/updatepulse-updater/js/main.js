/* global UPupdater */

let P = function(global) {

	let P = {lang: 'en-GB'};

	// Format tokens and functions
	let tokens = {
		// DAY
		// day of month, pad to 2 digits
		d: d => pad(d.getDate()),
		// Day name, first 3 letters
		D: d => getDayName(d).substr(0,3),
		// day of month, no padding
		j: d => d.getDate(),
		// Full day name
		l: d => getDayName(d),
		// ISO weekday number (1 = Monday ... 7 = Sunday)
		N: d => d.getDay() || 7,
		// Ordinal suffix for day of the month
		S: d => getOrdinal(d.getDate()),
		// Weekday number (0 = Sunday, 6 = Saturday)
		w: d => d.getDay(),
		// Day of year, 1 Jan is 0
		z: d => {
			let Y = d.getFullYear(),
			M = d.getMonth(),
			D = d.getDate();
			return Math.floor((Date.UTC(Y, M, D) - Date.UTC(Y, 0, 1)) / 8.64e7);
		},
		// ISO week number of year
		W: d => getWeekNumber(d)[1],
		// Full month name
		F: d => getMonthName(d),
		// Month number, padded
		m: d => pad(d.getMonth() + 1),
		// 3 letter month name
		M: d => getMonthName(d).substr(0, 3),
		// Month number, no pading
		n: d => d.getMonth() + 1,
		// Days in month
		t: d => new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate(),
		// Return 1 if d is a leap year, otherwise 0
		L: d => new Date(d.getFullYear(), 1, 29).getDate() == 29? 1 : 0,
		// ISO week numbering year
		o: d => getWeekNumber(d)[0],
		// 4 digit year
		Y: d => {
			let year = d.getFullYear();
			if (year < 0) {
				year = '-' + ('000' + Math.abs(year)).slice(-4);
			}
			return year;
		},
		// 2 digit year
		y: d => {
			let year = d.getFullYear();
			if (year >= 0) {
				return ('0' + year).slice(-2);
			} else {
				year = Math.abs(year);
				return - + ('0' + year).slice(-2);
			}
		},
		// Lowercase am or pm
		a: d => d.getHours() < 12? 'am' : 'pm',
		// Uppercase AM or PM
		A: d => d.getHours() < 12? 'AM' : 'PM',
		// Swatch internet time
		B: d => (((+d + 3.6e6) % 8.64e7) / 8.64e4).toFixed(0),
		// 12 hour hour no padding
		g: d => (d.getHours() % 12) || 12,
		// 24 hour hour no padding
		G: d => d.getHours(),
		// 12 hour hour padded
		h: d => pad((d.getHours() % 12) || 12),
		// 24 hour hour padded
		H: d => pad(d.getHours()),
		// Minutes padded
		i: d => pad(d.getMinutes()),
		// Seconds padded
		s: d => pad(d.getSeconds()),
		// Microseconds padded - always returns 000000
		u: d => '000000',
		// Milliseconds
		v: d => padd(d.getMilliseconds()),
		// Timezone identifier: UTC, GMT or IANA Tz database identifier - Not supported
		e: d => void 0,
		// If in daylight saving: 1 yes, 0 no
		I: d => d.getTimezoneOffset() == getOffsets(d)[0]? 0 : 1,
		// Difference to GMT in hours, e.g. +0200
		O: d => minsToHours(-d.getTimezoneOffset(), false),
		// Difference to GMT in hours with colon, e.g. +02:00
		P: d => minsToHours(-d.getTimezoneOffset(), true),
		// Timezone abbreviation, e.g. AEST. Dodgy but may work…
		T: d => d.toLocaleString('en',{year:'numeric',timeZoneName:'long'}).replace(/[^A-Z]/g, ''),
		// Timezone offset in seconds, +ve east
		Z: d => d.getTimezoneOffset() * -60,
		// ISO 8601 format - local
		c: d => P.format(d, 'Y-m-d\\TH:i:sP'),
		// RFC 2822 formatted date, local timezone
		r: d => P.format(d, 'D, d M Y H:i:s O'),
		// Seconds since UNIX epoch (same as ECMAScript epoch)
		U: d => d.getTime() / 1000 | 0
	};

	// Helpers
	// Return day name for date
	let getDayName = d => d.toLocaleString(P.lang, {weekday:'long'});
	// Return month name for date
	let getMonthName = d => d.toLocaleString(P.lang, {month:'long'});
	// Return [std offest, DST offset]. If no DST, same offset for both
	let getOffsets = d => {
		let y = d.getFullYear();
		let offsets = [0, 2, 5, 9].map(m => new Date(y, m).getTimezoneOffset());
		return [Math.max(...offsets), Math.min(...offsets)];
	}
	// Return ordinal for positive integer
	let getOrdinal = n => {
		n = n % 100;
		let ords = ['th','st','nd','rd'];
		return (n < 10 || n > 13) ? ords[n%10] || 'th' : 'th';
	};
	// Return ISO week number and year
	let getWeekNumber = d => {
		let e = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
		e.setUTCDate(e.getUTCDate() + 4 - (e.getUTCDay()||7));
		var yearStart = new Date(Date.UTC(e.getUTCFullYear(),0,1));
		var weekNo = Math.ceil(( ( (e - yearStart) / 86400000) + 1)/7);
		return [e.getUTCFullYear(), weekNo];
	};
	// Return true if o is a Date, otherwise false
	let isDate = o => Object.prototype.toString.call(o) == '[object Date]';
	// Convert numeric minutes to +/-HHMM or +/-HH:MM
	let minsToHours = (mins, colon) => {
		let sign = mins < 0? '-' : '+';
		mins = Math.abs(mins);
		let H = pad(mins / 60 | 0);
		let M = pad(mins % 60);
		return sign + H + (colon? ':' : '') + M;
	};
	// Pad single digits with a leading zero
	let pad = n => (n < 10? '0' : '') + n;
	// Pad single digits with two leading zeros, double digits with one leading zero
	let padd = n => (n < 10? '00' : n < 100? '0' : '') + n;
	// To be completed...
	let parse = s => 'not complete';

	P.parse = parse;

	// Format date using token string s
	function format(date, s) {

		// Minimal input validation
		if (!isDate(date) || typeof s != 'string') {
			return; // undefined
		}

		return s.split('').reduce((acc, c, i, chars) => {
			// Add quoted characters to output
			if (c == '\\') {
				acc += chars.splice(i+1, 1);
				// If character matches a token, use it
			} else if (c in tokens) {
				acc += tokens[c](date);
				// Otherwise, just add character to output
			} else {
				acc += c;
			}
			return acc;
		}, '');
	}
	P.format = format;

	return P;
}(this);

jQuery(document).ready(function ($) {

	$('.wrap-license').each(function () {
		var licenseContainer = $(this);

		if (licenseContainer.find('.deactivate-license').is(':disabled')) {
			var nextDeactivate = licenseContainer.find('.deactivate-license').data('next_deactivate');
			var dateFormat = licenseContainer.find('.deactivate-license').data('date_format');
			var date = new Date(nextDeactivate * 1000);
			console.log(date, nextDeactivate);

			P.lang = { lang: document.documentElement.lang };

			licenseContainer.find('.deactivate-license').val(licenseContainer.find('.deactivate-license').val() + ' ' + P.format(date, dateFormat));
		}
	});

	$('body').on('click', '.wrap-license .activate-license', function (e) {
		e.preventDefault();

		var licenseContainer = $(this).closest('.wrap-license');
		var packageID        = licenseContainer.attr('id').replace('wrap_license_', '');
		var data             = {
			'nonce' : licenseContainer.data('nonce'),
			'license_key' : licenseContainer.find('.license').val(),
			'package_slug' : licenseContainer.data('package_slug'),
			'action' : 'upupdater_' + packageID + '_activate_license'
		};

		$.ajax({
			url: UPupdater.ajax_url,
			data: data,
			type: 'POST',
			success: function (response) {

				if (response.success) {
					licenseContainer.find('.current-license').html(licenseContainer.find('.license').val());
					licenseContainer.find('.current-license-error').addClass('hidden');
					licenseContainer.find('.license-message').removeClass('hidden');
					licenseContainer.find('.deactivate-license-container').removeClass('hidden');
					licenseContainer.find('.activate-license-container').addClass('hidden');
					$('.license-error-' + licenseContainer.data('package_slug') + '.notice').addClass('hidden');

					if (response.data.may_deactivate) {
						licenseContainer.find('.deactivate-license').prop('disabled', false);
					} else {
						licenseContainer.find('.deactivate-license').prop('disabled', true);
					}

					licenseContainer.find('.deactivate-license').val(response.data.deactivate_text);

					if (licenseContainer.find('.deactivate-license').is(':disabled')) {
						var nextDeactivate = response.data.next_deactivate;
						var dateFormat = response.data.date_format;
						var date = new Date(nextDeactivate * 1000);
						console.log(date, nextDeactivate);

						P.lang = { lang: document.documentElement.lang };

						licenseContainer.find('.deactivate-license').val(licenseContainer.find('.deactivate-license').val() + ' ' + P.format(date, dateFormat));
					}

					$('tr[data-plugin="' + packageID + '"] .column-auto-updates a').removeClass('hidden');
					$('tr.plugin-update-tr:not(.updatepulse)[data-plugin="' + packageID + '"]').removeClass('hidden');
					licenseContainer.closest('.theme-info').find('.theme-autoupdate').removeClass('hidden');
					$('#update-theme').closest('.notice').removeClass('hidden');
				} else {
					var errorContainer = licenseContainer.find('.current-license-error');

					errorContainer.html(response.data[0].message + '<br/>');
					errorContainer.removeClass('hidden');
					licenseContainer.find('.license-message').removeClass('hidden');
				}

				if ('' === licenseContainer.find('.current-license').html()) {
					licenseContainer.find('.current-license-label').addClass('hidden');
					licenseContainer.find('.current-license').addClass('hidden');
				} else {
					licenseContainer.find('.current-license-label').removeClass('hidden');
					licenseContainer.find('.current-license').removeClass('hidden');
				}
			}
		});
	});

	$('body').on('click', '.wrap-license .deactivate-license', function (e) {
		e.preventDefault();

		var licenseContainer = $(this).closest('.wrap-license');
		var packageID        = licenseContainer.attr('id').replace('wrap_license_', '');
		var data             = {
			'nonce' : licenseContainer.data('nonce'),
			'license_key' : licenseContainer.find('.license').val(),
			'package_slug' : licenseContainer.data('package_slug'),
			'action' : 'upupdater_' + packageID + '_deactivate_license'
		};

		$.ajax({
			url: UPupdater.ajax_url,
			data: data,
			type: 'POST',
			success: function (response) {

				if (response.success) {
					licenseContainer.find('.current-license').html('');
					licenseContainer.find('.current-license-error').addClass('hidden');
					licenseContainer.find('.license-message').addClass('hidden');
					licenseContainer.find('.deactivate-license-container').addClass('hidden');
					licenseContainer.find('.activate-license-container').removeClass('hidden');

					$('tr[data-plugin="' + packageID + '"] .column-auto-updates a').addClass('hidden');
					$('tr.plugin-update-tr:not(.updatepulse)[data-plugin="' + packageID + '"]').addClass('hidden');
					licenseContainer.closest('.theme-info').find('.theme-autoupdate').addClass('hidden');
					$('#update-theme').closest('.notice').addClass('hidden');
				} else {
					var errorContainer = licenseContainer.find('.current-license-error');

					errorContainer.html(response.data[0].message + '<br/>');
					errorContainer.removeClass('hidden');
					licenseContainer.find('.license-message').removeClass('hidden');
				}

				if ('' === licenseContainer.find('.current-license').html()) {
					licenseContainer.find('.current-license-label').addClass('hidden');
					licenseContainer.find('.current-license').addClass('hidden');
				} else {
					licenseContainer.find('.current-license-label').removeClass('hidden');
					licenseContainer.find('.current-license').removeClass('hidden');
				}
			}
		});
	});
});