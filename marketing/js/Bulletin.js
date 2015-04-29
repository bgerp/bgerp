/**
 * @author Yusein Yuseinov <yyuseinov@gmail.com>
 */


/**
 * След зареждане на JQuery стартираме функцията
 */
$(document).ready(function() {
	startProcessing();
});


/**
 * Брояч, за отчитане времето на бездействие
 */
var idleTime;


/**
 * Брояч, за отчитане на времето
 */
var timer;


/**
 * Дали формата е показана вече
 */
var formIsShowed = false;


/**
 * След колко време формата да може да се показва повторно
 */
var showAgainAfter = [#showAgainAfter#];


/**
 * След колко време на бездействие да се покаже формата
 */
var idleTimeForShow = [#idleTimeForShow#];


/**
 * Стартира процесите, за показване на формата след определени условия
 */
function startProcessing()
{
	// Ако има регистрация да не сработва
	var isRegistered = getCookie('bulletinHaveReg');
	if (isRegistered) return;
	
	// Ако е показана скоро
	var lastShow = getCookie('bulletinLastShowTime');
	if (this.isShowed(lastShow)) return ;
	
	// Гледа за позицията на мишката
	this.startWatchingPos();
	
	// Следи за времето на бездействие
	this.startWatchingTime();
}


/**
 * Стартира таймера за бездействие в съответния таб
 */
function runIdleTimer()
{
    // Ако е бил стартиран преди, да не се изпълнява
    if (typeof this.idleTime != 'undefined') return;
    
    var thisInst = this;
    
    // Добавяме ивенти за ресетване при действие
    this.addEvent(document, 'mousemove', function() {
    	thisInst.resetIdleTimer();
    });
    this.addEvent(document, 'keypress', function() {
    	thisInst.resetIdleTimer();
    });
    
    // Стартираме процеса
    this.processIdleTimer();
};


/**
 * Стартира рекурсивен процес за определяне на времето за бездействие
 * и в зависимост от него определя дали да покаже формата
 */
function processIdleTimer()
{
	// Няма нужда да се стартира брояча, ако формата вече е показана
	if (this.formIsShowed) return;
	
    var thisInst = this;
    setTimeout(function() {
    	try {
    		thisInst.processIdleTimer();
    	} catch(err) {
    	    // Не се прави нищо
    	}
    }, 1000);
    
    if (this.idleTime >= idleTimeForShow) {
    	this.showForm();
    }
    
    // Увеличаваме брояча
    this.increaseIdleTime();
};


/**
 * Увеличава времето на бездействие
 */
function increaseIdleTime()
{
    // Ако не е дефиниран преди
    if (typeof this.idleTime == 'undefined') {
        this.idleTime = 0;
    } else {
        this.idleTime++;
    }
    
    if (typeof this.timer == 'undefined') {
        this.timer = 0;
    } else {
        this.timer++;
    }
};


/**
 * Нулира времето на бездействие
 */
function resetIdleTimer() {
    // При всяко извикване нулира времето на бездействие
    this.idleTime = 0;
};

/**
 * Добавя ивент към съответния елемент
 * 
 * @param object elem - Към кой обект да се добави ивента
 * @param string event - Евента, който да слуша
 * @param string function - Функцията, която да се изпълни при ивента
 */
function addEvent(elem, event, func) {
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
 * Следи позицията на мишката и след преминаване на определена позиция показва формата (ако има нужда)
 */
function startWatchingPos()
{
	// Дали мишката е започнала да напуска  зададените очертания
	var isBeginExit = false;
	
	// Дали е вътре в прозореца и извън isBeginExit зоната
	var isInside = false;
	
	// Предишната позиция на мишката
	var prevPos = 0;
	
	// Текущата позиция
	var pos = 0;
	
	// Най - горната видима точка до която е скролирано
	var bodyTop = 0;
	
	// При движение от коя до коя зона да се показва формата
	var maxPos = 10;
	var minPos = 1;
	
	// След колко секунди да може да се стартира
	var waitBeforeStart = [#waitBeforeStart#];
	
	var thisInst = this;
	
	$("body").mousemove(function(e) {
		
		// Ако формата е показана, да не се показва повторно
		if (thisInst.formIsShowed) return;
		
		// Ако не е дошло времето за показване
		if (thisInst.timer < waitBeforeStart) return;
		
		// Определяме очертанията на body и положението на мишката спрямо него
		bodyTop = document.documentElement.scrollTop + document.body.scrollTop;
		pos = e.pageY - bodyTop;
		
		// Ако веднъж е било извън isBeginExit и вътре в body
		if (pos > maxPos) {
			isInside = true;
		}
		
		// Ако вече сме били вътре и почнем да напускаме очертанията
		if (isInside && (pos <= maxPos)) {
			isBeginExit = true;
		} else {
			isBeginExit = false;
		}
		
		// Ако се направи движение от долната част към горната част и се напуснат очертанията
		if (isBeginExit && (prevPos > pos) && (pos <= minPos)) {
			thisInst.showForm();
		}
		
		prevPos = pos;
	});
}


/**
 * Стартира брояча за време
 */
function startWatchingTime()
{
	runIdleTimer();
}


/**
 * Проверява дали формата е показана преди определно време
 * 
 * @param integer
 * 
 * @return boolean
 */
function isShowed(lastShow)
{
	if (!lastShow) return;
	
	if (typeof lastShow == 'undefined') return;
	
	var diffFromLastShow = getNowTimetamp() - lastShow;
	
	if (this.showAgainAfter > diffFromLastShow) return true;
}


/**
 * Връща timestamp в секунди
 * 
 * @return integer
 */
function getNowTimetamp()
{
	var time = new Date().getTime();
	time = Math.floor(time / 1000);
	
	return time;
}


/**
 * Показва формата
 */
function showForm()
{
	setCookie('bulletinLastShowTime', getNowTimetamp(), 1000);
	
	$("body").append(this.getForm());
	
	$(".bulletinHolder").fadeIn(600);
	
	onClickCatcher();
	
	this.formIsShowed = true;
}


/**
 * Връща формата, която ще се показва
 * 
 * @return string
 */
function getForm()
{
	var form = "<div class='bulletinHolder' style='display:none'>" +
				"<div class='bulletinReg'>" +
				"<a href='#' class='bulletinClose'>X</a>"+
				"<div class='bulletinInner'>"+
				"<h2>[#formTitle#]</h2>"+
				"<div class='bulletinError' style='visibility: hidden; color: red;'>[#wrongMailText#]</div>"+
				"<form class='bulletinForm' action='[#formAction#]'>" +
                "<div class='bulletinFilelds'>" +
				"<div class='row bulletinEmailHolder'><span>[#emailName#]: </span><input type='text' id='bulletinEmail' name='email'></div>" ;
	
				if ('[#showAllForm#]' == 'yes') {
					form += "<div class='row bulletinNamesHolder'><span>[#namesName#]:</span> <input type='text' id='bulletinNames' name='names'></div>" +
					"<div class='row bulletinCompanyHolder'><span>[#companyName#]: </span><input type='text' id='bulletinCompany' name='company'></div>"
				}
                form += "</div><div class='clearfix21'></div> " ;

				form += "<div class='centered'><input type='submit' name='submit' value='[#submitBtnVal#]' class='push_button blue'></div>"+
				"<div style='text-align: center;'><a href='#' class='bulletinCancel'>[#cancelBtnVal#]</a></div>"+
				"</form>" +
				"</div>" +
				"</div>" +
				"</div>";
	
	return form;
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
 * Прихваща натисканията на бутоните от формата
 */
function onClickCatcher()
{
	$('.bulletinForm').submit(function(event) {
		stopBtnDefault(event);
		
        var url = $(this).attr('action');
        
        var lastChar = url.slice(-1);
        if (lastChar != '/') {
        	url += '/';
        }
    	
        var email = document.getElementById('bulletinEmail').value;
        
        // Трябва да е валиден имейл и да има стойност
        if (!validateEmail(email)) {
        	$('.bulletinError').css('visibility', 'visible');
        	$('#bulletinEmail').css('border', '1px solid red');
        	return;
        }
        
        email = encodeURIComponent(email);
        var namesElem = document.getElementById('bulletinNames');
        var companyElem = document.getElementById('bulletinCompany');
        var names = '';
        var company = '';
        
        if (namesElem) {
        	names = encodeURIComponent(namesElem.value);
        }
        
        if (companyElem) {
        	company = encodeURIComponent(companyElem.value);
        }
        
        url += '?email=' + email + '&names=' + names + '&company=' + company + '&r=' + Math.random();
        
        // Скриваме част от формата и показваме текст за успешна регистрация
        $('.bulletinInner').fadeOut('slow', function() {
        	
		    $(this).html(getSuccessText(url));
		    $('.bulletinInner').fadeIn("slow");
		    hideAfterTimeout(7000, 800);
		});
        
    	setCookie('bulletinHaveReg', true, 1000);
	});
	
	// Скриваме формата при натискане на отказ или X
	$(document.body).on('click', ".bulletinCancel, .bulletinClose", function(event){
		stopBtnDefault(event);
		hideAfterTimeout(0, 800);
	});
}


/**
 * Връща текст след успешно приключване
 * 
 * @param string
 * 
 * @return string
 */
function getSuccessText(url)
{
	var res = '<div class="thanksText"><img src="' + url + '" alt="Img">' +
	"<div class='successText'>[#successText#]</div></div>";
	
	return res;
}


/**
 * Скрива полетата след определено време
 * 
 * @param integer
 * @param integer
 */
function hideAfterTimeout(timeout, fadeOut) 
{
	setTimeout(function(){ 
		$('.bulletinHolder').fadeOut(fadeOut);
		
		// След скриването премахваме всичко
		setTimeout(function(){ 
			$('.bulletinHolder').remove();
		}, fadeOut);
		
	}, timeout);
}


/**
 * Създава бисквитка
 * 
 * @param string
 * @param string
 * @param integer
 */
function setCookie(key, value, exdays)
{
	key = prepareCookieKey(key);
	
    var expires = new Date();
    // 86400000 - 1 ден
    expires.setTime(expires.getTime() + (exdays * 86400000));
    document.cookie = key + '=' + value + ';expires=' + expires.toUTCString() + "; path=/";
}


/**
 * Чете информацията от дадена бисквитка
 * 
 * @param string
 * 
 * @return string
 */
function getCookie(key)
{
	key = prepareCookieKey(key);
	
	key = escapeRegExp(key);
	
    var keyValue = document.cookie.match('(^|;) ?' + key + '=([^;]*)(;|$)');
    
    return keyValue ? keyValue[2] : null;
}


/**
 * Подготвя ключа за "бисквитките"
 * 
 * @param string
 * 
 * @return string
 */
function prepareCookieKey(key)
{
	key = key + '_[#cookieKey#]';
	
	return key;
}


/**
 * Ескейпва регулярния израз
 * 
 * @param string
 * 
 * @return string
 */
function escapeRegExp(str)
{
	
	return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
}

/**
 * Проверява имейла далие е коректен
 * 
 * @param string
 * 
 * @return string
 */
function validateEmail(email)
{
    var re = /^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i;
    
    return re.test(email);
}
