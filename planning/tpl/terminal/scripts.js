function planningActions() {
	$("input[name=serial]").focus();

	// Използване на числата за въвеждане на суми за плащания
	$(document.body).on('click', ".tab-link", function(e){
		var url = $(this).attr("data-url");
		if(!url) return;
		
		if($(this).parent().hasClass( "disabled" )){
			return;
		}
		
		resObj = new Object();
		resObj['url'] = url;
		
		console.log(url);
		getEfae().process(resObj);
		
		$("input[name=serial]").val("");
		$("input[name=serial]").focus("");
		if($('.select2').length){
			$('select').trigger("change");
		}
	});
	
	
	// Изпращане на формата за прогреса
	$(document.body).on('click', "#sendBtn", function(e){
		var url = $(this).attr("data-url");
		if(!url) return;
		
		resObj = new Object();
		resObj['url'] = url;
		
		var serial = $("input[name=serial]").val();
		var action = $("#actionIdSelect").is('[readonly]') ? $("input[name=action]").val() :  $("#actionIdSelect").val() ;
		var res = action.split('|');
		var type = res[0];
		var productId = res[1];
		
		var quantity = $("input[name=quantity]").val();
		var employees = $("select#employeeSelect").length ? $("select#employeeSelect").val() : $('input[id^="employees"]').is(':checked') ? $('input[id^="employees"]').val() : null;
		var weight = $("input[name=weight]").val();
		var fixedAsset = $("#fixedAssetSelect").val();
		var taskId = $("input[name=taskId]").val();

		var data = {serial:serial,taskId:taskId,productId:productId,quantity:quantity,employees:employees,fixedAsset:fixedAsset,weight:weight,type:type};
		
		getEfae().process(resObj, data);
		$("input[name=serial]").val("");
		$("input[name=serial]").focus("");
		if($('.select2').length){
			$('select').trigger("change");
		}
		
		
	});

	$(document.body).on('click', ".changeTab ", function(e){
		setCookie('terminalTab', "tab-progress");
	});

	var menutabInfo = getCookie('terminalTab');
	var currentTab = $('#' + menutabInfo).addClass('active').find('a').attr('href');
	$('.tabContent' + currentTab).addClass('active');

	// Скриване на табовете
	$(document.body).on('click', ".tabs-holder li:not('.disabled') a ", function(e){
		var currentAttrValue= $(this).attr('href');
		var currentId = $(this).parent().attr('id');
		
		$('.tabContent' + currentAttrValue).show().siblings().hide();
		$(this).parent('li').addClass('active').siblings().removeClass('active');
		if($('.serialField').length) $('.serialField').focus();
		setCookie('terminalTab', currentId);
		
		e.preventDefault();
	});
	
	
	// Търсене по баркод
	$(document.body).on('click', "#searchBtn", function(e){
		var url = $(this).attr("data-url");
		if(!url) return;
		
		var searchVal = $("input[name=searchBarcode]").val();
		resObj = new Object();
		resObj['url'] = url;
		
		var data = {search:searchVal};
		getEfae().process(resObj, data);
		
		console.log(url,searchVal);
	});
	
	// При клик на полето за баркод да се отваря приложение
	$(document.body).on('click', ".scanElement", function(e){
		var url = $(this).attr("data-url");
		if(!url) return;
		
		$(location).attr('href', url);
	});
}

// Кой таб да е активен
function render_activateTab(data)
{
	if(data.tabId){
		$("#" + data.tabId).removeClass('disabled');
		$("#" + data.tabId).addClass('active')
		$("#" + data.tabId).siblings().removeClass('active');
		
		var currentAttrValue= $("#" + data.tabId + " a").attr('href');
		$('.tabContent' + currentAttrValue).show().siblings().hide();
		
		setCookie('terminalTab',  data.tabId);
		return;
	}
}

function render_prepareKeyboard()
{
	$('.nmpd-wrapper').remove();

	setTimeout(function(){
		$('#numPadBtn').numpad({gridTpl: '<div class="holder"><table></table></div>',
			target: $('.quantityField')
		});
		$('#weightPadBtn').numpad({gridTpl: '<div class="holder"><table></table></div>',
			target: $('.weightField')
		});
	}, 500);
}


/**
 * Подготовка на клавиатурата
 */
function prepareKeyboard()
{
	$('#numPadBtn').numpad({gridTpl: '<div class="holder"><table></table></div>',
		target: $('.quantityField')
	});
	
	$('#weightPadBtn').numpad({gridTpl: '<div class="holder"><table></table></div>',
		target: $('.weightField')
	});
	
	$('#serialPadBtn').numpad({gridTpl: '<div class="holder"><table></table></div>',
		target: $('.serialField')
	});
}


/**
 * Създава бисквитка
 */
function setCookie(key, value) {
	var expires = new Date();
	expires.setTime(expires.getTime() + (1 * 24 * 60 * 60 * 1000));
	document.cookie = key + '=' + value + ';expires=' + expires.toUTCString() + "; path=/";
}


/**
 * Чете информацията от дадена бисквитка
 */
function getCookie(key) {
	var keyValue = document.cookie.match('(^|;) ?' + key + '=([^;]*)(;|$)');
	return keyValue ? keyValue[2] : null;
}