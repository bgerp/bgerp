function resizeQuoteTable() {
	$('td[id^=product-row]').each(function(){
		 var id = $(this).attr('id');
		 id = id.replace('product-row', '');
		 //взимаме височината на клетката + падинг-а 
		 var heightAndPadding = $(this).outerHeight(); 
		 
		 var paddingTop = $(this).css("paddingTop");
		 paddingTop = parseInt(paddingTop);
		 
		 var paddingBottom = $(this).css("paddingBottom");
		 paddingBottom = parseInt(paddingBottom);
		
		 
		 var eH = heightAndPadding/$('td[class^=product-row'+id+']').length;
		 
		 //от получената височина вадим падинг-а на клетката
		 eH = eH - paddingTop - paddingBottom;
		 
		 $('td[class^=product-row'+id+']').each(function(){
		        $(this).height(eH);
		        
		 });
	});
}

function render_resizeQuoteTable(){
	resizeQuoteTable();
}