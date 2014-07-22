var shortURL;


function runOnLoad(functionName)
{
	if(window.attachEvent) {
		window.attachEvent('onload', functionName);
	} else {
		if(window.onload) {
			var curronload = window.onload;
			var newonload = function() {
				curronload();
				functionName();
			};
			window.onload = newonload;
		} else {
			window.onload = functionName;
		}
	} 
}


// Функция за лесно селектиране на елементи
function get$()
{
	var elements = new Array();
	for (var i = 0; i < arguments.length; i++) {
		var element = arguments[i];
		if (typeof element == 'string')
			element = document.getElementById(element);
		if (arguments.length == 1)
			return element;
		elements.push(element);
	}
	return elements;
}


function createXHR() 
{
	var request = false;
	try {
		request = new ActiveXObject('Msxml2.XMLHTTP');
	}
	catch (err2) {
		try {
			request = new ActiveXObject('Microsoft.XMLHTTP');
		}
		catch (err3) {
			try {
				request = new XMLHttpRequest();
			}
			catch (err1) 
			{
				request = false;
			}
		}
	}
	
	return request;
}


function ajaxRefreshContent(url, sec, id)
{
	var xmlHttpReq = createXHR();

	xmlHttpReq.open('GET', url, true);

	xmlHttpReq.onreadystatechange = function() {												
	
		if (xmlHttpReq.readyState == 4) {
			if(xmlHttpReq.responseText.length > 0) {
 					
				if (xmlHttpReq.responseText){
					try{
						var res = JSON.parse(xmlHttpReq.responseText);
					}catch(e){
						// Debug ?
						// alert(xmlHttpReq.responseText);
					}
				}
				
				if(res) {
					if(res.content) { 
						if(get$(id).innerHTML !=  res.content) {
							get$(id).innerHTML =  res.content;
						}
					}

					if(res.alert) {
						alert(res.alert);
					}

					if(res.script) {
						eval(res.script);
					}
				}

 			}
		}
	}
	
	xmlHttpReq.send(null);

	setTimeout(function(){ajaxRefreshContent(url, sec, id)}, sec);
}



//XMLHttpRequest class function
function efAjaxServer() {};

efAjaxServer.prototype.iniciar = function() 
{
	try {
		// Mozilla & Safari
		this._xh = new XMLHttpRequest();
	} catch (e) {
		// Explorer
		var _ieModelos = new Array(
		'MSXML2.XMLHTTP.5.0',
		'MSXML2.XMLHTTP.4.0',
		'MSXML2.XMLHTTP.3.0',
		'MSXML2.XMLHTTP',
		'Microsoft.XMLHTTP'
		);
		var success = false;
		for (var i=0;i < _ieModelos.length && !success; i++) {
			try {
				this._xh = new ActiveXObject(_ieModelos[i]);
				success = true;
			} catch (e) {
			}
		}
		if ( !success ) {
			return false;
		}
		return true;
	}
}

efAjaxServer.prototype.ocupado = function() 
{
	estadoActual = this._xh.readyState;
	return (estadoActual && (estadoActual < 4));
}

efAjaxServer.prototype.procesa = function() 
{
	if (this._xh.readyState == 4 && this._xh.status == 200) {
		this.procesado = true;
	}
}

efAjaxServer.prototype.get = function( params ) 
{
	if (!this._xh) {
		this.iniciar();
	}

	if(typeof(params) == 'object') {
		var urlget = '/root/bgerp/?';

		if(params.relative_web_root) {
			 urlget = params['relative_web_root'] + '/' + urlget;
		}

		var amp    = '';
		
		// Генерираме UTL-то
		for( var n in params) {
			urlget = urlget + amp + n + '=' + encodeURIComponent(params[n]);
			amp = '&';
		}
	} else {
		var urlget = params;
	}

	// Изпращаме заявката и обработваме отговора
	if (!this.ocupado()) {
 		this._xh.open("GET", urlget, false);
		this._xh.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		this._xh.send(urlget);
		if (this._xh.readyState == 4 && this._xh.status == 200) {
			return  eval('(' + this._xh.responseText + ')');  
		}
	}
	
	//alert(this._xh.readyState);

	return false;
}


//връща информация за браузъра
function getUserAgent()
{
	return navigator.userAgent;
}


// Проверява дали браузърът е IE
function isIE()
{
  return /msie/i.test(navigator.userAgent) && !/opera/i.test(navigator.userAgent);
}


function getIEVersion () {
	  var myNav = navigator.userAgent.toLowerCase();
	  return (myNav.indexOf('msie') != -1) ? parseInt(myNav.split('msie')[1]) : false;
}


// Инициализира комбобокса
function comboBoxInit(id, suffix) 
{ 
	var txtCombo = get$(id);
	var selCombo = get$(id + suffix);

	if(txtCombo && selCombo) {
		var width = txtCombo.offsetWidth;
		var arrow = 22;
 
		selCombo.style.width = (width+1) + 'px'; 
		txtCombo.style.width = (width -  arrow + 6 ) + 'px';
		txtCombo.style.marginRight = (arrow - 5) + 'px';
		selCombo.style.clip = 'rect(auto, auto, auto, ' + (width -  arrow + 3 ) + 'px)';
		txtCombo.style.paddingRight = '2px';

		if(txtCombo.offsetHeight != selCombo.offsetHeight) {
			txtCombo.style.height = (selCombo.offsetHeight -0) + 'px';
			// txtCombo.style.lineHeight = (selCombo.offsetHeight + 6) + 'px';
			//txtCombo.style.marginTop = '2px';
			//selCombo.style.marginTop = '2px';
		}

		selCombo.style.visibility = 'visible';
	}
}


// Помощна функция за комбобокс компонента
// Прехвърля съдържанието от SELECT елемента към INPUT полето
function comboSelectOnChange(id, value, suffix) 
{   
	var inp = get$(id);

	var exVal = inp.value;

	if(exVal != '' && inp.getAttribute('data-role') == 'list') {
		if (value) {
			get$(id).value += ', ' +  value; 
		}
	} else {
		get$(id).value = value.replace(/&lt;/g, '<'); 
	}
	
	get$(id).focus(); 
	$(id).trigger("change");

	var selCombo = get$(id + suffix);
	selCombo.value = '?';
}

// Присвоява стойност на вътрешния елемент, като отчита проблемите на IE
// при сетването на SELECT 
function setInnerHtml(element, html) 
{ 
	if(isIE()) {
		var re = new RegExp("(\<select(.*?)\>)(.*?)(\<\/select\>)", "i");
		element.outerHTML = 
		element.outerHTML.replace(re, "$1" + html + "$4");
	} else {
		element.innerHTML = html;
	}
}


/**
 * Проверява дали зададената опция е съществува в посочения с id селект
 */
function isOptionExists(selectId, option)
{
	for (i = 0; i < document.getElementById(selectId).length; ++i){
		if (document.getElementById(selectId).options[i].value == option){

		  return true;
		}
	}

	return false;
}



function focusSelect(event, id)
{
    var evt = event ? event : window.event;

	if(evt.keyCode == 18) {
		var select = document.getElementById(id);
		 select.focus();
	}
}

 // Обновява опциите в комбобокс, като извлича новите под условие от сървъра
function ajaxAutoRefreshOptions(id, selectId, input, params) 
{	

	if( typeof(input.savedValue) != 'undefined') {
		if(input.savedValue == input.value) return;
	}

	params.q = get$(id).value;

	// От параметрите прави УРЛ
	if(typeof(params) == 'object') {
		var urlget = '../?';

		if(params.relative_web_root) {
			 urlget = params['relative_web_root'] + '/' + urlget;
		}

		var amp    = '';
		
		// Генерираме UTL-то
		for( var n in params) {
			urlget = urlget + amp + n + '=' + encodeURIComponent(params[n]);
			amp = '&';
		}
	} else {
		var urlget = params;
	}

 
	var xmlHttpReq = createXHR();

	xmlHttpReq.open('GET', urlget, true);

	xmlHttpReq.onreadystatechange = function() {												
	
		if (xmlHttpReq.readyState == 4) {

			
			if(xmlHttpReq.responseText.length > 0) {
 				jsonGetContent(xmlHttpReq.responseText, function(c) {setInnerHtml( get$(selectId),  c); input.savedValue = input.value; input.onchange(); } );
			}
		}
	}
	
	xmlHttpReq.send(null);
}


// Парсира отговора на сървъра
// Показва грешки и забележки, 
// ако е необходимо стартира скриптове
function jsonGetContent(ans, parceContent) 
{   
	ans = eval('(' + ans + ')');

	if(ans.error) {
		alert(ans.error);
		return false;
	}

	if(ans.warning) {
		if(!confirm(ans.warning)) {
			return false;
		}
	}

	if( ans.js ) {
		var headID = document.getElementsByTagName("head")[0];
		for( var id in ans.js) {
			var newScript = document.createElement('script');
			newScript.type = 'text/javascript';
 			newScript.src = ans.js[id];
			//alert(ans.js[id]);
			waitForLoad = true;
			newScript.onload = function () { waitForLoad = false; alert(waitForLoad);  }
			headID.appendChild(newScript);
			
			do
			{
				 alert(1);
			}
			while (waitForLoad);
		}
	}
	
	if( ans.css ) {
		var headID = document.getElementsByTagName("head")[0];
		for( var id in ans.css) {
			var cssNode = document.createElement('link');
			cssNode.type = 'text/css';
			cssNode.rel = 'stylesheet';
			cssNode.href = ans.css[id];
			cssNode.media = 'screen';
			headID.appendChild(cssNode);		
		}
	}

 	if( parceContent(ans.content) == false) {
		alert(ans.content);
		return false;
	}

	if(ans.javascript ) {
		if( eval(ans.javascript) == false ) {
			return false;
		}
	}

	return true;
}


// Глобален масив за popup прозорците
popupWindows = new Array();

// Отваря диалогов прозорец
function openWindow(url, name, args)
{  
	// Записваме всички popup прозорци в глобален масив
	popupWindows[name] = window.open(url, name, args);
 
	var popup = popupWindows[name];

	if (popup) {
		// Ако браузърат е Chrome първо блърва главния прозорец, 
		// а след това фокусира на popUp прозореца
		var isChrome = navigator.userAgent.toLowerCase().indexOf('chrome') > -1;
		if (isChrome && popup.parent) {
			popup.parent.blur();
		}

		// Фокусиране върху новия прозорец
		popup.focus();
	}
}


// Редактор за BBCode текст: показва ...
function sc(text)
{
	if (typeof(text.createTextRange) != 'undefined') {
		text.caretPos = document.selection.createRange().duplicate();
	}
}


// Редактор за BBCode текст:   ...
function rp(text, textarea, newLine)
{
	var version = getIEVersion();
	if( (version == 8 || version == 9) && typeof(textarea.caretPos) != 'undefined' && textarea.createTextRange )
	{  
		textarea.focus();
		var caretPos = textarea.caretPos;
		
		var textareaText = textarea.value;
		var position = textareaText.length;
		var previousChar = textareaText.charAt(position - 1);
		
		if (previousChar !="\n"  && position != 0  && newLine){
			text = "\n" + text;
		}
		
		caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? text + ' ' : text;
		
		textarea.focus();
	} else if (typeof(textarea.selectionStart) != 'undefined' ) {
		
		var begin = textarea.value.substr(0, textarea.selectionStart);
		var end = textarea.value.substr(textarea.selectionEnd);
		var scrollPos = textarea.scrollTop;
		
		if (begin.charAt(begin.length-1) != "\n" && begin != "" && newLine){
			begin += "\n";
		}
		
		textarea.value = begin + text + end;

		if (textarea.setSelectionRange)
		{
			textarea.focus();
			textarea.setSelectionRange(begin.length + text.length, begin.length + text.length);
		}
		textarea.scrollTop = scrollPos;
	} else {
		var textareaText = textarea.value;
		var position = textareaText.length;
		var previousChar = textareaText.charAt(position - 1);
		
		if (previousChar !="\n"  && position != 0  && newLine){
			text = "\n" + text;
		}
		
		textarea.value += text;
		textarea.focus(textarea.value.length - 1);
	}
}


/*
 * добавяне на необходимите за създаване на таблица в ричедит символи, по зададени колони и редове
 */
function crateRicheditTable(textarea, newLine, tableCol, tableRow)
{
	if(tableRow < 2 ||  tableRow > 10 || tableCol < 2 ||  tableCol > 10 ) return;
	var version = getIEVersion();
	if( (version == 8 || version == 9) && typeof(textarea.caretPos) != 'undefined' && textarea.createTextRange )
	{  
		textarea.focus();
		var caretPos = textarea.caretPos;
		
		var textareaText = textarea.value;
		var position = textareaText.length;
		var previousChar = textareaText.charAt(position - 1);
		
		if (previousChar !="\n"  && position != 0  && newLine){
			text = "\n" + text;
		}
		text="";
		var i,j;
		for(j=0; j < tableRow; j++){
			for(i=0; i <= tableCol; i++){
				if(i < tableCol){
					text += "|  ";
				} else {
					text += "|";
				}	
			}  
			text += "\n";
		}

		caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? text + ' ' : text;
		
		textarea.focus();
	} else if (typeof(textarea.selectionStart) != 'undefined' ) {
		
		var begin = textarea.value.substr(0, textarea.selectionStart);
		var end = textarea.value.substr(textarea.selectionEnd);
		var scrollPos = textarea.scrollTop;
		
		if (begin.charAt(begin.length-1) != "\n" && begin != "" && newLine){
			begin += "\n";
		}
		text="";
		var i,j;
		for(j=0; j < tableRow; j++){
			for(i=0; i <= tableCol; i++){
				if(i < tableCol){
					text += "|  ";
				} else {
					text += "|";
				}	
			}  
			text += "\n";
		}
		
		textarea.value = begin + text + end;

		if (textarea.setSelectionRange)
		{
			textarea.focus();
			textarea.setSelectionRange(begin.length + text.length, begin.length + text.length);
		}
		textarea.scrollTop = scrollPos ;
	} else {
		var textareaText = textarea.value;
		var position = textareaText.length;
		var previousChar = textareaText.charAt(position - 1);
		
		if (previousChar !="\n"  && position != 0  && newLine){
			text = "\n" + text;
		}
		for(j=0; j < tableRow; j++){
			for(i=0; i <= tableCol; i++){
				if(i < tableCol){
					text += "|  ";
				} else {
					text += "|";
				}	
			}  
			text += "\n";
		}
		textarea.value += text;
		textarea.focus(textarea.value.length - 1);
	}
}

/*
 * предпазване от субмит на формата, при натискане на enter във форма на richedit
 */
function bindEnterOnRicheditTableForm(textarea){
	var richedit = $(textarea).closest('.richEdit');
	$(richedit).find(".popupBlock input").keypress(function(e) {
		if (e.which === 13) {
			e.preventDefault();
			$('#getTableInfo').click();
		}
	}); 
}


/**
 * Връща избрания текст в textarea
 * 
 * @param textarea
 * @return text
 */
function getSelectedText(textarea)
{
	var selectedText = '';
	
	if (textarea && typeof(textarea.selectionStart) != 'undefined') {
		selectedText = textarea.value.substr(textarea.selectionStart, textarea.selectionEnd - textarea.selectionStart);
	}
	
	return selectedText;
}


/**
 * Редактор за BBCode текст: селектира ...
 * 
 * @param text1 - текст, който се добавя в преди селектирания текст
 * @param text2 - текст, който се добавя в след селектирания текст
 * @param newLine - дали селектирания текст трябва да премине на нов ред
 * @param multiline - дали началния текст, селектирания текст, крайния текст трябва да са на отделни редове
 * @param maxOneLine - максимален брой символи за едноредов код
 * @param everyLine - дали при селектиран текст обграждащите текстове се отнасят за всеки ред
 */
function s(text1, text2, textarea, newLine, multiline, maxOneLine, everyLine)
{	
	if (typeof(textarea.caretPos) != 'undefined' && textarea.createTextRange)
	{					
		
		var caretPos = textarea.caretPos, temp_length = caretPos.text.length;
		var textareaText = textarea.value;
		var position = textareaText.length;
		var previousChar = textareaText.charAt(position - 1);
	
		if(caretPos.text != '' && caretPos.text.indexOf("\n") == -1 && (text2 == '[/code]' ||  text2 == '[/bQuote]') && caretPos.text.length <= maxOneLine)  {
			text1 = "`";
			text2 = "`";
		} else {
			
			if(selection != '' && caretPos.text.indexOf("\n") == -1 && text2 == '[/code]') {
				text1 = '[code=text]';
			}
			
			if (previousChar !="\n"  && position != 0  && newLine && caretPos.text == ''){
				text1 = "\n" + text1;
			}	
			
			if(multiline) {
				if(getIEVersion()==10){
					text1 = text1 + "\n";
				}
				text2 = "\n" + text2;
			}
		}
		if(caretPos.text != '' && caretPos.text.indexOf("\n") && everyLine){
			var temp = caretPos.text.replace(/\n/g, text2 + "\n" + text1);
			caretPos.text = text1 + temp + text2 ;
		} else {
			caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? text1 + caretPos.text + text2 + ' ' : text1 + caretPos.text + text2;
		}
		
		if (temp_length == 0)
		{
			caretPos.moveStart('character', -text2.length);
			caretPos.moveEnd('character', -text2.length);
			caretPos.select();
		}
		else
			textarea.focus(caretPos);
	} else if (typeof(textarea.selectionStart) != 'undefined') {

		var begin = textarea.value.substr(0, textarea.selectionStart);
		var selection = textarea.value.substr(textarea.selectionStart, textarea.selectionEnd - textarea.selectionStart);
		var end = textarea.value.substr(textarea.selectionEnd);
		var scrollPos = textarea.scrollTop;
		
		var beginPosition = textarea.selectionStart;
		var endPosition = textarea.selectionEnd;
		
		if(!selection){
			if(textarea.getAttribute('data-readySelection')){
				selection = textarea.getAttribute('data-readySelection');
				beginPosition = textarea.getAttribute('data-selectionStart');
				endPosition = textarea.getAttribute('data-selectionEnd');
				
				begin = textarea.value.substr(0, beginPosition);
				if (beginPosition == endPosition){
					var strBefore = textarea.value.substring(beginPosition - selection.length, beginPosition);
					var strAfter = textarea.value.substring(beginPosition , beginPosition + selection.length);
					
					if(strBefore == selection){
						beginPosition = beginPosition - selection.length;
					} else if(strAfter == selection){
						endPosition = beginPosition + selection.length;
					}
				}
			} 
		}
		
		if(selection != '' && selection.indexOf("\n") == -1  && (text2 == '[/code]' ||  text2 == '[/bQuote]') && selection.length <= maxOneLine) {
			text1 = "`";
			text2 = "`";
		} else{
			if(selection != '' && selection.indexOf("\n") && everyLine){
				var startLine = begin.lastIndexOf("\n") + 1;
				//Стринга от новия ред до маркирания ред
				var beforeSel = begin.substr(startLine,beginPosition) ;
				var tempSel =  beforeSel + selection;
				beginPosition = startLine;
				selection = tempSel.replace(/\n/g, text2 + "\n" + text1);
			}
			
			if(selection != '' && selection.indexOf("\n") == -1 && text2 == '[/code]') {
				text1 = '[code=text]';
			}
			
			if (begin.charAt(begin.length-1) != "\n" && begin != '' && newLine && selection == '') {
				text1 = "\n" + text1;
			}
			
			if(multiline) {
				text1 = text1 + "\n";
				text2 = "\n" + text2;
			}
		}
		
		textarea.value = textarea.value.substring(0, beginPosition) + text1 + selection + text2 + textarea.value.substring(endPosition);	
		
		if (textarea.setSelectionRange)
		{
			if (selection.length == 0)
				textarea.setSelectionRange(beginPosition + text1.length, beginPosition + text1.length);
			else{
				var endRange = parseInt(beginPosition) + parseInt(text1.length) + parseInt(selection.length) + parseInt(text2.length);
				textarea.setSelectionRange(beginPosition, endRange);
			}
			textarea.focus();
		}
		textarea.scrollTop = scrollPos;
	} else {
		
		var textareaText = textarea.value;
		var position = textareaText.length;
		var previousChar = textareaText.charAt(position - 1);
		
		
		if (previousChar != "\n"  && position != 0  && newLine){
			text1 = "\n" + text1;
		}	
		
		if(multiline) {
			if(getIEVersion()==10){
				text1 = text1 + "\n";
			}
			text2 = "\n" + text2;
		}
		
		textarea.value += text1 + text2;
		textarea.focus(textarea.value.length - 1);
	}
}


// Редактор за BBCode текст: показва ...
function insertImage(id, img)
{
	var e = document.getElementById(id+'_id');
	if (e) {
		var openTag = '[img='+img.align;
		if (img.haveBorder) {
			openTag = openTag + ',border';
		}
		openTag = openTag + ']';
		if (img.caption) {
			img.url = img.url + ' ' + img.caption;
		}
		rp(openTag+img.url+'[/img]', e);
	}
	showImgFrame(id, 'hidden');
}


// Редактор за BBCode текст: показва ...
function showImgFrame(name, visibility)
{
	var e = top.document.getElementById(name + '-rt-img-iframe');
	if (e) {
		e.style.visibility = visibility;
	}
}


// Оцветява входен елемент в зависимост от оставащите символи за писане
function  colorByLen(input, maxLen, blur) 
{	
	blur = typeof blur !== 'undefined' ? blur : false;
	var rest = maxLen - input.value.length; 
	var color = 'white'; 
	if(rest <  0) color = 'red'; 
	if(rest == 0 && input.value.length > 3 && !blur) color = '#ff9999'; 
	if(rest == 1 && input.value.length > 3 && !blur) color = '#ffbbbb'; 
	if(rest == 2 && input.value.length > 3 && !blur) color = '#ffdddd'; 
	if(rest >= 3) color = '#ffffff';
	input.style.backgroundColor = color;
}

 

// Конвертира Javascript обект към GET заявка
function js2php(obj, path, new_path)
{
	if (typeof(path) == 'undefined') var path=[];
	if (typeof(new_path) != 'undefined') path.push(new_path);
	var post_str = [];
	if (typeof(obj) == 'array' || typeof(obj) == 'object') {
		for (var n in obj) {
			post_str.push(js2php(obj[n],path,n));
		}
	} else if (typeof(obj) != 'function') {
		var base = path.shift();
		post_str.push(base + (path.length > 0 ? '[' + path.join('][') + ']' : '') + '=' + encodeURI(obj));
		path.unshift(base);
	}
	path.pop();

	return post_str.join('&');
}

function prepareContextMenu(){
	 jQuery.each($('.more-btn'), function( i, val ) {
			var el = $(this).parent().find('.modal-toolbar');
			$(this).contextMenu('popup', el,{
				'displayAround':'trigger'
			});
	 });
}

// Скрива или показва съдържанието на div (или друг) елемент
function toggleDisplay(id)
{ 
	if (typeof jQuery != 'undefined') {
		var elem = $("#" + id).parent().find('.more-btn');
	    $("#" + id).fadeToggle("slow");
	    elem.toggleClass('show-btn');
 
	} else {
	
		var el = document.getElementById(id);
		
		if (el.style.display == 'none' || el.style.display == '') el.style.display='block';
			else el.style.display='none';
		
		var child = el.parentNode.children[0].children[0];
		
		if(hasClass(child, 'show-btn')){
			child.className= child.className.replace(/\bshow-btn\b/,'');
		} else {
			child.className += ' show-btn';
		}
	}
}

function hasClass(element, className) {
    return element.className && new RegExp("(^|\\s)" + className + "(\\s|$)").test(element.className);
}

function hideRichtextEditGroups()
{
	if (typeof jQuery != 'undefined') {	
		$('body').live('click',function(e) {
			if(!($(e.target).is('input[type=text]'))){
				hideRichtextEditGroupsBlock();
			}
		});
	}else{
		window.onclick = function() {
			hideRichtextEditGroupsBlock();
		};
	}
	
	return false;
}

function toggleRichtextGroups(id, event)
{
	if (typeof event == 'undefined') {
		event = window.event;
	}
	
	if (event.stopPropagation) {
		event.stopPropagation();
	} else if (event.preventDefault) {
		event.preventDefault();
	} else {
		event.returnValue = false;
		event.cancelBubble = true;
	}
	
	if (typeof jQuery != 'undefined') {
		var hidden = $("#" + id).css("display");

		hideRichtextEditGroupsBlock();
	    if (hidden == 'none') {
	    	$("#" + id).show("fast");
	    }
	} else {

		var el = document.getElementById(id);  
		var hidden = el.style.display;
		
		hideRichtextEditGroupsBlock();
		
		if (hidden != 'block' && (el.style.display == 'none' || el.style.display == '')) {
			el.style.display='block';
		} else {
			el.style.display='none';
		}
	}
	
	return false;
}

function hideRichtextEditGroupsBlock()
{
	if (typeof jQuery != 'undefined') {	
		$('.richtext-holder-group-after').css("display", "none" );
	}else{
		
		var richtextGroupHide = document.getElementsByClassName('richtext-holder-group-after');
		
		if (richtextGroupHide) {
			for (var i = richtextGroupHide.length - 1; i >= 0; i--)
			{
				if (richtextGroupHide[i]) {
					richtextGroupHide[i].style.display='none';
				}
			}
		}
	}
}


/****************************************************************************************
 *																				        *
 *	Добавки за съвместимост със стари браузъри   										*
 *																						*
 ****************************************************************************************/

if (!Array.prototype.forEach)
{
  Array.prototype.forEach = function(fun /*, thisp*/)
  {
    var len = this.length;
    if (typeof fun != "function")
      throw new TypeError();

    var thisp = arguments[1];
    for (var i = 0; i < len; i++)
    {
      if (i in this)
        fun.call(thisp, this[i], i, this);
    }
  };
}


if(typeof String.prototype.trim !== 'function') {
  String.prototype.trim = function() {
    return this.replace(/^\s+|\s+$/g, ''); 
  }
}


/****************************************************************************************
 *																				        *
 *	Финкции за плъгина plg_Select    													*
 *																						*
 ****************************************************************************************/

/**
 * Масив с оригиналните цветове на редовете от listView, където има checkbox-ове
 */
var trColorOriginals = new Array();


/**
 * След промяната на даден чек-бокс променя фона на реда
 */
function chRwCl(id)
{
    var markColor = "#ffffbb";

    var pTR = get$("lr_" + id);
    var pTarget = get$("cb_" + id);

    if(pTR.nodeName.toLowerCase() != "tr") {
        return;
    }
    if(pTarget.checked == true) {
        trColorOriginals[id] = pTR.style.backgroundColor;
        pTR.style.backgroundColor = markColor;
    } else {
        if(trColorOriginals[id] != undefined) {
            pTR.style.backgroundColor = trColorOriginals[id];
        } else {
            trColorOriginals[id] = pTR.style.backgroundColor;
        }
    }
}


/**
 * Обновява фона на реда и състоянието на бутона "С избраните ..."
 */
function chRwClSb(id)
{
	chRwCl(id);
	SetWithCheckedButton();
}


/**
 * Инвертира всички чек-боксове
 */
function toggleAllCheckboxes()
{
    trColorOriginals.forEach( function (el, id, all) {
        var pTarget = get$("cb_" + id);
        if(pTarget.checked == true) {
            pTarget.checked = false;
        } else {
            pTarget.checked = true;
        }
        chRwCl(id);
    });

	SetWithCheckedButton();

	return true;
}


/**
 * Задава състоянието на бутона "S izbranite ..."
 */
function SetWithCheckedButton()
{ 
	var state = false;
	trColorOriginals.forEach( function (el, id, all) {
	    var pTarget = get$("cb_" + id);
	    if(pTarget.checked == true) {
	    	state = true;
	    } 
	});

	var btn = get$('with_selected');
	
	// Ако не може да се определи
	if (!btn) return;
	
	btn.className = btn.className.replace(' btn-with-selected-disabled', '');
	btn.className = btn.className.replace(' btn-with-selected', '');
	
	if(state) {
		btn.className += ' btn-with-selected';
		btn.disabled = false;
	} else {
		btn.className += ' btn-with-selected-disabled';
		btn.disabled = true;
		btn.blur();
	}
}

function flashHashDoc(flasher)
{
	var h = window.location.hash.substr(1); 
	if(h) {
		if (!flasher) {
			flasher = flashDoc;
		}
		flasher(h);
	}
}

function flashDoc(docId, i)
{
	var tr = get$(docId);
	
	var cells = tr.getElementsByTagName('td');
	if(typeof i == 'undefined') {
        i = 1;
    }  
	var col = i * 5 + 155;

	var y = col.toString(16);

	var color = '#' + 'ff' + 'ff' + y;

	cells[0].style.backgroundColor = color;
	cells[1].style.backgroundColor = color;

	if(i<20) {
		i++;
		setTimeout( "flashDoc('" + docId + "', " + i + ")", 220);
	} else {
		cells[0].style.backgroundColor = 'transparent';
		cells[1].style.backgroundColor = 'transparent';
	}
	
}

function flashDocCss3(docId)
{
	var tr = get$(docId),
		oldClassName = tr.className;
	
	tr.className = (tr.className || '') + ' flash';
	
	setTimeout(function () {
		tr.className += ' transition';
		setTimeout(function () {
			tr.className = oldClassName + ' transition';
		}, 1);
	}, 1);
}

function flashDocInterpolation(docId)
{
	var el = get$(docId); // your element
	
	// Ако е null или undefined
	if (!el || el=='undefined') {
		
		return ;
	}
	
	// linear interpolation between two values a and b
    // u controls amount of a/b and is in range [0.0,1.0]
    function lerp(a,b,u) {
        return (1-u) * a + u * b;
    };
    
    function fade(element, property, start, end, duration) {
      var interval = 10;
      var steps = duration/interval;
      var step_u = 1.0/steps;
      var u = 0.0;
      var theInterval = setInterval(function(){
        if (u >= 1.0){ clearInterval(theInterval) }
        var r = parseInt(lerp(start.r, end.r, u));
        var g = parseInt(lerp(start.g, end.g, u));
        var b = parseInt(lerp(start.b, end.b, u));
        var colorname = 'rgb('+r+','+g+','+b+')';
        element.style.backgroundColor=colorname;
        u += step_u;
      }, interval);
    };
    
    // in action
    
	var endColorHex = getBackgroundColor(el);
    var flashColor = {r:255, g:  255, b:  128};  // yellow

    el.style.backgroundColor='#ffff80';
    setTimeout(function () {
        el.style.backgroundColor=endColorHex;
    }, 2010);

    if (endColorHex.substring(0, 1) != '#') {
        return;
    }
    
    var endColor = {
		r: parseInt(endColorHex.substring(1,3),16),
		g: parseInt(endColorHex.substring(3,5),16),
    	b: parseInt(endColorHex.substring(5,7),16)
    };
    
    fade(el,'background-color', flashColor, endColor, 2000);
}

function getBackgroundColor(el)
{
	if (typeof jQuery != 'undefined') {
		var bgColor = $(el).css('background-color');
	} else {
		
		bgColor = el.style.backgroundColor;
	}
	
	if (bgColor == 'transparent'){
		bgColor = 'rgba(0, 0, 0, 0)';
	}
	return rgb2hex(bgColor);
}

function rgb2hex(rgb) {
	
    if (  rgb.search("rgb") == -1 ) {
         return rgb;
    } else {
         rgb = rgb.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+))?\)$/);
         function hex(x) {
              return ("0" + parseInt(x).toString(16)).slice(-2);
         }
         if (rgb[4] != 'undefined' && rgb[4] == 0) {
        	 rgb[1] = rgb[2] = rgb[3] = 255;
         }
         return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]); 
    }
}

/**
 * Задава максиналната височина на опаковката и основното съдържание
 */
function setMinHeight()
{  
	var ch = document.documentElement.clientHeight;
                    
	if(document.getElementById('framecontentTop')) {
		var fct = document.getElementById('framecontentTop').offsetHeight;
                        
		if(document.getElementById('maincontent')) {
			var mc = document.getElementById('maincontent');
			var h = (ch - fct - 51) + 'px';
			mc.style.minHeight = h;
		}

		if(document.getElementById('packWrapper')) {
			var pw = document.getElementById('packWrapper');
			var sub = 100;
			if( document.body.className.match('wide')){
				sub = 118;
			}
			var h = (ch - fct - sub) + 'px';
			pw.style.minHeight = h;
		}
	}
}

/**
 * мащабиране на страницата при touch устройства с по-голяма ширина 
 */
function scaleViewport(){
	if(is_touch_device())
    {
		var pageWidth = $(window).width();
		var customWidth = 1024;
		if(pageWidth > customWidth ){
			var scaleFactor = pageWidth/customWidth;
            $('meta[name=viewport]').remove();
            $('head').append('<meta name="viewport" content="width='+ customWidth + ', initial-scale=' +scaleFactor+ '">');
            $('body').css('maxWidth',customWidth);
    	} 
    }
}


function is_touch_device() {
	  return (('ontouchstart' in window) || (navigator.msMaxTouchPoints > 0));
}
	
	
/**
 * Задава минимална височина на контента във външната част
 */
function setMinHeightExt()
{ 
	var ch = document.documentElement.clientHeight;          
	if(document.getElementById('cmsTop')) {
		var ct = document.getElementById('cmsTop').offsetHeight;
		var cb = document.getElementById('cmsBottom').offsetHeight;     
		var cm = document.getElementById('cmsMenu').offsetHeight; 
		
		var add = 7;
		if( document.body.className.match('wide')){
			add = 36;
		}
		
		if(document.getElementById('maincontent')) {
			var mc = document.getElementById('maincontent');
			var h = (ch - ct - cb - cm - add ) ;
			if(h > 60) {
				mc.style.minHeight = h + 'px';
			}
		}
	}
}

/**
 * Задава padding на логин-формата при малки дисплеи
 */
function loginFormPadding()
{  
	if (typeof jQuery != 'undefined') {
		var winw = $(window).width();
	} else {
		var winw = window.innerWidth || document.documentElement.clientWidth || document.getElementsByTagName('body')[0].clientWidth;
	}
	
	if(document.body.className.match('narrow') && (winw<400) && document.getElementById('login-form')){
		var lf = document.getElementById('login-form');
		var form = lf.getElementsByTagName("table")[0].offsetWidth;
		var dist = ((winw - form - 12)/2);
		lf.style.marginLeft = dist  + 'px';
		lf.style.paddingLeft ='0px';
		lf.style.paddingTop = dist  + 'px';
	}
}


/**
 * Задава ширина на елементите от форма в зависимост от ширината
 */
function setFormElementsWidth()
{
	var form = $('.formTable');
	var winWidth = parseInt($(window).width());
	
	var off = form.offset();
	var formOffsetL = parseInt(off.left);
	var paddingL = parseInt(form.css('paddingLeft'));
	var inlinePadding = 14;
	var formElWidth = winWidth - 2 * formOffsetL - 2 * paddingL - inlinePadding;
	var preferredSizeInEm = 42;
	var outsideWidth = 44;
	
	// колко ЕМ е широка страницата
	var sizeInEm = winWidth / parseFloat($(".formTable input[type=text]").css("font-size")); 
	
	// колко РХ е 1 ЕМ
	var em = parseInt(winWidth / sizeInEm);
	var width = preferredSizeInEm * em;

	if(winWidth > (width + outsideWidth)){
		formElWidth =  width;
		form.css('maxWidth', '42em');
	} else {
		formElWidth =  winWidth - outsideWidth;
		form.css('maxWidth', parseInt(formElWidth/sizeInEm) + 'em' );
	}
	
	$('.formTable label').each(function() {
		var colsInRow = parseInt($(this).attr('data-colsInRow'));
		if(!colsInRow) {
			colsInRow = 1;
		}
		$(this).css('maxWidth', parseInt((formElWidth - 25)/colsInRow));
	});
	
	$('.formTable').css('width', formElWidth);
	$('.formTable textarea').css('width', formElWidth);
	$('.formTable input').not(':input[type=button], :input[type=submit], :input[type=reset], :input[type=radio],:input[type=checkbox]').css('maxWidth', formElWidth);
	$('.formTable label').css('whiteSpace','pre-wrap');
	$('.formTable select').css('maxWidth',formElWidth);
	$('.formTable .chzn-container').css('maxWidth',formElWidth);
	$('.formTable .chzn-container .chzn-drop').css('maxWidth',formElWidth - 2 );
	$('.formTable .chzn-container-single .chzn-search input').css('maxWidth',formElWidth - 12);
}


/**
 * Задава ширина на елементите от нишката в зависимост от ширината
 */
function setThreadElemWidth()
{
	var winWidth = parseInt($(window).width()) - 45;
	$('.doc_Containers table.listTable > tbody > tr >td').css('maxWidth',winWidth + 8);
	$('.docStatistic').css('maxWidth',winWidth);
	$('.scrolling-holder').css('maxWidth',winWidth );
}


/**
 * Задава ширината на текстареата спрямо ширината на клетката, в която се намира
 */
function setRicheditWidth(el)
{
	var width = parseInt($('.formElement').width());
	$('.richEdit textarea').css('width', width);
}


/**
 * Скролира listTable, ако е необходимо
 */
function scrollLongListTable()
{
	var winWidth = parseInt($(window).width()) - 45;
	var tableWidth = parseInt($('.listTable').width());
	if(winWidth < tableWidth){
		$('.listRows').addClass('overflow-scroll');
		$('.main-container').css('display','block');
		$('.switching-display').css('display','block');
	}
}


/**
 * При натискане с мишката върху елемента, маркираме текста
 */
function onmouseUpSelect()
{
	if (document.selection) {
		var range = document.body.createTextRange();
		range.moveToElementText(document.getElementById('selectable'));
		range.select();
	}
	else if (window.getSelection) {
		var range = document.createRange();
		range.selectNode(document.getElementById('selectable'));
		window.getSelection().addRange(range);
	}
}


/**
 * Записва избрания текст в сесията и текущото време
 * 
 * @param string handle - Манипулатора на докуемента
 */
function saveSelectedTextToSession(handle, onlyHandle)
{
	// Ако не е дефиниран
	if (typeof sessionStorage === "undefined") return ;
	
	// Вземаме избрания текст
	var selText = getEO().getSavedSelText();
	
	// Ако има избран текст
	if (selText) {
		
		// Ако има подадено id
		if (handle) {
			
			// Записваме манипулатора
			sessionStorage.selHandle = handle;
		}
		
		// Ако няма да записваме само манипулатора
		if (!onlyHandle) {
			
			// Записваме в сесията новия текст
			sessionStorage.selText = selText;
		}
		
		// Записваме текущото време
		sessionStorage.selTime = new Date().getTime();
	} else {
		
		// Записваме в сесията празен стринг
		sessionStorage.selText = '';
	}
}


/**
 * Връща маркирания текст
 * 
 * @returns {String}
 */
function getSelText()
{
    var txt = '';
    
    try {
	    if (window.getSelection)
	    {
	        txt = window.getSelection();
	    }
	    else if (document.getSelection)
	    {
	        txt = document.getSelection();
	    }
	    else if (document.selection.createRange)
	    {
    		txt = document.selection.createRange();
    	}
    } catch(err) {
    	getEO().log('Грешка при извличане на текста');
    }
    
	return txt;
}


/**
 * Добавя в посоченото id на елемента, маркирания текст от сесията, като цитат, ако не е по стар от 5 секунди
 * 
 * @param id
 */
function appendQuote(id)
{
	// Ако не е дефиниран
	if (typeof sessionStorage === "undefined") return;
	
	// Вземаме времето от сесията
	selTime = sessionStorage.getItem('selTime');
	
	// Вземаме текущото време
	now = new Date().getTime();
	
	// Махаме 5s
	now = now-5000;
	
	// Ако не е по старо от 5s
	if (selTime > now) {
		
		// Вземаме текста
		text = sessionStorage.getItem('selText');
		
		if (text) {

			// Вземаме манипулатора на документа
			selHandle = sessionStorage.getItem('selHandle');
			
			// Стринга, който ще добавим
			str = "\n[bQuote";
			
			// Ако има манипулато, го добавяме
			if (selHandle) {
				str += "=" + selHandle + "]";
			} else {
				str += "]";
			}
			str += text + "[/bQuote]";
			
			// Добавяме към данните
			get$(id).value += str;
		}
	}
}


/**
 * Добавя скрито инпут поле Cmd със стойност refresh
 * 
 * @param form
 */
function addCmdRefresh(form)
{
	var input = document.createElement("input");
	
	input.setAttribute("type", "hidden");

	input.setAttribute("name", "Cmd[refresh]");

	input.setAttribute("value", "1");

	form.appendChild(input);
}


/**
 * Рефрешва посочената форма
 */
function refreshForm(form)
{
	addCmdRefresh(form);
	form.submit();
}


/**
 * Променя visibility атрибута на елементите
 */
function changeVisibility(id, type)
{
	element=document.getElementById(id);

	element.style.visibility=type;
}

/**
 * На по-големите от дадена дължина стрингове, оставя началото и края, а по средата ...
 * Работи подобно на str::limitLen(...)
 */
function limitLen(string, maxLen)
{
	// Дължината на подадения стринг
	var stringLength = string.length;
	
	// Ако дължината на стринга е над допустмите
	if (stringLength > maxLen) {
		
		// Ако максималния размер е над 20
		if (maxLen > 20) {
			
			var remain = (maxLen - 5) / 2;
			remain = parseInt(remain);
			
			// По средата на стринга добавяме ...
			string = string.substr(0, remain) + ' ... ' + string.slice(-remain);
		} else {
			
			var remain = (maxLen - 3);
			remain = parseInt(remain);
			
			// Премахваме края на стринга
			string = string.substr(0, remain);
		}
	}
	
	return string;
}


// записва съкратеното URL в глобална променлива
function getShortURL(shortUrl)
{
	shortURL = decodeURIComponent(shortUrl);
}


// добавяне на линк към текущата страница при копиране на текст
function addLinkOnCopy(text) 
{
	var body_element = document.getElementsByTagName('body')[0];
	var selection = window.getSelection();
	
	if (("" + selection).length < 30) return;
	    
	var htmlDiv = document.createElement('div');
	
	htmlDiv.style.position = 'absolute';
	htmlDiv.style.left = '-99999px';
	
	body_element.appendChild(htmlDiv);
	
	htmlDiv.appendChild(selection.getRangeAt(0).cloneContents());

	if (typeof shortURL != 'undefined'){
		var locationURL = shortURL;
	} else {
		var locationURL = document.location.href;
	}
	
	htmlDiv.innerHTML += "<br /><br />" + text + ": <a href='" + locationURL + "'>" + locationURL + "</a> ";
			
	selection.selectAllChildren(htmlDiv);
	
	window.setTimeout(function () { body_element.removeChild(htmlDiv); }, 200);
}


/**
 * Масив със сингълтон обектите
 */
var _singletonInstance = new Array();


/**
 * Връща сингълтон обект за съответната функция
 * 
 * @param string name - Името на функцията
 * 
 * @return object
 */
function getSingleton(name)
{
	// Ако не е инстанциран преди
	if (!this._singletonInstance[name]) {
		
		// Вземаме обекта
		this._singletonInstance[name] = this.createObject(name);
	}
	
	return this._singletonInstance[name];
}


/**
 * Създава обект от подаденат функция
 * 
 * @param string name - Името на функцията
 * 
 * @return object
 */
function createObject(name)
{
	try {
		var inst = new window[name];
	} catch (err) {

		var inst = Object.create(window[name].prototype);
	}
	
	return inst;
}



// Предпазване от двойно събмитване
function preventDoubleSubmission(id) {
	var form = '#' + id;
	var lastSubmitStr, submitStr, lastSubmitTime, timeSinceSubmit;
	
	jQuery(form).bind('submit', function(event, data) {	
		if(lastSubmitTime) {
	    	timeSinceSubmit = jQuery.now() - lastSubmitTime;
	    }
	    submitStr = $(form).serialize();
	    
	    if((typeof lastSubmitStr == 'undefined') || (lastSubmitStr != submitStr) || ((typeof timeSinceSubmit != 'undefined') && timeSinceSubmit > 10000)) {
	    	lastSubmitTime = jQuery.now();
	    	lastSubmitStr = submitStr;
	    	
	    	return true;
	    }
	    // Блокиране на събмита, ако няма промени и за определено време
	    event.preventDefault();
	});
}

/*
 * Функция за подравняване на числа по десетичния знак
 */
function tableElemsFractionsWidth(){
	$('.alignDecimals > table').each(function() { 
		var table = $(this);
		var fracPartWidth = [];
        $(this).find('.fracPart').each(function() {  
        	var elem = $(this);
        	var parent = $(this).parent();
        	if(!fracPartWidth[parent.attr('data-col')] || fracPartWidth[parent.attr('data-col')] < $(elem).width()){
        		fracPartWidth[parent.attr('data-col')] = $(elem).width();
        	}
        });
		for(key in fracPartWidth) {
			$(table).find("span[data-col='" + key + "'] .fracPart").css('width', fracPartWidth[key]);
		}
	});
}

/**
 * EFAE - Experta Framework Ajax Engine
 * 
 * @category  ef
 * @package   js
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
function efae()
{
	var efaeInst = this;
	
	// Добавяме ивенти за ресетване при действие
	getEO().addEvent(document, 'mousemove', function(){efaeInst.resetTimeout()});
	getEO().addEvent(document, 'keypress', function(){efaeInst.resetTimeout()});
	
	// Масив с всички абонирани
	efae.prototype.subscribedArr = new Array();
	
	// Масив с времето на последно извикване на функцията
	efae.prototype.lastTimeArr = new Array();
	
	// През колко време да се вика функцията `run`
	efae.prototype.timeout = 1000;
	
	// URL-то, което ще се вика по AJAX
	efae.prototype.url;
	
	// Префикса, за рендиращата функция
	efae.prototype.renderPrefix = 'render_';
	
	// Времето в милисекунди, с което ще се увеличава времето на изпълнение
	efae.prototype.increaseInterval = 100;
	
	// Горната граница (в милисекунди), до която може да се увеличи брояча
	efae.prototype.maxIncreaseInterval = 60000;
	
	// През колко време да се праща AJAX заяка към сървъра
	efae.prototype.ajaxInterval = efae.prototype.ajaxDefInterval = 5000;
	
	// Кога за последно е стартирана AJAX заявка към сървъра
	efae.prototype.ajaxLastTime = new Date();
	
	// Дали процеса е изпратена AJAX заявка за извличане на данните за показване след рефреш
	efae.prototype.isSendedAfterRefresh = false;

	// УРЛ, от което се вика AJAX-a - отворения таб
	Experta.prototype.parentUrl;
}


/**
 * Функция, която абонира дадено URL да извлича данни в определен интервал
 * 
 * @param string name - Името
 * @param string url - URL-то, което да се използва за извличане на информация
 * @param integer interval - Интервала на извикване в милисекунди
 */
efae.prototype.subscribe = function(name, url, interval)
{
	// Създаваме масив с името и добавяме неоходимите данни в масива
	this.subscribedArr[name] = new Array();
	this.subscribedArr[name]['url'] = url;
	this.subscribedArr[name]['interval'] = interval;
	
	// Текущото време
	this.lastTimeArr[name] = new Date();
}


/**
 * Фунцкция, която се самозацикля и извиква извличането на данни
 */
efae.prototype.run = function()
{
	try {
		// Увеличаваме брояча
		this.increaseTimeout();
		
		// Вземаме всички URL-та, които трябва да се извикат в този цикъл
		var subscribedObj = this.getSubscribed();
		
		// Стартираме процеса
		this.process(subscribedObj);
		
	} catch(err) {
		
		// Ако възникне грешка
		getEO().log('Грешка при стартиране на процеса');
	} finally {
		// Инстанция на класа
		var thisEfaeInst = this;
		
		// Задаваме да се самостартира
		setTimeout(function(){thisEfaeInst.run()}, this.timeout);
	}
}


/**
 * Връща броя на записите в обекта
 * 
 * @param object subscribedObj - Обект, който да се преброи
 * 
 * @return integer
 */
efae.prototype.getObjectKeysCnt = function(subscribedObj)
{
	// Ако не е дефинир
	// За IE < 9
	if (!Object.keys) {
		var keys = [];
		for (var i in subscribedObj) {
			if (subscribedObj.hasOwnProperty(i)) {
				keys.push(i);
			}
		}
	} else {
		var keys = Object.keys(subscribedObj);
	}
	
	return keys.length;
}


/**
 * Извиква URL, който стартира абонираните URL-та на които им е дошло времето да се стартират
 * и рендира функциите от резултата
 * 
 * @param object subscribedObj - Обект с URL-то, което трябва да се вика
 * @param object otherData - Обект с допълнителни параметри, които ще се пратят по POST
 * @param boolean async - Дали да се стартира асинхронно. По подразбиране не true
 */
efae.prototype.process = function(subscribedObj, otherData, async)
{
	// Ако няма URL, което трябва да се извика, връщаме
	if (!this.getObjectKeysCnt(subscribedObj)) return;
	
	// Ако не е подададена стойност
	if (typeof async == 'undefined') {
		
		// По подразбиране да се стартира асинхронно
		async = true;
	}
	
	// URL-то, което да се вика
	var efaeUrl = this.getUrl();
	
	// Ако не е дефинирано URL
	if (!efaeUrl) {
		
		// Изкарваме грешката в лога
		getEO().log('Не е дефинирано URL, което да се вика');
	}
	
	// Инстанция на класа
	var thisEfaeInst = this;
	
	// Ако има дефиниран JQuery
	if (typeof jQuery != 'undefined') {
		
		// Преобразуваме обекта в JSON вид
		var subscribedStr = JSON.stringify(subscribedObj);
		
		// Обект с параметри, които се пращат по POST
		var dataObj = new Object();
		
		// Ако е дефиниран
		if (typeof otherData != 'undefined') {
			
			// Добавяме към обекта
			dataObj = otherData;
		}
		
		// Обекст с данните, които ще изпращаме
		dataObj['subscribed'] = subscribedStr;
		
		// Ако е зададено времето на извикване на страницата
		if (typeof(hitTime) != 'undefined') {
			
			// Добавяме в масива
			dataObj['hitTime'] = hitTime;
		}
		
		// Ако е зададено времето на бездействие в таба
		if (typeof(getEO().getIdleTime()) != 'undefined') {
			
			// Добавяме в масива
			dataObj['idleTime'] = getEO().getIdleTime();
		}
		
		// Ако е зададено URL-то
		if (typeof(this.getParentUrl()) != 'undefined') {
			
			// Добавяме в масива
			dataObj['parentUrl'] = this.getParentUrl();
		}
		
		// Добавяме флаг, който указва, че заявката е по AJAX
		dataObj['ajax_mode'] = 1;
		
		// Извикваме по AJAX URL-то и подаваме необходимите данни и очакваме резултата в JSON формат
		$.ajax({
			  async: async,
			  type: "POST",
			  url: efaeUrl,
			  data: dataObj,
	  		  dataType: 'json'
			}).done(function(res) {
				
				var n = res.length;
				
				// Обхождаме всички получени данни
				for (var i = 0; i < n; ++i) {
			    	
			    	// Фунцкцията, която да се извика
			    	func = res[i].func;
			    	
			    	// Аргументи на функцията
			    	arg = res[i].arg;
			    	
			    	// Ако няма функция
		    		if (!func) {
		    			
		    			// Изкарваме грешката в лога
		    			getEO().log('Не е подадена функция');
		    			
		    			continue;
		    		}
		    		
		    		// Името на функцията с префикаса
		    		func = thisEfaeInst.renderPrefix + func;
		    		
		    		try {
		    			
		    			// Извикваме функцията
		    			window[func](arg);
		    		} catch(err) {
		    			
		    			// Ако възникне грешка
		    			getEO().log(err + 'Несъществуваща фунцкция: ' + func + ' с аргументи: ' + arg);
		    		}
			    }
			    
			}).fail(function(res) {
				
				// Ако възникне грешка
				getEO().log('Грешка при извличане на данни по AJAX');
			});
	} else {
		
		// Изкарваме грешката в лога
		getEO().log('JQuery не е дефиниран');
	}
}


/**
 * Намира абонираните URL-та на които им е време да се стартират
 * 
 * @return object - Обект с абонираните URL-та на които им е време да се стартират
 */
efae.prototype.getSubscribed = function()
{
	// Обект с резултатите
	resObj = new Object();
	
	// Броя на елементите
	var cnt = this.getObjectKeysCnt(this.subscribedArr);
	
	// Ако няма елементи, няма нужда да се изпълнява
	if (!cnt) return resObj;
	
	// Текущото време
	var now = new Date();
	
	// Ако не е изпратена заявката след рефрешване
	if (!this.isSendedAfterRefresh) {
		
		// Обхождаме всички абонирани URL-та
		for (name in this.subscribedArr) {
			
			// Всички абонирани процеси с интервал 0
			if (this.subscribedArr[name]['interval'] == 0) {
				
				// Добавяме URL-то
				resObj[name] = this.subscribedArr[name]['url'];
				
				// Премахваме от масива
				delete(this.subscribedArr[name]);
			}
		}
		
		// Променяме флага
		this.isSendedAfterRefresh = true;
	}
	
	// Разликата между текущото време и последното извикване
	var diff = now - this.ajaxLastTime;
	
	// Ако времето от последното извикване и е по - голяма от интервала
	if (diff >= this.ajaxInterval) {
		
		// Задаваме текущото време
		this.ajaxLastTime = now;
		
		// Обхождаме всички абонирани URL-та
		for (name in this.subscribedArr) {
			
			// Разлика във времето на абонираните процеси
			var diffSubscribed = now - this.lastTimeArr[name];
			
			// Ако разликата е повече от интервала
			if (diffSubscribed >= this.subscribedArr[name]['interval']) {
				
				// Задаваме текущото време
				this.lastTimeArr[name] = now;
				
				// Добавяме URL-то
				resObj[name] = this.subscribedArr[name]['url'];
			}
		}
	}
	
	return resObj;
}


/**
 * Сетваме URL-то, което ще се вика по AJAX
 * 
 * @param string - Локолното URL, което да се извика по AJAX
 */
efae.prototype.setUrl = function(url)
{
	this.url = url;
}


/**
 * Връща локалното URL, което да се извика
 * 
 * @return - Локолното URL, което да се извикa по AJAX
 */
efae.prototype.getUrl = function()
{
	
	return this.url;
}


/**
 * Задаваме URL-то, от което се вика AJAX-а
 * 
 * @param string - Локолното URL, което да се извика по AJAX
 */
efae.prototype.setParentUrl = function(parentUrl)
{
	this.parentUrl = parentUrl;
}


/**
 * Връща URL-то, от което се вика AJAX-а
 * 
 * @return - Локолното URL, което да се извикa по AJAX
 */
efae.prototype.getParentUrl = function()
{
	
	return this.parentUrl;
}

/**
 * Увеличава времето за стартиране 
 */
efae.prototype.increaseTimeout = function()
{
	// Ако не сме достигнали горната граница
	if (this.ajaxInterval < this.maxIncreaseInterval) {
		
		// Увеличаваме брояча
		this.ajaxInterval += this.increaseInterval;
	}
}


/**
 * Връща стойността на брояча в началната стойност
 */
efae.prototype.resetTimeout = function()
{
	// Връщаме старата стойност
	this.ajaxInterval = this.ajaxDefInterval;
}


/**
 * Функция, която показва toast съобщение с помощта на toast плъгина
 * Може да се комбинира с efae
 * 
 * @param object data - Обект с необходимите стойности
 * data.timeOut - след колко време да се покаже
 * data.text - текст, който да се покаже
 * data.isSticky - дали да се премахне или да остане на екрана след изтичане на времето
 * data.stayTime - колко време да се задържи на екрана - в ms
 * data.type - типа на статуса
 */
function render_showToast(data)
{
	if (typeof showToast != 'undefined') {
		showToast({
			timeOut: data.timeOut,
			text: data.text,
			isSticky: data.isSticky,
			stayTime: data.stayTime,
			type: data.type
		});
	}
}


/**
 * Накара документа да флашне/светне
 * Може да се комбинира с efae
 * 
 * @param integer docId - id на документа
 */
function render_flashDoc(docId)
{
	if (typeof flashDoc != 'undefined') {
		flashDoc(docId);
	}
}


/**
 * Скролира до документа
 * Може да се комбинира с efae
 * 
 * @param integer docId - id на документа
 */
function render_scrollTo(docId)
{
	getEO().scrollTo(docId);
}


/**
 * Функция, която добавя даден текст в съответния таг
 * Може да се комбинира с efae
 * 
 * @param object data - Обект с необходимите стойности
 * data.id - id на таг
 * data.html - текста
 * data.replace - дали да се замести текста или да се добави след предишния
 */
function render_html(data)
{
	// Неоходимите параметри
	var id = data.id;
	var html = data.html;
	var replace = data.replace;
	
	// Ако няма HTML, да не се изпуълнява
	if ((typeof html == 'undefined') || !html) return ;
	
	// Ако има JQuery
	if (typeof jQuery != 'undefined') {
		
		var idObj = $('#'+id);
		
		// Ако няма такъв таг
		if (!idObj.length) {
			
			// Задаваме грешката
			getEO().log('Липсва таг с id: ' + id);
		}
		
		// Ако е зададено да се замества
		if ((typeof replace != 'undefined') && (replace)) {
			
			// Заместваме
			idObj.html(html);
		} else {
			
			// Добавяме след последния запис
			idObj.append(html);
		}
	}
}


/**
 * Функция, която променя броя на нотификациите
 * Може да се комбинира с efae
 * 
 * @param object data - Обект с необходимите стойности
 * data.id - id на таг
 * data.cnt - броя на нотификациите
 */
function render_notificationsCnt(data)
{	
	changeTitleCnt(data.cnt);
	
	var nCntLink = get$(data.id);
	
	if(nCntLink != null) {
		changeNotificationsCnt(data);
	}
}


/**
 * Функция, която извиква подготвянето на контекстното меню
 * Може да се комбинира с efae
 */
function render_prepareContextMenu()
{	
	prepareContextMenu();
}


/**
 * Функция, която редиректва към определена страница, може да се
 * използва с efae
 * 
 * @param object data - Обект с необходимите стойности
 * data.url - URL към което да се пренасочи
 */
function render_redirect(data)
{
	var url = data.url;
	
	document.location = url;
}


/**
 * Променя броя на нотификациите в титлата на таба
 * 
 * @param cnt - броя на нотификациите
 */
function changeTitleCnt(cnt)
{
	var title = document.title;
	var numbArr = title.match(/\(([^) ]+)\)/);
	cnt = parseInt(cnt);
	
	if(numbArr) {
		numb = numbArr[1];
	} else {
		numb = '0';
	}
	
	var textSpace =  "  " ;
	
	if( parseInt(numb) > 0) {
		if(parseInt(cnt) > 0) {
			title = title.replace("(" + numb + ") ", "(" + cnt + ") ");
		} else {
			title = title.replace("(" + numb + ") ", "");
		}
		
	} else {
		if(cnt > 0) {
			title = "(" + cnt + ") " + title;
		}
	}
	
	document.title = title;
}


/**
 * Променя броя на нотификациите
 * 
 * @param object data - Обект с необходимите стойности
 * data.id - id на таг
 * data.cnt - броя на нотификациите
 */
function changeNotificationsCnt(data)
{
	render_html({'id': data.id, 'html': data.cnt, 'replace': 1});
	
	var nCntLink = get$(data.id);
	
	if(nCntLink != null) {
		
		if(parseInt(data.cnt) > 0) {
			nCntLink.className = 'haveNtf';
		} else {
			nCntLink.className = 'noNtf';
		}
	}
}


/**
 * Показва статус съобщениет
 * 
 * @param object data - Обект с необходимите стойности
 * data.text - Текста, който да се показва
 * data.isSticky - Дали да е лепкаво
 * data.stayTime - Време за което да стои
 * data.type - Типа
 * data.timeOut - Изчакване преди да се покаже
 */
function showToast(data)
{
	setTimeout(function(){
        $().toastmessage('showToast', {
            text            : data.text,
            sticky          : data.isSticky,
            stayTime        : data.stayTime,
            type            : data.type,
            inEffectDuration: 800,
            position        : 'bottom-right'
            });
    	}, data.timeOut);
}


/**
 * Experta - Клас за функции на EF
 * 
 * @category  ef
 * @package   js
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
function Experta()
{
	// Селектирания текст при първия запис
	Experta.prototype.fSelText='';
	
	// Селектирания текст, ако първя запис не е променян
	Experta.prototype.sSelText='';
	
	// Време на извикване
	Experta.prototype.saveSelTextTimeout=500;
	
	// Време на извикване в textarea
	Experta.prototype.saveSelTextareaTimeout=400;
	
	// Данни за селектирания текст в textarea
	Experta.prototype.textareaAttr = new Array();
	
	// Времето на бездействие в таба
	Experta.prototype.idleTime;
}


/**
 * Стартира таймера за бездействие в съответния таб
 */
Experta.prototype.runIdleTimer = function()
{
	// Ако е бил стартиран преди, да не се изпълнява
	if (typeof this.idleTime != 'undefined') return ;
	
	var EOinst = this;
	
	// Добавяме ивенти за ресетване при действие
	getEO().addEvent(document, 'mousemove', function(){EOinst.resetIdleTimer()});
	getEO().addEvent(document, 'keypress', function(){EOinst.resetIdleTimer()});
		
	// Стартираме процеса
	this.processIdleTimer();
}


/**
 * Стартира рекурсивен процес за определяне на времето за бездействие
 */
Experta.prototype.processIdleTimer = function()
{
	// Текущия клас
	var thisEOInst = this;
	
	// Задаваме функцията да се вика всяка секунда
	setTimeout(function() {thisEOInst.processIdleTimer()}, 1000);

	// Увеличаваме брояча
	this.increaseIdleTime();
}


/**
 * Увеличава времето на бездействие
 */
Experta.prototype.increaseIdleTime = function()
{
	// Ако не е дефиниран преди
	if (typeof this.idleTime == 'undefined') {
		
		// Стойността по подразбиране
		this.idleTime = 0;
	} else {
		
		// При всяко извикване увеличава с единица
		this.idleTime++;
	}
}


/**
 * Нулира времето на бездействие
 */
Experta.prototype.resetIdleTimer = function()
{
	// При всяко извикване нулира времето на бездействие
	this.idleTime = 0;
}


/**
 * Връща стойността на брояча за бездействие
 */
Experta.prototype.getIdleTime = function()
{
	
	return this.idleTime;
}


/**
 * Записва избрания текст
 */
Experta.prototype.saveSelText = function()
{
	// Вземаме избрания текст
	var selText = getSelText();
	
	// Ако има функция за превръщане в стринг
	if (selText.toString) {
		
		// Вземаме стринга
		selText = selText.toString();
    } else {
    	
    	return;
    }
	
	// Ако първия записан текст е еднакъв с избрания
	if (this.fSelText == selText) {
		
		// Записваме текста във втората променлива
		this.sSelText = selText;
	} else {
		
		// Ако са различни, записваме новия избран текст в първата променлива
		this.fSelText = selText;
	}

	// Инстанция
	var thisEOInst = this;
	
	// Задаваме функцията да се самостартира през определен интервал
	setTimeout(function() {thisEOInst.saveSelText()}, this.saveSelTextTimeout);
}


/**
 * Връща избрания текст, който е записан във втората променлива
 */
Experta.prototype.getSavedSelText = function()
{
	
	return this.sSelText;
}


/**
 * Добавя в атрибутите на текстареа позицията и текста на избрания текст
 * 
 * @param integer id
 */
Experta.prototype.saveSelTextInTextarea = function(id)
{
	// Текстареата
	textarea = document.getElementById(id);
	
	// Ако текстареата е на фокус
	if (textarea.getAttribute('data-focus') == 'focused') {
		
		// id на текстареата
		//id = textarea.getAttribute('id');
		
		// Вземаме избрания текст
		// var selText = getSelText();
		var selText = getSelectedText(textarea);
		
		// Ако има функция за превръщане в стринг
		if (selText.toString) {
			
			// Вземаме стринга
			selText = selText.toString();
	    } else {
	    	
	    	return;
	    }
		
		// Позиция на начало на избрания текст
		var selectionStart = textarea.selectionStart;
		
		// Позиция на края на избрания текст
		var selectionEnd = textarea.selectionEnd;
		
		// Ако не е създаден обект за тази текстареа
		if (typeof this.textareaAttr[id] == 'undefined') {
			
			// Създаваме обект, със стойности по подразбиране
			this.textareaAttr[id] = {'data-hotSelection': '', 'data-readySelection': '',
									'data-readySelectionStart': 0, 'data-readySelectionEdn': 0};
		}
		
		// Ако сме избрали нещо различно от предишното извикване на функцията
		if ((this.textareaAttr[id]['data-hotSelection'] != selText) || 
				(this.textareaAttr[id]['data-selectionStart'] != selectionStart) ||
				(this.textareaAttr[id]['data-selectionEnd'] != selectionEnd)) {
			
			// Задаваме новите стойности
			this.textareaAttr[id]['data-hotSelection'] = selText;
			this.textareaAttr[id]['data-selectionStart'] = selectionStart;
			this.textareaAttr[id]['data-selectionEnd'] = selectionEnd;
			
		} else {
			
			// Ако не сме променили избрания текст
			
			// Задаваме стойностите
			this.textareaAttr[id]['data-readySelection'] = selText;
			this.textareaAttr[id]['data-readySelectionStart'] = this.textareaAttr[id]['data-selectionStart'];
			this.textareaAttr[id]['data-readySelectionEdn'] = this.textareaAttr[id]['data-selectionEnd'];
		}
		
		// Добавяме необходимите стойности в атрибутите на текстареата
		textarea.setAttribute('data-hotSelection', this.textareaAttr[id]['data-hotSelection']);
		textarea.setAttribute('data-readySelection', this.textareaAttr[id]['data-readySelection']);
		textarea.setAttribute('data-selectionStart', this.textareaAttr[id]['data-readySelectionStart']);
		textarea.setAttribute('data-selectionEnd', this.textareaAttr[id]['data-readySelectionEdn']);
	}
	
	// Инстанция
	var thisEOInst = this;
	
	// Задаваме функцията да се самостартира през определен интервал
	setTimeout(function() {thisEOInst.saveSelTextInTextarea(id)}, this.saveSelTextareaTimeout);
}


/**
 * Сетва атрибута на текстареа, при фокус
 * 
 * @param integer id
 */
Experta.prototype.textareaFocus = function(id)
{
	// Текстареата
	textarea = document.getElementById(id);
	
	// Задваме в атрибута
	textarea.setAttribute('data-focus', 'focused');
}


/**
 * Сетва атрибута на текстареа, при загуба на фокус
 * 
 * @param integer id
 */
Experta.prototype.textareaBlur = function(id)
{
	// Текстареата
	textarea = document.getElementById(id);
	
	// Задваме в атрибута
	textarea.setAttribute('data-focus', 'none');
}


/**
 * Добавя ивент към съответния елемент
 * 
 * @param object elem - Към кой обект да се добави ивента
 * @param string event - Евента, който да слуша
 * @param string function - Функцията, която да се изпълни при ивента
 */
Experta.prototype.addEvent = function(elem, event, func)
{
	// Ако има съответната фунцкция
	// Всички браузъри без IE<9
	if (elem.addEventListener) {
		
		// Абонираме ивента
		elem.addEventListener(event,func,false);
	} else if (elem.attachEvent) {
		// За IE6, IE7 и IE8
		elem.attachEvent("on" + event, func);
	} else { 
		elem["on" + event] = func;
	}
}


/**
 * Скролва до зададеното id
 * 
 * @param integer id
 */
Experta.prototype.scrollTo = function(id)
{
	// Ако е зададен id, скролваме до него
	if (id && (typeof id != 'undefined')) {
		
		el = get$(id);
		
		// Ако има такъв елемент
		if (el && el != 'undefined' && el.scrollIntoView) {
			
			el.scrollIntoView();
		}
	} else {
		
		// Скролваме в края на екрана
		window.scroll(0, 1000000);
	}
}


/**
 * Показва съобщението в лога
 * 
 * @param string txt - Съобщението, което да се покаже
 */
Experta.prototype.log = function(txt)
{
	// Ако не е дефиниран обекта
	if (typeof console != "undefined") {
		
		// Показваме съобщението
		console.log(txt);
	}
}


/**
 * Връща сингълтон инстанция за класа Experta
 * 
 * @return object
 */
function getEO()
{
	
	return this.getSingleton('Experta');
}


/**
 * Връща сингълтон инстанция за efae класа
 * 
 * @return object
 */
function getEfae()
{
	
	return this.getSingleton('efae');
}
