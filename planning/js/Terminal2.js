// Добавя символ в инпут полето
function addLetter(letter, fieldName) {
	const elem = document.getElementById(fieldName);
	//IE support
	if (document.selection) {
		elem.focus();
		sel = document.selection.createRange();
		sel.text = letter;
	}
	//MOZILLA and others
	else if (elem.selectionStart || elem.selectionStart == '0') {
		var startPos = elem.selectionStart;
		var endPos = elem.selectionEnd;
		elem.value = elem.value.substring(0, startPos)
			+ letter
			+ elem.value.substring(endPos, elem.value.length);
		elem.setSelectionRange(startPos+1, startPos+1);
	} else {
		elem.value += letter;
	}
	
	elem.focus();
}

/**
 * Премахва последния символ в инпута
 */
function bckSp(fieldName) {
	const elem = document.getElementById(fieldName);
	if (elem.selectionStart >= 0) {
		var startPos = elem.selectionStart;
		var endPos = elem.selectionEnd;
		if(startPos == endPos && startPos > 0) {
			startPos--;
		}
		elem.value = elem.value.substring(0, startPos)
			+ elem.value.substring(endPos, elem.value.length);
		elem.setSelectionRange(startPos, startPos);
		elem.focus();
	}
}

/**
 * Намира всички елементи с базовия клас и показва само тези от тях, които имат displayClass
 */
function displayByClass(baseClass, displayClass) {
	const elements = document.querySelectorAll("." + baseClass);
    for (var i = 0; i < elements.length; i++) {
		var display = 'block';
		if(elements[i].classList.contains(displayClass)) {
			if(elements[i].getAttribute('data-display')  != null) {
				display = elements[i].getAttribute('data-display');
			}
		} else {
			if(elements[i].style.display != 'none' && elements[i].style.display != null && elements[i].style.display != '') {
				elements[i].setAttribute('data-display', elements[i].style.display);
			}
			display = 'none';
		}
		elements[i].style.display = display;
    }
}

/**
 * При натискане на бутон, добавя символа му
 */
function kbPress(evn) {
	addLetter(evn.target.innerHTML, 'cmdInput');
}

/**
 * Добавя команда в началото на командния ред
 */
function setCmd(cmd) {
	const input = document.getElementById('cmdInput');
	str = input.value.trim();
	const len = str.length;
	if(str.charAt(len - 1) == ':') {
		str = '';
	}

	input.value = cmd + ': ' + str;
	const end = input.value.length;
	input.setSelectionRange(end, end);
	doCmd();
	 
}

/**
 * Задава команда за количество
 */
function setQuantity()
{
	setCmd('Количество');
}

/**
 * Задава команда за тегло
 */
function setWeight()
{
	setCmd('Тегло');
}

/**
 * Задава команда за работници
 */
function setWorkers()
{
	setCmd('Изпълнители');
}

/**
 * Задава команда за продукт
 */
function setProduct()
{
	setCmd('Артикул');
}

/**
 * Задава команда за етикет
 */
function setLabel()
{
	setCmd('Етикет');
}

/**
 * Задава команда за търсене
 */
function setSearch()
{
	setCmd('Търсене');
}

/**
 * Задава команда за Операция
 */
function setOperation()
{
	setCmd('Операция');
}


/**
 * Задава команда за Задание
 */
function setJob()
{
	setCmd('Задание');
}

/**
 * Задава команда за Поддръжка
 */
function setSignal()
{
	setCmd('Сигнал');
}


/**
 * Изпълнява зададената команда в главния инпут
 */
function doCmd() {
	const elem = document.getElementById('cmdInput');

	cmd = elem.value;
	
	// Създаваме XMLHttpRequest обект 
    var xhr = new XMLHttpRequest();
  
    // Правим връзката  
    var url = '/planning_Terminal2/cmd/?cmd=' + encodeURIComponent(cmd);
    xhr.open("GET", url, true);
  
    // При успех обработваме резултата 
    xhr.onreadystatechange = function () {
        if (this.readyState == 4 && this.status == 200) {
            setResponse(this.responseText);
			const input = document.getElementById('cmdInput');
			input.value = '';
        }
    }

    // Изпращаме заявката
    xhr.send();
}

/**
 * Разполага резултата
 */
function setResponse(res)
{
	const resArr = JSON.parse(res);
	
	console.log(resArr);
	console.log('quantity' in resArr);
	if('quantity' in resArr) {
		var elem = document.getElementById('quantity');
		elem.innerHTML = resArr['quantity'];
	}

	if('selectMenu' in resArr) {
		var elem = document.getElementById('selectMenu');
		elem.innerHTML = resArr['selectMenu'];
		displayByClass('panel', 'selectMenu')
	}
    
	if('modalWindow' in resArr) {
		var elem = document.getElementById('modalWindow');
		elem.innerHTML = resArr['modalWindow'];
		modal.open('modalWindowDialog');
	}
}

/**
 * Начално инициализиране на терминала
 */
function init(evn) {
	const elements = document.querySelectorAll('.kb-row > div');
    for (var i = 0; i < elements.length; i++) {
		if(elements[i].getAttribute('onClick')  == null) {
			elements[i].addEventListener('click', kbPress);
		}
	}

	const elem = document.getElementById('cmdInput');
	elem.addEventListener("keyup", function(event) {
		if (event.key === "Enter") {
			doCmd();
		}
	});
}

/**
 * Компонент за модален диалог
 */
modal = {
    'open' : function (name) {
        // Get the modal
        const modal = document.getElementById(name);
 
        // Show the modal
        modal.classList.add("visible");

        // Get the <span> element that closes the modal
        const span = document.getElementsByClassName("close")[0];
		span.focus();

        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.classList.remove("visible");
        }

        // When the user clicks anywhere outside of the modal, close it
        modal.onclick = function(event) {
            if (event.target === modal) {
                event.stopPropagation()
                modal.classList.remove("visible");
                return false;
            }
        }
    }
}

window.onload = init;
 