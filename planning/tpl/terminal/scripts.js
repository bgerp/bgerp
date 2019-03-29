function planningActions() {
	$("input[name=serial]").focus();
	
	// Използване на числата за въвеждане на суми за плащания
	$(document.body).on('click', "#sendBtn", function(e){
		var url = $(this).attr("data-url");
		if(!url) return;
		
		resObj = new Object();
		resObj['url'] = url;
		
		var serial = $("input[name=serial]").val();
		var type = $("#typeSelect").is('[readonly]') ?  $("input[name=type]").val() : $("#typeSelect").val();
		var productId = $("#productIdSelect").is('[readonly]') ? $("input[name=productId]").val() :  $("#productIdSelect").val() ;
		var quantity = $("input[name=quantity]").val();
		var employees = $("select#employeeSelect").length ? $("select#employeeSelect").val() : $('input[id^="employees"]').is(':checked') ? $('input[id^="employees"]').val() : null;
		var weight = $("input[name=weight]").val();
		var fixedAsset = $("#fixedAssetSelect").val();
		var taskId = $("input[name=taskId]").val();

		var data = {serial:serial,taskId:taskId,productId:productId,quantity:quantity,employees:employees,fixedAsset:fixedAsset,weight:weight,type:type};
		console.log(data);
		getEfae().process(resObj, data);
		$("input[name=serial]").val("");
		$("input[name=serial]").focus("");
		if($('.select2').length){
			$('select').trigger("change");
		}
		
	});
	
	$(document.body).on('click', "tr.terminal-task-row", function(e){
		var url = $(this).attr("data-url");
		if(!url) return;
		
		resObj = new Object();
		resObj['url'] = url;
		getEfae().process(resObj);

		if($('.select2').length){
			$('select').trigger("change");
		}
	});

	var currentTab = $('.tabs-holder li.active a').attr('href');
	$('.tabContent' + currentTab).addClass('active');

	// Скриване на табовете
	$(document.body).on('click', ".tabs-holder li:not('.disabled') a ", function(e){
		var currentAttrValue= $(this).attr('href');
		$('.tabContent' + currentAttrValue).show().siblings().hide();
		$(this).parent('li').addClass('active').siblings().removeClass('active');
		
		e.preventDefault();
	});
}

// Кой таб да е активен
function render_activateTab(data)
{
	if(data.selectedTask){
		$('#tab-progress').removeClass('disabled');
		$('#tab-job').removeClass('disabled');
		$('#tab-task').removeClass('disabled');
		$('#tab-progress a').click();
	}
}
