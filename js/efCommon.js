var shortURL;


function spr(sel, refresh) {
    if(refresh === undefined) {
        refresh = true;
    }
    if(sel.value == 'select') {
        $("input[name*='from']").closest('tr').fadeIn();
        $("input[name*='to']").closest('tr').fadeIn();
        $("input[name*='from']").prop('disabled', false);
        $("input[name*='to']").prop('disabled', false);
        $("input[name*='from'], input[name*='to']").addClass('flashElem');
        $("input[name*='from'], input[name*='to']").css('transition', 'background-color linear 500ms');
        setTimeout(function(){ $('.flashElem').removeClass('flashElem')}, 1000);
    } else {
        $("input[name*='from']").prop('disabled', true);
        $("input[name*='to']").prop('disabled', true);
        $("input[name*='from']").closest('tr').fadeOut();
        $("input[name*='to']").closest('tr').fadeOut();
        if(refresh) {
            sel.form.submit();
        }
    }

}

/**
 * Опитваме се да репортнем JS грешките
 */
window.onerror = function (errorMsg, url, lineNumber, columnNum, errorObj) {

	if (typeof $.ajax != 'undefined') {
		$.ajax({
			url: "/A/wp/",
			data: {errType: 'JS error', currUrl: window.location.href, error: errorMsg, script: url, line: lineNumber, column: columnNum}
		})
	}
}

function runOnLoad(functionName) {
    if (window.attachEvent) {
        window.attachEvent('onload', functionName);
    } else {
        if (window.onload) {
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


/**
 * Сменя изображенията с fade ефект
 */
function fadeImages(el, delay){
	$('.fadein img:gt(0)').hide();
	setInterval(function(){
		$('.fadein :first-child').css({position: 'absolute'}).fadeOut(el).next('img').css({position: 'absolute'}).fadeIn(1500).end().appendTo('.fadein');
		$('.fadein :first-child').css({position: 'relative'});
	}, delay);
}


/**
 *  Показва тултип с данни идващи от ajax
 */
function showTooltip() {
    if (!($('.tooltip-arrow-link').length)) {
        return;
    }
    // Aко има тултипи
    var element;

    var cachedArr = new Array();

    $('body').on('click', function(e) {
        var target = $(e.target).parent();
        if ($(target).is(".tooltip-arrow-link")) {
            var url = $(target).attr("data-url");
            if (!url) {
                return;
            }

            var getDataFromUrl = true;

            if ($(e.target).attr('data-useCache')) {
            	if ($.inArray(url, cachedArr) == -1) {
            		cachedArr.push(url);
            	} else {
            		getDataFromUrl = false;
            	}
            }

            if (getDataFromUrl) {
            	resObj = new Object();
                resObj['url'] = url;
                getEfae().process(resObj);
            }

            // затваряме предишния тултип, ако има такъв
            if (typeof element != 'undefined') {
                $(element).hide();
            }

            // намираме този, който ще покажем сега
            element = $(target).parent().find('.additionalInfo');

            // Ако тултипа е в скролиращ елемент и няма достатъчно място нагоре, го показваме надолу от срелката, за да не се отреже
            if($(element).closest('.overflow-scroll').length && $(element).parent().offset().top - 150 < $(element).closest('.overflow-scroll').offset().top){
                $(element).addClass('bottom');
            }

            $(element).css('display', 'block');
        } else {
            // при кликане в бодито затвавяме отворения тултип, ако има такъв
            if (typeof element != 'undefined') {
                $(element).hide();
            }
        }
    });

    $('.tooltip-arrow-link').each(function(){
    	if ($(this).attr("data-useHover")) {

    		$(this).hover(function(){$(this).children().click();}, function(){$(element).hide();});
    	}
    });
};


function treeViewAction() {
    if(!$('.searchResult').length) {
        $('.treeView tbody tr').not('.treeLevel0').addClass('hiddenRow closedChildren');
        $('.treeView tr.treeLevel0').addClass('closedChildren');
    }

    $( ".treeView .toggleBtn" ).on( "click", function(event) {
        var id = $(this).closest('tr').attr('data-id');
        if(!$(this).closest('tr').hasClass('closedChildren')){
            $(this).closest('tr').addClass('closedChildren');
            closeChildren(id);
        } else {
            $(this).closest('tr').removeClass('closedChildren');
            openChildren(id);
        }
    });
}


function closeChildren(id){
    var children = $('tr[data-parentid=' + id + ']') ;
    $(children).each(function() {
        $(this).addClass('hiddenRow');
        var childrenId = $(this).attr('data-id');
        if($('tr[data-parentid=' + childrenId + ']').length){
            closeChildren(childrenId);
        }
    });
}


function openChildren(id){
    var children = $('tr[data-parentid=' + id + ']') ;
    $(children).each(function() {
        $(this).removeClass('hiddenRow');
        var childrenId = $(this).attr('data-id');
        if($('tr[data-parentid=' + childrenId + ']').length && !$('tr[data-id=' + childrenId + ']').hasClass('closedChildren')){
            openChildren(childrenId);
        }
    });
}


// Функция за лесно селектиране на елементи
function get$() {
    var elements = new Array();
    for (var i = 0; i < arguments.length; i++) {
        var element = arguments[i];
        if (typeof element == 'string') element = document.getElementById(element);
        if (arguments.length == 1) return element;
        elements.push(element);
    }
    return elements;
}


function createXHR() {
    var request = false;
    try {
        request = new ActiveXObject('Msxml2.XMLHTTP');
    } catch (err2) {
        try {
            request = new ActiveXObject('Microsoft.XMLHTTP');
        } catch (err3) {
            try {
                request = new XMLHttpRequest();
            } catch (err1) {
                request = false;
            }
        }
    }

    return request;
}


function ajaxRefreshContent(url, sec, id) {
    var xmlHttpReq = createXHR();

    xmlHttpReq.open('GET', url, true);

    xmlHttpReq.onreadystatechange = function() {

        if (xmlHttpReq.readyState == 4) {
            if (xmlHttpReq.responseText.length > 0) {

                if (xmlHttpReq.responseText) {
                    try {
                        var res = JSON.parse(xmlHttpReq.responseText);
                    } catch (e) {
                    }
                }

                if (res) {
                    if (res.content) {
                        if (get$(id).innerHTML != res.content) {
                            get$(id).innerHTML = res.content;
                        }
                    }

                    if (res.alert) {
                        alert(res.alert);
                    }

                    if (res.script) {
                        eval(res.script);
                    }
                }

            }
        }
    }

    xmlHttpReq.send(null);

    setTimeout(function() {
        ajaxRefreshContent(url, sec, id)
    }, sec);
}



//XMLHttpRequest class function
function efAjaxServer() {};

efAjaxServer.prototype.iniciar = function() {
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
            'Microsoft.XMLHTTP');
        var success = false;
        for (var i = 0; i < _ieModelos.length && !success; i++) {
            try {
                this._xh = new ActiveXObject(_ieModelos[i]);
                success = true;
            } catch (e) {}
        }
        if (!success) {
            return false;
        }
        return true;
    }
}

efAjaxServer.prototype.ocupado = function() {
    estadoActual = this._xh.readyState;
    return (estadoActual && (estadoActual < 4));
}

efAjaxServer.prototype.procesa = function() {
    if (this._xh.readyState == 4 && this._xh.status == 200) {
        this.procesado = true;
    }
}

efAjaxServer.prototype.get = function(params) {
    if (!this._xh) {
        this.iniciar();
    }

    if (typeof(params) == 'object') {
        var urlget = '/root/bgerp/?';

        if (params.relative_web_root) {
            urlget = params['relative_web_root'] + '/' + urlget;
        }

        var amp = '';

        // Генерираме UTL-то
        for (var n in params) {
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
            return eval('(' + this._xh.responseText + ')');
        }
    }

    return false;
}

/**
 * Връща информация за браузъра
 */
function getUserAgent()
{
	return navigator.userAgent;
}


/**
 * Връща разликата в минути между часовите зони на Гринуич и местното време
 */
function getTimezoneOffset(){
	var date = new Date();

	return date.getTimezoneOffset();
}

/**
 * Проверява дали браузърът е IE
 */
function isIE()
{
    return /msie/i.test(navigator.userAgent) && !/opera/i.test(navigator.userAgent);
}

function isRaspBerryPi()
{
    var info = getUserAgent();
    return info.indexOf("Linux armv7l") >= 0;
}

/**
 * Връща коя версия на IE е браузъра
 */
function getIEVersion()
{
    var myNav = navigator.userAgent.toLowerCase();
    return (myNav.indexOf('msie') != -1) ? parseInt(myNav.split('msie')[1]) : false;
}


/**
 * Инициализира комбобокса
 *
 * @param string id
 * @param string selectId
 */
function comboBoxInit(id, selectId) {
    var txtCombo = get$(id);
    var selCombo = get$(selectId);

    if (txtCombo && selCombo) {
        var width = txtCombo.offsetWidth;
        var arrow = 22;
        var clipPadding = isIE() ? 1 : 3;
        selCombo.style.width = (width + 1) + 'px';
        txtCombo.style.width = (width - arrow + 6) + 'px';
        txtCombo.style.marginRight = (arrow - 5) + 'px';
        selCombo.style.clip = 'rect(auto, auto, auto, ' + (width - arrow + clipPadding) + 'px)';
        txtCombo.style.paddingRight = '2px';

        if (txtCombo.offsetHeight != selCombo.offsetHeight) {
            txtCombo.style.height = (selCombo.height - 0) + 'px';
        }

        selCombo.style.visibility = 'visible';
    }
}


/**
 * Помощна функция за комбобокс компонента
 * Прехвърля съдържанието от SELECT елемента към INPUT полето
 *
 * @param string id
 * @param string value
 * @param string selectId
 */
function comboSelectOnChange(id, value, selectId) {
    var inp = get$(id);

    var exVal = inp.value;

    if (exVal != '' && inp.getAttribute('data-role') == 'list') {
        if (value) {
            get$(id).value += ', ' + value;
        }
    } else {
        //get$(id).value = value.replace(/&lt;/g, '<');
        get$(id).value = value;
    }

    get$(id).focus();
    $(id).trigger("change");

    var selCombo = get$(selectId);
    selCombo.value = '?';
    $('#' + id).change();

}


// масив, който съдържа вече инициализираните comboselect елементи
var comboBoxInited = [];


/**
 * Скрива и показва групите във формите
 * @param id на групата
 */
function toggleFormGroup(id)
{
	if($('.fs' + id).css('display') == 'none') {
		$('.fs' + id).fadeIn('slow');
		if($('.fs' + id).find('input.combo').length){
			$('.fs' + id).find('input.combo').each(function(){
				var idComboBox = $(this).attr('id');
				if(!comboBoxInited[idComboBox]){
					comboBoxInit(idComboBox, idComboBox + "_cs");
					comboBoxInited[idComboBox] = true;
				}
			});
		}
	} else {
		$('.fs' + id).fadeOut('slow');
	}
	$('.fs-toggle' + id).find('.btns-icon').fadeToggle();
	$('.fs-toggle' + id).toggleClass('openToggleRow');
    setRicheditWidth();
}


function toggleFormType(el) {
	if($(el).hasClass('toggleRight')){
		$("input[name='Advanced']").val(1);
	} else {
		$("input[name='Advanced']").val(0);
	}
	$(el).closest('form').submit();
}


/**
 * Присвоява стойност за блока с опции на SELECT елемент, като отчита проблемите на IE
 */
function setSelectInnerHtml(element, html) {
    if (isIE()) {
        var re = new RegExp("(\<select(.*?)\>)(.*?)(\<\/select\>)", "i");
        element.outerHTML = element.outerHTML.replace(re, "$1" + html + "$4");
    } else {
        element.innerHTML = html;
    }
}


/**
 * Проверява дали зададената опция е съществува в посочения с id селект
 */
function isOptionExists(selectId, option) {
    for (i = 0; i < document.getElementById(selectId).length; ++i) {
        if (document.getElementById(selectId).options[i].value == option) {

            return true;
        }
    }

    return false;
}



function focusSelect(event, id) {
    var evt = event ? event : window.event;

    if (evt.keyCode == 18) {
        var select = document.getElementById(id);
        select.focus();
    }
}

// Обновява опциите в комбобокс, като извлича новите под условие от сървъра
function ajaxAutoRefreshOptions(id, selectId, input, params) {

    if (typeof(input.savedValue) != 'undefined') {
        if (input.savedValue == input.value) return;
    }

    params.q = get$(id).value;

    if(params.q == '') return;

    // Не зареждаме нови опции, ако текущо е избрана опция
    if(isOptionExists(selectId, params.q)) return;

    // От параметрите прави УРЛ
    if (typeof(params) == 'object') {
        var urlget = '../?';

        if (params.relative_web_root) {
            urlget = params['relative_web_root'] + '/' + urlget;
        }

        var amp = '';

        // Генерираме UTL-то
        for (var n in params) {
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


            if (xmlHttpReq.responseText.length > 0) {
                jsonGetContent(xmlHttpReq.responseText, function(c) {
                    setSelectInnerHtml(get$(selectId), c);
                    input.savedValue = input.value;
                    input.onchange();
                });
            }
        }
    }

    xmlHttpReq.send(null);
}


// Парсира отговора на сървъра
// Показва грешки и забележки,
// ако е необходимо стартира скриптове
function jsonGetContent(ans, parceContent) {
    ans = eval('(' + ans + ')');

    if (ans.error) {
        alert(ans.error);
        return false;
    }

    if (ans.warning) {
        if (!confirm(ans.warning)) {
            return false;
        }
    }

    if (ans.js) {
        var headID = document.getElementsByTagName("head")[0];
        for (var id in ans.js) {
            var newScript = document.createElement('script');
            newScript.type = 'text/javascript';
            newScript.src = ans.js[id];
            //alert(ans.js[id]);
            waitForLoad = true;
            newScript.onload = function() {
                waitForLoad = false;
                alert(waitForLoad);
            }
            headID.appendChild(newScript);

            do {
                alert(1);
            }
            while (waitForLoad);
        }
    }

    if (ans.css) {
        var headID = document.getElementsByTagName("head")[0];
        for (var id in ans.css) {
            var cssNode = document.createElement('link');
            cssNode.type = 'text/css';
            cssNode.rel = 'stylesheet';
            cssNode.href = ans.css[id];
            cssNode.media = 'screen';
            headID.appendChild(cssNode);
        }
    }

    if (parceContent(ans.content) == false) {
        alert(ans.content);
        return false;
    }

    if (ans.javascript) {
        if (eval(ans.javascript) == false) {
            return false;
        }
    }

    return true;
}


// Глобален масив за popup прозорците
popupWindows = new Array();

// Отваря диалогов прозорец
function openWindow(url, name, args) {
    // Записваме всички popup прозорци в глобален масив
    popupWindows[name] = window.open(url, '_blank', args);

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
function sc(text) {
    if (typeof(text.createTextRange) != 'undefined') {
    	if (document.selection && document.selection.createRange) {
    		text.caretPos = document.selection.createRange().duplicate();
    	}
    }
}


// Редактор за BBCode текст:   ...
function rp(text, textarea, newLine) {
    var version = getIEVersion();
    if ((version == 8 || version == 9) && typeof(textarea.caretPos) != 'undefined' && textarea.createTextRange) {
        textarea.focus();
        var caretPos = textarea.caretPos;

        var textareaText = textarea.value;
        var position = textareaText.length;
        var previousChar = textareaText.charAt(position - 1);

        if (previousChar != "\n" && position != 0 && newLine) {
            text = "\n" + text;
        }

        caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? text + ' ' : text;

        textarea.focus();
    } else if (typeof(textarea.selectionStart) != 'undefined') {

        var begin = textarea.value.substr(0, textarea.selectionStart);
        var end = textarea.value.substr(textarea.selectionEnd);
        var scrollPos = textarea.scrollTop;

        if (begin.charAt(begin.length - 1) != "\n" && begin != "" && newLine) {
            begin += "\n";
        }

        textarea.value = begin + text + end;

        if (textarea.setSelectionRange) {
            textarea.focus();
            textarea.setSelectionRange(begin.length + text.length, begin.length + text.length);
        }
        textarea.scrollTop = scrollPos;
    } else {
        var textareaText = textarea.value;
        var position = textareaText.length;
        var previousChar = textareaText.charAt(position - 1);

        if (previousChar != "\n" && position != 0 && newLine) {
            text = "\n" + text;
        }

        textarea.value += text;
        textarea.focus(textarea.value.length - 1);
    }
}


/*
 * добавяне на необходимите за създаване на таблица в ричедит символи, по зададени колони и редове
 */
function createRicheditTable(textarea, newLine, tableCol, tableRow) {
    if (tableRow < 2 || tableRow > 10 || tableCol < 2 || tableCol > 10) return;
    var version = getIEVersion();
    if ((version == 8 || version == 9) && typeof(textarea.caretPos) != 'undefined' && textarea.createTextRange) {
        textarea.focus();
        var caretPos = textarea.caretPos;

        var textareaText = textarea.value;
        var position = textareaText.length;
        var previousChar = textareaText.charAt(position - 1);

        if (previousChar != "\n" && position != 0 && newLine) {
            text = "\n" + text;
        }
        text = "";
        var i, j;
        for (j = 0; j < tableRow; j++) {
            for (i = 0; i <= tableCol; i++) {
                if (i < tableCol) {
                    text += "|  ";
                } else {
                    text += "|";
                }
            }
            text += "\n";
        }

        caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? text + ' ' : text;

        textarea.focus();
    } else if (typeof(textarea.selectionStart) != 'undefined') {

        var begin = textarea.value.substr(0, textarea.selectionStart);
        var end = textarea.value.substr(textarea.selectionEnd);
        var scrollPos = textarea.scrollTop;

        if (begin.charAt(begin.length - 1) != "\n" && begin != "" && newLine) {
            begin += "\n";
        }
        text = "";
        var i, j;
        for (j = 0; j < tableRow; j++) {
            for (i = 0; i <= tableCol; i++) {
                if (i < tableCol) {
                    text += "|  ";
                } else {
                    text += "|";
                }
            }
            text += "\n";
        }

        textarea.value = begin + text + end;

        if (textarea.setSelectionRange) {
            textarea.focus();
            textarea.setSelectionRange(begin.length + text.length, begin.length + text.length);
        }
        textarea.scrollTop = scrollPos;
    } else {
        var textareaText = textarea.value;
        var position = textareaText.length;
        var previousChar = textareaText.charAt(position - 1);

        if (previousChar != "\n" && position != 0 && newLine) {
            text = "\n" + text;
        }
        for (j = 0; j < tableRow; j++) {
            for (i = 0; i <= tableCol; i++) {
                if (i < tableCol) {
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


function dblRow(table, tpl){
    $("#" + table).append(tpl);
}


/**
 * предпазване от субмит на формата, при натискане на enter във форма на richedit
 */
function bindEnterOnRicheditTableForm(textarea) {
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
function getSelectedText(textarea) {
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
function s(text1, text2, textarea, newLine, multiline, maxOneLine, everyLine) {
    if (typeof(textarea.caretPos) != 'undefined' && textarea.createTextRange) {

        var caretPos = textarea.caretPos,
            temp_length = caretPos.text.length;
        var textareaText = textarea.value;
        var position = textareaText.length;
        var previousChar = textareaText.charAt(position - 1);

        if (caretPos.text != '' && caretPos.text.indexOf("\n") == -1 && (text2 == '[/code]' || text2 == '[/bQuote]') && caretPos.text.length <= maxOneLine) {
            text1 = "`";
            text2 = "`";
        } else {

            if (selection != '' && caretPos.text.indexOf("\n") == -1 && text2 == '[/code]') {
                text1 = '[code=text]';
            }

            if (previousChar != "\n" && position != 0 && newLine && caretPos.text == '') {
                text1 = "\n" + text1;
            }

            if (multiline) {
                if (getIEVersion() == 10) {
                    text1 = text1 + "\n";
                }
                text2 = "\n" + text2;
            }
        }
        if (caretPos.text != '' && caretPos.text.indexOf("\n") && everyLine) {
            var temp = caretPos.text.replace(/\n/g, text2 + "\n" + text1);
            caretPos.text = text1 + temp + text2;
        } else {
            caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? text1 + caretPos.text + text2 + ' ' : text1 + caretPos.text + text2;
        }

        if (temp_length == 0) {
            caretPos.moveStart('character', -text2.length);
            caretPos.moveEnd('character', -text2.length);
            caretPos.select();
        } else textarea.focus(caretPos);
    } else if (typeof(textarea.selectionStart) != 'undefined') {

        var begin = textarea.value.substr(0, textarea.selectionStart);
        var selection = textarea.value.substr(textarea.selectionStart, textarea.selectionEnd - textarea.selectionStart);
        var end = textarea.value.substr(textarea.selectionEnd);
        var scrollPos = textarea.scrollTop;

        var beginPosition = textarea.selectionStart;
        var endPosition = textarea.selectionEnd;

        if (!selection) {
            if (textarea.getAttribute('data-readySelection')) {
                selection = textarea.getAttribute('data-readySelection');
                beginPosition = textarea.getAttribute('data-selectionStart');
                endPosition = textarea.getAttribute('data-selectionEnd');

                begin = textarea.value.substr(0, beginPosition);
                if (beginPosition == endPosition) {
                    var strBefore = textarea.value.substring(beginPosition - selection.length, beginPosition);
                    var strAfter = textarea.value.substring(beginPosition, beginPosition + selection.length);

                    if (strBefore == selection) {
                        beginPosition = beginPosition - selection.length;
                    } else if (strAfter == selection) {
                        endPosition = beginPosition + selection.length;
                    }
                }
            }
        }

        if (selection != '' && selection.indexOf("\n") == -1 && (text2 == '[/code]' || text2 == '[/bQuote]') && selection.length <= maxOneLine) {
            text1 = "`";
            text2 = "`";
        } else {
            if (selection != '' && selection.indexOf("\n") && everyLine) {
                var startLine = begin.lastIndexOf("\n") + 1;
                //Стринга от новия ред до маркирания ред
                var beforeSel = begin.substr(startLine, beginPosition);
                var tempSel = beforeSel + selection;
                beginPosition = startLine;
                selection = tempSel.replace(/\n/g, text2 + "\n" + text1);
            }

            if (selection != '' && selection.indexOf("\n") == -1 && text2 == '[/code]') {
                text1 = '[code=text]';
            }

            if (begin.charAt(begin.length - 1) != "\n" && begin != '' && newLine && selection == '') {
                text1 = "\n" + text1;
            }

            if (multiline) {
                text1 = text1 + "\n";
                text2 = "\n" + text2;
            }
        }

        textarea.value = textarea.value.substring(0, beginPosition) + text1 + selection + text2 + textarea.value.substring(endPosition);

        if (textarea.setSelectionRange) {
            if (selection.length == 0) textarea.setSelectionRange(beginPosition + text1.length, beginPosition + text1.length);
            else {
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


        if (previousChar != "\n" && position != 0 && newLine) {
            text1 = "\n" + text1;
        }

        if (multiline) {
            if (getIEVersion() == 10) {
                text1 = text1 + "\n";
            }
            text2 = "\n" + text2;
        }

        textarea.value += text1 + text2;
        textarea.focus(textarea.value.length - 1);
    }
}


// Редактор за BBCode текст: показва ...
function insertImage(id, img) {
    var e = document.getElementById(id + '_id');
    if (e) {
        var openTag = '[img=' + img.align;
        if (img.haveBorder) {
            openTag = openTag + ',border';
        }
        openTag = openTag + ']';
        if (img.caption) {
            img.url = img.url + ' ' + img.caption;
        }
        rp(openTag + img.url + '[/img]', e);
    }
    showImgFrame(id, 'hidden');
}


// Редактор за BBCode текст: показва ...
function showImgFrame(name, visibility) {
    var e = top.document.getElementById(name + '-rt-img-iframe');
    if (e) {
        e.style.visibility = visibility;
    }
}


// Оцветява входен елемент в зависимост от оставащите символи за писане
function colorByLen(input, maxLen, blur) {
    blur = typeof blur !== 'undefined' ? blur : false;
    var rest = maxLen - input.value.length;
    var color = 'white';
    if (rest < 0) color = 'red';
    if (rest == 0 && input.value.length > 3 && !blur) color = '#ff9999';
    if (rest == 1 && input.value.length > 3 && !blur) color = '#ffbbbb';
    if (rest == 2 && input.value.length > 3 && !blur) color = '#ffdddd';
    if (rest >= 3) color = '#ffffff';
    input.style.backgroundColor = color;
}



// Конвертира Javascript обект към GET заявка
function js2php(obj, path, new_path) {
    if (typeof(path) == 'undefined') var path = [];
    if (typeof(new_path) != 'undefined') path.push(new_path);
    var post_str = [];
    if (typeof(obj) == 'array' || typeof(obj) == 'object') {
        for (var n in obj) {
            post_str.push(js2php(obj[n], path, n));
        }
    } else if (typeof(obj) != 'function') {
        var base = path.shift();
        post_str.push(base + (path.length > 0 ? '[' + path.join('][') + ']' : '') + '=' + encodeURI(obj));
        path.unshift(base);
    }
    path.pop();

    return post_str.join('&');
}

function prepareContextMenu() {
    jQuery.each($('.more-btn'), function(i, val) {
        if($(this).hasClass('nojs')) return;
        
        var el = $(this).parent().find('.modal-toolbar');
        var position = el.attr('data-position');
        var sizeStyle = el.attr('data-sizeStyle');

        if (!position || !( position == 'left' || position== 'top' || position == 'bottom')) {
            position = 'auto'
        }

        if (!sizeStyle || sizeStyle != 'context') {
            sizeStyle = 'auto'
        }

        var act = 'popup';

        if ($(this).hasClass('iw-mTrigger')) {
        	act = 'update';
        }

        var vertAdjust = $(this).outerHeight();
        var horAdjust = -30;
        if($(this).hasClass("textBtn")) {
            horAdjust -= $(this).width() + 9;

        }

        if($(el).hasClass("twoColsContext")) {
            vertAdjust += 2;
            horAdjust += 1;
        }
        if($(el).closest(".contractorExtHolder").length) {
            horAdjust -= 6;
        }

        $(this).contextMenu(act, el, {
            'displayAround': 'trigger',
            'position': position,
            'sizeStyle': sizeStyle,
            'verAdjust': vertAdjust,
            'horAdjust': horAdjust
        });
        
        $('.modal-toolbar .button').on("click", function(){
            $('.more-btn').contextMenu('close');
        });
    });
}

function openCurrentTab(lastNotifyTime){
    if(!$('body').hasClass('modern-theme') || $('body').hasClass('wide')) return;
    var current;
    // взимаме данните за портала в бисквитката
    var portalTabs = getCookie('portalTabs');
    var lastLoggedNotification = getCookie('notifyTime');
    if($(location.hash).length) {
        // взимаме таба от # в url-то
        current = $(location.hash);
    } else if(typeof lastLoggedNotification !== 'undefined' && lastLoggedNotification < lastNotifyTime) {
        current = $("#notificationsPortal");
    } else if($("#" +  portalTabs).length) {
        current = $("#" + portalTabs );
    }  else {
        // първия таб да е активен
        current = $('.narrowPortalBlocks').first();
    }
    if(current.attr('id') == 'notificationsPortal') {
        setCookie('notifyTime', lastNotifyTime);
    }
    $(current).addClass('activeTab');
    $(current).siblings().removeClass('activeTab');

    var id = $(current).attr('id');
    setCookie('portalTabs', id);
    timeOfSettingTab =  jQuery.now();

    var tab = $('li[data-tab="' + id + '"]');
    $(tab).addClass('activeTab');
    $(tab).siblings().removeClass('activeTab');

    portalTabsChange(lastNotifyTime);
}


/**
 * Действия на табовете в мобилен
 */
function portalTabsChange(lastNotifyTime) {
    $('ul.portalTabs li').click(function(){
        var tab_id = $(this).attr('data-tab');
        $('ul.portalTabs li').removeClass('activeTab');
        $('.narrowPortalBlocks').removeClass('activeTab');

        $(this).addClass('activeTab');
        $("#"+tab_id).addClass('activeTab');
        if(tab_id == 'notificationsPortal') {
            setCookie('notifyTime', lastNotifyTime);
        }
        setCookie('portalTabs', tab_id);
    });
}

// Скрива или показва съдържанието на div (или друг) елемент
function toggleDisplay(id) {
    var elem = $("#" + id).parent().children('.more-btn');
    $("#" + id).fadeToggle("slow");
    elem.toggleClass('show-btn');
}


// Скрива групите бутони от ричедита при клик някъде
function hideRichtextEditGroups() {
	$(document.body).on("click", this, function(e){
        if (!($(e.target).is('input[type=text]'))) {
        	$('.richtext-holder-group-after').css("display", "none");
        }
    });

    return false;
}


function toggleRichtextGroups(id, event) {
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

    var hidden = $("#" + id).css("display");

    $('.richtext-holder-group-after').css("display", "none");
    if (hidden == 'none') {
        $("#" + id).show("fast");
    }

    return false;
}

// id на текущия език
var currentLangId = 0;
function prepareLangBtn(obj) {

	var arrayLang= obj.data;
	var hint = obj.hint;
	var initialLang = obj.lg;
	var id = obj.id;

	elemSelector = '.richEdit > textarea';

	if (typeof id != 'undefined') {
		elemSelector = '#' + id;
	}

	// добавяме бутона за смяна на език
	var elem = "<a class='rtbutton lang " + initialLang + "' title='" + hint +"'>" + initialLang + "</a>" ;
	$(elemSelector).parent().append(elem);

	// на всеки клик подготряме данните за смяна на езика
	$(document.body).on('click', ".rtbutton.lang", function(e){
		nextLangId = (currentLangId + 1) % arrayLang.length;
		var langObj = arrayLang[currentLangId];
		var lang = langObj.lg;
		var nextLang = arrayLang[nextLangId]['lg'];
		// смяна на класа, за да се смени цвета на бутона
		$('.rtbutton.lang').removeClass(lang).addClass(nextLang);
		currentLangId = nextLangId;
		// подаваме необходите данни за нов3ия език
		changeLang(arrayLang[nextLangId], elemSelector);
	});

	// при промяна на текста да скрием бутона
	$(elemSelector).bind('input propertychange', function() {
		 $('.rtbutton.lang').fadeOut(600);
		 setTimeout(function() {
			 $('.rtbutton.lang').remove();
		 }, 1000);

	});
}


/**
 * Действия при смяна на езика
 */
function changeLang(data, elemSelector){

	var lang = data.lg;
	$(elemSelector).val(data.data);
	$('.rtbutton.lang').text(lang);

	// spellcheck
	$('input[name=subject]').attr('spellcheck','true');
	$(elemSelector).attr('spellcheck','true');
	$('input[name=subject]').attr('lang',lang);
	$(elemSelector).attr('lang',lang);

	appendQuote(quoteId, quoteLine);
}


/****************************************************************************************
 *                                                                                      *
 *  Добавки за съвместимост със стари браузъри                                          *
 *                                                                                      *
 ****************************************************************************************/

if (!Array.prototype.forEach) {
    Array.prototype.forEach = function(fun /*, thisp*/ ) {
        var len = this.length;
        if (typeof fun != "function") return;

        var thisp = arguments[1];
        for (var i = 0; i < len; i++) {
            if (i in this) fun.call(thisp, this[i], i, this);
        }
    };
}


if (typeof String.prototype.trim !== 'function') {
    String.prototype.trim = function() {
        return this.replace(/^\s+|\s+$/g, '');
    }
}


/****************************************************************************************
 *                                                                                      *
 *  Функции за плъгина plg_Select                                                       *
 *                                                                                      *
 ****************************************************************************************/


/**
 * След промяната на даден чек-бокс променя фона на реда
 */
function chRwCl(id) {
    var pTR = $("#lr_" + id);
    var pTarget = $("#cb_" + id);

    if (!$(pTR).is("tr")) {
        return;
    }

    if ($(pTarget).is(":checked")) {
        $(pTR).addClass('highlight-row');
    } else {
        $(pTR).removeClass('highlight-row');
    }
}


/**
 * Обновява фона на реда и състоянието на бутона "С избраните ..."
 */
function chRwClSb(id) {
    chRwCl(id);
    SetWithCheckedButton();
}


/**
 * Инвертира всички чек-боксове
 */
function toggleAllCheckboxes() {
    $('[id^=cb_]').each(function() {
        var id = $(this).attr('id').replace(/^\D+/g, '');
        if ($(this).is(":checked") == true) {
            $(this).prop('checked',false);
            $('#check' + id).text("Избор");
        } else {
            $(this).prop('checked',true);
            $('#check' + id).text($("#with_selected").val());
        }
        chRwCl(id);
    });

    SetWithCheckedButton();

    return true;
}


/**
 * Задава състоянието на бутона "S izbranite ..."
 */
function SetWithCheckedButton() {
    var state = false;
    $('[id^=cb_]').each(function(i) {
        if ($(this).is(":checked") == true) {
            state = true;
        }
    });

    var btn = $('#with_selected');

    if (!btn) return;

    btn.removeClass('btn-with-selected-disabled');
    btn.removeClass('btn-with-selected');

    if (state) {
        btn.addClass('btn-with-selected');
        btn.removeAttr("disabled");
    } else {
        btn.addClass('btn-with-selected-disabled');
        btn.attr("disabled", "disabled");
    }
}

function flashHashDoc(flasher) {
    var h = window.location.hash.substr(1);
    if (h) {
        if (!flasher) {
            flasher = flashDoc;
        }
        flasher(h);
    }
}

function flashDoc(docId, i) {
    var tr = get$(docId);

    var cells = tr.getElementsByTagName('td');
    if (typeof i == 'undefined') {
        i = 1;
    }
    var col = i * 5 + 155;

    var y = col.toString(16);

    var color = '#' + 'ff' + 'ff' + y;

    cells[0].style.backgroundColor = color;
    cells[1].style.backgroundColor = color;

    if (i < 20) {
        i++;
        setTimeout("flashDoc('" + docId + "', " + i + ")", 220);
    } else {
        cells[0].style.backgroundColor = 'transparent';
        cells[1].style.backgroundColor = 'transparent';
    }

}


function flashDocInterpolation(docId) {
    var el = get$(docId); // your element

    // Ако е null или undefined
    if (!el || el == 'undefined') {
        return;
    }

    // linear interpolation between two values a and b
    // u controls amount of a/b and is in range [0.0,1.0]
    function lerp(a, b, u) {
        return (1 - u) * a + u * b;
    };

    function fade(element, property, start, end, duration) {
        var interval = 10;
        var steps = duration / interval;
        var step_u = 1.0 / steps;
        var u = 0.0;
        var theInterval = setInterval(function() {
            if (u >= 1.0) {
                clearInterval(theInterval)
            }
            var r = parseInt(lerp(start.r, end.r, u));
            var g = parseInt(lerp(start.g, end.g, u));
            var b = parseInt(lerp(start.b, end.b, u));
            var colorname = 'rgb(' + r + ',' + g + ',' + b + ')';
            element.style.backgroundColor = colorname;
            u += step_u;
        }, interval);
    };

    // in action

    var endColorHex = getBackgroundColor(el);
    var flashColor = {
        r: 255,
        g: 255,
        b: 128
    }; // yellow

    el.style.backgroundColor = '#ffff80';
    setTimeout(function() {
        el.style.backgroundColor = endColorHex;
    }, 2010);

    if (endColorHex.substring(0, 1) != '#') {
        return;
    }

    var endColor = {
        r: parseInt(endColorHex.substring(1, 3), 16),
        g: parseInt(endColorHex.substring(3, 5), 16),
        b: parseInt(endColorHex.substring(5, 7), 16)
    };

    fade(el, 'background-color', flashColor, endColor, 2000);
}


function getBackgroundColor(el) {
    var bgColor = $(el).css('background-color');

    if (bgColor == 'transparent') {
        bgColor = 'rgba(0, 0, 0, 0)';
    }

    return rgb2hex(bgColor);
}

function rgb2hex(rgb) {

    if (rgb.search("rgb") == -1) {

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
function setMinHeight() {

    var ch = document.documentElement.clientHeight;

    if (document.getElementById('framecontentTop')) {
        var fct = document.getElementById('framecontentTop').offsetHeight;

        if (document.getElementById('maincontent')) {
            var mc = document.getElementById('maincontent');
            var h = (ch - fct - 51) + 'px';
            mc.style.minHeight = h;
        }

        if (document.getElementById('packWrapper')) {
            var pw = document.getElementById('packWrapper');
            var sub = 100;
            if (document.body.className.match('wide')) {
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
function scaleViewport() {

    if (isTouchDevice()) {
        var pageWidth = $(window).width();
        var customWidth = 1024;
        if (pageWidth > customWidth) {
            $('meta[name=viewport]').remove();
            $('head').append('<meta name="viewport" content="width=' + customWidth + '">');
            $('body').css('maxWidth', customWidth);
        }
    }
}

/**
 * Проверка дали използваме touch устройство
 */
function isTouchDevice() {
    return (('ontouchstart' in window) || (navigator.msMaxTouchPoints > 0));
}


/**
 * Задава минимална височина на контента във външната част
 */
function setMinHeightExt() {
    var clientHeight = document.documentElement.clientHeight;
    if ($('#cmsTop').length) {
    	var padding = $('.background-holder').css('padding-top');
    	var totalPadding = 2 * parseInt(padding);

        var ct = $('#cmsTop').height();
        var cb = $('#cmsBottom').height();
        var cm = $('#cmsMenu').height();

        var add = 16;
        if ($('body').hasClass('wide')) {
            add = 28;
        }

        if ($('#maincontent').length) {
            var h = (clientHeight - ct - cb - cm - add);
            if(totalPadding) {
            	h = h - totalPadding + 2;
            }
            if($(window).width() > 600 && $('body').hasClass('narrow')){
            	h -= 3;
	        }
            if (h > 60) {
            	$('#maincontent').css('minHeight', h);
            }
        }
    }
}
function getWindowWidth() {
	var winWidth = parseInt($(window).width());
	// Приемаме, че най-малкият екран е 320px
    if (winWidth < 320) {
        winWidth = 320;
    }
    return winWidth;
}


function getCalculatedElementWidth() {
	var winWidth = getWindowWidth();
    // разстояние около формата
	var outsideWidth = 42;
    var menuSize = 0;
	if($('#all').length) {
		outsideWidth = 30;
		if($('#login-form input').length) {
			outsideWidth = parseInt($('#login-form input').offset().left * 2  + 2);
		}
	}  else if ($('.modern-theme').length && $('.vertical .formCell > input[type="text"]').length) {
        outsideWidth = parseInt($('.vertical .formCell > input[type="text"]').first().offset().left * 2 + 2);
    }
    if($('.sidemenu-open').length) {
        menuSize = $('.sidemenu-open').length * $('.sidemenu-open').first().width();
    }
    var formElWidth = winWidth - outsideWidth - menuSize;

    return formElWidth;
}


function markElementsForRefresh() {
    $('input, select').each(function(){
        if($(this).attr('onchange') && $(this).attr('onchange').indexOf('refreshForm') != -1 && !$(this).hasClass('readonly')) {
            $(this).addClass('contextCursor');
            setTimeout(function(){
                $('.contextCursor').siblings().addClass('contextCursor');
            }, 0);
        }
    });
}

/**
 * Задава ширина на елементите от форма в зависимост от ширината на прозореца/устройството
 */
function setFormElementsWidth() {
    if ($('body').hasClass('narrow')){
        // предпочитана ширина в em
        var preferredSizeInEm = 42;
        // разстояние около формата

        // изчислена максимална ширина формата
        var formElWidth = getCalculatedElementWidth();
        var winWidth = getWindowWidth();

        // колко ЕМ е широка страницата
        var currentEm = parseFloat($(".formTable input[type=text]").first().css("font-size"));
        if (!currentEm) {
            currentEm = parseFloat($(".formTable select").first().css("font-size"));
        }

        var sizeInEm = winWidth / currentEm;

        // колко РХ е 1 ЕМ
        var em = parseInt(winWidth / sizeInEm);

        // изчислена ширина, равна на ширината в ем, която предпочитаме
        var preferredSizeInPx = preferredSizeInEm * em;

        if (formElWidth > preferredSizeInPx) formElWidth = preferredSizeInPx;

        $('.formTable label').each(function() {
            var colsInRow = parseInt($(this).attr('data-colsInRow'));
            if (!colsInRow) {
                colsInRow = 1;
            }

            $(this).parent().css('maxWidth', parseInt((formElWidth - 10) / colsInRow));
        	$(this).parent().css('overflow-x', 'hidden');

            $(this).attr('title', $(this).text());
        });

        $('.staticFormView .formFieldValue').css('max-width', formElWidth - 5);

        $('.vertical .formTitle').css('min-width', formElWidth -10);
        $('.formTable textarea').css('width', formElWidth);
        $('.formTable .chzn-container').css('maxWidth', formElWidth);
        $('.formTable .select2-container').css('maxWidth', formElWidth);
        $('.formTable select').css('maxWidth', formElWidth);

        $('.formTable .scrolling-holder').css('maxWidth', formElWidth);

        $('.formTable .hiddenFormRow select.w50').css('width', formElWidth);
        $('.formTable .hiddenFormRow select.w75').css('width', formElWidth);
        $('.formTable .hiddenFormRow select.w100').css('width', formElWidth);
        $('.formTable .hiddenFormRow select.w25').css('width', formElWidth/2);

        $('.formTable .hiddenFormRow .inlineTo select.w50').css('width', formElWidth - 8);
        $('.formTable .hiddenFormRow .inlineTo select.w25').css('width', formElWidth/2 - 8);

        $('.formTable .inlineTo .chzn-container').css('maxWidth', formElWidth/2 - 10);
        $('.formTable .inlineTo .select2-container').css('maxWidth', formElWidth/2 - 10);
        $('.formTable .inlineTo  select').css('maxWidth', formElWidth/2 - 10);
    } else {
        $('.formTable .hiddenFormRow select.w50').css('width', "50%");
        $('.formTable .hiddenFormRow select.w75').css('width', "75%");
        $('.formTable .hiddenFormRow select.w100').css('width', "100%");
        $('.formTable .hiddenFormRow select.w25').css('width', "25%");

    	 $('.formTable label').each(function() {
    		 if($(this).parent().is('td')){
             	$(this).parent().css('white-space', "nowrap");
             }
             // ако етикета е много широк, режем го и слагаме хинт
             if ($(this).width() > 450){
            	 $(this).css('max-width', "450px");
                 $(this).css('position', "relative");
                 $(this).css('top', "3px");
                 $(this).css('overflow', "hidden");
                 $(this).attr('title', $(this).text());
             }
         });
    }
}


/**
 * Задава ширина на селект2 в зависимост от ширината на прозореца/устройството
 */
function maxSelectWidth(){
	 if ($('.narrow .horizontal .select2-container').length ) {
		 var formElWidth = getCalculatedElementWidth();
		 $('.narrow .horizontal .select2-container').css('maxWidth', formElWidth - 15);
	 }
}


/**
 * Задава ширина на елементите от нишката в зависимост от ширината на прозореца/устройството
 */
function setThreadElemWidth() {
	var offsetWidth = 45;
    var threadWidth = parseInt($(window).width()) - offsetWidth;
    $('#main-container .doc_Containers table.listTable.listAction > tbody > tr > td').css('maxWidth', threadWidth + 10);
    $('.background-holder .doc_Containers table.listTable > tbody > tr > td').css('maxWidth', threadWidth + 10);
    $('.doc_Containers .scrolling-holder').css('maxWidth', threadWidth + 10);
}

function checkForElementWidthChange() {
    $(window).resize(function(){
        setFormElementsWidth();
        setThreadElemWidth();
    });
}


function getAllLiveElements() {
    $('[data-live]').each(function() {
        var text = $(this).attr('data-live');
        var data = text.split("|");
        var el = $(this);
        $.each( data, function( key, value ) {
            var fn = window["live_" + value];
            if (typeof fn === "function") fn.apply(null, el);
        });

    });
}


/**
 * Прави елементите с определен клас да станат disabled след зареждането на страницата
 * @param className
 */
function  live_disableFieldsAfterLoad(el){
    setTimeout(function(){
        $(el).prop('disabled', true);
    }, 1000);
}


// функция, която взема елементите в контекстното меню от ajax
function dropMenu(data) {
    var ajaxFlag = 0;
    var id = data[0];
    var height = data[1];
    var url = data[2];

    var numId = id.match(/\d+/)[0];
    $('#' + id).parent().css('position', 'relative');
    $('#' + id).parent().append("<div class='modal-toolbar' data-sizestyle='context' id='contextHolder" + numId + "' data-height='" + height + "'>");

    $("#" + id).hover(function() {
        if(ajaxFlag == 1) return;
        var resObj = new Object();
        resObj['url'] = url;
        getEfae().process(resObj);
        ajaxFlag = 1;
    });

    prepareContextMenu();
}

/**
 * Задава ширината на текстареата спрямо ширината на клетката, в която се намира
 */
function setRicheditWidth(el) {
    var width = parseInt($('.formElement').width());
    $('.formElement textarea').css('width', width);
}

/**
 * Ако имаме 6 бутона в richedit, да излизат в 2 колони
 */
function prepareRichtextAddElements(){
    if($('.richedit-toolbar .addElements').length && $('.richedit-toolbar .addElements').children().length == 6) {
        $( "<span class='clearfix21'></span>" ).insertAfter( '.richedit-toolbar .addElements a:odd' );
        $('.richedit-toolbar .addElements a' ).css('display', 'table-cell');
    }
}


/**
 * Скролира listTable, ако е необходимо
 */
function scrollLongListTable() {
    if ($('body').hasClass('wide') && !$('.listBlock').hasClass('doc_Containers')) {
        var winWidth = parseInt($(window).width()) - 45;
        var tableWidth = parseInt($('.listBlock .listTable').width());
        if (winWidth < tableWidth) {
            $('.listBlock .listRows').addClass('overflow-scroll');
            $('.listBlock .listRowsDetail').addClass('overflow-scroll');
            $('.main-container').css('display', 'block');
            $('.listBlock').css('display', 'block');
        }
    }
}


/**
 * При натискане с мишката върху елемента, маркираме текста
 */
function selectInnerText(text) {
    var doc = document, range, selection;
    if (doc.body.createTextRange) {
        range = document.body.createTextRange();
        range.moveToElementText(text);
        range.select();
    } else if (window.getSelection) {
        selection = window.getSelection();
        range = document.createRange();
        range.selectNodeContents(text);
        selection.removeAllRanges();
        selection.addRange(range);
    }
}

/**
 * Записва избрания текст в сесията и текущото време
 *
 * @param string handle - Манипулатора на докуемента
 */
function saveSelectedTextToSession(handle, onlyHandle) {
    // Ако не е дефиниран
    if (typeof sessionStorage === "undefined") return;

    // Вземаме избрания текст
    var selText = getEO().getSavedSelText();

    // Ако има избран текст
    if (selText) {

        // Ако има подадено id
        if (handle) {
        	
        	// Опитваме по-коректно да определим, към кой документ се отнася избрания текст
        	try {
	        	if (typeof window.getSelection != "undefined") {
	        		var sel = window.getSelection();
	        		
	        		if (sel.rangeCount) {
		        		var c = 0;
		        		var parentNode = sel.anchorNode.parentNode;
		        		while(true) {
		        			if (c++ > 20) break;
		        			
		        			// От нивото на ричтекста, намираме div с id на документа
		        			if ($(parentNode).attr('class') == 'richtext') {
		        				parentNode = parentNode.parentNode.parentNode.parentNode.parentNode.parentNode;
		        				var handle2 = $(parentNode).attr('id');
		        				break;
		        			}
		        			
		        			parentNode = parentNode.parentNode;
		        		}
	        		}
	        		
	        		if (typeof handle2 == "undefined") {
        				handle = handle2;
	        		}
	        	}
	        } catch (err) { }
	        
	        if (typeof handle2 != "undefined") {
	        	handle = handle2;
	        }
	        
	        if ((typeof handle != "undefined") && (handle != "undefined")) {
	        	// Записваме манипулатора
	            sessionStorage.selHandle = handle;
	        }
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
        sessionStorage.selHandle = '';
    }
}


/**
 * Връща маркирания текст
 *
 * @returns {String}
 */
function getSelText() {
    var txt = '';

    try {
        if (window.getSelection) {
            txt = window.getSelection();
        } else if (document.getSelection) {
            txt = document.getSelection();
        } else if (document.selection.createRange) {
            txt = document.selection.createRange();
        }
    } catch (err) {
        getEO().log('Грешка при извличане на текста');
    }

    return txt;
}


/**
 * Текста, който се цитира (след обработка)
 */
var quoteText;


/**
 * id на полето
 */
var quoteId;


/**
 * Реда, в който ще се замества
 */
var quoteLine;


/**
 * Добавя в посоченото id на елемента, маркирания текст от сесията, като цитат, ако не е по стар от 5 секунди
 *
 * @param id
 * @param line
 */
function appendQuote(id, line) {

	quoteId = id;
	quoteLine = line;

    // Ако не е дефиниран
    if (typeof sessionStorage === "undefined") return;

    // Вземаме времето от сесията
    selTime = sessionStorage.getItem('selTime');

    // Вземаме текущото време
    now = new Date().getTime();

    // Махаме 5s
    now = now - 5000;

    // Ако вече е нагласен или не е изтекъл
	if ((!quoteText) && (selTime > now)) {

        // Вземаме текста
        text = sessionStorage.getItem('selText');

        if (text) {

            // Вземаме манипулатора на документа
            selHandle = sessionStorage.getItem('selHandle');

            // Стринга, който ще добавим
            quoteText = "[bQuote";

            // Ако има манипулато, го добавяме
            if (selHandle && (typeof selHandle != "undefined") && (selHandle != 'undefined')) {
            	quoteText += "=" + selHandle + "]";
            } else {
            	quoteText += "]";
            }
            quoteText += text + "[/bQuote]";
        }
	}

    if (quoteText) {
        var textVal = get$(id).value;

        // Добавяме към данните
        if (textVal && line) {
        	var splited = textVal.split("\n");
        	splited.splice(line, 0, "\n" + quoteText);
        	get$(id).value = splited.join("\n");
        } else {
        	get$(id).value += "\n" + quoteText + "\n\n";
        }
    }

    if (!line) {
    	moveCursorToEnd(get$(id));
    }
}


/**
 * Премества курсора в края на полето
 */
function moveCursorToEnd(el) {
    if (typeof el.selectionStart == "number") {
        el.selectionStart = el.selectionEnd = el.value.length;
    } else if (typeof el.createTextRange != "undefined") {
        el.focus();
        var range = el.createTextRange();
        range.collapse(false);
        range.select();
    }
}

/**
 * Добавя скрито инпут поле Cmd със стойност refresh
 *
 * @param form
 */
function addCmdRefresh(form) {

	// Премахва Cmd дефиниции
	$('[name^="Cmd[default]"]').remove();
	$('[name^="Cmd[refresh]"]').remove();

	var input = document.createElement("input");
	input.setAttribute("type", "hidden");
	input.setAttribute("name", "Cmd[refresh]");
	input.setAttribute("value", "1");
	form.appendChild(input);
}


/**
 * Returns the type of the argument
 * @param {Any}    val    Value to be tested
 * @returns    {String}    type name for argument
 */
function getType (val) {
    if (typeof val === 'undefined') return 'undefined';
    if (typeof val === 'object' && !val) return 'null';
    return ({}).toString.call(val).match(/\s([a-zA-Z]+)/)[1].toLowerCase();
}


/**
 * Рефрешва посочената форма. добавя команда за refresh и маха посочените полета
 */
function refreshForm(form, removeFields) {

	// Добавяме команда за рефрешване на формата
	addCmdRefresh(form);

	var frm = $(form);

	frm.css('cursor', 'wait');

	frm.find('input, select, textarea').css('cursor', 'wait');
    frm.find('#save, #saveAndNew').prop( "disabled", true );

    // Запазваме всички пароли преди ajax
    var savedPwd = [];
    $('input[type=password]').each(function(){
      savedPwd[$(this).attr('name')] =  $(this).val();
    });

	var params = frm.serializeArray();

	// Блокираме посочените полета да не се субмитват
	if (typeof removeFields == 'undefined') {
		var filteredParams = params;
	} else {
		var filteredParams = params.filter(function(e){
				var name = /[^/[]*/.exec(e.name)[0];
                
                if($.inArray(name, removeFields) == -1) {
				    return true;
                } else {
                    // $(form[e.name]).remove();
                    return false;
                }
			});
	}

	var serialized = $.param(filteredParams);

//    form.submit(); return;

	$.ajax({
		type: frm.attr('method'),
		url: frm.attr('action'),
		data: serialized + '&ajax_mode=1',
		dataType: 'json'
	}).done( function(data) {
		getEO().saveFormData(frm.attr('id'), data);
		replaceFormData(frm, data);

        // Възстановяваме запазените пароли
        setTimeout(function(){
            for (var k in savedPwd) {
                if($('[name=' + k + ']').val() == '')
                    $('[name=' + k + ']').val(savedPwd[k]);}
        },  600);
	});
}


/**
 * Изчиства съдържанието на няколко Select2 елемента с посочения клас - cssClass,
 * Като запазва стойността на текущия елемант, посочен в select2
 */
function clearSelect(select2, cssClass) {
    // Дефиниране статичен семафор за заключване
    if(typeof clearSelect.semafor == 'undefined') {
        clearSelect.lock = 0;
    }

    // Ако състоянието е блокирано - нищо не правим
    if(clearSelect.lock == 1) {
        return;
    }

    // Ако съдържанието на текущия елемент е празно - нищо не правим
    if(select2.value == '') {
        return;
    }

    // Заключваме
    clearSelect.lock = 1;

    $('.' + cssClass).each(function(i, obj) {

        if(obj.tagName == 'SELECT' && $(obj).hasClass('combo')) return;

        if(obj.name == select2.name) return;

        if(obj.tagName == 'SELECT') {

            $(obj).val("").trigger("change");
        }
        if(obj.tagName == 'INPUT') {
            $(obj).val("");
        }

    });

    // Отключване
    clearSelect.lock = 0;
}


/**
 * Помощна функция за заместване на формата
 *
 * @param object
 * @param object
 */
function replaceFormData(frm, data)
{
	// Памет за заредените вече файлове
    if ( typeof refreshForm.loadedFiles == 'undefined' ) {
        refreshForm.loadedFiles = [];
    }
    var params = frm.serializeArray();

	// Затваря всики select2 елементи
	if ($.fn.select2) {
		var selFind = frm.find('.select2-src');
		if (selFind) {
			$.each(selFind, function(a, elem){
				try {
					if ($(elem).select2()) {
						$(elem).select2().select2("close");
					}
				} catch(e) {

				}
			});
		}
	}

	if (getType(data) == 'array') {
		var r1 = data[0];
		if(r1['func'] == 'redirect') {
			render_redirect(r1['arg']);
		}
	}

	// Разрешаваме кеширането при зареждане по ajax
	$.ajaxSetup ({cache: true});

	// Зареждаме стиловете
	$.each(data.css, function(i, css) {
		if(refreshForm.loadedFiles.indexOf(css) < 0) {
			$("<link/>", {
			   rel: "stylesheet",
			   type: "text/css",
			   href: css
			}).appendTo("head");
			refreshForm.loadedFiles.push(css);
		}
	});

	// Зареждаме JS файловете синхронно
	loadFiles(data.js, refreshForm.loadedFiles, frm, data.html);

	// Забраняваме отново кеширането при зареждане по ajax
	$.ajaxSetup ({cache: false});

	var newParams = $('form').serializeArray();
	var paramsArray = [];

	$.each(params, function (i, el) {
		paramsArray[el.name] = el.value;
	});

	$.each(newParams, function () {
        if (this.name.indexOf('[') == -1 && this.name.indexOf('_') == -1  ) {
            var matchVisibleElements =  ($('*[name="' + this.name + '"]').attr('type') != 'hidden');
            var matchSmartSelects = $('input[name="' + this.name + '"]').attr('type') == 'hidden'  && $('select[data-hiddenname=' + this.name + ']').length;
            var prevElem = frm.find('input[name="' + this.name + '"][type="hidden"]')
            var newElem = $('form').find('input[name="' + this.name + '"]');
            // елементи, които са със същата стойност, но от скрити стават видими
            var matchPrevHidden = prevElem.length && newElem.length && newElem.attr('type') != 'hidden' && prevElem.val() == newElem.val();
            // за всички елементи, които са видими или смарт селект
            if(matchVisibleElements || matchSmartSelects) {
                if( (typeof paramsArray[this.name] == 'undefined' || this.value != paramsArray[this.name] || matchPrevHidden )) {
                    // добавяме класа, който използваме за пресветване и transition
                    $('*[name="' + this.name + '"]').addClass('flashElem');
                    $('select[data-hiddenname=' +  this.name + ']').addClass('flashElem');
                    $('*[name="' + this.name + '"]').siblings().addClass('flashElem');
                    $('.flashElem, .flashElem.select2 > .selection > .select2-selection').css('transition', 'background-color linear 500ms');
                    // махаме класа след 1сек
                    setTimeout(function(){ $('.flashElem').removeClass('flashElem')}, 1000);
                }
            }
        }
	});

	// Показваме нормален курсур
	frm.css('cursor', 'default');
    frm.find('#save, #saveAndNew').prop( "disabled", false );
	frm.find('input, select, textarea').css('cursor', 'default');
}
/**
 * Зарежда подадените JS файлове синхронно
 *
 * @param jsFiles
 * @param loadedFiles
 * @param frm
 * @param html
 */
function loadFiles(jsFiles, loadedFiles, frm, html)
{
	if (typeof jsFiles == 'undefined' || (jsFiles.length == 0)) {
		if (typeof frm != 'undefined') {
			frm.replaceWith(html);
		}

		return ;
	}

	file = jsFiles.shift();

	if (typeof file == 'undefined') {
		if (typeof frm != 'undefined') {
			frm.replaceWith(html);
		}

		return ;
	}

	if (loadedFiles.indexOf(file) < 0) {
		$.getScript(file, function(){loadFiles(jsFiles, loadedFiles, frm, html)});
		loadedFiles.push(file);
	} else {
		loadFiles(jsFiles, loadedFiles, frm, html);
	}
}


/**
 * Променя visibility атрибута на елементите
 */
function changeVisibility(id, type) {
	$('#' + id).css('visibility', type);
}

/**
 * На по-големите от дадена дължина стрингове, оставя началото и края, а по средата ...
 * Работи подобно на str::limitLen(...)
 */
function limitLen(string, maxLen) {
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
function getShortURL(shortUrl) {
    shortURL = decodeURIComponent(shortUrl);
}


/**
 * добавяне на линк към текущата страница при копиране на текст
 *
 * @param string text: допълнителен текст, който се появява при копирането
 */
function addLinkOnCopy(text, symbolCount) {
    var body_element = document.getElementsByTagName('body')[0];
    var selection = window.getSelection();

    if (("" + selection).length < symbolCount) return;

    var htmlDiv = document.createElement('div');

    htmlDiv.style.position = 'absolute';
    htmlDiv.style.left = '-99999px';

    body_element.appendChild(htmlDiv);

    htmlDiv.appendChild(selection.getRangeAt(0).cloneContents());

    if (typeof shortURL != 'undefined') {
        var locationURL = shortURL;
    } else {
        var locationURL = document.location.href;
    }

    htmlDiv.innerHTML += "<br /><br />" + text + ": <a href='" + locationURL + "'>" + locationURL + "</a> ";

    selection.selectAllChildren(htmlDiv);

    window.setTimeout(function() {
        body_element.removeChild(htmlDiv);
    }, 0);
}



function prepareContextHtmlFromAjax() {

    $( ".ajaxContext").parent().css('position', 'relative');
    $( ".ajaxContext").each(function() {
        var holder = document.createElement('div');
        $(holder).addClass('modal-toolbar');
        $(holder).attr('id', $(this).attr("data-id"));
        $(holder).attr('data-sizestyle', 'context');
        $(holder).css('min-height', '120px');
        $(holder).css('min-width', '140px');


        $(this).parent().append(holder);
    });
}




/**
 * Подготовка за контекстно меню по ajax
 */
function getContextMenuFromAjax() {
    prepareContextHtmlFromAjax();

    $('.ajaxContext').on('mousedown touchstart', function(e) {
        openAjaxMenu(this);
    });

    $('.ajaxContext').each(function(){
        var el = $(this);
        el.contextMenu(el.siblings('.modal-toolbar'),{triggerOn:'contextmenu', 'sizeStyle': 'context', 'displayAround': 'cursor'});
    });
}

function openAjaxMenu(el) {

    var url = $(el).attr("data-url");
    if(!url) return;

    resObj = new Object();
    resObj['url'] = url;
    getEfae().process(resObj);
}


/**
 * При копиране на текст, маха интервалите от вербалната форма на дробните числа
 */
function editCopiedTextBeforePaste() {
	$('.listTable').bind('copy', function(event, data) {
		var body_element = document.getElementsByTagName('body')[0];
		var selection = window.getSelection();

		var htmlDiv = document.createElement('div');

		htmlDiv.style.position = 'absolute';
		htmlDiv.style.left = '-99999px';

		body_element.appendChild(htmlDiv);

		htmlDiv.appendChild(selection.getRangeAt(0).cloneContents());

		//В клонирания елемент сменяме стиловете, за да избегнем отделните редове, ако имаме елементи със smartCenter
		$(htmlDiv).find('.maxwidth').css('display', 'inline');

		// временна променлива, в която ще заменстваме
		var current = htmlDiv.innerHTML.toString();

		//намира всеки стринг, който отгоравя на израза
		var matchedStr =  current.match(/([0-9]{1,3})((\&nbsp\;){1}([0-9]{1,3}))+/g);

		if(matchedStr){
			var replacedStr = new Array();

			for(var i=0; i< matchedStr.length; i++){
				// променя всеки от стринговете
				replacedStr[i] = matchedStr[i].replace(/(\&nbsp\;)/g, '');
				
				var mRegExp = escapeRegExp(matchedStr[i]);
				
				var regExp = new RegExp(mRegExp, "g");
				
				// прави замяната в тези стрингове
				current = current.replace(regExp ,replacedStr[i]);
			}
			if(current.indexOf('<table>') == -1){
				current = '<table>' + current + "</table>";
			}
			
			htmlDiv.innerHTML = current;
			selection.selectAllChildren(htmlDiv);
		}

		window.setTimeout(function() {
			body_element.removeChild(htmlDiv);
		}, 0);
	});
}


/**
 * Ескейпва регулярния израз
 * 
 * @param str
 * 
 * @returns
 */
function escapeRegExp(str) {
	
	if (!str.trim()) return ;
	
    return str.replace(/[\'\"\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
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
function getSingleton(name) {
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
function createObject(name) {
    try {
        var inst = new window[name];
    } catch (err) {

        var inst = Object.create(window[name].prototype);
    }

    return inst;
}



/**
 * Предпазване от двойно събмитване
 *
 * @param id: id на формата
 */
function preventDoubleSubmission(id) {
    var form = '#' + id;
    var lastSubmitStr, submitStr, lastSubmitTime, timeSinceSubmit;

    jQuery(form).bind('submit', function(event, data) {
        if (lastSubmitTime) {
            timeSinceSubmit = jQuery.now() - lastSubmitTime;
        }
        submitStr = $(form).serialize();

        if ((typeof lastSubmitStr == 'undefined') || (lastSubmitStr != submitStr) || ((typeof timeSinceSubmit != 'undefined') && timeSinceSubmit > 10000)) {
            lastSubmitTime = jQuery.now();
            lastSubmitStr = submitStr;

            return true;
        }
        // Блокиране на събмита, ако няма промени и за определено време
        event.preventDefault();
    });
}


/*
 * Функция за предпазване от двоен клик
 */
var lastClickTime, timeSinceClick;
function preventDoubleClick() {
	if (lastClickTime) {
		timeSinceClick = jQuery.now() - lastClickTime;
	}

	if ((typeof lastClickTime == 'undefined') || ((typeof timeSinceClick != 'undefined') && timeSinceClick > 3000)) {
		lastClickTime = jQuery.now();
	    return true;
	}

	// Блокиране на клика,  за определено време
	return false;
}

/**
 * Подравняване на числата в средата
 */
function centerNumericElements() {
	$('.document .listTable').each(function() {
        var table = $(this);
        var numericWidth = [];
        $(this).find(' > tbody > tr').each(function() {
        	var i = 1;
	        $(this).find('td').each(function() {
	        	if($(this).find('.numericElement') && (!numericWidth[i] || numericWidth[i] < $(this).find('.numericElement').width())){
	            	numericWidth[i] = $(this).find('.numericElement').width();
	            }
	            i++;
	        });
        });

        for (key in numericWidth) {
        	if(numericWidth[key]){
        		$(table).find("td:nth-child(" + key + ") .numericElement").css('width', numericWidth[key] + 1);
        	}
        }
    });
}


/**
 * Подравняване на числата в средата
 */
function smartCenter() {
		if(!$("span.maxwidth").length) return;

        var smartCenterWidth = [];
    	$("span.maxwidth").css('display', 'inline-block');
		$("span.maxwidth").each(function() {
            if($(this).hasClass('totalCol') ){
                var dataCol = $(this).closest('table').find('tr td:last').find('.maxwidth').attr('data-col');
                $(this).attr('data-col', dataCol);
            }
        	if(!smartCenterWidth[$(this).attr('data-col')] || smartCenterWidth[$(this).attr('data-col')] < $(this).width()){
        		smartCenterWidth[$(this).attr('data-col')] = $(this).width();
            }
        });

        for (key in smartCenterWidth) {
        	$("span.maxwidth[data-col='" + key + "']").css('width', smartCenterWidth[key] );
        }

        $("span.maxwidth:not('.notcentered')").css('display', "block");
        $("span.maxwidth:not('.notcentered')").css('margin', "0 auto");
        $("span.maxwidth:not('.notcentered')").css('white-space', "nowrap");
}


/**
 * Решава кои keylist групи трябва да са отворени при зареждане на страницата
 */
function checkForHiddenGroups() {
    //Взимаме всички inner-keylist таблици
    var groupTables = $(".inner-keylist");

    groupTables.each(function() {
        //за всяка ще проверяваме дали има чекнати инпути
        var checkGroup = $(this);

        var currentKeylistTable = $(checkGroup).closest("table.keylist");
        var className = checkGroup.find('tr').attr('class');

        var groupTitle = $(currentKeylistTable).find("#" + className);

        if (groupTitle.hasClass('group-autoOpen')) {
            groupTitle.addClass('opened');

        } else {
            var checked = 0;
            var currentInput = checkGroup.find("input");

            //за всеки инпут проверяваме дали е чекнат
            currentInput.each(function() {
                if (this.checked) {
                    checked = 1;
                }
            });

            //ако нямаме чекнат инпут скриваме цялата група и слагаме състояние затворено
            if (checked == 0) {
                groupTitle.addClass('closed');
                checkGroup.find('tr').addClass('hiddenElement');

            } else {
                //в проривен случай е отворено
                groupTitle.addClass('opened');
            }
        }

    });
}


/**
 * В зависимост от натиснатия елемент, се определя какво действие трябва да се извърши с кейлист полетата
 */
function keylistActions(el) {
	 $('.keylistCategory').on('click', function(e) {
		 // ако натиснем бутона за инвертиране на чекбоксовете
		  if ($(e.target).is(".invert-checkbox")) {
			  // ако групата е затворена, я отваряме
			  if($(e.target).closest('.keylistCategory').hasClass('closed')) {
				  toggleKeylistGroups(e.target);
			  }
			  //инвертираме
			  inverseCheckBox(e.target);
		  } else {
			  // в противен случай затваряме/отваряме групата
			  toggleKeylistGroups(e.target);

		  }
	 });
}


/**
 * Скролиране на табовете в мобилен
 */
function sumOfChildrenWidth() {
	if($('body').hasClass('narrow')){
		if ($('#main-container > div.tab-control > .tab-row .row-holder .tab').length){
			var sum=0;
			$('#main-container > div.tab-control > .tab-row .row-holder .tab').each( function(){ sum += $(this).width() + 5; });
			$('#main-container > div.tab-control > .tab-row .row-holder').width( sum );

			var activeOffset = $('#main-container > div.tab-control > .tab-row .row-holder .tab.selected').offset();
			if(activeOffset && activeOffset.left > $(window).width() - 30) {
				$('#main-container > div.tab-control > .tab-row ').scrollLeft(activeOffset.left);
			}
		}
		if ($('.single-thread .docStatistic div.alphabet div.tab-row .tab').length){
            var sumDocTab=0;
            $('.docStatistic div.alphabet div.tab-row .tab').each( function(){ sumDocTab += $(this).width() + 5; });
            var tableWidth = $('.docStatistic div.alphabet.tab-control .listTable').width();
            var maxWidth = Math.max(tableWidth, sumDocTab);
            $('.docStatistic div.tab-row').css('width',  sumDocTab);
            $('.docStatistic  div.alphabet.tab-control .listTable').css('width',  sumDocTab);
            $('.docStatistic').css('width',  maxWidth);
            $('.docStatistic').css('display', 'table');
            $('.docStatistic').css('overflow', 'scroll');
		}
	}
}


/**
 *  скриваме/показваме прилежащата на елемента група
 */
function toggleKeylistGroups(el) {
	//в нея намириме всички класове, чието име е като id-то на елемента, който ще ги скрива
    var trItems = findElementKeylistGroup(el);
    var element = $(el).closest("tr.keylistCategory");
    if (trItems.length) {
        //и ги скриваме
        trItems.toggle("slow");

        //и сменяме състоянието на елемента, на който е кликнато
        element.toggleClass('closed');
        element.toggleClass('opened');
    }

}


/**
 *  намираме прилежащата на елемента група
 */
function findElementKeylistGroup(el){
	  var element = $(el).closest("tr.keylistCategory");

	    var trId = element.attr("id");

	    //намираме keylist таблицата, в която се намира
	    var tableHolder = $(element).closest("table.keylist");

	    //в нея намириме всички класове, чието име е като id-то на елемента, който ще ги скрива
	    var keylistGroups = tableHolder.find("tr." + trId);

	    return keylistGroups;
}


/**
 *  инвертираме чекбоксовете в групата на елемента
 */
function inverseCheckBox(el){
	// сменяме иконката
	$(el).parent().find(".invert-checkbox").toggleClass('hidden');
	var trItems = findElementKeylistGroup(el);

	//инвертираме
	$(trItems).find('.checkbox').each(function() {
		if(this.checked) {
			$(this).prop('checked',false);
		} else {
			$(this).prop('checked',true);
		}
	});
}


function actionsWithSelected() {
    prepareCheckboxes();


    $('.checkbox-btn').on('click', function(e){
        e.preventDefault();
        if($(this).text() == $("#with_selected").val()) {
            $("#with_selected").click();
        }
        var id = $(this).attr("id").match(/\d+/)[0];

        $(".custom-checkboxes").css("visibility", "visible");
        $(".custom-checkboxes").css("display", "table-cell");

        $("#cb_" + id).prop("checked", !$("#cb_" + id).prop("checked"));

        $(this).closest('.modal-toolbar').css('display', 'none');
        SetWithCheckedButton();
        $(".invert-checkboxes").css("display", "table-cell");
        $(".invert-checkboxes").css("margin-right", "12px");
        if($("#cb_" + id).is(':checked')) {
            $('#check' + id).text($("#with_selected").val());
        }
    });

    $(".custom-checkboxes").on('click', function(e){
        if($(this).is(':checked')) {
            var id = $(this).attr("id").match(/\d+/)[0];
            $('#check' + id).text($("#with_selected").val());
        }
    });

}

function prepareCheckboxes(){
    var toggle = $(document.createElement('input')).attr({
        name: "toggle",
        style: "display:none",
        type:  'checkbox',
        onclick: 'toggleAllCheckboxes();'
    });
    $(toggle).addClass('invert-checkboxes');

    $('.checkbox-btn').each(function(){
        var id = $(this).attr("id").match(/\d+/)[0];
        var element = $(document.createElement('input')).attr({
            id:    'cb_' + id,
            name: "R[" + id + "]",
            style: "display:none",
            value: 'myValue',
            type:  'checkbox',
            onclick: 'chRwClSb("' + id +'")'
        });
        $(element).addClass('custom-checkboxes');
        $(this).closest('td').prepend(element);
        $(this).closest('tr').attr("id", 'lr_' + id);


        $(".custom-checkboxes").css("visibility", "hidden");
        $(".custom-checkboxes").css("display", "none");
    });

    $('.checkbox-btn').first().closest('table').find('th').first().prepend(toggle);
}

// проверява дали могат да се съберат 2 документа на една страница
function checkForPrintBreak(maxHeightPerDoc) {
    if ($(".print-break").height() <= maxHeightPerDoc) {
        $(".print-break").addClass("print-nobreak");
    }
}


function scalePrintingDocument(pageHeight){
	if($('.printing').height() > pageHeight) {
		if($('.printing').height() > pageHeight) {
			$('.document').css('line-height', '105%');
			$('.document').css('font-size', '0.95em');
			if($('.printing').height() > pageHeight) {
				$('.document').css('line-height', '100%');
				$('.document').css('font-size', '0.9em');
				if($('.printing').height() > pageHeight) {
					$('.document').css('font-size', '0.87em');
					if($('.printing').height() > pageHeight) {
						$('.document').css('line-height', '110%');
						$('.document').css('font-size', '0.97em');
						$('.footerDocInfo').css('display', 'table-cell');
						$('.footerDocBlock').css('display', 'block');
					}
				}
			}
		}
	}
	if($(".print-break").length){
		checkForPrintBreak(620);
	}
}

function makeTooltipFromTitle(){
	var targets = $( '[rel~=tooltip]' ),
    target  = false,
    tooltip = false,
    title   = false;

	targets.bind( 'mouseenter', function()
	{
	    target  = $( this );
	    tip     = target.attr( 'title' );
	    tooltip = $( '<div class="tooltip"></div>' );

	    if( !tip || tip == '' )
	        return false;

	    target.removeAttr( 'title' );
	    tooltip.css( 'opacity', 0 )
	           .html( tip )
	           .appendTo( 'body' );

	    var init_tooltip = function()
	    {
	        if( $( window ).width() < tooltip.outerWidth() * 1.5 )
	            tooltip.css( 'max-width', $( window ).width() / 2 );
	        else
	            tooltip.css( 'max-width', 340 );

	        var pos_left = target.offset().left + ( target.outerWidth() / 2 ) - ( tooltip.outerWidth() / 2 ),
	            pos_top  = target.offset().top - tooltip.outerHeight() - 20;

	        if( pos_left < 0 )
	        {
	            pos_left = target.offset().left + target.outerWidth() / 2 - 20;
	            tooltip.addClass( 'left' );
	        }
	        else
	            tooltip.removeClass( 'left' );

	        if( pos_left + tooltip.outerWidth() > $( window ).width() )
	        {
	            pos_left = target.offset().left - tooltip.outerWidth() + target.outerWidth() / 2 + 20;
	            tooltip.addClass( 'right' );
	        }
	        else
	            tooltip.removeClass( 'right' );

	        if( pos_top < 0 )
	        {
	            var pos_top  = target.offset().top + target.outerHeight();
	            tooltip.addClass( 'top' );
	        }
	        else
	            tooltip.removeClass( 'top' );

	        tooltip.css( { left: pos_left, top: pos_top } )
	               .animate( { top: '+=10', opacity: 1 }, 50 );
	    };

	    init_tooltip();
	    $( window ).resize( init_tooltip );

	    var remove_tooltip = function()
	    {
	        tooltip.animate( { top: '-=10', opacity: 0 }, 50, function()
	        {
	            $( this ).remove();
	        });

	        target.attr( 'title', tip );
	    };

	    target.bind( 'mouseleave', remove_tooltip );
	    tooltip.bind( 'click', remove_tooltip );
	});
}
/**
 *  Плъгин за highlight на текст
 */
jQuery.extend({
    highlight: function(node, re, nodeName, className) {
        if (node.nodeType === 3) {
            var match = node.data.match(re);
            if (match) {
                var highlight = document.createElement(nodeName || 'span');
                highlight.className = className || 'highlight';
                if (/\s/.test(node.data[match.index])) {
                    match.index++;
                }
                var wordNode = node.splitText(match.index);
                wordNode.splitText(match[2].length);
                var wordClone = wordNode.cloneNode(true);
                highlight.appendChild(wordClone);
                wordNode.parentNode.replaceChild(highlight, wordNode);
                return 1; //skip added node in parent
            }
        } else if ((node.nodeType === 1 && node.childNodes) && // only element nodes that have children
        !/(script|style)/i.test(node.tagName) && // ignore script and style nodes
        !(node.tagName === nodeName.toUpperCase() && node.className === className)) { // skip if already highlighted
            for (var i = 0; i < node.childNodes.length; i++) {
                i += jQuery.highlight(node.childNodes[i], re, nodeName, className);
            }
        }
        return 0;
    }
});


jQuery.fn.unhighlight = function(options) {
    var settings = {
        className: 'highlight',
        element: 'span'
    };
    jQuery.extend(settings, options);

    return this.find(settings.element + "." + settings.className).each(function() {
        var parent = this.parentNode;
        parent.replaceChild(this.firstChild, this);
        parent.normalize();
    }).end();
};


jQuery.fn.highlight = function(words, options) {
    var settings = {
        className: 'highlight',
        element: 'span',
        caseSensitive: false,
        wordsOnly: false,
        startsWith: true
    };
    jQuery.extend(settings, options);

    if (words.constructor === String) {
        words = [words];
    }
    words = jQuery.grep(words, function(word, i) {
        return word != '';
    });
    words = jQuery.map(words, function(word, i) {
        return word.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
    });
    if (words.length == 0) {
        return this;
    };

    var flag = settings.caseSensitive ? "" : "i";
    var pattern = "(" + words.join("|") + ")";
    if (settings.wordsOnly) {
        pattern = "\\b" + pattern + "\\b";
    }
    if (settings.startsWith) {
        pattern = "(\\s|^)" + pattern;
    }
    var re = new RegExp(pattern, flag);

    return this.each(function() {
        jQuery.highlight(this, re, settings.element, settings.className);
    });
};


function render_google(){
    googleSectionalElementInit();
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
function efae() {
    var efaeInst = this;

    // Добавяме ивенти за ресетване при действие
    getEO().addEvent(document, 'mousemove', function() {
        efaeInst.resetTimeout()
    });
    getEO().addEvent(document, 'keypress', function() {
        efaeInst.resetTimeout()
    });

    // Масив с всички абонирани
    efae.prototype.subscribedArr = new Array();

    // Масив с времето на последно извикване на функцията
    efae.prototype.lastTimeArr = new Array();

    // През колко време да се вика функцията `run`
    efae.prototype.timeout = 1000;

    // URL-то, което ще се вика по AJAX
    efae.prototype.url;

    // Уникално ID на хита
    efae.prototype.hitId;

    // Префикса, за рендиращата функция
    efae.prototype.renderPrefix = 'render_';

    // Времето в милисекунди, с което ще се увеличава времето на изпълнение
    efae.prototype.increaseInterval = 100;

    // Горната граница (в милисекунди), до която може да се увеличи брояча
    efae.prototype.maxIncreaseInterval = 300000;

    // През колко време да се праща AJAX заяка към сървъра
    efae.prototype.ajaxInterval = efae.prototype.ajaxDefInterval = 5000;

    // Кога за последно е стартирана AJAX заявка към сървъра
    efae.prototype.ajaxLastTime = new Date();

    // Интервал над който ще се нулира брояча
    // Когато устройството е заспало, да се форсират всички табове след събуждане (30 мин)
    efae.prototype.forceStartInterval = 1800000;

    // Дали процеса е изпратена AJAX заявка за извличане на данните за показване след рефреш
    efae.prototype.isSendedAfterRefresh = false;

    // Флаг, указващ дали има грешка в AJAX
    efae.prototype.AJAXHaveError = false;

    // Флаг, указващ дали е оправена грешката в AJAX
    efae.prototype.AJAXErrorRepaired = false;

    // УРЛ, от което се вика AJAX-a - отворения таб
    Experta.prototype.parentUrl;

    // Флаг, който се вдига преди обновяване на страницата
    Experta.prototype.isReloading = false;

    // Флаг, който указва дали все още се чака резултат от предишна AJAX заявка
    Experta.prototype.isWaitingResponse = false;
	
    // Флаг, който указва колко време да не може да се прави AJAX заявки по часовник
    efae.prototype.waitPeriodicAjaxCall = 0;
}


/**
 * Функция, която абонира дадено URL да извлича данни в определен интервал
 *
 * @param string name - Името
 * @param string url - URL-то, което да се използва за извличане на информация
 * @param integer interval - Интервала на извикване в милисекунди
 */
efae.prototype.subscribe = function(name, url, interval) {
    // Създаваме масив с името и добавяме неоходимите данни в масива
    this.subscribedArr[name] = new Array();
    this.subscribedArr[name]['url'] = url;
    this.subscribedArr[name]['interval'] = interval;

    // Текущото време
    this.lastTimeArr[name] = new Date();
};


/**
 * Фунцкция, която се самозацикля и извиква извличането на данни
 */
efae.prototype.run = function() {
    try {
        // Увеличаваме брояча
        this.increaseTimeout();
    	
        if (this.waitPeriodicAjaxCall <= 0) {
        	// Вземаме всички URL-та, които трябва да се извикат в този цикъл
            var subscribedObj = this.getSubscribed();
			
            // Стартираме процеса
            this.process(subscribedObj);
        } else {
        	this.waitPeriodicAjaxCall--;
        }
    } catch (err) {

        // Ако възникне грешка
        getEO().log('Грешка при стартиране на процеса');
    } finally {
        // Инстанция на класа
        var thisEfaeInst = this;

        // Задаваме да се самостартира
        setTimeout(function() {
            thisEfaeInst.run()
        }, this.timeout);
    }
};


/**
 * Връща броя на записите в обекта
 *
 * @param object subscribedObj - Обект, който да се преброи
 *
 * @return integer
 */
efae.prototype.getObjectKeysCnt = function(subscribedObj) {
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
};


/**
 * Извиква URL, който стартира абонираните URL-та на които им е дошло времето да се стартират
 * и рендира функциите от резултата
 *
 * @param object subscribedObj - Обект с URL-то, което трябва да се вика
 * @param object otherData - Обект с допълнителни параметри, които ще се пратят по POST
 * @param boolean async - Дали да се стартира асинхронно. По подразбиране не true
 */
efae.prototype.process = function(subscribedObj, otherData, async) {
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

        if (typeof(this.getHitId()) != 'undefined') {
            dataObj['hitId'] = this.getHitId();
        }

        // Добавяме флаг, който указва, че заявката е по AJAX
        dataObj['ajax_mode'] = 1;

        // Преди да пратим заявката, вдигаме флага, че е пратена заявката, за да не се прати друга
        // докато не завърши текущата
        this.isWaitingResponse = true;

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
                } catch (err) {

                    // Ако възникне грешка
                    getEO().log(err + 'Несъществуваща фунцкция: ' + func + ' с аргументи: ' + arg);
                }
            }

            if (getEfae().AJAXHaveError) {
            	getEfae().AJAXErrorRepaired = true;
            }
        }).fail(function(res) {

        	// Ако се обновява страницата без AJAX и възникне грешка
        	if (getEO().isReloading) return ;

        	if((res.readyState == 0 || res.status == 0) && res.getAllResponseHeaders()) return;

        	var text = 'Connection error';
    		var errType = 'error';
    		var timeOut = 3000;
        	
        	if (res.status == 404) {
        		text = 'Липсващ ресурс';
        	} else if (res.status == 500) {
        		text = 'Грешка в сървъра';
        	} else if (res.status == 503) {
        		text = res.responseText;
        		text = text.replace(/<\/?[^>]+(>|$)/g, "\n");
        		text = text.trim();
        		text = text.replace(/(?:\r\n|\r|\n)+\s*/g, "<br>");
        		
        		errType = 'warning';
        		timeOut = 1;
        	}
        	
        	var toastErrType = '.toast-type-' + errType;
        	var connectionerrStatus = '.connection-' + errType + '-status';
        	
        	if (res.status == 503) {
				
            	// Ако е имало грешка, премахваме статуса за да покажем новия
            	if (getEfae().AJAXHaveError) {
					
            		if ($(connectionerrStatus).length) {
            			$(connectionerrStatus).remove();
            		}
					
            		if ($(toastErrType).length) {
            			$(toastErrType).remove();
            		}
            	}
        	}
        	
        	getEO().log('Грешка при извличане на данни по AJAX - ReadyStatus: ' + res.readyState + ' - Status: ' + res.status);

        	getEfae().AJAXHaveError = true;
            getEfae().AJAXErrorRepaired = false;

        	setTimeout(function() {

        		if (getEfae().AJAXErrorRepaired) return ;

	        	if (typeof showToast != 'undefined' && $().toastmessage) {
	        		if (!$(toastErrType).length) {
	        			showToast({
		                    timeOut: 1,
		                    text: text,
		                    isSticky: true,
		                    stayTime: 4000,
		                    type: errType
		                });
	        		}
	            } else {
	            	// Ако не е добавено съобщение за грешка
	            	if (!$(connectionerrStatus).length) {
	            		// Ако възникне грешка
	                    var errorData = {id: "statuses", html: "<div class='statuses-message statuses-error connection-error-status'>" + text +"</div>", replace: false};
	                    render_html(errorData);
	            	}
	            }
        	}, timeOut);
        }).always(function(res) {

        	// След приключване на процеса сваляме флага
        	getEfae().isWaitingResponse = false;

        	// Ако е имало грешка и е оправенена, премахваме статуса
        	if (getEfae().AJAXHaveError && getEfae().AJAXErrorRepaired) {

        		if ($('.connection-error-status').length) {
        			$('.connection-error-status').remove();
        		}
        		if ($('.connection-warning-status').length) {
        			$('.connection-warning-status').remove();
        		}

        		if ($('.toast-type-error').length) {
        			$('.toast-type-error').remove();
        		}
        		if ($('.toast-type-warning').length) {
        			$('.toast-type-warning').remove();
        		}
        	}
        });
    } else {

        // Изкарваме грешката в лога
        getEO().log('JQuery не е дефиниран');
    }
};


/**
 * Намира абонираните URL-та на които им е време да се стартират
 *
 * @return object - Обект с абонираните URL-та на които им е време да се стартират
 */
efae.prototype.getSubscribed = function() {
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

    // Нулираме брояча, ако дълго време не е стартирано
    // Ако е заспало устройството да се уеднаквят табовете при събуждане
    if (diff >= this.forceStartInterval) {
    	this.resetTimeout();
    }

    // След една минута без заявка, не се проверява дали има висяща заявка
    if (diff <= this.maxIncreaseInterval) {

    	// Ако има друга заявка, която все още не е изпълнена изчакваме да приключи
    	// Преди да пратим следваща
    	if (this.isWaitingResponse) return resObj;
    }

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
};


/**
 * Сетваме URL-то, което ще се вика по AJAX
 *
 * @param string - Локолното URL, което да се извика по AJAX
 */
efae.prototype.setUrl = function(url) {
    this.url = url;
};


/**
 * Връща локалното URL, което да се извика
 *
 * @return - Локолното URL, което да се извикa по AJAX
 */
efae.prototype.getUrl = function() {

    return this.url;
};


/**
 * Задаваме URL-то, от което се вика AJAX-а
 *
 * @param string - Локолното URL, което да се извика по AJAX
 */
efae.prototype.setParentUrl = function(parentUrl) {
    this.parentUrl = parentUrl;
};


/**
 * Връща URL-то, от което се вика AJAX-а
 *
 * @return - Локолното URL, което да се извикa по AJAX
 */
efae.prototype.getParentUrl = function() {

    return this.parentUrl;
};


/**
 * Задава стойност за hitId
 *
 * @param string
 */
efae.prototype.setHitId = function(hitId) {
    this.hitId = hitId;
};


/**
 * Връща стойността на hitId
 *
 * @return string
 */
efae.prototype.getHitId = function() {

    return this.hitId;
};

/**
 * Увеличава времето за стартиране
 */
efae.prototype.increaseTimeout = function() {
    // Ако не сме достигнали горната граница
    if (this.ajaxInterval < this.maxIncreaseInterval) {

        // Увеличаваме брояча
        this.ajaxInterval += this.increaseInterval;
    }
};


/**
 * Връща стойността на брояча в началната стойност
 */
efae.prototype.resetTimeout = function() {
    // Връщаме старата стойност
    this.ajaxInterval = this.ajaxDefInterval;
};


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
function render_showToast(data) {
    if (typeof showToast != 'undefined' && $().toastmessage) {
        showToast({
            timeOut: data.timeOut,
            text: data.text,
            isSticky: data.isSticky,
            stayTime: data.stayTime,
            type: data.type
        });
    } else {
    	var errorData = {id: "statuses", html: "<div class='statuses-message statuses-" + data.type + "'>" + data.text +"</div>", replace: !data.isSticky};
        render_html(errorData);
    }
}


/**
 * Накара документа да флашне/светне
 * Може да се комбинира с efae
 *
 * @param integer docId - id на документа
 */
function render_flashDoc(docId) {
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
function render_scrollTo(docId) {
    getEO().scrollTo(docId);
}


/**
 *
 */
function render_replaceById(data) {

    // Неоходимите параметри
    var id = data.id;
    var html = data.html;

    var idsArr = data.Ids.split(",");

	var id;

	for (index = 0; index < idsArr.length; ++index) {
		id = "#" + idsArr[index];
		$(id).html( $(html).find(id).html() );
	}
}


/**
 * Форсира презареждането на страницта след връщане назад
 */
function render_forceReloadAfterBack()
{
	getEO().saveBodyId();
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
function render_html(data) {
    // Неоходимите параметри
    var id = data.id;
    var html = data.html;
    var replace = data.replace;
    var dCss = data.css;
    var dJs = data.js;

    // Ако няма HTML, да не се изпуълнява
    if ((typeof html == 'undefined') || !html) return;

    // Ако има JQuery
    if (typeof jQuery != 'undefined') {

        var idObj = $('#' + id);

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

    // Зареждаме CSS файловете
    if (dCss) {
    	$.each(dCss, function(i, css) {
    		var a = $("<link/>", {
    		   rel: "stylesheet",
    		   type: "text/css",
    		   href: css
    		}).appendTo("head");
    	})
    }

    // Зареждаме JS файловете
    if (dJs) {
        if ( typeof refreshForm.loadedFiles == 'undefined' ) {
            refreshForm.loadedFiles = [];
        }
        loadFiles(data.js, refreshForm.loadedFiles);
    }

    scrollLongListTable();
}


/**
 * Фокусира поле с определено ид
 */
function render_setFocus(data){
	var id = data.id;
	$("#"+id).focus();
}


/**
 * Затваря отвореното контекстно меню
 */
function render_closeContextMenu(data)
{
    if ($('.iw-mTrigger').contextMenu) {
    	$('.iw-mTrigger').contextMenu('close');
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
function render_notificationsCnt(data) {
    changeTitleCnt(data.cnt);

    var nCntLink = get$(data.id);

    if (nCntLink != null) {
        changeNotificationsCnt(data);
    }
}


/**
 * Функция, която извиква подготвянето на контекстното меню
 * Може да се комбинира с efae
 */
function render_prepareContextMenu() {
    prepareContextMenu();
}


/**
 * Функция, която извиква подготвянето на контекстното меню по ajax
 * Може да се комбинира с efae
 */
function render_getContextMenuFromAjax() {
    getContextMenuFromAjax();
}


/**
* Функция, която извиква подготвянето на smartCenter
* Може да се комбинира с efae
*/
function render_smartCenter() {
    smartCenter();
}


/**
* Функция, която извиква подготвянето на sumOfChildrenWidth
* Може да се комбинира с efae
*/
function render_sumOfChildrenWidth() {
	sumOfChildrenWidth();
}


/**
* Функция, която извиква подготвянето на setFormElementsWidth
* Може да се комбинира с efae
*/
function render_setFormElementsWidth() {
	setFormElementsWidth();
}


/**
* Функция, която извиква подготвянето на  setThreadElemWidth
* Може да се комбинира с efae
*/
function render_setThreadElemWidth() {
	setThreadElemWidth();
}



/**
* Функция, която извиква подготвянето на editCopiedTextBeforePaste
* Може да се комбинира с efae
*/
function render_editCopiedTextBeforePaste() {
	editCopiedTextBeforePaste();
}


/**
* Функция, която извиква подготвянето на editCopiedTextBeforePaste
* Може да се комбинира с efae
*/
function render_removeNarrowScroll() {
	removeNarrowScroll();
}


/**
* Функция, която извиква подготвянето на показването на тоолтипове
* Може да се комбинира с efae
 */
function render_showTooltip() {

	showTooltip();
}


/**
* Функция, която извиква подготвянето на показването на тоолтипове
* Може да се комбинира с efae
 */
function render_makeTooltipFromTitle() {

	makeTooltipFromTitle();
}


/**
* Функция, която извиква подготвянето на показването на тоолтипове
* Може да се комбинира с efae
 */
function render_runHljs() {
	if (typeof hljs != 'undefined') {
		hljs.initHighlighting.called = false;
  		hljs.initHighlighting();
	}
}


/**
 * Стартира кода за оцветяване, ако има такава функция
 */
function runHljs() {
	if (typeof hljs != 'undefined') {
		hljs.tabReplace = '    ';
  		hljs.initHighlightingOnLoad();
	}
}



/**
 * Функция, която редиректва към определена страница, може да се
 * използва с efae
 *
 * @param object data - Обект с необходимите стойности
 * data.url - URL към което да се пренасочи
 */
function render_redirect(data) {
    var url = data.url;
    document.location = url;
}

var oldTitle;
var oldIconPath;
var isChanged = false;
var blinkerWorking = false;

/**
 * Функция на нотифициране, чрез звук и премигане на текста и иконката в таба
 * използва с efae
 *
 * @param object data - Обект с необходимите стойности
 * data.title - заглавие, което ще се задава
 * data.favicon - път до фав иконата
 * data.blinkTimes - брой премигвания
 * data.soundOgg - път до ogg файла
 * data.soundMp3 - път до mp3 файла
 */
function render_Notify(data) {
	if(blinkerWorking) return;
	if(!data.blinkTimes){
		data.blinkTimes = 5;
	}
	render_Sound(data);
	blinkerWorking = true;
	var counter = 1;

	// ако няма зададен текст, ще скриваме титлата
    var title = data.title ? data.title : '\u200E';

	//запазваме старата фав икона
	oldIconPath = $('link[rel="shortcut icon"]')[0].href;

	// подготвяме фав иконките
	var newIcon = prepareFavIcon(data.favicon);
	var oldIcon = prepareFavIcon(oldIconPath);

	var interval = setInterval(function(){
		// Задаваме новия текст и икона

        if(title != oldTitle && !isChanged) {
            isChanged = true;
            setTitle(title);
            setFavIcon(newIcon);
        }

		// задаваме старите текст и икона след като изтече времето за показване
		var timeOut = setTimeout(function(){
            if(title != oldTitle && isChanged) {
                isChanged = false;
                restoreTitle(oldTitle);
                setFavIcon(oldIcon);
            }
		}, 600);

		counter++;

		// дали са мигнали достатъчно пъти
		if(counter > data.blinkTimes) {
			blinkerWorking = false;
			clearInterval(interval);
		}
	}, 1000);
}

/**
 * задава титла на страницата
 * @param title
 */
function setTitle(title) {
	oldTitle = document.title;
	document.title = title;
}


/**
 * задава старата титла
 * @param oldTitle
 */
function restoreTitle(oldTitle) {
	document.title = oldTitle;
}

/**
 * връща необходимия за смяна на фав иконата таг
 * @param iconPath - пътя до картинката
 * @returns object|false
 */
function prepareFavIcon(iconPath) {

	if ((!iconPath) || (typeof iconPath == 'undefined')) return false;

	var icon = document.createElement('link');
	icon.type = 'image/x-icon';
	icon.rel = 'shortcut icon';
	icon.href = iconPath;

	return icon;
}


/**
 * задава фав икона
 * @param icon - иконата, която ще задаваме
 */
function setFavIcon(icon){
	if (icon) {
		$('head').append(icon);
	}
}


/**
 * изпълнява аудио
 * @param soundMp3 - път до mp3 файла
 * @param soundOgg - път до ogg файла
 */
function render_Sound(data){
	var soundMp3 = data.soundMp3;
	var soundOgg = data.soundOgg;
	if(soundMp3 != undefined || soundOgg  != undefined){
		// добавяме аудио таг и пускаме звука
		setTimeout(function(){
			$('body').append("<div class='soundBlock'></div>");
		    $(".soundBlock").append('<audio autoplay="autoplay" onended="removeParentTag(this);">' +
		    		'<source src=' + soundMp3 + ' type="audio/mpeg" />' +
		    		'<source src=' + soundOgg + ' type="audio/ogg" />' +
		    	'</audio>');


		    if (typeof Audio !== "function" && typeof Audio !== "object") {
		    	$(".soundBlock").append('<embed hidden="true" autostart="true" loop="false" src=' + soundMp3 +' />');
		    }

		}, 500);
	}
}


/**
 * изтрива бащата на зададения елемент
 */
function removeParentTag(el) {
	$(el).parent().remove();
}


/**
 * Функция, която отваря посоченото URL, като спира разпространението на събитието
 */
function openUrl(url, event) {
	if(event) {
		if(event.stopPropagation){
			event.stopPropagation();
		}
		event.cancelBubble = true;
	}

	window.location = url;

	return false;
}


/**
 * Променя броя на нотификациите в титлата на таба
 *
 * @param cnt - броя на нотификациите
 */
function changeTitleCnt(cnt) {
    var title = document.title;
    var numbArr = title.match(/\(([^) ]+)\)/);
    cnt = parseInt(cnt);

    if (numbArr) {
        numb = numbArr[1];
    } else {
        numb = '0';
    }

    var textSpace = "  ";

    if (parseInt(numb) > 0) {
        if (parseInt(cnt) > 0) {
            title = title.replace("(" + numb + ") ", "(" + cnt + ") ");
        } else {
            title = title.replace("(" + numb + ") ", "");
        }

    } else {
        if (cnt > 0) {
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
function changeNotificationsCnt(data) {
    render_html({
        'id': data.id,
        'html': data.cnt,
        'replace': 1
    });

    var nCntLink = get$(data.id);

    if (nCntLink != null) {
        var notificationsCnt = parseInt(data.cnt);
        if (notificationsCnt > 0) {
            nCntLink.className = 'haveNtf';
        } else {
            nCntLink.className = 'noNtf';
        }

        if($('body').hasClass('modern-theme') && $('body').hasClass('narrow')  && typeof data.notifyTime !== 'undefined' && data.notifyTime) {
           if(getCookie('portalTabs') == "notificationsPortal") {
               setCookie('notifyTime', data.notifyTime);
           }
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
function showToast(data) {
    setTimeout(function() {
        $().toastmessage('showToast', {
            text: data.text,
            sticky: data.isSticky,
            stayTime: data.stayTime,
            type: data.type,
            inEffectDuration: 800,
            position: 'bottom-right'
        });
    }, data.timeOut);
}


/**
 * Рендира новото изображение за превю на картина
 * 
 * @param object data - Обект с необходимите стойности
 * data.data-url
 * data.src
 * data.width
 * data.height
 * data.fh
 */
function render_setNewFilePreview(data) {
	console.log(data);
}
var oldImageSrc, oldImageWidth, oldImageHeight;
function changeZoomImage(el) {
    if($(el).attr("data-zoomed") == "no") {
        if($('body').hasClass('wide')){
            $(el).css("width",$(el).css("width"));
            $(el).css("height","auto");
        } else {
            oldImageSrc = $(el).attr("src");
            oldImageWidth = $(el).attr("width");
            oldImageHeight = $(el).attr("height");
        }
        $(el).attr("width", $(el).attr("data-bigwidth"));
        $(el).attr("height", $(el).attr("data-bigheight"));
        $(el).attr("src",$(el).attr("data-bigsrc"));
        $(el).attr("data-zoomed", "yes");
    } else {
        if($('body').hasClass('narrow')){
            $(el).attr("src", oldImageSrc);
            $(el).attr("width", oldImageWidth);
            $(el).attr("height", oldImageHeight);
            $(el).attr("data-zoomed", "no");
        }
    }
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
function Experta() {
    // Селектирания текст при първия запис
    Experta.prototype.fSelText = '';

    // Селектирания текст, ако първя запис не е променян
    Experta.prototype.sSelText = '';

    // Време на извикване
    Experta.prototype.saveSelTextTimeout = 500;

    // Време на извикване в textarea
    Experta.prototype.saveSelTextareaTimeout = 400;

    // Данни за селектирания текст в textarea
    Experta.prototype.textareaAttr = new Array();

    // Времето на бездействие в таба
    Experta.prototype.idleTime;

    // id на атрибута в който ще се добавя локацията
    Experta.prototype.geolocationId;

    // Име на сесията за id-та на body тага
    Experta.prototype.bodyIdSessName = 'bodyIdArr';

    // Име на сесията за id-та на body тага
    Experta.prototype.formSessName = 'refreshFormObj';
}


/**
 * Стартира таймера за бездействие в съответния таб
 */
Experta.prototype.runIdleTimer = function() {
    // Ако е бил стартиран преди, да не се изпълнява
    if (typeof this.idleTime != 'undefined') return;

    var EOinst = this;

    // Добавяме ивенти за ресетване при действие
    getEO().addEvent(document, 'mousemove', function() {
        EOinst.resetIdleTimer();
    });
    getEO().addEvent(document, 'keypress', function() {
        EOinst.resetIdleTimer();
    });

    // Стартираме процеса
    this.processIdleTimer();
};


/**
 * Стартира рекурсивен процес за определяне на времето за бездействие
 */
Experta.prototype.processIdleTimer = function() {
    // Текущия клас
    var thisEOInst = this;

    // Задаваме функцията да се вика всяка секунда
    setTimeout(function() {
        thisEOInst.processIdleTimer();
    }, 1000);

    // Увеличаваме брояча
    this.increaseIdleTime();
};


/**
 * Увеличава времето на бездействие
 */
Experta.prototype.increaseIdleTime = function() {
    // Ако не е дефиниран преди
    if (typeof this.idleTime == 'undefined') {

        // Стойността по подразбиране
        this.idleTime = 0;
    } else {

        // При всяко извикване увеличава с единица
        this.idleTime++;
    }
};


/**
 * Нулира времето на бездействие
 */
Experta.prototype.resetIdleTimer = function() {
    // При всяко извикване нулира времето на бездействие
    this.idleTime = 0;
};


/**
 * Връща стойността на брояча за бездействие
 */
Experta.prototype.getIdleTime = function() {

    return this.idleTime;
};


/**
 * Записва избрания текст
 */
Experta.prototype.saveSelText = function() {
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
    setTimeout(function() {
        thisEOInst.saveSelText();
    }, this.saveSelTextTimeout);
};


/**
 * Връща избрания текст, който е записан във втората променлива
 */
Experta.prototype.getSavedSelText = function() {

    return this.sSelText;
};


/**
 * Добавя в атрибутите на текстареа позицията и текста на избрания текст
 *
 * @param integer id
 */
Experta.prototype.saveSelTextInTextarea = function(id) {
    // Текстареата
    textarea = document.getElementById(id);

    // Ако текстареата е на фокус
    if (textarea && textarea.getAttribute('data-focus') == 'focused') {

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
            this.textareaAttr[id] = {
                'data-hotSelection': '',
                'data-readySelection': '',
                'data-readySelectionStart': 0,
                'data-readySelectionEdn': 0
            };
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
    setTimeout(function() {
        thisEOInst.saveSelTextInTextarea(id)
    }, this.saveSelTextareaTimeout);
};


/**
 * Сетва атрибута на текстареа, при фокус
 *
 * @param integer id
 */
Experta.prototype.textareaFocus = function(id) {
    // Текстареата
    textarea = document.getElementById(id);

    // Задваме в атрибута
    textarea.setAttribute('data-focus', 'focused');
};


/**
 * Сетва атрибута на текстареа, при загуба на фокус
 *
 * @param integer id
 */
Experta.prototype.textareaBlur = function(id) {
    // Текстареата
    textarea = document.getElementById(id);

    // Задваме в атрибута
    textarea.setAttribute('data-focus', 'none');
};


/**
 * Добавя ивент към съответния елемент
 *
 * @param object elem - Към кой обект да се добави ивента
 * @param string event - Евента, който да слуша
 * @param string function - Функцията, която да се изпълни при ивента
 */
Experta.prototype.addEvent = function(elem, event, func) {
    // Ако има съответната фунцкция
    // Всички браузъри без IE<9
    if (elem.addEventListener) {

        // Абонираме ивента
        elem.addEventListener(event, func, false);
    } else if (elem.attachEvent) {
        // За IE6, IE7 и IE8
        elem.attachEvent("on" + event, func);
    } else {
        elem["on" + event] = func;
    }
};


/**
 * Скролва до зададеното id
 *
 * @param integer id
 */
Experta.prototype.scrollTo = function(id) {
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
};


/**
 * Закръгля дробни числа до подадения брой символи след десетичната запетая
 *
 * @param double id
 * @param integer id
 *
 * @return integer|double|NULL
 */
Experta.prototype.round = function(val, decimals) {

	if (typeof Math == "undefined") return ;

	var pow = Math.pow(10, parseInt(decimals));

	val = Math.round(parseFloat(val) * pow) / pow;

	return val;
}


/**
 * Задава позицията от geolocation в полето
 *
 * @param string attrId
 */
Experta.prototype.setPosition = function(attrId) {
	this.setGeolocation(attrId);
}


/**
 * Задава геолокациите
 *
 * @param string attrId
 */
Experta.prototype.setGeolocation = function(attrId) {
	if (navigator.geolocation) {
		this.geolocationId = attrId;
        navigator.geolocation.getCurrentPosition(this.setCoords);
    }
}


/**
 * Задава координатите
 *
 * @param object
 */
Experta.prototype.setCoords = function(position) {

	var lat = getEO().round(position.coords.latitude, 6);
	var long = getEO().round(position.coords.longitude, 6);

	$('#' + getEO().geolocationId).val(lat + ',' + long);
}


/**
 * Ескейпва подадения стринг
 *
 * @param string str
 *
 * @param return
 */
Experta.prototype.escape = function(str) {
	
	if (!str) return str;
	
	if (typeof str != 'string') return str;
	
	str = str.replace(/[&<>]/g, function(tag) {
		var tagsToReplace = {
			    '&': '&amp;',
			    '<': '&lt;',
			    '>': '&gt;'
			};

		return tagsToReplace[tag] || tag;
	});

	return str;
}


/**
 * Показва съобщението в лога
 *
 * @param string txt - Съобщението, което да се покаже
 */
Experta.prototype.log = function(txt) {
    // Ако не е дефиниран обекта
    if (typeof console != "undefined") {

        // Показваме съобщението
        console.log(txt);
    }
};


/**
 * Записва id-то на body в сесията на браузъра
 */
Experta.prototype.saveBodyId = function() {
	// Ако не е дефиниран
    if (typeof sessionStorage == "undefined") return ;

    var bodyId = $('body').attr('id');

    if (!bodyId) return ;

    var bodyIds = sessionStorage.getItem(this.bodyIdSessName);

    if (bodyIds) {
    	bodyIds =  $.parseJSON(bodyIds);
    } else {
    	bodyIds = new Array();
    }

    if ($.inArray(bodyId, bodyIds) == -1) {
    	bodyIds.push(bodyId);
    }

    sessionStorage.setItem(this.bodyIdSessName, JSON.stringify(bodyIds));
};


/**
 * Проверява дали id-то на body се съдържа в сесията на браузъра
 */
Experta.prototype.checkBodyId = function(bodyId) {
	if (!bodyId || typeof bodyId == 'undefined') {
		bodyId = $('body').attr('id');
	}

	var bodyIds = sessionStorage.getItem(this.bodyIdSessName);
	
	if (!bodyIds) return ;

	bodyIds =  $.parseJSON(bodyIds);

	if ($.inArray(bodyId, bodyIds) != -1) {

		return true;
    }
};


/**
 * Записва данните за формата в id на страницата
 */
Experta.prototype.saveFormData = function(formId, data) {

	var maxItemOnSession = 3;

	bodyId = $('body').attr('id');

	if (!bodyId) return ;

	var formObj = sessionStorage.getItem(this.formSessName);

	var maxN = 0;
	var minN = 0;
	var minNKey;

	if (!formObj) {
		formObj = {};
	} else {
		formObj = $.parseJSON(formObj);

		// Определяме най-голямата и най-малка стойност
		// За да ги премахнем от сесията, при достигане на лимита
		for (var key in formObj) {
			if (maxN < formObj[key].num) {
				maxN = formObj[key].num;
			}

			if ((minN == 0) || minN > formObj[key].num) {
				minN = formObj[key].num;
				minNKey = key;
			}
		}
	}

	if (!formObj[bodyId]) {
		maxN++;
	}

	if ((minN != maxN) && ((maxN - minN) >= maxItemOnSession)) {
		delete formObj[minNKey];
	}

	formObj[bodyId] = {'formId': formId, 'data': data, 'num': maxN};

	sessionStorage.setItem(this.formSessName, JSON.stringify(formObj));
};


/**
 * Замества данните на формата
 * Взема ги от сесията за съответната страница
 */
Experta.prototype.reloadFormData = function() {

	bodyId = $('body').attr('id');

	if (!bodyId) return ;

	var formObj = sessionStorage.getItem(this.formSessName);

	if (!formObj) return ;

	formObj = $.parseJSON(formObj);

	if (!formObj[bodyId]) return ;

	if (!formObj[bodyId].formId) return ;

	replaceFormData($('#' + formObj[bodyId].formId), formObj[bodyId].data);
}


/**
 * Добавя ивент, който да кара страницата да се презарежда, ако условиет е изпълнено
 */
function reloadOnPageShow() {
	getEO().addEvent(window, 'pageshow', function() {
        if (getEO().checkBodyId()) {
        	location.reload();
        }

        // Заместваме данните от формата с предишно избраната стойност
        getEO().reloadFormData();
    });
}


/**
 * Намаляващ брояч на време
 */
Experta.prototype.doCountdown = function(l1, l2, l3) {
	$('span.countdown').each(function() {
		var text = $(this).text();
		var res = text.split(":");
		var hour =  parseInt(res[0],10);
		var min = parseInt(res[1],10);
		var sec =  parseInt(res[2],10);

		if(!(hour == 0 && min == 0 && sec==0 )) {
			sec--;
			if(sec < 0){
				// ако са се изчерпали секундите
				sec = 59;
				min--;
				if(min < 0){
					// ако са се изчерпали минутите
					min = 59;
					hour--;
				}
			}

			var timeInSec = hour * 3600 + min * 60 + sec;

			if (l1 && timeInSec < l1) $(this).removeClass('cd-l2 cd-l3').addClass('cd-l1');
			if (l2 && timeInSec < l2) $(this).removeClass('cd-l1 cd-l3').addClass('cd-l2');
			if (l3 && timeInSec < l3) $(this).removeClass('cd-l1 cd-l2').addClass('cd-l3');

			if(timeInSec == 0) {
				$(this).removeClass('cd-l3').css('color', 'black');
			}

			//добавяме водещи нули ако е необходимо
			if(hour < 10){
				hour = "0" +  hour;
			}

			if(sec < 10){
				sec = "0" +  sec;
			}
			if(min < 10){
				min = "0" + min;
			}

			$(this).text(hour + ":" + min + ":" +sec);
		}
	});
};


/**
 * Извиква функцията doCountdown през 1 сек
 */
Experta.prototype.setCountdown = function(l1, l2, l3) {
	if(!l1){
		l1 = 3600;
	}
	if(!l2){
		l2 = 180;
	}
	if(!l3){
		l3 = 10;
	}
	setInterval(function(){
		getEO().doCountdown(l1, l2, l3);
	}
	, 1000);
};


/**
 * Връща сингълтон инстанция за класа Experta
 *
 * @return object
 */
function getEO() {

    return this.getSingleton('Experta');
}


/**
 * Връща сингълтон инстанция за efae класа
 *
 * @return object
 */
function getEfae() {

    return this.getSingleton('efae');
}


function prepareBugReport(form, user, domain, name, ctr, act, sysDomain)
{
	var url = document.URL;
	var width = $(window).width();
	var height = $(window).height();
	var browser = getUserAgent();
	var title = sysDomain + '/' + ctr + '/' + act;
	
	if (url && (url.length > 250)) {
		url = url.substring(0, 250);
		url += '...';
	}
	
	addBugReportInput(form, 'title', title);
	addBugReportInput(form, 'url', url);
	addBugReportInput(form, 'email', user + '@' + domain);
	addBugReportInput(form, 'name', name);
	addBugReportInput(form, 'width', width);
	addBugReportInput(form, 'height', height);
	addBugReportInput(form, 'browser', browser);
}


function addBugReportInput(form, nameInput, value)
{
	if($(form).find('input[name="' + nameInput + '"]').length == 0){
		$('<input>').attr({
	        type: 'hidden',
	        name: nameInput,
	        value: value
	    }).appendTo(form);
	}
}


/**
 * При хоризонтален скрол на страницата, да създадем watch point
 */
function detectScrollAndWp() {
    if($('#packWrapper').outerWidth() > $(window).width() ) {
    	getEfae().process({url: wpUrl}, {errType: 'Scroll Detected', currUrl: window.location.href});
    }
}


function removeNarrowScroll() {
	if($('body').hasClass('narrow-scroll') && !checkNativeSupport()){
		$('body').removeClass('narrow-scroll');
	}
}



/**
 * Скролиране до определен
 */
$.fn.scrollView = function () {
	return this.each(function () {
						$('html, body').animate({
                            scrollTop: $(this).offset().top - $(window).height() + $(this).height()
                        }, 500);
                    });
};

function mailServerSettings() {
    var email = document.getElementById('email');
    var server = document.getElementById('server');
    var protocol = document.getElementById('protocol');
    var security = document.getElementById('security');
    var cert = document.getElementById('cert');
    var folder = document.getElementById('folder');
    var user = document.getElementById('user');
    var smtpServer = document.getElementById('smtpServer');
    var smtpSecure = document.getElementById('smtpSecure');
    var smtpAuth = document.getElementById('smtpAuth');
    var smtpUser = document.getElementById('smtpUser');

    var n = email.value.search("@");

    var provider = email.value.substr(n+1);
    var userAccountt = email.value.substr(0,n);

    if (server.value == "") {
		switch (provider) {
		    case "abv.bg":
		    	server.value = "pop3.abv.bg:995";
			    protocol.value = "pop3";
			    security.value = "ssl";
			    cert.value = "validate";
			    smtpServer.value = "smtp.abv.bg:465";
			    smtpSecure.value = "tls";
			    smtpAuth.value = "LOGIN";
			    user.value = email.value;
			    smtpUser.value = email.value;
		    	break;
		    case "gmail.com":
		    	server.value = "imap.gmail.com:993";
		    	protocol.value = "imap";
		    	security.value = "ssl";
		    	cert.value = "validate";
		    	smtpServer.value = "smtp.gmail.com:587";
		    	smtpSecure.value = "tls";
		    	smtpAuth.value = "LOGIN";
		    	user.value = email.value;
		    	smtpUser.value = email.value;
		        break;
		    case "yahoo.com":
		    	server.value = "imap.mail.yahoo.com:993";
		    	protocol.value = "imap";
		    	security.value = "ssl";
		    	cert.value = "validate";
		    	smtpServer.value = "smtp.mail.yahoo.com:465";
		    	smtpSecure.value = "ssl";
		    	smtpAuth.value = "LOGIN";
		    	user.value = email.value;
		    	smtpUser.value = email.value;
		        break;
		    case "outlook.com":
		    	server.value = "imap-mail.outlook.com:993";
		    	protocol.value = "imap";
		    	security.value = "ssl";
		    	cert.value = "validate";
		    	smtpServer.value = "smtp-mail.outlook.com:587";
		    	smtpSecure.value = "tls";
		    	smtpAuth.value = "LOGIN";
		    	user.value = email.value;
		    	smtpUser.value = email.value;
		        break;
		    case "mail.bg":
		    	server.value = " imap.mail.bg:143";
		    	protocol.value = "imap";
		    	security.value = "tls";
		    	cert.value = "validate";
		    	smtpServer.value = "smtp.mail.bg:25";
		    	smtpSecure.value = "tls";
		    	smtpAuth.value = "LOGIN";
		    	user.value = email.value;
		    	smtpUser.value = email.value;
		        break;

		    default:
		    	protocol.value = "imap";
		    	security.value = "default";
		    	cert.value = "noValidate";
		    	smtpSecure.value = "no";
		    	smtpAuth.value = "no";
		}

        if($('.select2').length){
            $('select').trigger("change");
        }
    }
};


/**
 * Вика url-то w data-url на линка и спира норматлноното му действие
 *
 * @param event
 * @param stopOnClick
 *
 * @return boolean
 */
function startUrlFromDataAttr(obj, stopOnClick)
{
	if (this.event) {
		stopBtnDefault(this.event);
	}

	resObj = new Object();
	resObj['url'] = obj.getAttribute('data-url');

	if (stopOnClick) {
		$(obj).css('pointer-events', 'none');
	}

	getEfae().process(resObj);
	
	getEfae().waitPeriodicAjaxCall = 0;
	
	return false;
}


/**
 * Спира нормалното дейстие на бутона след натискане
 *
 * @param event
 */
function stopBtnDefault(event)
{
	if (event.preventDefault) {
        event.preventDefault();
    } else if (event.stopPropagation) {
        event.stopPropagation();
    } else {
        event.returnValue = false;
        event.cancelBubble = true;
    }
}

/**
 * Прихващач за обновяването на страницата без AJAX
 */
function onBeforeUnload()
{
	window.onbeforeunload = function () {
		getEO().isReloading = true;
	}
}


/**
 * Добавя текущото URL И титлата към url-то
 */
function addParamsToBookmarkBtn(obj, parentUrl, localUrl)
{

	//var url = encodeURIComponent(document.URL);
	//var title = encodeURIComponent(document.title);
	var url = localUrl;
	if (!url) {
		url = document.URL;
	}
	var title = document.title;

    obj.setAttribute("href", parentUrl + '&url=' + url + '&title=' + title);
}

/**
 * Вика по AJAX екшън, който добавя документа към последни
 * 
 * @param fh
 */
function copyFileToLast(fh)
{
	if (this.event) {
		stopBtnDefault(this.event);
	}
	
    getEfae().process({url: '/fileman_Files/CopyToLast/' + fh});
    
    // Затваряме прозореца
    if ($('.iw-mTrigger').contextMenu) {
    	$('.iw-mTrigger').contextMenu('close');
    }
}


/**
 * Fix за IE8
 * @see http://stackoverflow.com/questions/3629183/why-doesnt-indexof-work-on-an-array-ie8
 */
if (!Array.prototype.indexOf)
{
  Array.prototype.indexOf = function(elt /*, from*/)
  {
    var len = this.length >>> 0;

    var from = Number(arguments[1]) || 0;
    from = (from < 0)
         ? Math.ceil(from)
         : Math.floor(from);
    if (from < 0)
      from += len;

    for (; from < len; from++)
    {
      if (from in this &&
          this[from] === elt)
        return from;
    }
    return -1;
  };
}


/**
 * Fix за IE7
 *
 * @see http://www.sitepoint.com/javascript-json-serialization/
 */
var JSON = JSON || {};


/**
 * Fix за IE7
 * implement JSON.stringify serialization
 *
 * @see http://www.sitepoint.com/javascript-json-serialization/
 */
JSON.stringify = JSON.stringify || function (obj) {

	var t = typeof (obj);
	if (t != "object" || obj === null) {

		// simple data type
		if (t == "string") obj = '"'+obj+'"';
		return String(obj);

	}
	else {

		// recurse array or object
		var n, v, json = [], arr = (obj && obj.constructor == Array);

		for (n in obj) {
			v = obj[n]; t = typeof(v);

			if (t == "string") v = '"'+v+'"';
			else if (t == "object" && v !== null) v = JSON.stringify(v);

			json.push((arr ? "" : '"' + n + '":') + String(v));
		}

		return (arr ? "[" : "{") + String(json) + (arr ? "]" : "}");
	}
};


/**
 * Fix за IE7
 * implement JSON.parse de-serialization
 *
 * @see http://www.sitepoint.com/javascript-json-serialization/
 */
JSON.parse = JSON.parse || function (str) {
	if (str === "") str = '""';
	eval("var p=" + str + ";");
	return p;
};

runOnLoad(maxSelectWidth);
runOnLoad(onBeforeUnload);
runOnLoad(reloadOnPageShow);
