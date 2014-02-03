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
 				jsonGetContent(xmlHttpReq.responseText, function(c) {setInnerHtml( get$(selectId),  c); input.savedValue = input.value; } );
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

/**
 * Връща избрания текст в textarea
 * 
 * @param textarea
 * @return text
 */
function getSelectedText(textarea)
{
	if (typeof(textarea.selectionStart) != 'undefined' ) {
		var selectedText = textarea.value.substr(textarea.selectionStart, textarea.selectionEnd - textarea.selectionStart);
		
		return selectedText;
	}
}

// Редактор за BBCode текст: селектира ...
function s(text1, text2, textarea, newLine, multiline)
{	
	if (typeof(textarea.caretPos) != 'undefined' && textarea.createTextRange)
	{					
		
		var caretPos = textarea.caretPos, temp_length = caretPos.text.length;
		
		if(caretPos.text != '' && caretPos.text.indexOf("\n") == -1 && text2 == '[/code]')  {
			text1 = "`";
			text2 = "`";
		}
	
		var textareaText = textarea.value;
		var position = textareaText.length;
		var previousChar = textareaText.charAt(position - 1);
	
		
		if (previousChar !="\n"  && position != 0  && newLine){
			text1 = "\n" + text1;
		}	
		
		if(multiline) {
			if(getIEVersion()==10){
				text1 = text1 + "\n";
			}
			text2 = "\n" + text2;
		}
		
		caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? text1 + caretPos.text + text2 + ' ' : text1 + caretPos.text + text2;
		
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
		var newCursorPos = textarea.selectionStart;
		var scrollPos = textarea.scrollTop;

		if(selection != '' && selection.indexOf("\n") == -1 && text2 == '[/code]') {
			text1 = "`";
			text2 = "`";
		}
		
		if (begin.charAt(begin.length-1) != "\n" && begin != '' && newLine) {
			text1 = "\n" + text1;
		}
		
		if(multiline) {
			text1 = text1 + "\n";
			text2 = "\n" + text2;
		}
			
		textarea.value = begin + text1 + selection + text2 + end;

		if (textarea.setSelectionRange)
		{
			if (selection.length == 0)
				textarea.setSelectionRange(newCursorPos + text1.length, newCursorPos + text1.length);
			else
				textarea.setSelectionRange(newCursorPos, newCursorPos + text1.length + selection.length + text2.length);
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
		$('body').live('click',function() {
			hideRichtextEditGroupsBlock();
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
		
		bgColor = 'transparent';
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
 * Показване на статус съобщения през определен интервал
 */
function getStatuses(url, timeout) {
	$.get(url,
    	function(data){
        	$.each(data, function(index, value) { 
             	var id = (value.id);
             	var text = (value.statusText);
             	var type = (value.statusType);
             	if(type == 'open') {
					var title = document.title;
					var numbArr = title.match(/\(([^) ]+)\)/);
					if(numbArr) {
						numb = numbArr[1];
					} else {
						numb = '0';
					}
					
					 
					var textSpace =  "  " ;
					

					if( parseInt(numb) > 0) {
						if(parseInt(text) > 0) {
							title = title.replace("(" + numb + ") ", "(" + text + ") ");
						} else {
							title = title.replace("(" + numb + ") ", "");
						}
						
					} else {
						if(parseInt(text) > 0) {
							title = "(" + text + ") " + title;
						}
					}

					document.title = title;
					
					var link = "";
					
					var nCntLink = get$('nCntLink');
					
					if(nCntLink != null) {
						nCntLink.innerHTML = text;

						if(parseInt(text) > 0) {
							nCntLink.className = 'haveNtf';
						} else {
							nCntLink.className = 'noNtf';
						}
					}

				} else {
					$().toastmessage('showToast', {
						text            : text,
						sticky          : true,
						stayTime        : 10000,
						inEffectDuration: 1800,
						type            : type,
						position        :'bottom-right'
					});
				}
                
            });
		}, 'json');
   		
		setTimeout(function(){getStatuses(url, timeout)}, timeout);
}


/**
 * Записва избрания текст в сесията и текущото време
 * 
 * @param string handle - Манипулатора на докуемента
 */
function saveSelectedTextToSession(handle, onlyHandle)
{
	// Вземаме избрания текст
	var selText = getSelText();

	// Ако има избран текст
	if (selText.focusOffset != selText.anchorOffset) {
		
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
    if (window.getSelection)
    {
        txt = window.getSelection();
    }
    else if (document.getSelection)
    {
        txt = document.getSelection();
    }
    else if (document.selection)
    {
        txt = document.selection.createRange().text;
    }
    else  { return; } 
	
	return txt;
}


/**
 * Добавя в посоченото id на елемента, маркирания текст от сесията, като цитат, ако не е по стар от 5 секунди
 * 
 * @param id
 */
function appendQuote(id)
{
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

	input.setAttribute("name", "Cmd");

	input.setAttribute("value", "refresh");

	form.appendChild(input);
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


// добавяне на линк към текущата страница при копиране на текст
function addLink() 
{
    var selection = window.getSelection();

    if (("" + selection).length < 30) return;
    
    var htmlDiv = document.createElement("div");
    for (var i = 0; i < selection.rangeCount; ++i) {
        htmlDiv.appendChild(selection.getRangeAt(i).cloneContents());
    }
    var selectionHTML = htmlDiv.innerHTML;
       
    var pagelink = "<br /><br /> Прочети повече на: <a href='" + document.location.href+"'>" + document.location.href + "</a>";

    var copytext = selectionHTML + pagelink;
    
    var newdiv = document.createElement('div');
    newdiv.style.position = 'absolute';
    newdiv.style.left = '-99999px';
    
    document.body.appendChild(newdiv);
    newdiv.innerHTML = copytext;
    selection.selectAllChildren(newdiv);
    window.setTimeout(function () { document.body.removeChild(newdiv); }, 0);
    
}
