$(document).ready(function () {
	$('td[id^=product-row]').each(function(){
		 var id = $(this).attr('id');
		 id = id.replace('product-row', '');
		 var h = $(this).height();
		 var eH = h/$('td[class^=product-row'+id+']').length;
		 $('td[class^=product-row'+id+']').each(function(){
		        $(this).height(eH);
		 });
	});
});