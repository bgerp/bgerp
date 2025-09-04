var dialog;
var activeInput;
var timeout;
var timeoutRemoveDisabled;
var timeoutPageNavigation;
var searchTimeout;
var addedProduct;
let pressedCardPayment;

function posActions() {
	setOpenedReceiptQueue();
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

	$(document.body).on('click', ".closePaymentModal", function(e){
		$(".fullScreenCardPayment").css("display", "none");
		let msg = pressedCardPayment.attr("data-oncancel");
		$("#modalTitleSpan").text('');
        $("#modalTitleSubSpan").text('');
		render_showToast({timeOut: 800, text: msg, isSticky: true, stayTime: 8000, type: "error"});
	});

	$(document.body).on('click', ".confirmPayment", function(e){
		let url = pressedCardPayment.attr("data-url");
		let type = pressedCardPayment.attr("data-type");
		let deviceId = pressedCardPayment.attr("data-deviceId");

		doPayment(url, type, 'manual', deviceId);
		$(".fullScreenCardPayment").css("display", "none");
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

	//  ресет бутона на изтрива текста в инпута
	$(document.body).on('click', ".close-icon", function(e){
		$('.large-field').val("");

		// След изтриване, се тригърва, че все едно ръчно е изтрито
		triggerSearchInput($(".large-field"), 0, true);
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
		if(e.key == "Enter" || e.key == "ArrowRight" || e.key == "ArrowLeft" || e.key == 'Shift' || e.key == "ArrowUp" || e.key == "ArrowDown"  || e.key == "PageUp" || e.key == "PageDown" || e.key == 'Alt' || e.key == 'Control' || e.key == 'Escape' || e.key == 'F2') return;

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

		var timeOffset = Date.now() - oldTime;

		// при double click да изпраща веднъж
		// или при touch устройства
		if(timeOffset > 400 || isTouchDevice()) {
			localStorage.setItem("resultScroll", $('#result-holder.fixedPosition .withTabs').scrollTop());
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

	document.querySelectorAll('.scrollingGrid').forEach(grid => {
		let isDown = false;
		let startX;
		let scrollLeft;

		grid.addEventListener('mousedown', (e) => {
			isDown = true;
			grid.classList.add('dragging');   // за стил по желание
			startX = e.pageX - grid.offsetLeft;
			scrollLeft = grid.scrollLeft;
		});

		grid.addEventListener('mouseleave', () => {
			isDown = false;
			grid.classList.remove('dragging');
		});

		grid.addEventListener('mouseup', () => {
			isDown = false;
			grid.classList.remove('dragging');
		});

		grid.addEventListener('mousemove', (e) => {
			if (!isDown) return;
			e.preventDefault(); // за да не маркира текст
			const x = e.pageX - grid.offsetLeft;
			const walk = (x - startX); // разстояние на влачене
			grid.scrollLeft = scrollLeft - walk;
		});
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
	$(document.body).on('click', ".newContragentBtn", function(e){
		presssNavigable(this);
	});
	
	// При натискане на бутона за клавиатура
	$(document.body).on('click', ".helpBtn", function(e){
		clearTimeout(timeout);
		openHelp();
	});

	$("body").setShortcutKey( CONTROL , BACK_SPACE ,function() {
		openPrev();
	});

	$("body").setShortcutKey( CONTROL , Y ,function() {
		openNext();
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

	$(document.body).on('dblclick', 'input[name="ean"]', function () {
		var operation = getSelectedOperation();
		if(operation != 'add') return;

		var ph = $.trim($(this).attr('placeholder'));   // placeholder-ът като текст
		if (!ph || !$.isNumeric(ph.replace(',', '.'))) return;

		$(this).val(ph + '*');           // записваме стойност + звездичка
	});
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
		$('#keyboard-num').css('display','block');
		$('.buttons').removeClass('oneRow');
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
function doPayment(url, type, value, deviceId){

	if(!url || !type) return;

	var amount = $("input[name=ean]").val();
	if(!amount){
		amount = $("input[name=ean]").attr('data-defaultpayment');
	}
	
	var data = {amount:amount, type:type};
	if(value){
		data.param = value;
	}
	if(deviceId){
		data.deviceId = deviceId;
	}

	console.log(data);
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

	// Ако е отворен модал да се игнорира ентера
	if (openedModal) {
		console.log('ENTER STOPPED OPEN MODAL');

		return;
	}

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
	console.log('enter');
	localStorage.setItem("resultScroll", $('#result-holder.fixedPosition .withTabs').scrollTop());
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
		var warning = element.attr("data-warning");
		if(warning){
			if (!confirm(warning)) return false;
		}

		var sendAmount = element.attr("data-sendamount");
		if(sendAmount == 'yes'){

			var maxamount = parseFloat(element.attr("data-maxamount")).toFixed(2);
			var amount = $("input[name=ean]").val();
			if(!amount){
				amount = $("input[name=ean]").attr('data-defaultpayment');
			}

			if(!$.isNumeric(amount) || amount == 0){
				var msg = element.attr("data-notnumericmsg");
				render_showToast({timeOut: 800, text: msg, isSticky: true, stayTime: 8000, type: "error"});
				return;
			}

			amount = parseFloat(amount).toFixed(2);

			if(parseFloat(amount) > parseFloat(maxamount)){
				console.log("AM " + amount + ' MAX ' + maxamount);
				var msg = element.attr("data-amountoverallowed");
				render_showToast({timeOut: 800, text: msg, isSticky: true, stayTime: 8000, type: "error"});
				return;
			}

			pressedCardPayment = element;

			let deviceUrl = element.attr("data-deviceUrl");
			let comPort = element.attr("data-deviceComPort");
			let deviceName = element.attr("data-deviceName");
            let subTitle = element.attr('data-modal-subTitle')

			console.log('SEND:' + amount + " TO " + deviceUrl + "/ cPort " + comPort);
			$(".fullScreenCardPayment").css("display", "block");
			$('.select-input-pos').prop("disabled", true);
			$("#modalTitleSpan").text(" " + deviceName);
            $("#modalTitleSubSpan").text(" " + subTitle);
			let fncName = element.attr("data-sendfunction");
			window[fncName](amount, deviceUrl, comPort);
			return;
		} else {
			type = (!type) ? '-1' : type;
			doPayment(url, type, null, null);
			return;
		}
	} else if(element.hasClass('contragentRedirectBtn')){
		
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
		
	} else if(element.hasClass("newContragentBtn") || element.hasClass("locationBtn")){
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


function showPaymentErrorStatus()
{
	var error = pressedCardPayment.attr("data-onerror");
	render_showToast({timeOut: 800, text: error, isSticky: true, stayTime: 8000, type: "error"});
}

/**
 * Връща резултат при успешно свързване с банковия терминал
 *
 * @param res
 */
function getAmountRes(res, sendAmount)
{
	let element = pressedCardPayment;
	let url = element.attr("data-url");
	console.log("ANSWER FROM: " + url);
	$('.select-input-pos').prop("disabled", false);

	console.log("RES: " + res + " S " + sendAmount);
	let resString = String(res);
	if (resString.startsWith("OK") || res == 'OK') {

		let parts = resString.split("|");
		let rightPart = parts.slice(1).join("|"); // всичко след първото "|"
		console.log("RIGHT PART: " + rightPart);

		// Вземаме само първия елемент от rightPart, ако има няколко
		let firstNumberStr = rightPart.split("|")[0];

		// Парсираме като число и форматираме до 2 дес. знака
		let resAmount = parseFloat(firstNumberStr).toFixed(2);
		let sendAmountFormatted = parseFloat(sendAmount).toFixed(2);

		if(!rightPart || resAmount === sendAmountFormatted){
			let deviceId = pressedCardPayment.attr("data-deviceId");
			let type = element.attr("data-type");
			console.log("RES IS OK");
			doPayment(url, type, 'card', deviceId);
		} else {
			console.log("DIFF AMOUNT");
			let error = pressedCardPayment.attr("data-diffamount");
			error += " " + resAmount;
			render_showToast({timeOut: 800, text: error, isSticky: true, stayTime: 8000, type: "error"});
		}
	} else {
		showPaymentErrorStatus();
		console.log("RES ERROR /" + res + "/");
	}

	$(".fullScreenCardPayment").css("display", "none");
}


/**
 * Връща резултат при грешка със свързването с банковия терминал
 *
 * @param res
 */
function getAmountError(err)
{
	$(".fullScreenCardPayment").css("display", "none");
	$('.select-input-pos').prop("disabled", false);
	$("#modalTitleSpan").text('');
    $("#modalTitleSubSpan").text('');

	showPaymentErrorStatus();
	console.log("ERR:" + err);

	pressedCardPayment = null;
}


/**
 * Извикване на урл-то след потвърждение на предупреждението
 */
function confirmAndRedirect(warning, url)
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
		if(string.endsWith("*") || string.startsWith("*") || string.startsWith("+") || string.startsWith("-")){

			// Премахваме * да остане чист стринг
			let quantity = string.replace("*", "");
			quantity = quantity.replace("+", "");
			quantity = quantity.replace("-", "");
			quantity = quantity.replace(",", ".");
			console.log("NUM: '" + string + "' Q:" + quantity);
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
					
					let split = quantity.split(".");
					let cnt = (split[1]) ? split[1].length : 0;
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
	var height = (heightModal == "smallHeight" ) ?  500 : 740;
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
	if ($(".highlighted").length) {
		$(".highlighted")[0].scrollIntoView(false);
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
	var scroll = localStorage.getItem("resultScroll");
	if(scroll) {
		$('#result-holder.fixedPosition .withTabs').scrollTop(scroll)
	}
	$("input[name=ean]").attr("placeholder", placeholder);
	$("input[name=ean]").attr("data-original-placeholder", placeholder);
}


/**
 * Изпълнява се след връщане на резултатите по ajax
 */
function afterload() {
	setInputPlaceholder();
	disableOrEnableEnlargeBtn();
	disableOrEnableCurrencyBtn();

	if (typeof readWeightScale === 'function') {
		readWeightScale();
	}
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

	$('.scrollingGrid .fadedElement').removeClass('fadedElement');
	$(el).addClass('fadedElement');
	sessionStorage.setItem('changedOpacityElementId', $(el).attr("id"));
	clearTimeout(timeout);

	let elemRow = $(el).closest('.receiptRow');
	$(elemRow).addClass('highlighted');
	let url = $(el).attr("data-url");
	let productId = $(el).attr("data-productId");
	let data = {productId:productId};

	let weightSysId = $(el).attr("data-weight-measure-sys-id");

	// При добавяне на артикул ако в инпута има написано число или число и * да го третира като число
	var quantity = $("input[name=ean]").val();

	if(quantity.length){
		if(isMicroformat(quantity)){
			if(quantity.endsWith("*")){
				quantity = quantity.replace("*", "");
				data.string = quantity;
			}
		}
	} else {
		if (typeof readWeightScale === 'function' && weightSysId) {
			let scaleVal =  document.querySelector("input[name=ean]").getAttribute("placeholder")
			if($.isNumeric(scaleVal)){
				if(weightSysId == 'g'){
					scaleVal = parseFloat(scaleVal) * 1000;
				}
				data.string = scaleVal;
			}
		}
	}

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


/**
 * Автоматично разпечатване на КБ
 */
function render_autoFiscPrintIfPossible()
{
	$( ".printFiscBtn" ).trigger( "click" );
}

/**
 * Възстановява опаситито на бутоните
 */
function render_restoreOpacity()
{
	var restoreOpacityId = sessionStorage.getItem('changedOpacityElementId');
	$("#" + restoreOpacityId).removeClass('fadedElement');

	sessionStorage.removeItem("changedOpacityElementId");
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

	// Ако е засечен микроформат няма да се търси, ще се чака потребителя да направи нещо
	if(isMicroformat(inpVal) && (operation == 'add' || operation == 'edit')){
		console.log('MICROFORMAT waiting');
		return;
	}
	console.log('trigger SEARCH:' + operation + ' - ' + inpVal);

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
                if(id){
                    params.selectedProductGroupId = id;
                } else {
					let gridClass = activeTab.attr("data-grid");
					let element = $('.' + gridClass)[0];
					if (element) {

						if (!$(element).find('.navigable:visible').first().hasClass('selected') && !$(element).find('.navigable.selected').length) {
							selectFirstInRow($(element));
						} else {
							// дори да е селектиран, не скролирай ако вече е видим
							const $first = $(element).find('.navigable:visible').first();
							const container = $(element).closest('.scrollingGrid')[0];
							if (container && !isVisibleInContainer($first[0], container, 4)) {
								const prev = container.style.scrollBehavior;
								container.style.scrollBehavior = 'auto';
								$first[0].scrollIntoView({ block: 'nearest', inline: 'nearest' });
								container.style.scrollBehavior = prev || '';
							}
						}
					}
                }
			}
		}
		
		if(keyupTriggered){
			params.keyupTriggered = 'yes';
		}
		
		processUrl(url, params);

	}, timeoutTime);
}

function isVisibleInContainer(el, container, margin = 0) {
	const r = el.getBoundingClientRect();
	const c = container.getBoundingClientRect();
	return (
		r.left   >= c.left + margin &&
		r.right  <= c.right - margin &&
		r.top    >= c.top + margin &&
		r.bottom <= c.bottom - margin
	);
}

function selectFirstInRow($row) {
	const $first = $row.find('.navigable:visible').first();
	if (!$first.length) return;

	// 1) селекция без click
	$('.navigable.selected').removeClass('selected');
	$first.addClass('selected');

	const id = $first.attr('id');
	if (id) sessionStorage.setItem('focused', id);

	// 2) скрол само при нужда И спрямо контейнера
	const container = $row.closest('.scrollingGrid')[0] || document.scrollingElement;
	if (!isVisibleInContainer($first[0], container, 4)) {
		const prev = container.style.scrollBehavior;
		container.style.scrollBehavior = 'auto';        // без плавност => без подскачане
		$first[0].scrollIntoView({ block: 'nearest', inline: 'nearest' });
		container.style.scrollBehavior = prev || '';
	}
}


/**
 * Записва в сесията че бележката е отваряна
 */
function setOpenedReceiptQueue()
{
	let openedReceiptArr = JSON.parse(localStorage.getItem("openedReceipts"));
	if(!openedReceiptArr){
		openedReceiptArr = [];
	}
	let url = $(location).attr('href');
	url = url.split('&')[0];

	if(jQuery.inArray(url, openedReceiptArr) === -1){
		openedReceiptArr.push(url);
		localStorage.setItem("openedReceipts", JSON.stringify(openedReceiptArr));
	}
}


/**
 * Отваря предишната отваряна бележка
 */
function openPrev()
{
	let openedReceiptArr = JSON.parse(localStorage.getItem("openedReceipts"));
	let url = $(location).attr('href');

	let key = jQuery.inArray(url, openedReceiptArr);
	if(key > 0){
		let prevReceiptUrl = openedReceiptArr[key-1];
		if(prevReceiptUrl){
			location.href = prevReceiptUrl;
		}
	}
}


/**
 * Отваря следващата отваряна бележка
 */
function openNext()
{
	let openedReceiptArr = JSON.parse(localStorage.getItem("openedReceipts"));
	let url = $(location).attr('href');

	let key = jQuery.inArray(url, openedReceiptArr);
	let nextReceiptUrl = openedReceiptArr[key+1];
	if(nextReceiptUrl){
		location.href = nextReceiptUrl;
	}
}

