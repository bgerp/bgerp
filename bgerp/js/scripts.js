$(document).ready(function () {
	
	// Ако формата за търсене е празна, я скриваме
	if($(".noticeFilter .hFormField input").val() == '') {
		$('.noticeFilter .hFormField').hide();
	}
	
	if($(".recentlyFilter .hFormField input").val() == '') {
		$('.recentlyFilter .hFormField').hide();
	}
	
	$("#noticeSearchBtnPortal").live("click", function(){
		if($(".noticeFilter .hFormField input").val() == ''){
			$('.noticeFilter .hFormField').toggle();
		}
	});
	
	$("#recentlySearchBtnPortal").live("click", function(){
		if($(".recentlyFilter .hFormField input").val() == ''){
			$('.recentlyFilter .hFormField').toggle();
		}
	});
	
	$('.noticeFilter form').live("submit", (function(e) { 
		if($(".noticeFilter .hFormField select").val() == '' && $(".noticeFilter .hFormField input").val() == ''){
			e.preventDefault();
		}
	}));
	
	$('.recentlyFilter form').live("submit",(function(e) { 
		if($(".recentlyFilter .hFormField select").val() == '' && $(".recentlyFilter .hFormField input").val() == ''){
			e.preventDefault();
		}
	}));
});