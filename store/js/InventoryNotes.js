function noteActions() {
	
	$(document.body).on('click', ".toggle-charge", function(e){
		var url = $(this).attr("data-url");
		if(!url) return;
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj);
	});
	
	$(document.body).on('click', ".inventoryNoteShowAddForm", function(e){
		var url = $(this).attr("data-url");
		var nextelement = $(this).attr("data-nextelement");
		
		if(!url) return;
		var data = {nextelement:nextelement};
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
	});
}

function cancelForm(form){
	var frm = $(form);
	frm.hide();
}

function submitAndCloseForm(form) {
	submitShowAddForm(form, true);
}

function submitShowAddForm(form, stop) {
	if (typeof stop === "undefined" || stop === null) { 
		stop = false; 
	}
	
	var frm = $(form);
	frm.css('cursor', 'wait');
	
	var params = frm.serializeArray();
	
	var serialized = $.param(params);
	
	$.ajax({
		type: frm.attr('method'),
		url: frm.attr('action'),
		data: serialized + '&ajax_mode=1&Cmd[default]=1',
		dataType: 'json'
	}).done( function(data) {
		var r1 = data[0];
		var id = r1['arg']['id'];
		var html = r1['arg']['html'];
		var hide = true;
		
		if(typeof data[0]['arg']['replaceFormOnError'] != 'undefined'){
			id = r1['arg']['replaceFormOnError'];
			hide = false;
		}
		
		id = "#" + id;
		
		$(id).html(html);
		
		if(typeof data[1] != 'undefined'){
			var r2 = data[1];
			var id2 = r2['arg']['id'];
			var html2 = r2['arg']['html'];
			id2 = "#" + id2;
			$(id2).html(html2);
		}
		
		if(hide == true){
			frm.hide();
		}
		
		if(stop == true){
			return;
		}
		
		if(typeof data[2] != 'undefined'){
			var r3 = data[2];
			var nextelement = r3['arg']['nextelement'];
			
			var event = jQuery.Event("click");
			$("#" + nextelement).trigger(event);
		}
	});
}