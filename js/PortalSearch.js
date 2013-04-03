$(document).ready(function () {
	
	if($(".recentlyFilter .hFormField input").val() == '') {
		$('.recentlyFilter .hFormField').hide();
	}
	
	if($(".noticeFilter .hFormField input").val() == '') {
		$('.noticeFilter .hFormField').hide();
	}
	
	$("#recentlySearchBtnPortal").live("click", function(){
		if($(".recentlyFilter .hFormField input").val() == ''){
			$('.recentlyFilter .hFormField').toggle();
		}
	});
	
	$("#noticeSearchBtnPortal").live("click", function(){
		if($(".noticeFilter .hFormField input").val() == ''){
			$('.noticeFilter .hFormField').toggle();
		}
	});
	
	$('.recentlyFilter').live("submit", (function(e) {
		if($(".recentlyFilter .hFormField select").val() == '' && $(".recentlyFilter .hFormField input").val() == ''){
			e.preventDefault();
		}
	}));
	
	$('.noticeFilter').live("submit", (function(e) {
		if($(".noticeFilter .hFormField select").val() == '' && $(".noticeFilter .hFormField input").val() == ''){
			e.preventDefault();
		}
	}));
});