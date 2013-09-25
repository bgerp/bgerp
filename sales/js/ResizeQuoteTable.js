$(document).ready(function () {
	$('td[id^=product-row]').each(function(){
		 var id = $(this).attr('id');
		 id = id.replace('product-row', '');
		 //взимаме височината на клетката + падинг-а 
		 var h = $(this).outerHeight(); 
		 var eH = h/$('td[class^=product-row'+id+']').length;
		 //от получената височина вадим падинг-а на клетката
		 eH = eH - 10;
		 $('td[class^=product-row'+id+']').each(function(){
		        $(this).height(eH);
		 });
	});
});