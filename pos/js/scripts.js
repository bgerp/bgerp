var dialog;
var activeInput;
var timeout;
var timeoutRemoveDisabled;
var timeoutPageNavigation;
var searchTimeout;
var addedProduct;

function posActions() {
	calculateWidth();
	activeInput = false;
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

	calculateWidth();
	$(window).resize( function() {
		calculateWidth();
	});

	// Използване на числата за въвеждане в пулта
	$(document.body).on('click', ".numPad", function(e){
		var val = $(this).val();

		var inputElement = $('.select-input-pos');
		activeInput = true;
		inputChars(inputElement, val);
	});
	
	// Добавяне 
	$(document.body).on('click', ".textResult", function(e){
		var url = $(this).attr("data-url");
		var params = {recId:getSelectedRowId()};
		
		processUrl(url, params);
	});

	
	/**
	 * При спиране на писането в полето за търсене
	 * @param e
	 * @returns
	 */
	$(document.body).on('keyup', ".large-field", function(e){
		// ако е клавишна комбинация с ctrl
		if(e.ctrlKey){
			return;
		}

		// Хак да не се тригърва ивента при натискане на ентър или при навигацията на страницата за избор на селектиран елемент
		if(e.key == "Enter" || e.key == "ArrowRight" || e.key == "ArrowLeft" || e.key == "ArrowUp" || e.key == "ArrowDown"  || e.key == "PageUp" || e.key == "PageDown" || e.key == 'Alt' || e.key == 'Control' || e.key == 'Escape' || e.key == 'F2') return;

		activeInput = true;

		var operation = getSelectedOperation();
		if(operation == 'payment'){
			disableOrEnableCurrencyBtn();
		}
		
		triggerSearchInput($(this), searchTimeout, true);
	});


	// При натискане на продукт, който не е селектиран и не сме на touch устройства да го добавяме
	$(document.body).on('dblclick', ".navigable", function(e){
		if(isTouchDevice() || !$(this).hasClass('pos-add-res-btn') || ($(this).hasClass('pos-add-res-btn') && $(this).hasClass('selected'))) return;

		pressNavigable(this);
		e.preventDefault();
	});

	var oldTime = Date.now();
	// При натискане на елемент с клас за навигиране (ако сме на touch устройство или не сме на продукти или артикула е селектиран) до добавяме
	$(document.body).on('click', ".navigable", function(e){
		if(!isTouchDevice() && $(this).hasClass('pos-add-res-btn') && !$(this).hasClass('selected')) return;
		if($(this).hasClass('reload')) return;
		if($(this).hasClass('deleteRow')) return;
		if($(this).hasClass('printReceiptBtn')) return;
		
		if(Date.now() - oldTime > 400) {	
			pressNavigable(this);
			e.preventDefault();
		}
		oldTime = Date.now();
	});

	$('body').on('paste', '.large-field', function (e){
		activeInput = true;
		var url = $(this).attr("data-keyupurl");
		
		if(url){
			setTimeout(function() {
				var ev = jQuery.Event("keyup");
				$('.large-field').trigger(ev);
	        }, 100);
		}
	});
	
	// Бутоните за приключване приключват бележката
	$(document.body).on('click', ".closeBtns", function(e){
		var url = $(this).attr("data-url");
		var receiptId = $("input[name=receiptId]").val();
		var data = {receipt:receiptId};
		
		processUrl(url, params);
	});
	
	// При клик на бутон изтрива запис от бележката
	$(document.body).on('click', ".deleteRow", function(e){
		deleteSelectedElement();
	});
	
	// Попълване на символи от клавиатурата
	$(document.body).on('click', ".keyboard-btn", function(e){

		var currentAttrValue = $(this).val();
		
		// Ако е натиснат бутон за смяна на език
		if($(this).hasClass('keyboard-change-btn')) {
			var lang = $(this).attr('data-klang');
			sessionStorage.setItem('activeKeyboard', lang);
			$('.keyboard#' + lang).show().siblings('.keyboard').hide();
			$('.keyboardText').focus();
			return;
		}

		if (currentAttrValue == "ENTER") {
			$('.select-input-pos').val($('.keyboardText').val());
			$('.ui-dialog-titlebar-close').click();
			triggerSearchInput($(".large-field"), 0, true);
			activeInput = true;
		} else {
			inputChars($('.keyboardText'), currentAttrValue);
		}
	});

	
	/**
	 * При клик на таба
	 */
	$(document.body).on('click', ".tabHolder li", function() {
		activateTab($(this), 0);
		sessionStorage.setItem('selectedTab', $(this).attr('id'));
		startNavigation();
	});

	$(document.body).on('change', "select.tabHolder", function() {
			activateTab($(this), 0);
			sessionStorage.setItem('selectedTab', $(this.selectedOptions).attr('id'));
			startNavigation();
	});

	
	$(document.body).on('click', ".ui-dialog-titlebar-close", function() {
		if($('.keyboardText').val()){
			$('.select-input-pos').val($('.keyboardText').val());
			var e = jQuery.Event("keyup");
			$('.select-input-pos').trigger(e);
			activeInput = true;
		}
		
		openedModal = false;
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

	document.addEventListener("keyup", function(event) {
		if(event.key == "Control"){
			hideHints();
		}
	});
	if($('.tabHolder').length == 0) {
		startNavigation();
	}


	// действие на бутоните за действията
	$(document.body).on('click', ".operationBtn", function(e){
		var operation = $(this).attr("data-value");
		var selectedRecId = getSelectedRowId();
		
		doOperation(operation, selectedRecId, true);
	});



	// При натискане на бутон с резултати да се чисти таймаута
	$(document.body).on('click', ".posBtns", function(e){
		activeInput = false;
		clearTimeout(timeout);
	});



	$(document.body).on('click', ".large-field.select-input-pos", function(e){
		activeInput = true;
	});
	
	// При прехвърляне на бележка, автоматично създаваме нова
	$(document.body).on('click', ".transferBtn", function(e){
		var url = $(this).attr("data-url");
		processUrl(url, null);
	});

	// Сменяне на селектирания ред от бележките при клик
	$(document.body).on('click', "#receipt-table .receiptRow", function(e){
		$('.highlighted').removeClass('highlighted');
		$(this).closest('.receiptRow').addClass('highlighted');
		
		//var operation = getSelectedOperation();
		refreshResultByOperation($(this), 'quantity');
	});


	// Време за изчакване
	var timeout1;


	// При отваряне на нова бележка маха се фокусирания елемент
	$(document.body).on('click', ".openNoteBtn, .revertReceipt", function(e){
		sessionStorage.removeItem("focused");
	});
	
	
	// При натискане на бутона за показване на подробна информация избрания елемент
	$(document.body).on('click', ".enlargeProductBtn", function(e){
		
		var element = $(this);
		openInfo(element);
	});
	
	// При натискане на бутона за клавиатура
	$(document.body).on('click', ".keyboardBtn", function(e){
		openKeyboard();
	});
	
	// При натискане на бутон за нова фирма
	$(document.body).on('click', ".newCompanyBtn", function(e){
		presssNavigable(this);
	});
	
	// При натискане на бутона за клавиатура
	$(document.body).on('click', ".helpBtn", function(e){
		clearTimeout(timeout);
		openHelp();
	});

	$("body").setShortcutKey( CONTROL , DELETE ,function() {
		 clearTimeout(timeout);
		 deleteSelectedElement();
	});

	$("body").setShortcutKey( CONTROL , A ,function() {
		clearTimeout(timeout);
		openProducts();
	});

	$("body").setShortcutKey( CONTROL , S ,function() {
		clearTimeout(timeout);
		openQuantity();
	});

	$("body").setShortcutKey( CONTROL , Z ,function() {
		clearTimeout(timeout);
		openPayment();
	});

	$("body").setShortcutKey( CONTROL , E ,function() {
		clearTimeout(timeout);
		openText();
	});

	$("body").setShortcutKey( CONTROL , K ,function() {
		clearTimeout(timeout);
		openClient();
	});

	$("body").setShortcutKey( CONTROL , B ,function() {
		clearTimeout(timeout);
		openReceipt();
	});

	$("body").setShortcutKey( null , F2 ,function() {
		clearTimeout(timeout);
		var element = $('.enlargeProductBtn');
		openInfo(element);
	});
	
	$("body").setShortcutKey( CONTROL , P ,function() {
		clearTimeout(timeout);
		openPrint();
	});

	$("body").setShortcutKey( CONTROL , M ,function() {
		clearTimeout(timeout);
		openKeyboard();
	});

	$("body").setShortcutKey( CONTROL , O ,function() {
		clearTimeout(timeout);
		openReject();
	});

	$("body").setShortcutKey( CONTROL , Q ,function() {
		clearTimeout(timeout);
		logout();
	});

	$("body").setShortcutKey( null , F1 ,function() {
		clearTimeout(timeout);
		openHelp();
	});

	$("body").setShortcutKey( CONTROL , LEFT ,function() {
		prevTab();
	});

	$("body").setShortcutKey( CONTROL , RIGHT ,function() {
		nextTab();
	});

	var timeoutAlt;
	$("body").setShortcutKey(null,  CONTROL,function() {
		showHints();
	});

	$("body").setShortcutKey( CONTROL , HOME ,function() {
		$( "#result-holder" ).scrollTop( 0 );
	});

	$("body").setShortcutKey( CONTROL , END ,function() {
		$( "#result-holder" ).scrollTop( 5000);
	});

	// При натискане на бутона за клавиатура
	$(document.body).on('click',  function(e){
		hideHints();
	})
}


/**
 * Отваряне на предишния таб
 */
function prevTab() {
	
	sessionStorage.removeItem("focused");
	var currentTab = $('.tabHolder li.active');
	if($(currentTab).prev().length) {
		$(currentTab).prev()[0].scrollIntoView({inline: "center", block: "end"});
		activateTab($(currentTab).prev(), 750);
		
		activeInput = false;
	}

	startNavigation();
}


/**
 * Отваряне на предишния таб
 */
function nextTab() {
	sessionStorage.removeItem("focused");

	var currentTab = $('.tabHolder li.active');
	if($(currentTab).next().length) {
		$(currentTab).next()[0].scrollIntoView({inline: "center", block: "end"});
		activateTab($(currentTab).next(), 750);
		
		activeInput = false;
	}
	startNavigation();
}

function inputChars(inputElement, val) {
	var strOffset = 1;
	var inpVal = $(inputElement).val();
	var start = inputElement[0].selectionStart;
	var end = inputElement[0].selectionEnd;

	if(val == '.' && inpVal == ""){
		inpVal = "0.";
		strOffset = 2;
	} else if(val == '«') {
		if (start == end) {
			inpVal =inpVal.substr(0, start-1) + inpVal.substr(end);
			strOffset = -1;
		} else {
			inpVal =inpVal.substr(0, start) + inpVal.substr(end);
			strOffset = 0;
		}
	} else {
		inpVal = inpVal.substr(0, start) + val + inpVal.substr(end);
	}

	inputElement.val(inpVal);
	inputElement[0].selectionStart = start + strOffset;
	inputElement[0].selectionEnd = start + strOffset;

	if($('body').hasClass('wide')){
		inputElement.focus();
	}

	var e = jQuery.Event("keyup");
	inputElement.trigger(e);

	activeInput = true;
}


// Активиране на лупата за увеличение
function openInfo(element) {
	
	var url = element.attr("data-url");
	url = (element.hasClass('disabledBtn')) ? null : url;
	
	var enlargeClassId = element.attr("data-enlarge-class-id");
	var enlargeObjectId = element.attr("data-enlarge-object-id");
	var enlargeTitle = element.attr("data-modal-title");
	
	var params = {enlargeClassId:enlargeClassId,enlargeObjectId:enlargeObjectId};
	processUrl(url, params);
	
	if(url){
		openModal(enlargeTitle, "defaultHeight");
	}
}

// Отваря модал с хелпа
function openHelp() {
	var url = $('.helpBtn').attr("data-url");
	
	var rejectAction = $('div.rejectBtn').attr("data-action");
	var params = {rejectAction:rejectAction};
	
	processUrl(url, params);
	
	var modalTitle = $('.helpBtn').attr("data-modal-title");
	openModal(modalTitle);
}

function showHints(){
	if ($('.buttonOverlay').css('display') == "none") {
		$('.buttonOverlay').fadeIn();
	}
}

function hideHints() {
	$('.buttonOverlay').fadeOut();
}

function openReceipt() {
	selectedRecId = getSelectedRowId();
	doOperation("receipts", selectedRecId, false)
}

// Отваря виртуалната клавиатура
function openKeyboard() {
	var url = $('.keyboardBtn').attr("data-url");
	var string = $("input[name=ean]").val();
	
	var params = {string:string};
	processUrl(url, params);
	
	var modalTitle = $('.keyboardBtn').attr("data-modal-title");
	openModal(modalTitle, "smallHeight");
}

function openPrint() {
	$('.printBtn').click();
}

function openClient() {
	var selectedRecId = getSelectedRowId();
	doOperation("contragent", selectedRecId, false);
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
	var selectedRecId = getSelectedRowId();
	doOperation("text", selectedRecId, false);
}

function openProducts() {
	var selectedRecId = getSelectedRowId();
	doOperation("add", selectedRecId, false);
}

function openQuantity() {
	var selectedRecId = getSelectedRowId();
	doOperation("quantity", selectedRecId, false);
}

function openPayment() {
	var selectedRecId = getSelectedRowId();
	doOperation("payment", selectedRecId, false);
}

// Калкулира ширината
function calculateWidth(){
	var winWidth = parseInt($(window).outerWidth());
	var winHeight = parseInt($(window).outerHeight());
	if (winWidth >= 1200) {
		//задаване на ширина на двете колони
		$('#result-holder').css('width', winWidth - $('#single-receipt-holder').width());

		$('#single-receipt-holder').addClass('fixedHolder');
		$('#result-holder').addClass('fixedPosition');
		$('#result-holder').removeClass('relativePosition');
		$('#single-receipt-holder').removeClass('blockHolder');
		$('#single-receipt-holder').addClass('fixedHolder');

		//височина за таблицата с резултатите
		var receiptHeight = winHeight -  $('.tools-content').height() - $('.paymentBlock').height();
		$('.scrolling-vertical').css('height',receiptHeight);

		var headerHeight = $('.headerContent').outerHeight();
		if($('#result-holder .withTabs').length) {
			var tabsFix = $('.withTabs .tabs').height();
			$('#result-holder').css('padding', '0');
			$('#result-holder').css('overflow-y', 'visible');

			$('#result-holder .withTabs').css('position', "relative");
			$('#result-holder .withTabs').css('top', tabsFix - 41);
			$('#result-holder .withTabs').css('height',winHeight - headerHeight - tabsFix - 22);
			$('#result-holder .tabs, #result-holder').css('width', winWidth - $('#single-receipt-holder').width());
		} else {
			$('#result-holder').css('padding', '15px');
			$('#result-holder').css('overflow-y', 'auto');
		}
		$('#result-holder').css('height',winHeight - headerHeight);

		$('#result-holder, #single-receipt-holder').css('top',headerHeight);

		$('.tools-content').css('height',460);

		if(!isTouchDevice()) {
			$('#keyboard-num').css('display','block');
			$('.buttons').removeClass('oneRow');
		} else {
			$('#tools-holder').css('height', 330);
			$('#keyboard-num').css('display','none');
			$('.buttons').addClass('oneRow');
		}

	} else {
		$('#keyboard-num').css('display','none');

		$('#single-receipt-holder').removeClass('fixedHolder');
		$('#result-holder').removeClass('fixedPosition');
		$('#result-holder').addClass('relativePosition');
		$('#single-receipt-holder').addClass('blockHolder');
		$('#single-receipt-holder').removeClass('fixedHolder');
		$('#single-receipt-holder').addClass('narrowHolder');
		$('.buttons').addClass('oneRow');

		$('#result-holder').css('width', "100%");
		$('.tools-content').css('height','auto');
		$('#result-holder .withTabs').css('height', "100%");
		$('#result-holder .tabs').css('width', "100%");

		$('.keyboardBtn.operationHolder').addClass('disabledBtn');
		$('.keyboardBtn.operationHolder').attr('disabled', 'disabled');
	}
}

// Направа на плащане
function doPayment(url, type){
	if(!url || !type) return;
	var amount = $("input[name=ean]").val();
	if(!amount){
		amount = $("input[name=ean]").attr('data-defaultpayment');
	}
	
	var data = {amount:amount, type:type};
	processUrl(url, data);
}

// При натискане на pageUp
function pageUp(){
	activeInput = false;
	var current = $('#receipt-table .receiptRow.highlighted');
	sessionStorage.setItem('lastHighlighted', current.attr('data-id'));
	
	if(current.length && $(current).prev('.receiptRow').length) {
		var newElement = $(current).prev('.receiptRow');
		newElement.addClass('highlighted');
		current.removeClass('highlighted');
		getCurrentElementFromSelectedRow(newElement);
	}
}

// При натискане на pageDown
function pageDown(){
	activeInput = false;
	var current = $('#receipt-table .receiptRow.highlighted');
	sessionStorage.setItem('lastHighlighted', current.attr('data-id'));
	
	if(current.length && $(current).next('.receiptRow').length) {
		var newElement = $(current).next('.receiptRow');
		newElement.addClass('highlighted');
		current.removeClass('highlighted');
		getCurrentElementFromSelectedRow(newElement);
	}
}

// При селектиране на текущ елемент
function getCurrentElementFromSelectedRow(element){
	var operation = getSelectedOperation();
	sessionStorage.removeItem("focused");
	
	clearTimeout(timeoutPageNavigation);

	timeoutPageNavigation = setTimeout(function(){
		
		var newOperation = 'quantity';
		var operationBtn = $('.operationBtn[data-value=quantity]');
		var url = operationBtn.attr("data-url");
		if(!url){
			newOperation = 'payment';
		}
		
		refreshResultByOperation(element, newOperation);
		if(operation != 'quantity'){
			scrollAfterKey();
		}
	}, 1000);

	scrollAfterKey();
}

function refreshResultByOperation(element, operation){
	
	sessionStorage.removeItem("focused");
	
	// Ако операцията е партидност и реда няма такава прехвърля се към артикул
	var click = operation;
	
	if(operation == 'quantity' || operation == 'payment' || operation == 'add'){
		var selectedRecId = getSelectedRowId();
		doOperation(operation, selectedRecId, false);
	}
}

function arrowDown(){
	activeInput = false;
	var current = $('#pos-search-result-table .rowBlock.active');
	if(current.length) {
		$(current).next().addClass('active');
		current.removeClass('active');
	}
	disableOrEnableEnlargeBtn();
}

function arrowUp(){
	activeInput = false;
	var current = $('#pos-search-result-table .rowBlock.active');
	if(current.length) {
		$(current).prev().addClass('active');
		current.removeClass('active');
	}
	disableOrEnableEnlargeBtn();
}

function arrowRight(){
	activeInput = false;
	disableOrEnableEnlargeBtn();
}

function arrowLeft(){
	activeInput = false;
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

// Изтриване на текущия селектиран елемент елемент
function deleteSelectedElement() {
	var selectedElement = $(".highlighted.receiptRow");
	if(!selectedElement.length) return;
	
	var warning = selectedElement.attr("data-delete-warning");
	var url = selectedElement.attr("data-delete-url");
	if(!url) return;
	
	if (!confirm(warning)){
		
		return false; 
	}
	
	selectedElement.closest('.receiptRow').css('border', '1px solid red');
	processUrl(url, null);
	CtrlMod = false;
}

function render_openCurrentPosTab() {
	openCurrentPosTab();
}

function render_prepareResult() {
	calculateWidth();

	activeInput = false;
	if($('#result-holder .noFoundInGroup:visible').length) {
		sessionStorage.setItem('focused', '');
	}

	if($('.tabHolder').length == 0) {
		startNavigation();
	}

	setTimeout(function () {
		if($('.updatedDiv').length){
			$('.updatedDiv').removeClass('updatedDiv');
		}
	}, 2000);
	// Бутона за увеличение да се дисейбва ако няма избран селектиран ред
	if($('.enlargeProductBtn').length){
		var selectedElement = $(".highlighted");
		
		if(selectedElement.length){
			$('.enlargeProductBtn').removeClass('disabledBtn');
		} else {
			$('.enlargeProductBtn').addClass('disabledBtn');
		}
	}
	
	clearTimeout(timeoutRemoveDisabled);
}

function render_calculateWidth(){
	calculateWidth();
}

// След презареждане
var semaphor;

function render_afterload()
{
	afterload();
}

function enter(){
	if (openedModal) return;

	clearTimeout(timeout);
	var value = $("input[name=ean]").val();

	// Ако има селектиран ред в резултатите
	var element = $(".navigable.selected:visible");

	// Ако има селектиран елемент в резултатите
	if(element.length){
		var operation = getSelectedOperation();
		sessionStorage.setItem('focused', element.attr('id'));

		// Ако инпута е активен но е с празен стринг, или е активен и е въведена операция за к-во или не е активен
		// тогава се клика на селектирания елемент в резултатите
		if((activeInput === true && (!value || operation == 'payment')) || activeInput === false){
			pressNavigable(element);

			return;
		}
	}

	submitInputString();
}


/**
 * Активира елемент от резултатите с navigable клас
 */
function pressNavigable(element)
{
	var element = $(element);
	
	var params = {recId:getSelectedRowId()};
	var url = element.attr("data-url");
	var onclick = element.attr("onclick");
	
	if(element.hasClass("disabledBtn")) return;
	
	if(element.hasClass('pos-add-res-btn')){
		addProduct(element);
		sessionStorage.setItem('focusedOffset', $('#result-holder .withTabs').scrollTop());
		return;
	} else if(element.hasClass('chooseStoreBtn')) {
		var storeId = element.attr("data-storeid");
		params = {string:storeId,recId:getSelectedRowId()};
	} else if(element.hasClass('resultPack')) {
		var pack = element.attr("data-pack");
		if(element.hasClass("packWithQuantity")){
			var quantity = element.attr("data-quantity");
		} else {
			var quantity = $("input[name=ean]").val();
		}
		
		quantity = (quantity) ? quantity : 1;
		var string = quantity + " " + pack;
		params = {string:string,recId:getSelectedRowId()};
	} else if(element.hasClass('payment')){
		var type = element.attr("data-type");
		type = (!type) ? '-1' : type;
		doPayment(url, type);
		return;
		
	} else if(element.hasClass('contragentLinkBtns') || element.hasClass('posResultContragent')){
		
		clearTimeout(timeout);
		if(element.hasClass("openInNewTab")){
			window.open(url, '_blank');
			var reloadUrl = element.attr("data-reloadurl");
			
			if(reloadUrl){
				document.location = reloadUrl;
			} else {
				location.reload();
			}
		} else {
			document.location = url;
		}
		return;
		
	} else if(element.hasClass("newCompanyBtn") || element.hasClass("locationBtn")){
		location.href = url;
		return;
	} else if(element.hasClass("deleteRow")){
		deleteSelectedElement();
		return;
	} else if(element.is("a")){
		var hrefUrl = element.attr("href");
		
		if(onclick){
			element.trigger("onclick");
			return;
		}
		
		location.href = hrefUrl;
		return;
	}
	
	if(onclick){
		element.trigger("onclick");
	}
	
	element.addClass( "disabledBtn");
	element.prop("disabled", true);
	
	timeoutRemoveDisabled = setTimeout(function(){
		element.removeClass('disabledBtn');
		element.removeAttr("disabled");
	}, 1000);
	
	processUrl(url, params);
}


/**
 * Извикване на урл-то след потвърждение на предупреждението
 */
function confirmAndRefirect(warning, url)
{
	if (!confirm(warning)){
		
		return false; 
	}
	
	location.href = url;
}


/**
 * Събмитва въведеното от глобалния инпут, ако има какво и има урл
 */
function submitInputString(){
	var value = $("input[name=ean]").val();
	var url = $("input[name=ean]").attr("data-url");
	
	if(!url){
		return;
	}
	
	clearTimeout(timeout);
	var params = {string:value,recId:getSelectedRowId()};

	processUrl(url, params);
}


// Дали подадения стринг е операция за задаване на количество
function isMicroformat(string) {
	var string = $.trim(string);
	
	// Ако има въведен непразен стринг
	if(string){
		// и той завършва с *
		if(string.endsWith("*") || string.startsWith("*")){
			
			// Премахваме * да остане чист стринг
			var quantity = string.replace("*", "");
			quantity = quantity.replace(",", ".");
			
			// Ако останалата част от стринга е положително число
			if($.isNumeric(quantity) && quantity > 0){
				if(string.startsWith("*")){
					var split = quantity.split(".");
					var cnt = (split[1]) ? split[1].length : 0;
					if(cnt == 2){
						return true;
					}
					
					return false;
				}
				
				return true;
			}
		} else if(string.endsWith("%") || string.startsWith("%")){
			var quantity = string.replace("%", "");
			quantity = quantity.replace(",", ".");
			
			if($.isNumeric(quantity)){
				if(string.startsWith("%")){
					
					var split = quantity.split(".");
					var cnt = (split[1]) ? split[1].length : 0;
					if(cnt == 2){
						return true;
					}
					
					return false;
				}
			
				return true;
			}
		}
	}
	
	// Ако се стигне до тук, значи операцията не е за промяна на количеството
	return false;
}

var openedModal;


// Отваря модала
function openModal(title, heightModal) {
	clearTimeout(timeout);
	
	// Изчистване на предишното съдържание на модала, да не се визуализира, докато се зареди новото
	$("#modalContent").html("");
	var height = (heightModal == "smallHeight" ) ?  500 : 700;
	var width = ($(window).width() > 1200) ?  1000 : parseInt($(window).width()) - 40;

	dialog = $("#modalContent").dialog({
		autoOpen: false,
		height: height,
		width: width,
		modal: true,
		title: title,
		beforeClose: event.preventDefault(),
		close: function () {openedModal = false;},
	});

	dialog.dialog( "open" );
	$('.ui-dialog-titlebar-close').focus();


	setTimeout(function () {
		if ($('#modalContent .keyboard').length) {
			var keyboard = sessionStorage.getItem('activeKeyboard');
			if($('#' + keyboard).length ){
				$('.keyboard#' + keyboard).show().siblings('.keyboard').hide();
			}

			$('.keyboardText').focus();

			$('.keyboardText').keydown(function(event) {
				$('.pressed').removeClass('pressed');
				var key = event.key.toLowerCase();
				$(".keyboard-btn[data-key=" + key+ "]").addClass('pressed');
				if (event.key == "Enter") {
					$('.select-input-pos').val($('.keyboardText').val());
					$('.ui-dialog-titlebar-close').click();
					activeInput = true;
				}
			});

		}
	}, 1);




	openedModal = true;
}


function selectFirstNavigable()
{
	focused = $('.navigable:visible').first();
	focused.addClass('selected');
	sessionStorage.setItem('focused', focused.attr('id'));
}

function startNavigation() {
	if($('.navigable').length) {
		var focused = sessionStorage.getItem('focused');
		$('.selected').removeClass('selected');
		// ръчно избирам първия елемент за селектед
		if(!focused ||  $('#' + focused ).length == 0){
			selectFirstNavigable();
		} else if (focused && !$('#' + focused ).hasClass('disabledBtn') && document.getElementById(focused) && $('.navigable.selected:visible').length == 0 && $('.navigable.contragentLinkBtns').length == 0) {
			$('#' + focused ).addClass('selected');
		}
		$('#result-holder .navigable:visible').keynav();
		if (!isItVisible($('.navigable.selected'))) {
			$('.navigable.selected')[0].scrollIntoView();
		}
	}
}

function isItVisible(element) {
	var viewportWidth = $(window).width(),
		viewportHeight = $(window).height(),
		documentScrollTop = $('.withTabs').scrollTop(),
		documentScrollLeft = $(document).scrollLeft(),

		elementOffset = element.offset(),
		elementHeight = element.height(),
		elementWidth = element.width(),

		minTop = documentScrollTop,
		maxTop = documentScrollTop + viewportHeight,
		minLeft = documentScrollLeft,
		maxLeft = documentScrollLeft + viewportWidth;

	return (elementOffset.top > minTop && elementOffset.top + elementHeight < maxTop) &&
		(elementOffset.left > minLeft && elementOffset.left + elementWidth < maxLeft);
}


function isInViewport(el){
	var rect     = el.getBoundingClientRect(),
		vWidth   = window.innerWidth || doc.documentElement.clientWidth,
		vHeight  = window.innerHeight || doc.documentElement.clientHeight,
		efp      = function (x, y) { return document.elementFromPoint(x, y) };

	// Return false if it's not in the viewport
	if (rect.right < 0 || rect.bottom < 0
		|| rect.left > vWidth || rect.top > vHeight)
		return false;


	// Return true if any of its four corners are visible
	return (
		el.contains(efp(rect.left,  rect.top))
		||  el.contains(efp(rect.right, rect.top))
		||  el.contains(efp(rect.right, rect.bottom))
		||  el.contains(efp(rect.left,  rect.bottom))
	);
}


function scrollToHighlight(){
	if ($(".highlighted").length && !isInViewport($(".highlighted")[0])) {
		$(".highlighted")[0].scrollIntoView({block: "end", inline: "end"});
	}
}

function render_scrollToHighlight() {
	scrollToHighlight();
}

function scrollAfterKey(){
	$(".highlighted")[0].scrollIntoView({block: "end", inline: "end"});
}

// Добавя хинт
function setInputPlaceholder() {
	var activeElement = $("div.operationBtn.active");
	var operation = getSelectedOperation();
	var placeholder = activeElement.attr("title");
	
	if(operation == 'payment'){
		var defaultPayment = $("input[name=ean]").attr("data-defaultpayment");
		if(defaultPayment){
			placeholder = defaultPayment;
		}
	}
	$("input[name=ean]").attr("placeholder", placeholder);	
}


/**
 * Изпълнява се след връщане на резултатите по ajax
 */
function afterload() {
	setInputPlaceholder();
	disableOrEnableEnlargeBtn();
	disableOrEnableCurrencyBtn();
}


/**
 * Активиране/скриване на бутона за Валутите
 */
function disableOrEnableCurrencyBtn()
{
	var value = $("input[name=ean]").val();
	
	if(!$('.currencyBtn').length) {
		
		return
	}
	
	if(!value.length || !$.isNumeric(value)){
		$('.currencyBtn').addClass('disabledBtn');
		$('.currencyBtn').prop('disabled', true);
	} else {
		$(".currencyBtn").removeClass('disabledBtn');
		$(".currencyBtn").prop("disabled", false);
	}
}


/**
 * Активира или закрива бутона за подробна информация на артикула
 */
function disableOrEnableEnlargeBtn()
{
	setTimeout(function () {
		var element = $(".navigable.selected");

		var operation = getSelectedOperation();
		if(operation == 'quantity' || operation == 'text' || operation == 'payment'){
			var selectedRow = $(".highlighted.productRow");
			element = selectedRow;
		}

		if(element.hasClass('enlargable')){
			var enlargeClassId = element.attr("data-enlarge-class-id");
			var enlargeObjectId = element.attr("data-enlarge-object-id");
			var enlargeTitle= element.attr("data-modal-title");

			if(enlargeClassId && enlargeObjectId && enlargeTitle) {
				$(".enlargeProductBtn").removeClass('disabledBtn');
				$(".enlargeProductBtn").removeAttr("disabled");

				$(".enlargeProductBtn").attr('data-modal-title', enlargeTitle);
				$(".enlargeProductBtn").attr('data-enlarge-class-id', enlargeClassId);
				$(".enlargeProductBtn").attr('data-enlarge-object-id', enlargeObjectId);
			}
		} else {
			$(".enlargeProductBtn").addClass('disabledBtn');
			$(".enlargeProductBtn").attr('disabled', 'disabled');
		}
	});

}


/**
 * Добавя артикул от натиснатия елемент в резултати
 */
function addProduct(el) {
	clearTimeout(timeout);

	var elemRow = $(el).closest('.receiptRow');
	$(elemRow).addClass('highlighted');
	var url = $(el).attr("data-url");
	var productId = $(el).attr("data-productId");
	var data = {productId:productId};

	// При добавяне на артикул ако в инпута има написано число или число и * да го третира като число
	var quantity = $("input[name=ean]").val();
	
	/*
	 * quantity = $.trim(quantity);
	 * quantity = quantity.replace("*", "");
	 * // Подаване и на количеството от инпута
	 * if(quantity && $.isNumeric(quantity) && quantity > 0){
	 * data.string = quantity;
	 * }
	 */
	data.recId = getSelectedRowId();
	
	processUrl(url, data);

	activeInput = false;
}


/**
 * Извиква подаденото урл с параметри
 */
function processUrl(url, params) {
	if(!url) return;

	resObj = new Object();
	resObj['url'] = url;

	if(params){
		getEfae().process(resObj, params);
	} else {
		getEfae().process(resObj);
	}
}


/**
 * Кой е селектирания ред
 */
function getSelectedRowId() {
	var selectedElement = $(".highlighted.productRow");
	
	return selectedElement.attr("data-id");
}

function openCurrentPosTab() {
	var activeTab = sessionStorage.getItem('selectedTab')
	$(".tabHolder  option#" + activeTab ).attr("selected", "selected");
	startNavigation();
}

/**
 * Редува два спана с цени
 */
function changePriceSpans(){
	setInterval(function (){
		$('.pos-notes .prices span').toggleClass('hidden');
	}, 1000);
}

/**
 * Извършва подадената операция
 */
function doOperation(operation, selectedRecId, forceSubmit) {
	clearTimeout(timeout);
	
	sessionStorage.removeItem("focused");
	var currentlySelected = getSelectedOperation();
	sessionStorage.setItem('lastSelectedOperation', currentlySelected);
	var lastHighlighted = sessionStorage.getItem('lastHighlighted');
	
	var operationBtn = $('.operationBtn[data-value="'+operation+'"]');
	
	var selectedRecId = getSelectedRowId();
	sessionStorage.setItem('lastHighlighted', selectedRecId);
	
	var url = operationBtn.attr("data-url");
	var disabled = operationBtn.hasClass("disabledBtn");
	
	if(!url || disabled){
		
		return;
	}
	
	// ако операцията е същата но стринга е празен да не се изпълнява заявката
	if(forceSubmit && operation == currentlySelected){
		submitInputString();
		
		return;
	}

	$("input[name=ean]").val("");

	sessionStorage.setItem('operationClicked', true);
	var data = {operation:operation,recId:selectedRecId};
	processUrl(url, data);

	activeInput = false;
	scrollToHighlight();
}


/**
 * Задава таймаута при търсенето
 */
function setSearchTimeout(timeout)
{
	searchTimeout = timeout;
}


/**
 * сетва флаг че артикул е добавен
 */
function render_toggleAddedProductFlag(data)
{
	addedProduct = data.flag;

	 $(document.body).on('keypress', ".large-field", function(e){
		if(e.key == "Enter" || e.key == "ArrowRight" || e.key == "ArrowLeft" || e.key == "ArrowUp" || e.key == "ArrowDown"  || e.key == "PageUp" || e.key == "PageDown" || e.key == 'Alt' || e.key == 'Control' || e.key == 'Escape' || e.key == 'F2') return;

		if(addedProduct) {
			$('.large-field.select-input-pos').val("");
			sessionStorage.removeItem("focused");
			sessionStorage.removeItem("focusedOffset");
			addedProduct = false;
		}
	});
}


/*
 * Активира таба
 */
function activateTab(element, timeOut)
{
	$('.tabHolder li').removeClass('active');
	element.addClass('active');
	
	triggerSearchInput($(".large-field"), timeOut, false);
}


/*
 * Търси по инпута ако може
 */
function triggerSearchInput(element, timeoutTime, keyupTriggered)
{
	// След всяко натискане на бутон изчистваме времето на изчакване
	clearTimeout(timeout);
	
	var url = element.attr("data-keyupurl");
	if(!url){
		return;
	}

	var inpVal = element.val();
	var operation = getSelectedOperation();

	if(isMicroformat(inpVal) && (operation == 'add' || operation == 'edit')){

		var selectedRecId = getSelectedRowId();
		doOperation(operation, selectedRecId, true);
		return;
	}
	
	if(inpVal.startsWith("*")){
		return;
	}
	
	var selectedElement = $(".highlighted.productRow");
	var selectedRecId = selectedElement.attr("data-id");

	// Правим Ajax заявката като изтече време за изчакване
	timeout = setTimeout(function(){
		resObj = new Object();
		resObj['url'] = url;
		
		var params = {operation:operation,search:inpVal,recId:selectedRecId};

		if ($(".tabHolder li.active").length) {
			var activeTab = $(".tabHolder li.active");
		} else {
			var activeTab = $('.tabHolder option:selected');
		}
		
		if(activeTab.length){
			var id = activeTab.attr("data-id");
			if(activeTab.parent().hasClass('receipts')){
				params.selectedReceiptFilter = id;
			} else {
				params.selectedProductGroupId = id;
			}
		}
		
		if(keyupTriggered){
			params.keyupTriggered = 'yes';
		}
		
		processUrl(url, params);

	}, timeoutTime);
}
