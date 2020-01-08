function posActions() {

	var dialog;
	
	var pageWidth = parseInt($(window).width());

	$(document.body).on('input', "input[name=ean]", function(e){
		var userText = $(this).val();
		$("#suggestions").find("option").each(function() {
		      if ($(this).val() == userText) {
		    	  $value = $(this).attr("data-value");
		    	  $("input[name=ean]").val($value);
		      }
		})
	});
	$('.large-field.select-input-pos').focus();
	// Забраняване на скалирането, за да избегнем забавяне
	if(isTouchDevice()){
		 $('meta[name=viewport]').remove();
		 $('meta').attr('name', 'viewport').attr('content', 'width=device-width, user-scalable=no').appendTo('head');
	}
	
	// Извикване на функцията за преизчисления на размерите на елементите
	if($('body').hasClass('wide')){
		calculateWidth();
		$(window).resize( function() {
			calculateWidth();
		});
	} 

	// Използване на числата за въвеждане в пулта
	$(document.body).on('click', ".numPad", function(e){
		var val = $(this).val();
		
		var closestSearch = $('.select-input-pos');

		var inpVal = $(closestSearch).val();
		if(val == '.'){
			if(inpVal.length == 0){
				inpVal = 0;
			}
			
			if(inpVal.indexOf(".")  != -1){
				return;
			}
		}
		if(val == '«') {
			inpVal = inpVal.substr(0, inpVal.length - 1);
		} else {
			inpVal += val;
		}
		closestSearch.val(inpVal);
		if($('body').hasClass('wide')){
			closestSearch.focus();
		}
	});

	// Добавяне на партида
	$(document.body).on('click', ".resultBatch", function(e){
		var url = $(this).attr("data-url");
		if(!url) return;
		
		resObj = new Object();
		resObj['url'] = url;
		
		var selectedElement = $(".highlighted.productRow");
		var selectedRecId = selectedElement.attr("data-id");
		
		getEfae().process(resObj, {recId:selectedRecId});
	});
	
	// Добавяне 
	$(document.body).on('click', ".textResult", function(e){
		var url = $(this).attr("data-url");
		if(!url) return;
		
		resObj = new Object();
		resObj['url'] = url;
		
		var selectedElement = $(".highlighted.productRow");
		var selectedRecId = selectedElement.attr("data-id");
		
		getEfae().process(resObj, {recId:selectedRecId});
	});
	
	
	// Използване на числата за въвеждане на суми за плащания
	$(document.body).on('click', ".revertBtn", function(e){
		var url = $(this).attr("data-url");
		if(!url) return;
		
		var searchVal = $("input[name=paysum]").val();
		var data = {search:searchVal};
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
		$("input[name=paysum]").val("");
	});

	
	/**
	 * При спиране на писането в полето за търсене
	 * @param e
	 * @returns
	 */
	$(document.body).on('keyup', "input[name=ean]", function(e){
		// Хак да не се тригърва ивента при натискане на ентър или при навигацията на страницата за избор на селектиран елемент
		if(e.key == "Enter" || e.key == "ArrowRight" || e.key == "ArrowLeft" || e.key == "ArrowUp" || e.key == "ArrowDown"  || e.key == "PageUp" || e.key == "PageDown" || e.key == 'Alt') return;
		
		// След всяко натискане на бутон изчистваме времето на изчакване
		clearTimeout(timeout);
		console.log("E " + e.key);
		var url = $(this).attr("data-keyupurl");
		if(!url){
			return;
		}

		var inpVal = $(this).val();
		var operation = getSelectedOperation();
		
		var selectedElement = $(".highlighted.productRow");
		var selectedRecId = selectedElement.attr("data-id");
		
		// Правим Ajax заявката като изтече време за изчакване
		timeout = setTimeout(function(){
			resObj = new Object();
			resObj['url'] = url;

			getEfae().process(resObj, {operation:operation,search:inpVal,recId:selectedRecId});

		}, 700);

	});
	
	
	// Направата на плащане след натискане на бутон
	$(document.body).on('click', ".payment", function(e){
		if(!$(this).hasClass( "disabledBtn")){
			var url = $(this).attr("data-url");

			var type = $(this).attr("data-type");


			type = (!type) ? '-1' : type;

			doPayment(url, type);
		}
	});

	// Бутоните за приключване приключват бележката
	$(document.body).on('click', ".closeBtns", function(e){
		var url = $(this).attr("data-url");
		var receiptId = $("input[name=receiptId]").val();
		
		if(!url) return;
		
		var data = {receipt:receiptId};
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
		scrollRecieptBottom();
	});
	
	
	// Добавяне на продукти от бързите бутони
	$(document.body).on('click', ".pos-product", function(e){
		var url = $(this).attr("data-url");
		if(!url) return;
		
		var productId = $(this).attr("data-id");
		var packId = $(this).attr("data-pack");
		var quant = $("input[name=ean]").val();
		
		var data = {productId:productId,ean:quant,packId:packId};
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
		scrollRecieptBottom();
	});

	
	// Скриване на бързите бутони спрямо избраната категория
	$(".pos-product-category[data-id='']").addClass('active');
	
	$(document.body).on('change', "select.pos-product-category", function(e){
		var value = $(this).val();
		showFavouriteButtons($(this), value);
	});
	
	// Скриване на бързите бутони спрямо избраната категория
	$(document.body).on('click', "div.pos-product-category", function(e){
		var value = $(this).attr("data-id");
		showFavouriteButtons($(this), value);
	});
	
	// При клик на бутон изтрива запис от бележката
	$(document.body).on('click', ".pos-del-btn", function(e){
		var warning = $(this).attr("data-warning");
		var url = $(this).attr("data-url");
		var recId = $(this).attr("data-recId");
		e.stopPropagation();
		
		if (!confirm(warning)){
			
			return false; 
		} else {
			
			$(this).closest('.receiptRow').css('border', '1px solid red');
			resObj = new Object();
			resObj['url'] = url;
			
			getEfae().process(resObj, {recId:recId});
		}
	});
	
	// При клик на бутон добавя отстъпка
	$(document.body).on('click', ".discountBtn", function(e){
		var url = $(this).attr("data-url");
		
		var selectedElement = $(".highlighted.productRow");
		var selectedRecId = selectedElement.attr("data-id");
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, {recId:selectedRecId});
	});

	// Избор на контрагент
	$(document.body).on('click', ".posResultContragent, .contragentLinkBtns", function(e){
		var url = $(this).attr("data-url");
		if(!url) return;
		
		if($(this).hasClass("openInNewTab")){
			window.open(url, '_blank');
			var reloadUrl = $(this).attr("data-reloadurl");
			
			if(reloadUrl){
				document.location = reloadUrl;
			} else {
				location.reload();
			}
		} else {
			document.location = url;
		}
	});
	
	// Смяна на текущата клавиатура
	$(document.body).on('click', ".keyboard-change-btn", function(e){
		var currentAttrValue = $(this).attr('data-klang');
		$('.keyboard#' + currentAttrValue).show().siblings('.keyboard').hide();
	}); 
	
	// Попълване на символи от клавиатурата
	$(document.body).on('click', ".keyboard-btn", function(e){

		var currentAttrValue = $(this).val();
		var isChangeBtn = $(this).attr('data-klang');
		
		// Ако е натиснат бутон за смяна на език, не правим нищо
		if(isChangeBtn != undefined) {
			return;
		}
		
		var closestSearch = $('.keyboardText');
		
		var inpVal = closestSearch.text();
		if (currentAttrValue == "ENTER") {
			$('.select-input-pos').val($('.keyboardText').text());
			$('.ui-dialog-titlebar-close').click();
		} else {
			inpVal += currentAttrValue;
			closestSearch.text(inpVal);

		}
		// Задействаме евент 'keyup' в инпут полето
		var e = jQuery.Event("keyup");
		$('.select-input-pos').trigger(e);
	});
	$(document.body).on('click', ".ui-dialog-titlebar-close", function() {
		if($('.keyboardText').text()){
			$('.select-input-pos').val($('.keyboardText').text());
			var e = jQuery.Event("keyup");
			$('.select-input-pos').trigger(e);
		}
	});

	document.addEventListener("keydown", function(event) {
		if(event.key == "ArrowUp"){
			arrowUp();
		}

		if(event.key == "ArrowDown") {
			arrowDown();
		}

		if(event.key == "ArrowLeft") {
			arrowLeft();
		}
		if(event.key == "ArrowRight") {
			arrowRight();
		}
		if(event.key == "PageDown"){
			pageDown();
		}
		if(event.key == "PageUp"){
			pageUp();
		}

		if(event.key == "Enter"){
			enter();
		}
	});

	startNavigation();

	$(document.body).on('click', ".navigable", function(e){
		$('.navigable').removeClass('selected');
		$(this).addClass('selected');
		sessionStorage.setItem('focused', $(this).attr('id'));
	});


	// Триене на символи от формата за търсене
	$(document.body).on('click', ".keyboard-back-btn", function(e){
		var inpValLength = $(".keyboardText").text().length;
		var newVal = $(".keyboardText").text().substr(0, inpValLength-1);
		$(".keyboardText").text(newVal);
	});

	// Време за изчакване
	var timeout;
	
	// действие на бутоните за действията
	$(document.body).on('click', ".operationBtn", function(e){
		sessionStorage.removeItem("focused");
		var currentlySelected = getSelectedOperation();
		sessionStorage.setItem('lastSelectedOperation', currentlySelected);
		
		clearTimeout(timeout);
		var operation = $(this).attr("data-value");
		
		var selectedElement = $(".highlighted.productRow");
		var selectedRecId = selectedElement.attr("data-id");
		
		var url = $(this).attr("data-url");
		var disabled = $(this).hasClass("disabledBtn");
		
		if(!url || disabled){
			return;
		}
		
		var string = $("input[name=ean]").val();
		
		resObj = new Object();
		resObj['url'] = url;
		
		sessionStorage.setItem('operationClicked', true);
		
		getEfae().process(resObj, {operation:operation,recId:selectedRecId,search:string});
	});
	
	
	// След въвеждане на стойност, прави заявка по Ajax
	$(document.body).on('change', "select[name=operation]", function(e){
		clearTimeout(timeout);
		var operation = $(this).val();
		
		var selectedElement = $(".selected");
		var selectedRecId = selectedElement.attr("data-id");
		
		var url = $(this).attr("data-url");
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, {operation:operation,recId:selectedRecId});
	});
	

	// Добавяне на продукт от резултатите за търсене
	$(document.body).on('click', ".pos-add-res-btn", function(e){
		var elemRow = $(this).closest('.receiptRow ');
		$(elemRow).addClass('highlighted');
		var url = $(this).attr("data-url");
		var productId = $(this).attr("data-productId");
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, {productId:productId});
		calculateWidth();
	});
	
	// При прехвърляне на бележка, автоматично създаваме нова
	$(document.body).on('click', ".transferBtn", function(e){
		var url = $(this).attr("data-url");
		
		if(!url) return;
		
		resObj = new Object();
		resObj['url'] = url;
		getEfae().process(resObj);
	});

	// Сменяне на селектирания ред от бележките при клик
	$(document.body).on('click', ".receiptRow", function(e){
		$('.highlighted').removeClass('highlighted');
		$(this).closest('.receiptRow').addClass('highlighted');
		
		var operation = getSelectedOperation();
		refreshResultByOperation($(this), operation);
		
		disableOrEnableBatch();
	});


	// Време за изчакване
	var timeout1;


	// При натискане на бутона за задаване на цена
	$(document.body).on('click', "div.resultPrice", function(e){
		var url = $(this).attr("data-url");
		if(!url) return;
		
		resObj = new Object();
		resObj['url'] = url;
		
		var selectedElement = $(".highlighted.productRow");
		var selectedRecId = selectedElement.attr("data-id");
		
		getEfae().process(resObj, {recId:selectedRecId});
	});

	// При отваряне на нова бележка маха се фокусирания елемент
	$(document.body).on('click', ".openNoteBtn, .revertReceipt", function(e){
		sessionStorage.removeItem("focused");
	});
	
	// При натискане на бутона за задаване на количество/опаковка
	$(document.body).on('click', "div.resultPack", function(e){
		var url = $(this).attr("data-url");
		if(!url) return;
		var pack = $(this).attr("data-pack");
		
		if($(this).hasClass("packWithQuantity")){
			var quantity = $(this).attr("data-quantity");
		} else {
			var quantity = $("input[name=ean]").val();
		}
		
		quantity = (quantity) ? quantity : 1;
		var string = quantity + " " + pack;
		
		resObj = new Object();
		resObj['url'] = url;
		
		var selectedElement = $(".highlighted.productRow");
		var selectedRecId = selectedElement.attr("data-id");
		
		getEfae().process(resObj, {string:string,recId:selectedRecId});
	});
	
	// При натискане на бутона за задаване на количество/опаковка
	$(document.body).on('click', "div.chooseStoreBtn", function(e){
		var url = $(this).attr("data-url");
		if(!url) return;
		
		var storeId = $(this).attr("data-storeid");
		
		resObj = new Object();
		resObj['url'] = url;
		
		var selectedElement = $(".highlighted.productRow");
		var selectedRecId = selectedElement.attr("data-id");
		
		getEfae().process(resObj, {string:storeId,recId:selectedRecId});
	});
	
	// При натискане на бутона за показване на подробна информация за артикула
	$(document.body).on('click', ".enlargeProductBtn", function(e){
		
		var url = $(this).attr("data-url");
		var operation = getSelectedOperation();
		
		if(!url || $(this).hasClass('disabledBtn')){
			return;
		}
		
		var selectedElement = $(".highlighted.productRow");
		var selectedRecId = selectedElement.attr("data-id");
		
		if(!selectedRecId || selectedRecId == undefined) return;
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, {recId:selectedRecId});

		openModal();
	});
	// При натискане на бутона за клавиатура
	$(document.body).on('click', ".keyboardBtn", function(e){
		
		var url = $(this).attr("data-url");
		var string = $("input[name=ean]").val();
		
		resObj = new Object();
		resObj['url'] = url;
		getEfae().process(resObj, {string:string});

		openModal();
	});
	
	$("body").setShortcutKey( ALT , D ,function() {
		 deleteElement();
	});

	$("body").setShortcutKey( ALT , A ,function() {
		openProducts();
	});

	$("body").setShortcutKey( ALT , K ,function() {
		openQuantity();
	});

	$("body").setShortcutKey( ALT , P ,function() {
		openPayment();
	});

	$("body").setShortcutKey( ALT , Z ,function() {
		openPrice();
	});

	$("body").setShortcutKey( ALT , T ,function() {
		openText();
	});
	$("body").setShortcutKey( ALT , N5 ,function() {
		openDiscount();
	});

	$("body").setShortcutKey( ALT , C ,function() {
		openClient();
	});

	$("body").setShortcutKey( ALT , R ,function() {
		openReceipt();
	});

	$("body").setShortcutKey( ALT , B ,function() {
		openBatch();
	});

	$("body").setShortcutKey( ALT , F ,function() {
		openInfo();
	});
	
	$("body").setShortcutKey( ALT , N3 ,function() {
		openPrint();
	});

	$("body").setShortcutKey( ALT , V ,function() {
		openKeyboard();
	});

	$("body").setShortcutKey( ALT , N ,function() {
		openReject();
	});

	$("body").setShortcutKey( ALT , X ,function() {
		logout();
	});

	var timeoutAlt;
	$("body").setShortcutKey(null,  ALT,function() {
		showHints();
		clearTimeout(timeoutAlt);

		timeoutAlt = setTimeout(function () {
			hideHints();
		}, 5000);
	});

	// При натискане на бутона за клавиатура
	$(document.body).on('click',  function(e){
		hideHints();
	})

}
function openInfo() {
	$('.enlargeProductBtn').click();
}

function showHints(){
	if ($('.buttonOverlay').css('display') == "none") {
		$('.buttonOverlay').fadeIn();
	}
}

function hideHints(){
	$('.buttonOverlay').fadeOut();
}

function openReceipt() {
	$('.operationBtn[data-value="receipts"]').click();
}
function openKeyboard() {
	$('.keyboardBtn').click();
}

function openPrint() {
	$('.printBtn').click();
}
function openClient() {
	$('.operationBtn[data-value="contragent"]').click();
}

function openDiscount() {
	if ($('.operationBtn[data-value="discount"]').length) {
		$('.operationBtn[data-value="discount"]').click();
	}
}
function logout() {
	var url = $('.logout.operationHolder').closest('a').attr("href");
	location.href = url;
}

function openReject() {
	if ($('.rejectBtn').length) {
		$('.rejectBtn').parent().click();
	}
}
function openText() {
	if ($('.operationBtn[data-value="text"]').length) {
		$('.operationBtn[data-value="text"]').click();
	}
}
function openBatch() {
	if ($('.operationBtn[data-value="batch"]').length) {
		$('.operationBtn[data-value="batch"]').click();
	}
}

function openPrice() {
	if ($('.operationBtn[data-value="price"]').length) {
		$('.operationBtn[data-value="price"]').click();
	}
}
function openProducts() {
	$('.operationBtn[data-value="add"]').click();
}

function openQuantity() {
	if ($('.operationBtn[data-value="quantity"]').length) {
		$('.operationBtn[data-value="quantity"]').click();
	}
}

function openPayment() {
	if ($('.operationBtn[data-value="payment"]').length) {
		$('.operationBtn[data-value="payment"]').click();
	}
}

// Калкулира ширината
function calculateWidth(){
	var winWidth = parseInt($(window).width());
	var winHeight = parseInt($(window).height());

	//задаване на ширина на двете колони
	$('.result-content').css('width', winWidth - $('#single-receipt-holder').width());

	//височина за таблицата с резултатите
	var receiptHeight = winHeight -  $('.tools-content').height() - $('.paymentBlock').height();
	$('.scrolling-vertical').css('height',receiptHeight);

	var headerHeight = $('.headerContent').outerHeight();
	$('#result-holder').css('height',winHeight - headerHeight);
	$('#result-holder, #single-receipt-holder').css('top',headerHeight);
}

// Скролиране на бележката до долу
function scrollRecieptBottom(){
	if($('body').hasClass('wide')){
		var el = $('.scrolling-vertical');
		setTimeout(function(){el.scrollTop( el.get(0).scrollHeight );},500);
	}
}

// Направа на плащане
function doPayment(url, type){
	if(!url || !type) return;
	var amount = $("input[name=ean]").val();
	
	var data = {amount:amount, type:type};
	
	resObj = new Object();
	resObj['url'] = url;
	getEfae().process(resObj, data);

	$("input[name=ean]").val("");
	scrollRecieptBottom();
}

// При натискане на pageUp
function pageUp(){
	console.log('pageUp')

	var current = $('#receipt-table .receiptRow.highlighted');
	if(current.length && $(current).prev('.receiptRow').length) {
		var newElement = $(current).prev('.receiptRow');
		newElement.addClass('highlighted');
		current.removeClass('highlighted');
		
		getCurrentElementFromSelectedRow(newElement);
		scrollAfterKey();
	}
}

// При натискане на pageDown
function pageDown(){
	var current = $('#receipt-table .receiptRow.highlighted');
	if(current.length && $(current).next('.receiptRow').length) {
		var newElement = $(current).next('.receiptRow');
		newElement.addClass('highlighted');
		current.removeClass('highlighted');
		getCurrentElementFromSelectedRow(newElement);

		scrollAfterKey();

	}
	console.log('pageDOwn')
}

// При селектиране на текущ елемент
function getCurrentElementFromSelectedRow(element){
	var operation = getSelectedOperation();
	sessionStorage.removeItem("focused");
	
	refreshResultByOperation(element, operation);
	disableOrEnableBatch();
}

function refreshResultByOperation(element, operation){
	
	sessionStorage.removeItem("focused");
	
	// Ако операцията е партидност и реда няма такава прехвърля се към артикул
	var click = operation;
	if(operation == 'batch' && element.hasClass('noBatch')){
		click = 'add';
	}
	
	if(operation == 'price' || operation == 'discount' || operation == 'quantity' || operation == 'text' || operation == 'batch'){
		$('.operationBtn[data-value="' + click+ '"]').click();
	}
}

function arrowDown(){
	console.log('arrowDOwn')

	var current = $('#pos-search-result-table .rowBlock.active');
	if(current.length) {
		$(current).next().addClass('active');
		current.removeClass('active');
	}
	disableOrEnableEnlargeBtn();
}

function arrowUp(){
	console.log('arrowUp')
	var current = $('#pos-search-result-table .rowBlock.active');
	if(current.length) {
		$(current).prev().addClass('active');
		current.removeClass('active');
	}
	disableOrEnableEnlargeBtn();
}

function arrowRight(){
	console.log('arrowRight');
	disableOrEnableEnlargeBtn();
}

function arrowLeft(){
	console.log('arrowLeft');
	disableOrEnableEnlargeBtn();
}

// Коя е текущо селектираната операция
function getSelectedOperation()
{
	if($("select[name=operation]").length){
		var operation = $("select[name=operation]").val();
	} else {
		var operation = $("div.operationBtn.active").attr("data-value");
	}
	
	return operation;
}

// Изтриване на елемент
function deleteElement() {
	if($('.receiptRow.highlighted').length) {
		$('.receiptRow.highlighted').find('.pos-del-btn').click();
	}
}
function render_prepareResult() {
	startNavigation();

	
	// Бутона за увеличение да се дисейбва ако няма избран селектиран ред
	if($('.enlargeProductBtn').length){
		var selectedElement = $(".highlighted");
		
		if(selectedElement.length){
			$('.enlargeProductBtn').removeClass('disabledBtn');
		} else {
			$('.enlargeProductBtn').addClass('disabledBtn');
		}
	}
}

function render_calculateWidth(){
	calculateWidth();
}

function disableOrEnableBatch()
{
	var element = $(".highlighted.productRow");
	
	var batchBtn = $('.operationBtn[data-value="batch"]');
	if(batchBtn.length){
		if(element.hasClass('noBatch')){
			batchBtn.addClass('disabledBtn');
			batchBtn.attr('disabled', 'disabled');
		} else {
			var addBtn = $('.operationBtn[data-value="add"]');
			
			if(!addBtn.hasClass('disabledBtn') && element.length){
				batchBtn.removeClass('disabledBtn');
				batchBtn.removeAttr("disabled");
			}
		}
	}
}

// След презареждане

var semaphor;

function render_afterload()
{
	var operation = getSelectedOperation();
	
	afterload();
	
	var eanInput = $("input[name=ean]");
	
	var searchVal = eanInput.val();
	var submitUrl = eanInput.attr("data-url");
	var clicked = sessionStorage.getItem('operationClicked');
	
	if(submitUrl && !semaphor && clicked){
		
		resObj = new Object();
		resObj['url'] = submitUrl;
		
		var selectedElement = $(".highlighted.productRow");
		var selectedRecId = eanInput.attr("data-id");

		var sendAjax = true;
		if(!searchVal && (operation == 'add' || operation == 'quantity')){
			sendAjax = false;
		}
		
		semaphor = 1;
		
		if(sendAjax){
			getEfae().process(resObj, {string:searchVal,recId:selectedRecId});
		}
		
		return;
	}
	
	semaphor = 0;
	sessionStorage.removeItem("operationClicked");
}

function enter() {
	var value = $("input[name=ean]").val();
	var url = $("input[name=ean]").attr("data-url");
	var operation = getSelectedOperation();

	// Ако има селектиран ред в резултатите
	var element = $(".navigable.selected");

	if(element.length){
		// Намира първия елемент с data-url
		var elementDataUrl = element.attr("data-url");
		var hrefUrl = element.attr("href");
		elementDataUrl = (elementDataUrl) ? elementDataUrl : ((hrefUrl) ? hrefUrl : elementDataUrl);

		if(elementDataUrl == undefined){
			var child = element.find('[data-url]');

			var elementDataUrl = child.attr("data-url");
			if(elementDataUrl){
				element = child;
			}
		}

		if(elementDataUrl == undefined){
			var child = element.find('[href]');
			if(child.length){
				element = child;
			}
		}

		if(element != undefined){

			// Вика се клик
			var event = jQuery.Event("click");
			element.trigger(event);

			return;
		}
	}

	if(!url){
		return;
	}

	resObj = new Object();
	resObj['url'] = url;
	
	var selectedElement = $(".highlighted.productRow");
	var selectedRecId = selectedElement.attr("data-id");


	getEfae().process(resObj, {string:value,recId:selectedRecId});
}


// Отваря модала
function openModal() {
	dialog = $("#modalContent").dialog({
		autoOpen: false,
		height: 700,
		width: 1000,
		modal: true
	});

	dialog.dialog( "open" );
}

function startNavigation() {
	if($('.navigable').length) {
		
		var focused = sessionStorage.getItem('focused');
		
		// ръчно избирам първия елемент за селектед
		if(!focused){
			focused = $('.navigable').first();
			focused.addClass('selected');
			sessionStorage.setItem('focused', focused.attr('id'));
		}
		
		if (focused && document.getElementById(focused)) {
			$('.selected').removeClass('selected');
			$('#' + focused ).addClass('selected');
		}
		$('#result-holder .navigable').keynav();
	}
}
function scrollToHighlight(){
	if ( $('.highlighted').length) {
		var offset = $('.highlighted').offset().top - $('.scrolling-vertical').height()  + $('.highlighted').height();

		$('.scrolling-vertical').animate({
			scrollTop:  offset
		}, 0);
	}
}

function scrollAfterKey(){
	var scroll = $('.highlighted').offset().top - $('.highlighted').height();
	$('.scrolling-vertical').animate({
		scrollTop:  scroll
	}, 0);
}

// Добавя хинт
function setInputPlaceholder() {
	var activeElement = $("div.operationBtn.active");
	var title = activeElement.attr("title");
	
	$("input[name=ean]").attr("placeholder", title);
}

function afterload() {
	scrollToHighlight();
	disableOrEnableBatch();
	setInputPlaceholder();
	disableOrEnableEnlargeBtn();
}

function disableOrEnableEnlargeBtn()
{
	var focused = sessionStorage.getItem('focused');
	var focusedElement = $("#" + focused);
	
	if(focusedElement.length){
		if(focusedElement.hasClass('enlargable')){
			$(".enlargeProductBtn").removeClass('disabledBtn');
			$(".enlargeProductBtn").removeAttr("disabled");

			
			var enlargeClassId = focusedElement.attr("data-enlarge-class-id");
			var enlargeObjectId = focusedElement.attr("data-enlarge-object-id");
			
			focusedElement.attr('disabled', 'disabled');
			focusedElement.attr('disabled', 'disabled');
			
		} else {
			$(".enlargeProductBtn").addClass('disabledBtn');
			$(".enlargeProductBtn").attr('disabled', 'disabled');
		}
	}
	
}
