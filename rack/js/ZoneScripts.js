function zoneActions() {

	// 1) Ранна визуална индикация/lock (БЕЗ disabled, за да не убие click-а)
	$(document.body)
		.off('pointerdown.toggleMovement mousedown.toggleMovement touchstart.toggleMovement', '.toggle-movement')
		.on('pointerdown.toggleMovement mousedown.toggleMovement touchstart.toggleMovement', '.toggle-movement', function () {
			var $btn = $(this);
			if ($btn.data('ajaxBusy') || $btn.data('tm_locked')) return;

			$btn.data('tm_locked', 1).addClass('is-busy');
			console.log('Busy btn');
		});

	// 2) Реалното пращане – само веднъж
	$(document.body)
		.off('click.toggleMovement', '.toggle-movement')
		.on('click.toggleMovement', '.toggle-movement', function (e) {
			e.preventDefault();
			e.stopImmediatePropagation();

			var $btn = $(this);

			// ако click дойде 2 пъти / или има 2 handler-а – стоп
			if ($btn.data('tm_sent')) return false;
			$btn.data('tm_sent', 1);

			var divId = $btn.closest('div.rowsContainerClass').attr("id");
			var url   = $btn.attr("data-url");
			if (!url) return false;

			// тук вече е безопасно да disable-нем
			$btn.data('ajaxBusy', 1)
				.prop('disabled', true)
				.addClass('is-busy');

			getEO().isReloading = true;
			getEO().isWaitingResponse = true;
			getEfae().waitPeriodicAjaxCall = 7;

			console.log('Call ' + url);
			getEfae().process({ url: url }, { divId: divId }, false);

			return false;
		});
}


function render_enableBtn(){
	console.log('enable');

	$('.toggle-movement.is-busy')
		.data('ajaxBusy', 0)
		.data('tm_sent', 0)
		.prop('disabled', false)
		.removeClass('is-busy');
}
