function zoneActions() {

	$(document.body).on('click', ".toggle-movement", function(e){
		var $btn = $(this);

		if ($btn.data('ajaxBusy') || $btn.prop('disabled')) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		}

		var divId = $btn.closest('div.rowsContainerClass').attr("id");
		var url   = $btn.attr("data-url");
		if(!url) return;

		$btn.data('ajaxBusy', 1)
			.prop('disabled', true)
			.addClass('is-busy');

		getEO().isReloading = true;
		getEO().isWaitingResponse = true;
		getEfae().waitPeriodicAjaxCall = 7;

		var data  = {divId: divId};
		var resObj = {url: url};
		getEfae().process(resObj, data);
	});
}


function render_enableBtn(){
	$('.toggle-movement').data('ajaxBusy', 0).prop('disabled', false).removeClass('is-busy');
}
