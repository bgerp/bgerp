function expEngine(commands, url, formData) {
	
	if(url == '') {
		url = globalUrl;
	}

	globalUrl = url;

	// Изкарваме ajaxLoader
	$('#ajaxLoader').show();
	
	var query='';

	//Подготвяме и изпращаме заявката
	$.each(commands, function(id, val){
		query = query + id + '=' + escape(val) ; 
		query = query + '&';
		} );
	if(formData) {
		query = query + formData;
	}
	//alert(url + ((url.indexOf('?')>0)?'':'?')+query);
	$.getJSON(  url + ((url.indexOf('?')>0)?'&':'?')+query ,
				function(data){  
					$('#ajaxLoader').hide(); 
					processExpertForm(data);
				}
     );

}

function processExpertForm(data) {
	
	// Ако имаме редирект - правим го
	if(data.alert) {
		alert(data.alert);
	}

	// Ако имаме редирект - правим го
	if(data.redirect) {
		document.location.href = data.redirect;
	}


	// Показваме бутоните на диалога
	var btn = {};
	if(data.btn.back) {
		btn['« Връщане']  = function() {
							var formData = $('#expertForm').serialize();
							expEngine({'AjaxCmd':'back'}, '', formData);
						}
	}

	if(data.btn.next) {
		btn['Продължение »']  = function() {
							var formData = $('#expertForm').serialize();
							expEngine({'AjaxCmd':'next'}, '', formData);
						}
	}
	

	if(data.btn.cancel) {
		btn['Отказ']  = function() {
							var formData = $('#expertForm').serialize();
							expEngine({'AjaxCmd':'cancel'}, '', formData);
						}
	}

	if(data.btn.close) {
		btn['Затваряне']  = function() {
							var formData = $('#expertForm').serialize();
							expEngine({'AjaxCmd':'close'}, '', formData);
		}
	}

	

	$('#expertDialog').dialog( 'option' , 'buttons' , btn);

	$('#expertDialog').html(data.msg);

	if(data.dialogWidth) {
		$('#expertDialog').dialog( 'option' , 'width' , data.dialogWidth);
	}
	
	if(data.dialogHeight) {
		$('#expertDialog').dialog( 'option' , 'height' , data.dialogHeight);
	}

	$('#expertDialog').dialog( 'option' , 'title' , data.title);

 	$('#expertDialog').dialog('open');	
}

