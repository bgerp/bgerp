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
	cmdFocus()
	doCmd();
}


function cmdFocus() {
	const input = document.getElementById('cmdInput');
	const end = input.value.length;
	input.setSelectionRange(end, end);
	input.focus();
}


/**
 * Задава команда за количество
 */
function setQuantity()
{
	const quantity = getFloat();
	const quantityInput = document.getElementById('quantity');
	if(!isNaN(quantity) && quantity > 0) {
		quantityInput.innerHTML = quantity;
	} else {
		quantityInput.innerHTML = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	}
}

/**
 * Задава команда за тегло
 */
function setWeight()
{
	const weight = getFloat();
	const weightInput = document.getElementById('weight');
	if(!isNaN(weight) && weight > 0) {
		weightInput.innerHTML = weight.toFixed(2);
		weightInput.setAttribute('data-manual', 'yes');
	} else {
		weightInput.innerHTML = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		weightInput.setAttribute('data-manual', 'no');
	}
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


function getFloat()
{
	const cmdInput = document.getElementById('cmdInput');

	str = cmdInput.value;
 
	cmdInput.value = '';

	cmdFocus();

	return parseFloat(str);
}


/**
 * Изпълнява зададената команда в главния инпут
 */
function doCmd() {

	const cmdInput = document.getElementById('cmdInput');

	cmd = cmdInput.value;
	
	// Създаваме XMLHttpRequest обект 
    var xhr = new XMLHttpRequest();
  
    // Правим връзката  
    var url = '/planning_Terminal2/cmd/?cmd=' + encodeURIComponent(cmd);
    xhr.open("GET", url, true);
  
    // При успех обработваме резултата 
    xhr.onreadystatechange = function () {
        if (this.readyState == 4 && this.status == 200) {
            setResponse(this.responseText);
			cmdInput.value = '';
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
	
	const selectTitle = document.getElementById('selectTitle');
	selectTitle.innerHtml = '';
	
	const selectBody = document.getElementById('selectBody');
	selectBody.innerHtml = '';

	const modalTitle = document.getElementById('modalTitle');
	modalTitle.innerHtml = '';

	const modalBody = document.getElementById('modalBody');
	modalBody.innerHtml = '';

	if('menuBody' in resArr && 'menuTitle' in resArr) {
		
		const right = document.getElementById('right');

		if(right.clientWidth >= 680) {
			selectTitle.innerHTML = resArr['menuTitle'];
			selectBody.innerHTML = resArr['menuBody'];
			if('menuBackgroundColor' in resArr) {
				const selectMenu = document.getElementById('selectMenu');
				selectMenu.style.backgroundColor = resArr['menuBackgroundColor'];
			}
			displayByClass('panel', 'selectMenu');
		} else {
			modalTitle.innerHTML = resArr['menuTitle'];
			modalBody.innerHTML = resArr['menuBody'];
			if('menuBackgroundColor' in resArr) {
				const modalMenu = document.getElementById('modalMenu');
				modalMenu.style.backgroundColor = resArr['menuBackgroundColor'];
			}
			modal.open('modalWindowDialog');
		}
	}
}


/**
 * Ако е зададено, обновява през малък интервал показанията на везната
 */
function updateWeight()
{
	const weightInput = document.getElementById('weight');
	
	if(weightInput.getAttribute('data-manual') != 'yes') {

		// TODO: Тук трябва да се направи определянето на теглото от кантара, чрез заявка към localhost
		const weight = (Math.random() * 0.5 + 13.5).toFixed(2);

		weightInput.innerHTML = weight;
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

	const cmdInput = document.getElementById('cmdInput');
	cmdInput.addEventListener("keyup", function(event) {
		if (event.key === "Enter") {
			doCmd();
		}
	});
	cmdFocus();

	document.addEventListener("keydown", function(e) {
	  if (e.key === "Enter") {
		toggleFullScreen();
	  }
	}, false);

	setInterval(updateWeight, 1000);
}


/**
 * Превключва към пълен екран и обратно
 */
function toggleFullScreen() {
  const fullScreen = document.getElementById('fullScreenToggleBtn');
  if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen();
	  fullScreen.innerHTML = 'fullscreen_exit';
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen();
	  fullScreen.innerHTML = 'fullscreen';
    }
  }
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
			cmdFocus();
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
 