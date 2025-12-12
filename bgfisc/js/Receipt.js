function fiscActions() {
	$('body').append('<div class="fullScreenBg" style="position: fixed; top: 0; z-index: 1002; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.9);display: none;"><h3 style="color: #fff; font-size: 56px; text-align: center; position: absolute; top: 30%; width: 100%">Отпечатва се фискален бон ...<br> Моля, изчакайте!</h3></div>');

	// Опит за отпечатване на фискален бон при натискане на бутона
	$(document.body).on('click', ".printReceiptBtn, .document-conto-btn", function(e){

		var url = $(this).attr("data-url");
		
		if(!url) return;
		$('.fullScreenBg').css('display', 'block');
		$('.select-input-pos').prop("disabled", true);

		// Бутона се дезактивира при натискане, след получаване на резултат ще се активира
		$(this).addClass( "disabledBtn");
		$(this).prop("disabled", true);
		
		var stornoReason = $("select[name=selectedStornoReason]").val();
		var data = {stornoReason:stornoReason};
		
		resObj = new Object();
		resObj['url'] = url;
		getEfae().process(resObj, data);
	});
};


/**
 * Рендиране на резултата от отпечатването на фискалната бележка
 */
function render_fiscresult(data)
{
	// Евалюирани на скрипта за печат на касовата бележка
	if(data.js){
		eval(data.js);
	}
}


/**
 * Рендиране на резултата от отпечатването на фискалната бележка
 */
function render_removeDisabledContoBtn(data)
{
	// Евалюирани на скрипта за печат на касовата бележка
	if(data.id){
		$(".document-conto-btn").removeClass( "disabledBtn");
		$(".document-conto-btn").prop("disabled", false);
	}
}

function removeDisabledBtn() {
	$(".printReceiptBtn").removeClass( "disabledBtn");
	$(".printReceiptBtn").prop("disabled", false);
}

function render_removeDisabledBtn() {
	removeDisabledBtn();
}
function render_removeBlackScreen() {
	$(".fullScreenBg").css("display", "none");
}



