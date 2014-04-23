function posActions() {

	// Засветяване на избрания ред и запис в хидън поле
	$(".pos-sale").live("click", function() {
		var id = $(this).attr("data-id");
		$(".pos-sale td").removeClass('pos-hightligted');
		$('[data-id="'+ id +'"] td').addClass('pos-hightligted');
		$("input[name=rowId]").val(id);
	});
	
	// Използване на числата за въвеждане в пулта
	$(".numPad").live("click", function() {
		var val = $(this).val();
		
		var inpVal = $("input[name=ean]").val();
		if(val == '.'){
			if(inpVal.length == 0){
				inpVal = 0;
			}
			
			if(inpVal.indexOf(".")  != -1){
				return;
			}
		}
		
		inpVal += val;
		$("input[name=ean]").val(inpVal);
	});
	
	// Използване на числата за въвеждане на суми за плащания
	$(".numPad2").live("click", function() {
		var val = $(this).val();
		
		var inpVal = $("input[name=paysum]").val();
		if(val == '.'){
			if(inpVal.length == 0){
				inpVal = 0;
			}
			
			if(inpVal.indexOf(".")  != -1){
				return;
			}
		}
		
		inpVal += val;
		$("input[name=paysum]").val(inpVal);
	});
	
	// Триене на числа в пулта
	$(".numBack").live("click", function() {
		var inpValLength = $("input[name=ean]").val().length;
		var newVal = $("input[name=ean]").val().substr(0, inpValLength-1);
		
		$("input[name=ean]").val(newVal);
	});
	
	// Триене на числа при плащанията
	$(".numBack2").live("click", function() {
		var inpValLength = $("input[name=paysum]").val().length;
		var newVal = $("input[name=paysum]").val().substr(0, inpValLength-1);
		
		$("input[name=paysum]").val(newVal);
	});
	
	// Модифициране на количество
	$("#tools-modify").live("click", function() {
		var inpVal = $("input[name=ean]").val();
		var rowVal = $("input[name=rowId]").val();
		
		var url = $(this).attr("data-url");
		var data = {recId:rowVal, amount:inpVal};
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
		$("input[name=ean]").val("");
	});
	
	// Добавя продукт при събмит на формата
	$("#toolsForm").on("submit",function(event){
	    var url = $("#toolsForm").attr("action");
		var code = $("input[name=ean]").val();
		var receiptId = $("input[name=receiptId]").val();
		var data = {receiptId:receiptId, ean:code};
		
		resObj = new Object();
		resObj['url'] = url;
		getEfae().process(resObj, data);
		
		$("input[name=ean]").val("");
		event.preventDefault();
	    return false; 
	});
	
	// Добавя продукт от комбо бокса
	$("#searchForm").on("submit",function(event){
		var url = $("#searchForm").attr("action");
		var productId = $("#searchForm select[name=productId]").val();
		var receiptId = $("#searchForm input[name=receiptId]").val();
		var data = {receiptId:receiptId, productId:productId};
		
		resObj = new Object();
		resObj['url'] = url;
		getEfae().process(resObj, data);
		
		event.preventDefault();
	    return false;
	});
	
	// Направата на плащане след натискане на бутон
	$(".paymentBtn").live("click", function() {
		var url = $(this).attr("data-url");
		var type = $(this).attr("data-type");
		var amount = $("input[name=paysum]").val();
		var receiptId = $("input[name=receiptId]").val();
		
		var data = {receiptId:receiptId, amount:amount, type:type};
		
		resObj = new Object();
		resObj['url'] = url;
		getEfae().process(resObj, data);
		$("input[name=paysum]").val("");
	});
	
	// Бутоните за приключване приключват бележката
	$(".closeBtns").live("click", function(event) {
		var url = $(this).attr("data-url");
		var receiptId = $("input[name=receiptId]").val();
		
		if(!url){
			return;
		}
		
		var data = {receipt:receiptId};
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
	});
	
	// Добавяне на продукти от бързите бутони
	$('.pos-product').live("click", function(event) {
		var url = $(this).attr("data-url");
		var productId = $(this).attr("data-id");
		var receiptId = $("input[name=receiptId]").val();
		
		var data = {receiptId:receiptId,productId:productId};
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
	});
	
	// Скриване на бързите бутони спрямо избраната категория
	$(".pos-product-category[data-id='']").addClass('active');
	$('.pos-product-category').live("click", function(event) {
		var value = $(this).attr("data-id");
		
		$(this).addClass('active');
		$(".active").not(this).removeClass('active');
		
		if(value) {
			var nValue = "|" + value + "|";
			
			$("div.pos-product[data-cat !*= '"+nValue+"']").each(function() {
				$(this).hide();
			});
			$("div.pos-product[data-cat *= '"+nValue+"']").each(function() {
				$(this).show();
			});
		} else {
			$("div.pos-product").each(function() {
				$(this).show();
			});
		}
	});
	
	// При клик на бутон изтрива запис от бележката
	$('.pos-del-btn').live("click", function(event) {
		var warning = $(this).attr("data-warning");
		var url = $(this).attr("data-url");
		var recId = $(this).attr("data-recId");
		if (!confirm(warning)){
			return false; 
		} else {
			
			resObj = new Object();
			resObj['url'] = url;
			
			getEfae().process(resObj, {recId:recId});
		}
	});
	
	$('.pos-tabs a ').on('click',function(e) {
		var currentAttrValue= $(this).attr('href');

		$('.tab-content' + currentAttrValue).show().siblings().hide();
		console.log(currentAttrValue);
		$(this).parent('li').addClass('active').siblings().removeClass('active');

		e.preventDefault();
	}); 
}
