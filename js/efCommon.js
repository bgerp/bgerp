// Функция за лесно селектиране на елементи
function get$() {
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


// Проверява дали браузърът е IE
function isIE()
{
  return /msie/i.test(navigator.userAgent) && !/opera/i.test(navigator.userAgent);
}


// Инициализира комбобокса
function comboBoxInit(id, suffix) 
{
	var txtCombo = get$(id);
	var selCombo = get$(id + suffix);
	var width = txtCombo.offsetWidth;
 	var arrow = selCombo.offsetHeight - 7;

	selCombo.style.width = (width + 0) + 'px'; 
	txtCombo.style.width = (width -  arrow + 2) + 'px';
	txtCombo.style.marginRight = (arrow+2) + 'px';
	selCombo.style.clip = 'rect(auto, auto, auto, ' + (width -  arrow) + 'px)';
	txtCombo.style.paddingRight = '2px';

	if(txtCombo.offsetHeight != selCombo.offsetHeight) {
		txtCombo.style.height = (selCombo.offsetHeight -0) + 'px';
		txtCombo.style.lineHeight = (selCombo.offsetHeight + 6) + 'px';
		txtCombo.style.marginTop = '2px';
		selCombo.style.marginTop = '2px';
	}

	selCombo.style.visibility = 'visible';
}


// Помощна функция за комбобокс компонента
// Прехвърля съдържанието от SELECT елемента към INPUT полето
function comboSelectOnChange(id, value, suffix) 
{   
	var inp = get$(id);

	var exVal = inp.value;

	if(exVal != '' && inp.getAttribute('data-role') == 'list') {
		get$(id).value += ', ' +  value; 
	} else {
		get$(id).value =  value; 
	}

	get$(id).focus();
	var selCombo = get$(id + suffix);
	selCombo.value = '';
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
function rp(text, textarea)
{
	if (typeof(textarea.caretPos) != 'undefined' && textarea.createTextRange)
	{
		var caretPos = textarea.caretPos;
		caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? text + ' ' : text;
		caretPos.select();
	} else if (typeof(textarea.selectionStart) != 'undefined' ) {

		var begin = textarea.value.substr(0, textarea.selectionStart);
		var end = textarea.value.substr(textarea.selectionEnd);
		var scrollPos = textarea.scrollTop;

		textarea.value = begin + text + end;

		if (textarea.setSelectionRange)
		{
			textarea.focus();
			textarea.setSelectionRange(begin.length + text.length, begin.length + text.length);
		}
		textarea.scrollTop = scrollPos;
	} else {
		textarea.value += text;
		textarea.focus(textarea.value.length - 1);
	}
}


// Редактор за BBCode текст: селектира ...
function s(text1, text2, textarea)
{	
	if (typeof(textarea.caretPos) != 'undefined' && textarea.createTextRange)
	{					

		var caretPos = textarea.caretPos, temp_length = caretPos.text.length;

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
	var el = document.getElementById(id);
	if (el.style.display=='none') el.style.display='block';
		else el.style.display='none';
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

function flashHashDoc()
{
	var h = window.location.hash.substr(1); 
	if(h) {
		flashDoc(h);
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

	var color = '#' + y + 'ff' + y;

	cells[0].style.backgroundColor = color;
	cells[1].style.backgroundColor = color;

	if(i<20) {
		i++;
		setTimeout( "flashDoc('" + docId + "', " + i + ")", 120);
	} else {
		cells[0].style.backgroundColor = 'transparent';
		cells[1].style.backgroundColor = 'transparent';
	}
}