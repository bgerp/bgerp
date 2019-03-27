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
		var employees = $("#employeeSelect").val();
		var fixedAsset = $("#fixedAssetSelect").val();
		var weight = $("input[name=weight]").val();
		var taskId = $("input[name=taskId]").val();
		
		var data = {serial:serial,taskId:taskId,productId:productId,quantity:quantity,employees:employees,fixedAsset:fixedAsset,weight:weight,type:type};
		console.log(data);
		getEfae().process(resObj, data);
		$("input[name=serial]").val("");
		$("input[name=serial]").focus("");
		
	});
	
	$(document.body).on('click', "tr.terminal-task-row", function(e){
		var url = $(this).attr("data-url");
		if(!url) return;
		
		resObj = new Object();
		resObj['url'] = url;
		getEfae().process(resObj);
	});

	var currentTab = $('.tabs-holder li.active a').attr('href');
	$('.tabContent' + currentTab).addClass('active');

	// Скриване на табовете
	$(document.body).on('click', ".tabs-holder li a ", function(e){
		var currentAttrValue= $(this).attr('href');
		$('.tabContent' + currentAttrValue).show().siblings().hide();
		$(this).parent('li').addClass('active').siblings().removeClass('active');
		e.preventDefault();
	});
}