/**
 * WM Newsticker — handbook page: confirmation before the destructive demo-data forms submit.
 *
 * The forms carried inline onsubmit handlers until 2026-09-14; the question now travels in a
 * data-wm-confirm attribute so the markup stays free of inline scripts.
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var forms = document.querySelectorAll('form[data-wm-confirm]');
		Array.prototype.forEach.call(forms, function (form) {
			form.addEventListener('submit', function (event) {
				var question = form.getAttribute('data-wm-confirm');
				if (question && !window.confirm(question)) {
					event.preventDefault();
				}
			});
		});
	});
})();
