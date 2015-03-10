function chosenrefresh() {
	$(document.body).on('change', ".balance-grouping", function(){
		var id = $(this).attr("id");
		var name = $(this).attr("name");
		lastChar = name.substr(name.length - 1);
		lastChar = lastChar.toString(); 
		var name = "feat" +lastChar;
		
		$("form select[name='feat"+lastChar+"']").val('');
		$("select[name='feat"+lastChar+"']").trigger('liszt:updated');
	});
	$(document.body).on('change', ".balance-feat", function(){
		var name = $(this).attr("name");
		lastChar = name.substr(name.length - 1);
		lastChar = lastChar.toString(); 
		var name = "grouping" + lastChar;
		$("form input[name='"+name+"']").val('');
		$("form select[name='"+name+"']").val('');
		$('select[name="'+name+'"]').trigger('liszt:updated');
	});
}